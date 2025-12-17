<?php

namespace Tests\Unit;

use App\Models\DgAnomaly;
use App\Models\DgWorkSession;
use App\Models\User;
use App\Services\Anomalies\AnomalyStatusService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AnomalyStatusServiceTest extends TestCase
{
    use RefreshDatabase;

    public function test_notes_are_appended_on_approval_and_flags_are_cleaned(): void
    {
        $actor = User::factory()->create();

        $session = DgWorkSession::create([
            'user_id' => $actor->id,
            'session_date' => '2024-01-01',
            'worked_minutes' => 60,
            'anomaly_flags' => [
                ['type' => 'late_entry', 'minutes' => 15],
                ['type' => 'absence'],
            ],
        ]);

        $anomaly = DgAnomaly::create([
            'user_id' => $actor->id,
            'session_id' => $session->id,
            'date' => '2024-01-01',
            'type' => 'late_entry',
            'status' => 'open',
            'note' => 'Nota precedente',
        ]);

        $service = app(AnomalyStatusService::class);

        $result = $service->approve($anomaly, $actor, 'Allineata');

        $anomaly->refresh();
        $session->refresh();

        $this->assertTrue($result);
        $this->assertEquals('approved', $anomaly->status);
        $this->assertStringContainsString('Nota precedente', $anomaly->note);
        $this->assertStringContainsString('Approvazione: Allineata', $anomaly->note);
        $this->assertEquals([['type' => 'absence']], $session->anomaly_flags);
    }

    public function test_approval_fails_when_anomaly_is_not_open(): void
    {
        $actor = User::factory()->create();

        $anomaly = DgAnomaly::create([
            'user_id' => $actor->id,
            'date' => '2024-01-05',
            'type' => 'absence',
            'status' => 'rejected',
            'note' => 'Motivo iniziale',
        ]);

        $service = app(AnomalyStatusService::class);

        $result = $service->approve($anomaly, $actor, 'Nuova nota');

        $this->assertFalse($result);
        $this->assertEquals('Motivo iniziale', $anomaly->fresh()->note);
    }
}
