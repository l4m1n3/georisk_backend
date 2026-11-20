<?php

namespace App\Services;

use App\Enums\ValidationStatus;
use App\Models\DrainageStructure;
use App\Models\Report;
use App\Models\RiskZone;
use App\Models\User;
use App\Models\ValidationHistory;
use Illuminate\Database\Eloquent\Model;
use InvalidArgumentException;

class ValidationService
{
    /**
     * Validate an entity (risk zone, drainage structure, or report).
     *
     * @param  RiskZone|DrainageStructure|Report  $entity
     */
    public function validate(Model $entity, User $validator, ?string $comment = null): void
    {
        $this->changeStatus($entity, $validator, ValidationStatus::Validated, $comment);
    }

    /**
     * Reject an entity.
     */
    public function reject(Model $entity, User $validator, ?string $comment = null): void
    {
        $this->changeStatus($entity, $validator, ValidationStatus::Rejected, $comment);
    }

    // ─── Private ────────────────────────────────────────────────────────────

    private function changeStatus(
        Model $entity,
        User $validator,
        ValidationStatus $newStatus,
        ?string $comment
    ): void {
        $oldStatus = $entity->validation_status?->value ?? ValidationStatus::Pending->value;

        $entity->update(['validation_status' => $newStatus->value]);

        ValidationHistory::create([
            'entity_type' => $this->resolveEntityType($entity),
            'entity_id' => $entity->getKey(),
            'user_id' => $validator->id,
            'old_status' => $oldStatus,
            'new_status' => $newStatus->value,
            'comment' => $comment,
        ]);
    }

    private function resolveEntityType(Model $entity): string
    {
        return match (true) {
            $entity instanceof RiskZone => 'risk_zone',
            $entity instanceof DrainageStructure => 'drainage_structure',
            $entity instanceof Report => 'report',
            default => throw new InvalidArgumentException('Unknown entity type for validation.'),
        };
    }
}
