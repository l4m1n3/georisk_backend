<?php

namespace App\Services;

use App\Enums\SyncStatus;
use App\Models\DrainageStructure;
use App\Models\Report;
use App\Models\RiskZone;
use App\Models\SyncQueue;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class SyncService
{
    public function __construct(
        private readonly GeoService $geoService
    ) {}

    /**
     * Process a batch of sync items coming from the mobile app.
     *
     * Each item in $items must contain:
     *   - entity_type  : 'risk_zone' | 'drainage_structure' | 'report'
     *   - entity_id    : UUID (generated on mobile)
     *   - operation    : 'create' | 'update' | 'delete'
     *   - payload      : array of entity fields
     *
     * @return array Results per item (entity_id => status)
     */
    public function processBatch(array $items): array
    {
        $results = [];

        foreach ($items as $item) {
            $entityType = $item['entity_type'];
            $entityId = $item['entity_id'];
            $operation = $item['operation'];
            $payload = $item['payload'];

            // Record in server-side sync queue for traceability
            $queueEntry = SyncQueue::firstOrCreate(
                ['entity_type' => $entityType, 'entity_id' => $entityId, 'operation' => $operation],
                ['payload' => $payload, 'status' => SyncStatus::Pending->value]
            );

            try {
                DB::beginTransaction();

                $queueEntry->update([
                    'status' => SyncStatus::Syncing->value,
                    'last_attempt_at' => now(),
                    'attempts' => $queueEntry->attempts + 1,
                ]);

                $entity = $this->upsertByUuid($entityType, $entityId, $payload);

                $queueEntry->update(['status' => SyncStatus::Synced->value]);

                DB::commit();

                $results[$entityId] = [
                    'status' => 'synced',
                    'id' => $entity->id,
                ];
            } catch (\Throwable $e) {
                DB::rollBack();

                $queueEntry->update([
                    'status' => SyncStatus::Failed->value,
                    'error_message' => $e->getMessage(),
                ]);

                Log::warning("Sync failed for {$entityType} {$entityId}: {$e->getMessage()}");

                $results[$entityId] = [
                    'status' => 'failed',
                    'error' => $e->getMessage(),
                ];
            }
        }

        return $results;
    }

    /**
     * Upsert an entity by UUID (idempotent: safe to call multiple times with same UUID).
     */
    public function upsertByUuid(string $entityType, string $uuid, array $payload): RiskZone|DrainageStructure|Report
    {
        return match ($entityType) {
            'risk_zone' => $this->upsertRiskZone($uuid, $payload),
            'drainage_structure' => $this->upsertDrainageStructure($uuid, $payload),
            'report' => $this->upsertReport($uuid, $payload),
            default => throw new \InvalidArgumentException("Unknown entity type: {$entityType}"),
        };
    }

    // ─── Private upserts ────────────────────────────────────────────────────

    private function upsertRiskZone(string $uuid, array $payload): RiskZone
    {
        $geometry = $payload['geometry'] ?? null;

        $zone = RiskZone::updateOrCreate(
            ['id' => $uuid],
            collect($payload)->except('geometry')->toArray()
        );

        if ($geometry) {
            $geojsonString = is_array($geometry) ? json_encode($geometry) : $geometry;
            $this->geoService->setGeometry('risk_zones', $zone->id, $geojsonString);
        }

        return $zone->fresh();
    }

    private function upsertDrainageStructure(string $uuid, array $payload): DrainageStructure
    {
        $geometry = $payload['geometry'] ?? null;

        $structure = DrainageStructure::updateOrCreate(
            ['id' => $uuid],
            collect($payload)->except('geometry')->toArray()
        );

        if ($geometry) {
            $geojsonString = is_array($geometry) ? json_encode($geometry) : $geometry;
            $this->geoService->setGeometry('drainage_structures', $structure->id, $geojsonString);
        }

        return $structure->fresh();
    }

    private function upsertReport(string $uuid, array $payload): Report
    {
        $lat = $payload['latitude'] ?? null;
        $lng = $payload['longitude'] ?? null;

        $report = Report::updateOrCreate(
            ['id' => $uuid],
            collect($payload)->toArray()
        );

        // Sync geometry from lat/lng if provided
        if ($lat !== null && $lng !== null) {
            DB::statement(
                'UPDATE reports SET geometry = ST_SetSRID(ST_MakePoint(?, ?), 4326) WHERE id = ?',
                [(float) $lng, (float) $lat, $report->id]
            );
        }

        return $report->fresh();
    }
}
