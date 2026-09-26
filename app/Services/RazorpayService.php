<?php

namespace App\Services;

use App\Models\Setting;
use Razorpay\Api\Api;
use Razorpay\Api\Errors\SignatureVerificationError;
use Illuminate\Support\Facades\Log;

class RazorpayService
{
    protected ?Api $api = null;
    protected ?string $keyId = null;
    protected ?string $keySecret = null;
    protected bool $isEnabled = false;

    public function __construct()
    {
        try {
            $this->keyId     = Setting::getSetting('payment_razorpay_key');
            $this->keySecret = Setting::getSetting('payment_razorpay_secret');
            $enabled         = Setting::getSetting('payment_razorpay_enabled');

            Log::info('=== RazorpayService::__construct ===');
            Log::info('keyId: ' . var_export($this->keyId, true));
            Log::info('keySecret length: ' . (is_string($this->keySecret) ? strlen($this->keySecret) : 'null'));
            Log::info('enabled raw: ' . var_export($enabled, true));
            Log::info('enabled == "1": ' . var_export($enabled == '1', true));

            if (!empty($this->keyId) && !empty($this->keySecret) && $enabled == '1') {
                $this->api       = new Api($this->keyId, $this->keySecret);
                $this->isEnabled = true;
                Log::info('Razorpay enabled: TRUE');
            } else {
                Log::error('Razorpay: Missing credentials or disabled');
                Log::error('Razorpay Key ID: '     . ($this->keyId     ? 'set' : 'missing'));
                Log::error('Razorpay Key Secret: ' . ($this->keySecret ? 'set' : 'missing'));
                Log::error('Razorpay Enabled: '    . ($enabled ?? 'null'));
            }
        } catch (\Throwable $e) {
            Log::error('Razorpay Service init error: ' . $e->getMessage());
            Log::error('Razorpay Service init trace: ' . $e->getTraceAsString());
            $this->isEnabled = false;
        }
    }

    public function isEnabled(): bool
    {
        return $this->isEnabled;
    }

    public function getKeyId(): ?string
    {
        return $this->keyId;
    }

    /**
     * Create a Razorpay order.
     *
     * @param  float  $amount   Amount in major units (e.g. ₹499.00)
     * @param  string $currency 'INR' etc.
     * @param  string|null $receipt
     * @return array{id:string, entity:string, amount:int, currency:string, status:string}
     */
    public function createOrder(float $amount, string $currency = 'INR', ?string $receipt = null): array
    {
        if (!$this->isEnabled || !$this->api) {
            throw new \Exception('Razorpay API not initialized. Check credentials.');
        }

        try {
            $orderData = [
                'receipt'         => $receipt ?? 'order_' . time(),
                'amount'          => (int) round($amount * 100), // paise
                'currency'        => strtoupper($currency),
                'payment_capture' => 1,
            ];

            Log::debug('Razorpay Order Data: ' . json_encode($orderData));

            $razorpayOrder = $this->api->order->create($orderData);

            Log::debug('Razorpay Order Response: ' . json_encode($razorpayOrder));

            return [
                'id'       => $razorpayOrder['id'],
                'entity'   => $razorpayOrder['entity'],
                'amount'   => $razorpayOrder['amount'],
                'currency' => $razorpayOrder['currency'],
                'status'   => $razorpayOrder['status'],
            ];
        } catch (\Throwable $e) {
            Log::error('Razorpay Order Creation Error: ' . $e->getMessage());
            Log::error('Razorpay Error Trace: '        . $e->getTraceAsString());
            throw new \Exception('Razorpay order creation failed: ' . $e->getMessage());
        }
    }

    /**
     * Verify Razorpay payment signature.
     *
     * @return array{success:bool, message:string}
     */
    public function verifyPayment(string $razorpayOrderId, string $razorpayPaymentId, string $razorpaySignature): array
    {
        if (!$this->isEnabled || !$this->api) {
            Log::error('verifyPayment - Razorpay API not initialized');
            return [
                'success' => false,
                'message' => 'Razorpay API not initialized.',
            ];
        }

        try {
            Log::debug(
                'verifyPayment - Verifying with: order_id=' . $razorpayOrderId .
                    ', payment_id=' . $razorpayPaymentId .
                    ', signature=' . $razorpaySignature
            );

            $attributes = [
                'razorpay_order_id'   => $razorpayOrderId,
                'razorpay_payment_id' => $razorpayPaymentId,
                'razorpay_signature'  => $razorpaySignature,
            ];

            // Throws SignatureVerificationError on failure
            $this->api->utility->verifyPaymentSignature($attributes);

            Log::debug('verifyPayment - Signature verified successfully');

            // Optional: log payment status
            try {
                $payment = $this->api->payment->fetch($razorpayPaymentId);
                Log::debug('verifyPayment - Payment status: ' . ($payment['status'] ?? 'unknown'));
            } catch (\Throwable $e) {
                Log::debug('verifyPayment - Could not fetch payment details: ' . $e->getMessage());
            }

            return [
                'success' => true,
                'message' => 'Payment verified successfully',
            ];
        } catch (SignatureVerificationError $e) {
            Log::error('verifyPayment - Signature Verification Error: ' . $e->getMessage());
            Log::error('verifyPayment - Error details: ' . $e->getTraceAsString());

            return [
                'success' => false,
                'message' => 'Payment verification failed: Invalid signature',
            ];
        } catch (\Throwable $e) {
            Log::error('verifyPayment - Payment Verification Error: ' . $e->getMessage());
            Log::error('verifyPayment - Error trace: '    . $e->getTraceAsString());

            return [
                'success' => false,
                'message' => 'Payment verification failed: ' . $e->getMessage(),
            ];
        }
    }

    /**
     * Fetch payment details from Razorpay.
     */
    public function fetchPayment(string $paymentId)
    {
        if (!$this->isEnabled || !$this->api) {
            throw new \Exception('Razorpay API not initialized.');
        }

        return $this->api->payment->fetch($paymentId);
    }
}
