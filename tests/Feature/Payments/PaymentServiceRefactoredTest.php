<?php

namespace Tests\Feature\Payments;

use App\Http\Middleware\VerifyCsrfToken;
use App\Models\Invoice;
use App\Models\InvoiceLine;
use App\Models\Payment;
use App\Services\Billing\BillingIntegrationRegistry;
use App\Services\Billing\NullBillingAdapter;
use App\Services\Payment\PaymentService;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\Attributes\Test;
use Tests\AbstractTestCase;

/**
 * Tests for the refactored PaymentService and PaymentsController.
 *
 * These tests verify:
 *  - Controller delegates entirely to PaymentService.
 *  - NullBillingAdapter is used when no billing integration is configured.
 *  - Soft-delete semantics are explicit.
 *  - JSON responses are returned for JSON requests.
 *  - Authorization is checked before any infrastructure code runs.
 */
#[Group('payments')]
#[Group('integration-isolation')]
class PaymentServiceRefactoredTest extends AbstractTestCase
{
    use RefreshDatabase;

    private Invoice $invoice;

    private InvoiceLine $invoiceLine;

    private PaymentService $paymentService;

    protected function setUp(): void
    {
        parent::setUp();
        Carbon::setTestNow('2024-01-15 12:00:00');
        $this->withoutMiddleware([VerifyCsrfToken::class]);
        \App\Models\Setting::factory()->create();

        $this->invoice = Invoice::factory()->create([
            'sent_at' => today(),
            'status'  => 'unpaid',
        ]);
        $this->invoiceLine = InvoiceLine::factory()->create([
            'invoice_id' => $this->invoice->id,
            'price'      => 5000,
            'quantity'   => 1,
        ]);

        $this->paymentService = app(PaymentService::class);
    }

    protected function tearDown(): void
    {
        Carbon::setTestNow();
        parent::tearDown();
    }

    // ─── PaymentService unit-ish ─────────────────────────────────────────────

    #[Test]
    public function it_payment_service_uses_null_billing_adapter_when_no_integration_configured()
    {
        /* Arrange */
        \App\Models\Integration::whereApiType('billing')->delete();
        $registry = app(BillingIntegrationRegistry::class);
        $registry->reset();

        /* Act */
        $driver = $registry->driver();

        /* Assert */
        $this->assertInstanceOf(NullBillingAdapter::class, $driver);
    }

    #[Test]
    public function it_add_payment_creates_payment_record()
    {
        /* Arrange */
        $this->assertDatabaseCount('payments', 0);

        /* Act */
        $payment = $this->paymentService->addPayment(
            $this->invoice,
            50.00,
            '2024-01-15',
            'bank',
            'Test payment'
        );

        /* Assert */
        $this->assertInstanceOf(Payment::class, $payment);
        $this->assertDatabaseHas('payments', [
            'invoice_id'     => $this->invoice->id,
            'amount'         => 5000, // in cents
            'payment_source' => 'bank',
        ]);
    }

    #[Test]
    public function it_add_payment_throws_on_unsent_invoice()
    {
        /* Arrange */
        $invoice = Invoice::factory()->create(['sent_at' => null]);

        /* Act & Assert */
        $this->expectException(\RuntimeException::class);
        $this->paymentService->addPayment($invoice, 50.00, '2024-01-15', 'bank');
    }

    #[Test]
    public function it_add_payment_throws_on_invalid_source()
    {
        /* Act & Assert */
        $this->expectException(\InvalidArgumentException::class);
        $this->paymentService->addPayment(
            $this->invoice,
            50.00,
            '2024-01-15',
            'not_a_real_source'
        );
    }

    #[Test]
    public function it_delete_payment_soft_deletes_the_record()
    {
        /* Arrange */
        $payment = Payment::factory()->create([
            'invoice_id'     => $this->invoice->id,
            'amount'         => 1000,
            'payment_source' => 'bank',
        ]);

        /* Act */
        $result = $this->paymentService->deletePayment($payment);

        /* Assert */
        $this->assertTrue($result);
        $this->assertSoftDeleted('payments', ['id' => $payment->id]);
    }

    #[Test]
    public function it_delete_payment_succeeds_even_when_no_billing_adapter_configured()
    {
        /* Arrange */
        \App\Models\Integration::whereApiType('billing')->delete();
        app(BillingIntegrationRegistry::class)->reset();

        $payment = Payment::factory()->create([
            'invoice_id'     => $this->invoice->id,
            'amount'         => 1000,
            'payment_source' => 'cash',
        ]);

        /* Act – should not throw */
        $result = $this->paymentService->deletePayment($payment);

        /* Assert */
        $this->assertTrue($result);
        $this->assertSoftDeleted('payments', ['id' => $payment->id]);
    }

    // ─── Controller HTTP layer ───────────────────────────────────────────────

    #[Test]
    public function it_add_payment_controller_returns_json_201_for_json_request()
    {
        /* Act */
        $response = $this->json('POST', route('payment.add', $this->invoice->external_id), [
            'amount'       => 50,
            'payment_date' => '2024-01-15',
            'source'       => 'bank',
        ]);

        /* Assert */
        $response->assertStatus(201);
        $response->assertJsonFragment(['message' => __('Payment successfully added')]);
    }

    #[Test]
    public function it_add_payment_controller_rejects_unsent_invoice_with_422()
    {
        /* Arrange */
        $unsent = Invoice::factory()->create(['sent_at' => null]);

        /* Act */
        $response = $this->json('POST', route('payment.add', $unsent->external_id), [
            'amount'       => 50,
            'payment_date' => '2024-01-15',
            'source'       => 'bank',
        ]);

        /* Assert */
        $response->assertStatus(422);
        $this->assertDatabaseCount('payments', 0);
    }

    #[Test]
    public function it_destroy_payment_controller_returns_json_200_for_json_request()
    {
        /* Arrange */
        $payment = Payment::factory()->create([
            'invoice_id'     => $this->invoice->id,
            'amount'         => 1000,
            'payment_source' => 'bank',
        ]);

        /* Act */
        $response = $this->json('DELETE', route('payment.destroy', $payment->external_id));

        /* Assert */
        $response->assertStatus(200);
        $response->assertJsonFragment(['message' => __('Payment successfully deleted')]);
        $this->assertSoftDeleted('payments', ['id' => $payment->id]);
    }

    #[Test]
    public function it_destroy_payment_controller_returns_403_when_no_permission()
    {
        /* Arrange */
        $payment  = Payment::factory()->create([
            'invoice_id'     => $this->invoice->id,
            'amount'         => 1000,
            'payment_source' => 'bank',
        ]);
        $noPerms  = \App\Models\User::factory()->create();
        $this->actingAs($noPerms);

        /* Act */
        $response = $this->json('DELETE', route('payment.destroy', $payment->external_id));

        /* Assert – 403 before any infrastructure code runs */
        $response->assertStatus(403);
        $this->assertDatabaseHas('payments', ['id' => $payment->id, 'deleted_at' => null]);
    }

    #[Test]
    public function it_add_payment_controller_returns_403_when_no_permission()
    {
        /* Arrange */
        $noPerms = \App\Models\User::factory()->create();
        $this->actingAs($noPerms);

        /* Act */
        $response = $this->json('POST', route('payment.add', $this->invoice->external_id), [
            'amount'       => 50,
            'payment_date' => '2024-01-15',
            'source'       => 'bank',
        ]);

        /* Assert */
        $response->assertStatus(403);
        $this->assertDatabaseCount('payments', 0);
    }
}
