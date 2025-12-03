<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use App\Models\JobPayment;
use App\Models\JobPackage;
use Exception;

class MidtransController extends Controller
{
    /**
     * Handle Midtrans HTTP Notification (webhook).
     * Validate signature and update job_payments + jobs.
     */
    public function webhook(Request $request)
    {
        $payload = $request->all();
        $raw = (string) $request->getContent();
        Log::info('Midtrans webhook raw', ['body' => $raw]);

        // Verify signature
        $serverKey = config('midtrans.server_key');
        $orderId = $payload['order_id'] ?? ($payload['transaction_details']['order_id'] ?? null);
        $statusCode = $payload['status_code'] ?? null;
        $grossAmount = $payload['gross_amount'] ?? ($payload['transaction_details']['gross_amount'] ?? null);
        $signatureKey = $payload['signature_key'] ?? null;

        if (empty($serverKey)) {
            Log::warning('Midtrans webhook: server key not configured');
            return response()->json(['ok' => false, 'message' => 'server key not configured'], 500);
        }

        if (!$signatureKey || !$orderId || !$statusCode || !$grossAmount) {
            Log::warning('Midtrans webhook: missing required fields', ['payload' => $payload]);
            return response()->json(['ok' => false, 'message' => 'missing fields'], 400);
        }

        // signature generation per Midtrans docs: SHA512(order_id + status_code + gross_amount + server_key)
        $computed = hash('sha512', $orderId . $statusCode . $grossAmount . $serverKey);

        if (!hash_equals($computed, $signatureKey)) {
            Log::warning('Midtrans webhook: invalid signature', ['computed' => $computed, 'signature' => $signatureKey]);
            return response()->json(['ok' => false, 'message' => 'invalid signature'], 403);
        }

        // Map status to our internal status
        $transactionStatus = strtolower($payload['transaction_status'] ?? $payload['transaction_status'] ?? '');
        $fraudStatus = strtolower($payload['fraud_status'] ?? '');

        try {
            $payment = JobPayment::where('external_id', $orderId)->first();
            if (!$payment) {
                Log::warning('Midtrans webhook: payment not found', ['order_id' => $orderId]);
                return response()->json(['ok' => true, 'message' => 'payment not found']);
            }

            // Determine new status
            $newStatus = $payment->status;
            // Common Midtrans statuses: capture, settlement, pending, cancel, deny, expire, refund
            if (in_array($transactionStatus, ['settlement','capture']) && ($fraudStatus === '' || $fraudStatus === 'accept')) {
                $newStatus = 'paid';
            } elseif (in_array($transactionStatus, ['cancel','deny','expire'])) {
                $newStatus = 'failed';
            } elseif ($transactionStatus === 'refund') {
                $newStatus = 'refunded';
            } elseif ($transactionStatus === 'pending') {
                $newStatus = 'pending';
            }

            $payment->status = $newStatus;
            $payment->transaction_id = $payload['transaction_id'] ?? $payment->transaction_id;
            $payment->meta = array_merge(is_array($payment->meta ?? []) ? $payment->meta : [], ['last_webhook' => $payload]);
            $payment->started_at = $payment->started_at ?? now();

            if ($payment->package_id && $newStatus === 'paid') {
                $pkg = JobPackage::find($payment->package_id);
                if ($pkg) {
                    $payment->expires_at = $payment->started_at->copy()->addDays($pkg->duration_days);
                    if ($payment->job_id) {
                        \DB::table('jobs')->where('id', $payment->job_id)->update([
                            'is_paid' => true,
                            'paid_until' => $payment->expires_at,
                            'package_id' => $pkg->id,
                        ]);
                    }
                }
            }

            $payment->save();

            Log::info('Midtrans webhook processed', ['payment_id' => $payment->id, 'status' => $payment->status]);

            // Return 200 OK
            return response()->json(['ok' => true]);
        } catch (Exception $e) {
            Log::error('Midtrans webhook error: ' . $e->getMessage(), ['payload' => $payload]);
            return response()->json(['ok' => false, 'message' => 'processing error'], 500);
        }
    }
}

