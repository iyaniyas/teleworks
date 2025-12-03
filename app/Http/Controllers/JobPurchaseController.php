<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Auth;
use App\Models\JobPackage;
use App\Models\JobPayment;
use App\Services\MidtransClient;

class JobPurchaseController extends Controller
{
    protected MidtransClient $midtrans;

    public function __construct(MidtransClient $midtrans)
    {
        $this->midtrans = $midtrans;
        $this->middleware('auth')->only(['create', 'store']);
    }

    /**
     * User returned after payment flow (Finish URL)
     * We DO NOT trust this 100% — verify via Midtrans status API.
     */
    public function finish(Request $request)
    {
        $orderId = $request->query('order_id') ?? $request->query('orderId') ?? null;
        if (!$orderId) {
            // no order id — show generic page
            return view('purchase.finish')->with(['status' => 'unknown', 'message' => 'Tidak ada order_id di URL.']);
        }

        try {
            $statusResp = $this->midtrans->getStatus($orderId);
            // statusResp includes 'transaction_status', 'fraud_status', etc.
            $txStatus = strtolower($statusResp['transaction_status'] ?? '');
            $fraud = strtolower($statusResp['fraud_status'] ?? '');

            // Map midtrans status to our state
            $isPaid = in_array($txStatus, ['settlement','capture']) && ($fraud === '' || $fraud === 'accept');

            // optional: fetch local payment row
            $payment = JobPayment::where('external_id', $orderId)->first();

            return view('purchase.finish', [
                'status' => $isPaid ? 'paid' : $txStatus,
                'midtrans' => $statusResp,
                'payment' => $payment,
            ]);
        } catch (\Exception $e) {
            \Log::error('Finish check midtrans status error: '.$e->getMessage(), ['order_id'=>$orderId]);
            return view('purchase.finish')->with(['status' => 'error', 'message' => 'Gagal memeriksa status pembayaran. Silakan cek dashboard atau tunggu notifikasi.']);
        }
    }

    /**
     * Unfinish: user did not complete (or returned early).
     * We still try to check status; usually transaction is not paid.
     */
    public function unfinish(Request $request)
    {
        $orderId = $request->query('order_id') ?? null;
        if (!$orderId) {
            return view('purchase.unfinish')->with(['status' => 'unknown']);
        }

        try {
            $statusResp = $this->midtrans->getStatus($orderId);
            $txStatus = strtolower($statusResp['transaction_status'] ?? '');
            return view('purchase.unfinish', ['status' => $txStatus, 'midtrans' => $statusResp]);
        } catch (\Exception $e) {
            \Log::error('Unfinish check midtrans status error: '.$e->getMessage());
            return view('purchase.unfinish')->with(['status' => 'error']);
        }
    }

    /**
     * Error page: user returned after error.
     * Also verify via API.
     */
    public function error(Request $request)
    {
        $orderId = $request->query('order_id') ?? null;
        if (!$orderId) {
            return view('purchase.error')->with(['status' => 'unknown']);
        }

        try {
            $statusResp = $this->midtrans->getStatus($orderId);
            $txStatus = strtolower($statusResp['transaction_status'] ?? '');
            return view('purchase.error', ['status' => $txStatus, 'midtrans' => $statusResp]);
        } catch (\Exception $e) {
            \Log::error('Error check midtrans status error: '.$e->getMessage());
            return view('purchase.error')->with(['status' => 'error']);
        }
    }

    protected function ensureIsCompany($user): bool
    {
        if (!$user) return false;
        if (method_exists($user, 'hasRole')) {
            return $user->hasRole('company') || $user->hasRole('employer');
        }
        if (property_exists($user, 'company_id') && !empty($user->company_id)) {
            return true;
        }
        if (property_exists($user, 'is_company') && $user->is_company) {
            return true;
        }
        return false;
    }

    public function create(Request $request)
    {
        $user = auth()->user();
        if (!$this->ensureIsCompany($user)) {
            return redirect()->route('login')->with('error', 'Hanya perusahaan yang dapat membeli paket.');
        }

        $packages = JobPackage::where('active', true)->orderBy('price')->get();
        return view('purchase.create', compact('packages'));
    }

    public function store(Request $request)
    {
        $user = auth()->user();
        if (!$this->ensureIsCompany($user)) {
            abort(403, 'Hanya perusahaan yang dapat melakukan pembelian paket.');
        }

        $this->validate($request, [
            'package_id' => 'required|exists:job_packages,id',
            'job_id' => 'nullable|exists:jobs,id',
        ]);

        $companyId = $user->company_id ?? $user->id;
        $package = JobPackage::findOrFail($request->input('package_id'));

        $externalId = 'jobpay-' . (string) Str::uuid();
        $amount = (int) $package->price;

        $payment = JobPayment::create([
            'job_id' => $request->input('job_id'),
            'company_id' => $companyId,
            'package_id' => $package->id,
            'external_id' => $externalId, // used as order_id for Midtrans
            'amount' => $amount,
            'currency' => config('midtrans.currency', 'IDR'),
            'payment_gateway' => 'midtrans',
            'status' => 'pending',
            'meta' => null,
        ]);

        // Build Midtrans payload for Snap
        // order_id must be unique per transaction (we use externalId)
        $midtransPayload = [
            'transaction_details' => [
                'order_id' => $externalId,
                'gross_amount' => $amount,
            ],
            'item_details' => [
                [
                    'id' => $package->id,
                    'price' => $amount,
                    'quantity' => 1,
                    'name' => 'Paket ' . $package->name,
                ],
            ],
            'customer_details' => [
                'first_name' => $user->name ?? 'Perusahaan',
                'email' => $user->email ?? null,
            ],
            // optional: 'callbacks' or 'expiry' can be set via advanced config
        ];

        try {
            $resp = $this->midtrans->createTransaction($midtransPayload);
            // Save response
            $payment->meta = $resp;
            // Midtrans usually returns 'token' and/or 'redirect_url'
            if (is_array($resp)) {
                $payment->transaction_id = $resp['transaction_id'] ?? $resp['token'] ?? $resp['redirect_url'] ?? null;
            }
            $payment->save();

            // Render instructions with redirect_url or token. If Snap returns token & redirect_url we can redirect user.
            // Prefer redirect_url if present:
            $redirect = $resp['redirect_url'] ?? null;
            $snapToken = $resp['token'] ?? null;

            // If redirect_url present, redirect user to midtrans hosted page
            if ($redirect) {
                return redirect()->away($redirect);
            }

            // Otherwise, render a view that instructs frontend to open Snap popup using snapToken
            return view('purchase.instructions_midtrans', [
                'payment' => $payment,
                'response' => $resp,
                'package' => $package,
                'snapToken' => $snapToken,
            ]);
        } catch (\Exception $e) {
            Log::error('Error creating Midtrans transaction', [
                'message' => $e->getMessage(),
                'payload' => $midtransPayload,
                'payment_id' => $payment->id ?? null,
            ]);

            return back()->withErrors(['payment' => 'Gagal membuat sesi pembayaran: ' . $e->getMessage()]);
        }
    }
}

