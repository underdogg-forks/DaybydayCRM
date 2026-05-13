<?php

namespace Feature\Absenses;

use App\Enums\PermissionName;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Session;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\Attributes\Test;
use Tests\AbstractTestCase;

class AbsenceControllerTest extends AbstractTestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        Carbon::setTestNow('2024-01-15 12:00:00');
    }

    protected function tearDown(): void
    {
        Carbon::setTestNow();
        parent::tearDown();
    }

    #[Test]
    #[Group('junie_repaired')]
    public function can_create_absence_for_other_user()
    {
        /* Arrange */
        $this->user = User::factory()->withRole('employee')->create();
        $this->withPermissions(PermissionName::ABSENCE_MANAGE);

        $this->assertTrue($this->user->can(PermissionName::ABSENCE_MANAGE->value), 'User should have absence-manage permission');

        $user = User::factory()->create();

        /* Act */
        $response = $this->json('POST', route('absence.store'), [
            'reason'              => 'Sick',
            'user_external_id'    => $user->external_id,
            'start_date'          => '2020-01-01',
            'end_date'            => '2020-01-02',
            'medical_certificate' => null,
            'comment'             => 'Sick kid',
        ]);

        /* Assert */
        $response->assertStatus(200);
        $absences = $user->fresh()->absences;
        $this->assertCount(1, $absences);
    }

    #[Test]
    #[Group('junie_repaired')]
    public function not_providing_user_external_id_creates_absence_for_authenticated_user()
    {
        /* Arrange */

        /* Act */
        $response = $this->json('POST', route('absence.store'), [
            'reason'              => 'Sick',
            'start_date'          => '2020-01-01',
            'end_date'            => '2020-01-02',
            'medical_certificate' => null,
            'comment'             => 'Sick kid',
        ]);

        /* Assert */
        $response->assertStatus(200);
        $this->assertCount(1, $this->user->fresh()->absences);
    }

    #[Test]
    #[Group('junie_repaired')]
    public function creating_absence_for_other_users_without_permission_creates_for_user_it_self()
    {
        /* Arrange */
        $this->user = User::factory()->create();
        $this->actingAs($this->user);
        $absentUser = User::factory()->create();

        /* Act */
        $response = $this->json('POST', route('absence.store'), [
            'reason'              => 'Sick',
            'user_external_id'    => $absentUser->external_id,
            'start_date'          => '2020-01-01',
            'end_date'            => '2020-01-02',
            'medical_certificate' => null,
            'comment'             => 'Sick kid',
        ]);

        /* Assert */
        $response->assertStatus(200);
        $this->assertCount(0, $absentUser->fresh()->absences);
        $this->assertCount(1, $this->user->fresh()->absences);
    }
}
