<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use App\Models\JobPayment;
use App\Models\JobPackage;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Carbon\Carbon;

class MidtransController extends Controller
{
    public function handleWebhook(Request $request)
    {
        \Log::info('Midtrans webhook received', [
            'headers' => $request->headers->all(),
            'payload' => $request->all(),
            'raw' => $request->getContent(),
        ]);

        $payload = $request->all();
        $orderId = $payload['order_id'] ?? $payload['external_id'] ?? null;
        if (! $orderId) {
            \Log::warning('Midtrans webhook: missing order id', ['payload' => $payload]);
            return response('OK', 200);
        }

        $payment = \App\Models\JobPayment::where('external_id', $orderId)->first();
        if (! $payment) {
            \Log::warning('Midtrans webhook: payment not found', ['external_id' => $orderId]);
            return response('OK', 200);
        }

        if ($payment->status === 'paid') {
            \Log::info('Midtrans webhook: payment already paid', ['payment_id' => $payment->id]);
            return response('OK', 200);
        }

        // verify signature (optional but recommended)
        $serverKey = config('midtrans.server_key') ?? env('MIDTRANS_SERVER_KEY');
        if (!empty($serverKey) && !empty($payload['signature_key'])) {
            $calc = hash('sha512', ($payload['order_id'] ?? '') . ($payload['status_code'] ?? '') . ($payload['gross_amount'] ?? '') . $serverKey);
            if (! hash_equals($calc, $payload['signature_key'])) {
                \Log::warning('Midtrans webhook: invalid signature', ['calc' => $calc, 'recv' => $payload['signature_key']]);
                return response('OK', 200);
            }
        }

        $status = $payload['transaction_status'] ?? null;
        $fraud = $payload['fraud_status'] ?? null;
        $isPaid = false;
        if ($status === 'capture' && (empty($fraud) || $fraud === 'accept')) {
            $isPaid = true;
        } elseif (in_array($status, ['settlement','success'])) {
            $isPaid = true;
        }

        if (! $isPaid) {
            \Log::info('Midtrans webhook: status not final', ['status' => $status, 'fraud' => $fraud]);
            return response('OK', 200);
        }

        \DB::beginTransaction();
        try {
            $payment->status = 'paid';
            $payment->transaction_id = $payload['transaction_id'] ?? $payment->transaction_id;

            // HARDENING: only set paid_at if column exists; otherwise fallback to started_at
            if (Schema::hasColumn('job_payments', 'paid_at')) {
                $payment->paid_at = now();
            } else {
                // fallback: pakai started_at atau updated_at jika paid_at tidak ada
                $payment->started_at = $payment->started_at ?? now();
                \Log::warning('Midtrans webhook: job_payments.paid_at column missing, used started_at as fallback', ['payment_id' => $payment->id]);
            }

            $expiry = now()->addDays(30);
            if ($payment->package_id) {
                $pkg = \App\Models\JobPackage::find($payment->package_id);
                if ($pkg && ($pkg->duration_days ?? null)) {
                    $expiry = $payment->started_at ? Carbon::parse($payment->started_at)->addDays($pkg->duration_days) : now()->addDays($pkg->duration_days);
                }
            }
            $payment->expires_at = $expiry;
            $payment->meta = $payload;
            $payment->save();

            if (! empty($payment->job_id) && Schema::hasTable('jobs')) {
                DB::table('jobs')->where('id', $payment->job_id)->update([
                    'is_paid' => 1,
                    'package_id' => $payment->package_id,
                    'expires_at' => $expiry,
                    'paid_until' => $expiry,
                ]);
            } else {
                \Log::warning('Midtrans webhook: payment has no job_id or jobs table missing', ['payment_id' => $payment->id]);
            }

            \DB::commit();
            \Log::info('Midtrans webhook: processed payment', ['payment_id' => $payment->id, 'job_id' => $payment->job_id]);
        } catch (\Throwable $e) {
            \DB::rollBack();
            \Log::error('Midtrans webhook: failed to update payment', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
                'payment_id' => $payment->id ?? null,
                'payload' => $payload,
            ]);
            return response('OK', 200);
        }

        return response('OK', 200);
    }
}

