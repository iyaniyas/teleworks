<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Services\FlipClient;
use Illuminate\Support\Facades\Log;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Str;
use App\Models\JobPayment;
use App\Models\JobPackage;
use Exception;

/**
 * FlipController
 *
 * Handles Flip integration endpoints:
 * - createPayment (optional helper)
 * - status (check payment status)
 * - webhook (callback from Flip)
 *
 * IMPORTANT:
 * - Configure FLIP_WEBHOOK_SECRET in .env if Flip provides a shared secret.
 * - Adjust header name and HMAC algorithm per Flip docs.
 */
class FlipController extends Controller
{
    protected FlipClient $flip;

    public function __construct(FlipClient $flip)
    {
        $this->flip = $flip;
    }

    /**
     * Optional helper endpoint to create a payment via Flip API.
     * Note: for production you might want this logic in your purchase controller/service.
     *
     * POST payload: external_id, amount, beneficiary_name, bank_code (optional), remark (optional)
     */
    public function createPayment(Request $request): JsonResponse
    {
        $this->validate($request, [
            'external_id' => 'required|string',
            'amount' => 'required|numeric|min:1',
            'beneficiary_name' => 'required|string',
        ]);

        $payload = $request->only(['external_id', 'amount', 'beneficiary_name', 'bank_code', 'remark']);

        try {
            $resp = $this->flip->createPayment($payload);
            return response()->json(['ok' => true, 'data' => $resp]);
        } catch (Exception $e) {
            Log::error('Flip createPayment exception: ' . $e->getMessage(), [
                'payload' => $payload,
            ]);
            return response()->json(['ok' => false, 'message' => $e->getMessage()], 500);
        }
    }

    /**
     * GET /api/flip/payments/{externalId}
     * Check payment status from Flip (proxy).
     */
    public function status(string $externalId): JsonResponse
    {
        try {
            $resp = $this->flip->getPaymentStatus($externalId);
            return response()->json(['ok' => true, 'data' => $resp]);
        } catch (Exception $e) {
            Log::error('Flip getPaymentStatus exception: ' . $e->getMessage(), [
                'external_id' => $externalId,
            ]);
            return response()->json(['ok' => false, 'message' => $e->getMessage()], 500);
        }
    }

    /**
     * Webhook endpoint accepted by Flip for callbacks.
     * Supports two common validation modes:
     *  - Plain callback token header (e.g. X-Callback-Token)
     *  - HMAC-SHA256 signature header (e.g. X-Flip-Signature)
     *
     * Set FLIP_WEBHOOK_SECRET in .env to enable validation.
     */
    public function webhook(Request $request): JsonResponse
    {
        $rawPayload = (string) $request->getContent();
        $payload = $request->all();

        Log::info('Flip webhook received (raw)', ['body' => $rawPayload]);

        // Validate webhook signature/token if configured
        $secret = config('flip.payment_callback_secret');
        if (!empty($secret)) {
            // Candidate headers to check (adjust to Flip's documented header name)
            $headerNames = [
                'x-flip-signature',    // HMAC signature (common)
                'x-signature',
                'x-callback-token',    // plain token
                'x-callback-signature',
                'x-flip-callback-token'
            ];

            $found = false;
            foreach ($headerNames as $h) {
                if ($request->headers->has($h)) {
                    $found = true;
                    $headerValue = $request->header($h);
                    // If header value exactly equals secret, accept (plain token)
                    if (hash_equals($headerValue, $secret)) {
                        Log::info('Flip webhook validated via plain token header', ['header' => $h]);
                        break;
                    }

                    // Otherwise, check HMAC-SHA256 (header expected to be hex)
                    $computed = hash_hmac('sha256', $rawPayload, $secret);
                    if (hash_equals($computed, $headerValue)) {
                        Log::info('Flip webhook validated via HMAC-SHA256', ['header' => $h]);
                        break;
                    }

                    // If header present but doesn't match, reject
                    Log::warning('Flip webhook signature mismatch', ['header' => $h, 'header_value' => $headerValue, 'computed' => $computed]);
                    return response()->json(['ok' => false, 'message' => 'Invalid signature'], 403);
                }
            }

            if (!$found) {
                Log::warning('Flip webhook: no signature header found while secret configured');
                return response()->json(['ok' => false, 'message' => 'Missing signature header'], 400);
            }
        } else {
            Log::info('Flip webhook: no webhook secret configured; skipping signature validation.');
        }

        // Process webhook payload
        // Typical payloads may include external_id, status/payment_status, transaction_id, amount, etc.
        $externalId = $payload['external_id'] ?? $payload['data']['external_id'] ?? null;
        $statusRaw = $payload['status'] ?? $payload['payment_status'] ?? ($payload['data']['status'] ?? null);
        $transactionId = $payload['transaction_id'] ?? $payload['data']['id'] ?? null;
        $amount = $payload['amount'] ?? $payload['data']['amount'] ?? null;

        if (!$externalId) {
            Log::warning('Flip webhook: no external_id in payload', ['payload' => $payload]);
            // Return 200 to avoid retries if this is not useful, or 400 to indicate bad request. We'll return 200.
            return response()->json(['ok' => true, 'message' => 'no external_id']);
        }

        try {
            $payment = JobPayment::where('external_id', $externalId)->first();

            if (!$payment) {
                // If no local payment found, log and return 200 to avoid retry storms
                Log::warning('Flip webhook: job payment not found', ['external_id' => $externalId]);
                return response()->json(['ok' => true, 'message' => 'payment not found']);
            }

            // Normalize status
            $normalized = is_string($statusRaw) ? strtolower($statusRaw) : null;
            $newStatus = $payment->status;

            if (in_array($normalized, ['paid', 'success', 'settlement', 'completed'])) {
                $newStatus = 'paid';
            } elseif (in_array($normalized, ['refunded', 'refund'])) {
                $newStatus = 'refunded';
            } elseif (in_array($normalized, ['failed', 'error', 'rejected'])) {
                $newStatus = 'failed';
            } elseif ($normalized === 'pending' || $normalized === null) {
                $newStatus = 'pending';
            }

            // Update fields
            $payment->status = $newStatus;
            if ($transactionId) {
                $payment->transaction_id = $transactionId;
            }
            if (!empty($amount)) {
                $payment->amount = $amount;
            }

            // Ensure started_at exists
            $payment->started_at = $payment->started_at ?? now();

            // If paid and package present, compute expires_at and activate job
            if ($payment->package_id && $payment->status === 'paid') {
                $pkg = JobPackage::find($payment->package_id);
                if ($pkg) {
                    // compute expires_at
                    $payment->expires_at = $payment->started_at->copy()->addDays((int) $pkg->duration_days);

                    // activate job if job_id present
                    if (!empty($payment->job_id)) {
                        \DB::table('jobs')->where('id', $payment->job_id)->update([
                            'is_paid' => true,
                            'paid_until' => $payment->expires_at,
                            'package_id' => $pkg->id,
                        ]);
                    }
                }
            }

            $payment->meta = array_merge(is_array($payment->meta ?? []) ? $payment->meta : [], ['last_webhook' => $payload]);
            $payment->save();

            Log::info('Flip webhook processed job payment', [
                'payment_id' => $payment->id,
                'external_id' => $externalId,
                'status' => $payment->status,
            ]);

            return response()->json(['ok' => true]);
        } catch (Exception $e) {
            Log::error('Flip webhook processing error: ' . $e->getMessage(), ['payload' => $payload]);
            // Return 500 so Flip will likely retry; but ensure you handle idempotency in processing.
            return response()->json(['ok' => false, 'message' => 'processing error'], 500);
        }
    }
}

