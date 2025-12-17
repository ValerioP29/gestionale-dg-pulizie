<?php

namespace Tests\Feature;

use App\Models\DgAnomaly;
use App\Models\DgWorkSession;
use App\Models\User;
use App\Services\Reports\WorkReportBuilder;
use Carbon\CarbonImmutable;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class WorkReportBuilderTest extends TestCase
{
    use RefreshDatabase;

    public function test_sessions_with_non_approved_anomalies_are_excluded_from_employee_report(): void
    {
        $user = User::factory()->create();
        $other = User::factory()->create();

        $sessionWithoutAnomalies = DgWorkSession::create([
            'user_id' => $user->id,
            'session_date' => '2024-01-01',
            'worked_minutes' => 120,
        ]);

        $sessionWithApprovedAnomaly = DgWorkSession::create([
            'user_id' => $user->id,
            'session_date' => '2024-01-02',
            'worked_minutes' => 60,
        ]);

        $sessionWithOpenAnomaly = DgWorkSession::create([
            'user_id' => $user->id,
            'session_date' => '2024-01-03',
            'worked_minutes' => 240,
        ]);

        $sessionWithRejectedAnomaly = DgWorkSession::create([
            'user_id' => $user->id,
            'session_date' => '2024-01-04',
            'worked_minutes' => 180,
        ]);

        $otherUserSession = DgWorkSession::create([
            'user_id' => $other->id,
            'session_date' => '2024-01-02',
            'worked_minutes' => 90,
        ]);

        DgAnomaly::create([
            'user_id' => $user->id,
            'session_id' => $sessionWithApprovedAnomaly->id,
            'date' => '2024-01-02',
            'type' => 'absence',
            'status' => 'approved',
        ]);

        DgAnomaly::create([
            'user_id' => $user->id,
            'session_id' => $sessionWithOpenAnomaly->id,
            'date' => '2024-01-03',
            'type' => 'late_entry',
            'status' => 'open',
        ]);

        DgAnomaly::create([
            'user_id' => $user->id,
            'session_id' => $sessionWithRejectedAnomaly->id,
            'date' => '2024-01-04',
            'type' => 'missing_punch',
            'status' => 'rejected',
        ]);

        DgAnomaly::create([
            'user_id' => $other->id,
            'session_id' => $otherUserSession->id,
            'date' => '2024-01-02',
            'type' => 'absence',
            'status' => 'open',
        ]);

        $builder = app(WorkReportBuilder::class);

        $report = $builder->buildEmployeeReport(
            $user->id,
            CarbonImmutable::parse('2024-01-01'),
            CarbonImmutable::parse('2024-01-31')
        );

        $this->assertCount(2, $report['rows']);
        $this->assertEqualsCanonicalizing([
            $sessionWithoutAnomalies->session_date,
            $sessionWithApprovedAnomaly->session_date,
        ], $report['rows']->pluck('date')->map->toDateString()->all());

        $this->assertEquals(3.0, $report['summary']['total_hours']);
        $this->assertEquals(2, $report['summary']['days_worked']);
    }
}
