<?php

namespace App\Services\Payment;

use App\Enums\PaymentSource;
use App\Models\Invoice;
use App\Models\Payment;
use App\Services\Billing\BillingIntegrationRegistry;
use App\Services\Billing\NullBillingAdapter;
use App\Services\Invoice\GenerateInvoiceStatus;
use Carbon\Carbon;
use Illuminate\Support\Facades\Log;
use InvalidArgumentException;
use Ramsey\Uuid\Uuid;
use RuntimeException;

class PaymentService
{
    public function __construct(private BillingIntegrationRegistry $billing) {}

    /**
     * Add a payment to an invoice.
     *
     * @param Invoice     $invoice     The invoice to add payment to
     * @param float       $amount      The payment amount (in decimal)
     * @param string      $paymentDate The payment date
     * @param string      $source      The payment source
     * @param string|null $description Optional payment description
     *
     * @return Payment The created payment
     *
     * @throws RuntimeException        If invoice is not sent
     * @throws InvalidArgumentException If payment source is invalid
     */
    public function addPayment(
        Invoice $invoice,
        float $amount,
        string $paymentDate,
        string $source,
        ?string $description = null
    ): Payment {
        if (! $invoice->isSent()) {
            throw new RuntimeException('Cannot add payment to unsent invoice');
        }

        // Validate payment source using the enum as single source of truth.
        $validSources = array_keys(PaymentSource::values());
        if (! in_array($source, $validSources, true)) {
            throw new InvalidArgumentException("Invalid payment source: {$source}");
        }

        $payment = Payment::query()->create([
            'external_id'    => Uuid::uuid4()->toString(),
            'amount'         => (int) ($amount * 100),
            'payment_date'   => Carbon::parse($paymentDate),
            'payment_source' => $source,
            'description'    => $description,
            'invoice_id'     => $invoice->id,
        ]);

        $this->syncWithBillingAPI($payment, $invoice);

        app(GenerateInvoiceStatus::class, ['invoice' => $invoice])->createStatus();

        return $payment;
    }

    /**
     * Delete a payment (soft-delete).
     *
     * @param Payment $payment The payment to delete
     *
     * @return bool True if deletion was successful
     */
    public function deletePayment(Payment $payment): bool
    {
        $api = $this->billing->driver();

        if ($api instanceof NullBillingAdapter) {
            return (bool) $payment->delete();
        }

        try {
            $api->deletePayment($payment);
        } catch (\Throwable $e) {
            Log::warning('PaymentService: failed to delete payment from billing API', [
                'payment_id' => $payment->id,
                'error'      => $e->getMessage(),
            ]);
        }

        return (bool) $payment->delete();
    }

    /**
     * Get payment by external ID.
     */
    public function findByExternalId(string $externalId): ?Payment
    {
        return Payment::where('external_id', $externalId)->first();
    }

    /**
     * Get all payments for an invoice.
     */
    public function getPaymentsForInvoice(Invoice $invoice)
    {
        return $invoice->payments()->get();
    }

    /**
     * Calculate total payments for an invoice.
     *
     * @return int Total payments in cents
     */
    public function calculateTotalPayments(Invoice $invoice): int
    {
        return (int) $invoice->payments()->sum('amount');
    }

    /**
     * Sync payment with billing API if available.
     */
    private function syncWithBillingAPI(Payment $payment, Invoice $invoice): void
    {
        $api = $this->billing->driver();

        if ($api instanceof NullBillingAdapter || ! $invoice->integration_invoice_id) {
            return;
        }

        try {
            $result = $api->createPayment($payment);

            if (isset($result['Guid'])) {
                $payment->integration_payment_id = $result['Guid'];
                $payment->integration_type       = get_class($api);
                $payment->save();
            }
        } catch (\Throwable $e) {
            Log::warning('PaymentService: failed to sync payment with billing API', [
                'payment_id' => $payment->id,
                'error'      => $e->getMessage(),
            ]);
        }
    }
}
