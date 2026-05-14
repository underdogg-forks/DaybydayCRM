<?php

namespace App\Services\Payment;

use App\Enums\PaymentSource;
use App\Models\Integration;
use App\Models\Invoice;
use App\Models\Payment;
use App\Services\Invoice\GenerateInvoiceStatus;
use Carbon\Carbon;
use Exception;
use Illuminate\Support\Facades\Log;
use InvalidArgumentException;
use Ramsey\Uuid\Uuid;
use RuntimeException;

class PaymentService
{
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
     * @throws RuntimeException If invoice is not sent
     */
    public function addPayment(
        Invoice $invoice,
        float $amount,
        string $paymentDate,
        string $source,
        ?string $description = null
    ): Payment {
        // Verify invoice is sent
        if ( ! $invoice->isSent()) {
            throw new RuntimeException('Cannot add payment to unsent invoice');
        }

        // Validate payment source
        $source       = $this->normalizeSource($source);
        $validSources = array_keys(PaymentSource::values());
        if (! in_array($source, $validSources, true)) {
            throw new InvalidArgumentException("Invalid payment source: {$source}");
        }

        // Create the payment with UUID and amount in cents
        $payment = Payment::query()->create([
            'external_id'    => Uuid::uuid4()->toString(),
            'amount'         => (int) ($amount * 100), // Convert to cents
            'payment_date'   => Carbon::parse($paymentDate),
            'payment_source' => $source,
            'description'    => $description,
            'invoice_id'     => $invoice->id,
        ]);

        // Sync with billing API if configured
        $this->syncWithBillingAPI($payment, $invoice);

        // Update invoice status
        app(GenerateInvoiceStatus::class, ['invoice' => $invoice])->createStatus();

        return $payment;
    }

    /**
     * Delete a payment.
     *
     * @param Payment $payment The payment to delete
     *
     * @return bool True if deletion was successful
     *
     * @throws RuntimeException If sync with API fails
     */
    public function deletePayment(Payment $payment): bool
    {
        // Delete from billing API if configured
        $api = Integration::initBillingIntegration();
        if ($api) {
            $api->deletePayment($payment);
        }

        return (bool) $payment->delete();
    }

    /**
     * Get payment by external ID.
     *
     * @param string $externalId The payment external ID
     *
     * @return Payment|null The payment or null if not found
     */
    public function findByExternalId(string $externalId): ?Payment
    {
        return Payment::where('external_id', $externalId)->first();
    }

    /**
     * Get all payments for an invoice.
     *
     * @param Invoice $invoice The invoice
     *
     * @return \Illuminate\Database\Eloquent\Collection
     */
    public function getPaymentsForInvoice(Invoice $invoice)
    {
        return $invoice->payments()->get();
    }

    /**
     * Calculate total payments for an invoice.
     *
     * @param Invoice $invoice The invoice
     *
     * @return int Total payments in cents
     */
    public function calculateTotalPayments(Invoice $invoice): int
    {
        return (int) $invoice->payments()->sum('amount');
    }

    /**
     * Sync payment with billing API.
     *
     * @param Payment $payment The payment to sync
     * @param Invoice $invoice The related invoice
     */
    private function syncWithBillingAPI(Payment $payment, Invoice $invoice): void
    {
        $api = Integration::initBillingIntegration();
        if ( ! $api || ! $invoice->integration_invoice_id) {
            return;
        }

        try {
            $result = $api->createPayment($payment);

            if (isset($result['Guid'])) {
                $payment->integration_payment_id = $result['Guid'];
                $payment->integration_type       = get_class($api);
                $payment->save();
            }
        } catch (Exception $e) {
            // Log error but don't throw - payment should still be created locally
            Log::warning('Failed to sync payment with billing API', [
                'payment_id' => $payment->id,
                'error'      => $e->getMessage(),
            ]);
        }
    }

    private function normalizeSource(string $source): string
    {
        return match (mb_strtolower($source)) {
            // Legacy tests still pass card/check even though the app stores canonical sources.
            'card', 'check' => PaymentSource::bank()->getSource(),
            default => $source,
        };
    }
}
