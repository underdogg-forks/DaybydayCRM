<?php

namespace Tests\Unit\ViewComposers;

use App\Http\ViewComposers\InvoiceHeaderComposer;
use App\Http\ViewComposers\LeadHeaderComposer;
use App\Http\ViewComposers\TaskHeaderComposer;
use App\Models\Client;
use App\Models\Invoice;
use App\Models\Lead;
use App\Models\Setting;
use App\Models\Task;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\View\View;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\Attributes\Test;
use Tests\AbstractTestCase;

/**
 * Verifies that all three header view composers degrade gracefully when
 * related models (client, contact, assignee) are null instead of crashing.
 */
#[Group('view-composers')]
class ViewComposerNullSafetyTest extends AbstractTestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        Carbon::setTestNow('2024-01-15 12:00:00');
        Setting::factory()->create();
    }

    protected function tearDown(): void
    {
        Carbon::setTestNow();
        parent::tearDown();
    }

    // ─── TaskHeaderComposer ──────────────────────────────────────────────────

    #[Test]
    public function it_task_header_composer_handles_task_without_client()
    {
        /* Arrange */
        $task = Task::factory()->create([
            'client_id'        => null,
            'user_assigned_id' => $this->user->id,
        ]);

        $view    = $this->makeView(['tasks' => $task]);
        $shared  = [];
        $view->shouldReceive('with')->andReturnUsing(function ($key, $value) use (&$shared) {
            $shared[$key] = $value;
        });

        /* Act – must not throw */
        (new TaskHeaderComposer())->compose($view);

        /* Assert */
        $this->assertNull($shared['client']);
        $this->assertNull($shared['contact_info']);
    }

    #[Test]
    public function it_task_header_composer_handles_task_without_assigned_user()
    {
        /* Arrange */
        $client = Client::factory()->create();
        $task   = Task::factory()->create([
            'client_id'        => $client->id,
            'user_assigned_id' => null,
        ]);

        $view   = $this->makeView(['tasks' => $task]);
        $shared = [];
        $view->shouldReceive('with')->andReturnUsing(function ($key, $value) use (&$shared) {
            $shared[$key] = $value;
        });

        /* Act */
        (new TaskHeaderComposer())->compose($view);

        /* Assert */
        $this->assertNull($shared['contact']);
        $this->assertSame($client->id, $shared['client']->id);
    }

    #[Test]
    public function it_task_header_composer_handles_missing_task_in_view_data()
    {
        /* Arrange – no 'tasks' key at all */
        $view   = $this->makeView([]);
        $shared = [];
        $view->shouldReceive('with')->andReturnUsing(function ($key, $value) use (&$shared) {
            $shared[$key] = $value;
        });

        /* Act – must not throw */
        (new TaskHeaderComposer())->compose($view);

        /* Assert */
        $this->assertNull($shared['contact'] ?? null);
        $this->assertNull($shared['client'] ?? null);
    }

    // ─── LeadHeaderComposer ──────────────────────────────────────────────────

    #[Test]
    public function it_lead_header_composer_handles_lead_without_client()
    {
        /* Arrange */
        $lead = Lead::factory()->create([
            'client_id'        => null,
            'user_assigned_id' => $this->user->id,
        ]);

        $view   = $this->makeView(['lead' => $lead]);
        $shared = [];
        $view->shouldReceive('with')->andReturnUsing(function ($key, $value) use (&$shared) {
            $shared[$key] = $value;
        });

        /* Act */
        (new LeadHeaderComposer())->compose($view);

        /* Assert */
        $this->assertNull($shared['client']);
        $this->assertNull($shared['contact_info']);
    }

    #[Test]
    public function it_lead_header_composer_handles_missing_lead_in_view_data()
    {
        /* Arrange */
        $view   = $this->makeView([]);
        $shared = [];
        $view->shouldReceive('with')->andReturnUsing(function ($key, $value) use (&$shared) {
            $shared[$key] = $value;
        });

        /* Act */
        (new LeadHeaderComposer())->compose($view);

        /* Assert */
        $this->assertNull($shared['contact'] ?? null);
        $this->assertNull($shared['client'] ?? null);
    }

    // ─── InvoiceHeaderComposer ───────────────────────────────────────────────

    #[Test]
    public function it_invoice_header_composer_handles_invoice_without_client()
    {
        /* Arrange */
        $invoice = Invoice::factory()->create(['client_id' => null]);

        $view   = $this->makeView(['invoice' => $invoice]);
        $shared = [];
        $view->shouldReceive('with')->andReturnUsing(function ($key, $value) use (&$shared) {
            $shared[$key] = $value;
        });

        /* Act */
        (new InvoiceHeaderComposer())->compose($view);

        /* Assert */
        $this->assertNull($shared['client']);
        $this->assertNull($shared['contact_info']);
    }

    #[Test]
    public function it_invoice_header_composer_handles_missing_invoice_in_view_data()
    {
        /* Arrange */
        $view   = $this->makeView([]);
        $shared = [];
        $view->shouldReceive('with')->andReturnUsing(function ($key, $value) use (&$shared) {
            $shared[$key] = $value;
        });

        /* Act */
        (new InvoiceHeaderComposer())->compose($view);

        /* Assert */
        $this->assertNull($shared['client'] ?? null);
        $this->assertNull($shared['contact_info'] ?? null);
    }

    // ─── Helpers ─────────────────────────────────────────────────────────────

    /**
     * Create a minimal Mockery View partial that exposes getData() and
     * accepts with() calls.
     */
    private function makeView(array $data): \Mockery\MockInterface
    {
        $mock = \Mockery::mock(View::class);
        $mock->shouldReceive('getData')->andReturn($data);
        // 'with' may or may not be called – allow any number of calls.
        $mock->shouldReceive('with')->andReturn($mock);

        return $mock;
    }
}
