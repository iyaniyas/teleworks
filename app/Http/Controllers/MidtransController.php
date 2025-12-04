<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use App\Models\JobPayment;
use App\Models\JobPackage;
use Illuminate\Support\Facades\DB;

class MidtransController extends Controller
{
    public function handleWebhook(Request $request)
    {
        // Simpan payload raw & header untuk debugging
        Log::info('Midtrans webhook received', [
            'headers' => $request->headers->all(),
            'payload' => $request->all(),
            'raw' => $request->getContent(),
        ]);

        $payload = $request->all();

        // Ambil order id (Midtrans menggunakan "order_id")
        $externalId = $payload['order_id'] ?? $payload['external_id'] ?? null;
        if (!$externalId) {
            Log::warning('Midtrans webhook: missing order_id', ['payload' => $payload]);
            return response('OK', 200);
        }

        // Cari payment
        $payment = JobPayment::where('external_id', $externalId)->first();
        if (!$payment) {
            Log::error('Midtrans webhook: JobPayment not found', ['external_id' => $externalId]);
            return response('OK', 200);
        }

        // (Optional) verify signature key — jangan lewati ke production tanpa verifikasi
        $serverKey = config('midtrans.server_key') ?? env('MIDTRANS_SERVER_KEY');
        if ($serverKey) {
            $orderId = $payload['order_id'] ?? '';
            $statusCode = $payload['status_code'] ?? ($payload['status'] ?? '');
            $grossAmount = $payload['gross_amount'] ?? '';
            $calcSignature = hash('sha512', $orderId.$statusCode.$grossAmount.$serverKey);
            $receivedSignature = $payload['signature_key'] ?? $request->header('signature_key') ?? null;
            if ($receivedSignature && $receivedSignature !== $calcSignature) {
                Log::warning('Midtrans webhook: invalid signature', [
                    'calc' => $calcSignature, 'recv' => $receivedSignature, 'payload' => $payload
                ]);
                // return 200 agar Midtrans tidak keep retrying? Anda bisa return 403 jika ingin
                return response('OK', 200);
            }
        }

        $status = $payload['transaction_status'] ?? $payload['status'] ?? null;

        // Handle sukses/capture/settlement
        if (in_array($status, ['capture', 'settlement', 'success'])) {
            DB::beginTransaction();
            try {
                if ($payment->status !== 'paid') {
                    $payment->status = 'paid';
                    $payment->paid_at = now();

                    // apply package if available
                    if ($payment->package_id) {
                        $package = JobPackage::find($payment->package_id);
                        if ($package) {
                            $payment->expires_at = now()->addDays($package->duration_days ?? 30);
                            // contoh: beri efek ke company/job
                            if ($payment->company_id) {
                                DB::table('companies')->where('id', $payment->company_id)->update([
                                    'package_id' => $package->id,
                                    'package_expires_at' => $payment->expires_at,
                                ]);
                            }
                        }
                    }
                    $payment->save();
                    Log::info('Midtrans webhook: payment updated to paid', ['payment_id' => $payment->id]);
                }
                DB::commit();
            } catch (\Throwable $e) {
                DB::rollBack();
                Log::error('Midtrans webhook: failed to update payment', [
                    'error' => $e->getMessage(), 'payment_id' => $payment->id ?? null
                ]);
                // tetap return 200 agar Midtrans tidak keep retrying buruk — tapi Anda bisa return 500 untuk memicu retry
                return response('OK', 200);
            }
        } else {
            Log::info('Midtrans webhook: unhandled status', ['status'=>$status, 'payload'=>$payload]);
        }

        return response('OK', 200);
    }
}

