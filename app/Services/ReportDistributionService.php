<?php

namespace App\Services;

use App\Models\DrainageStructure;
use App\Models\Report;
use App\Models\RiskZone;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Log;

/**
 * Transforme un signalement validé en zone à risque ou en ouvrage de drainage.
 *
 * L'entité créée reprend l'UUID du signalement : l'opération est donc
 * idempotente (valider deux fois ne crée jamais de doublon) et permet de
 * compléter ensuite le traitement du signalement.
 */
class ReportDistributionService
{
    /** Champs de traitement propres à une zone à risque. */
    private const RISK_FIELDS = [
        'risk_level',
        'estimated_exposed_population',
        'last_flood_date',
        'main_cause',
    ];

    /** Champs de traitement propres à un ouvrage de drainage. */
    private const STRUCTURE_FIELDS = [
        'structure_type',
        'condition',
        'material',
        'last_maintenance_date',
    ];

    /** Colonnes obligatoires : une valeur nulle est ignorée au lieu d'écraser la valeur existante. */
    private const NOT_NULL_FIELDS = ['risk_level', 'structure_type', 'condition'];

    /**
     * Crée l'entité du signalement, ou met à jour ses champs de traitement si elle existe déjà.
     *
     * @param  array  $details  Précisions du validateur (clés de RISK_FIELDS / STRUCTURE_FIELDS)
     * @param  bool  $matchLegacy  Rattache aussi une entité créée par l'ancienne version (UUID
     *                             différent) au même endroit, par le même collecteur. Réservé à la
     *                             commande de rattrapage.
     * @return bool true si une entité a été créée
     */
    public function distribute(Report $report, array $details = [], bool $matchLegacy = false): bool
    {
        return match ($report->category) {
            'risk_zone' => $this->distributeRiskZone($report, $details, $matchLegacy),
            'drainage_structure' => $this->distributeDrainageStructure($report, $details, $matchLegacy),
            default => $this->unknownCategory($report),
        };
    }

    /** Zone ou ouvrage correspondant au signalement, s'il existe. */
    public function entityFor(Report $report): ?Model
    {
        return match ($report->category) {
            'risk_zone' => RiskZone::find($report->id),
            'drainage_structure' => DrainageStructure::find($report->id),
            default => null,
        };
    }

    private function distributeRiskZone(Report $report, array $details, bool $matchLegacy): bool
    {
        $fields = $this->only($details, self::RISK_FIELDS);
        $zone = $this->find(RiskZone::class, $report, $matchLegacy);

        if ($zone) {
            if ($fields) {
                $zone->fill($fields)->save();
            }

            return false;
        }

        $zone = new RiskZone(array_merge([
            'commune_id' => $report->commune_id,
            'neighborhood_id' => $report->neighborhood_id,
            'latitude' => $report->latitude,
            'longitude' => $report->longitude,
            'risk_level' => $this->riskLevel((int) $report->severity),
            'photo_url' => $report->photo_url,
            'validation_status' => 'validated',
            'comment' => $report->comment,
            'created_by' => $report->collector_id,
        ], $fields));
        $zone->id = $report->id;
        $zone->save();

        Log::info("Zone de risque créée à partir du signalement {$report->id}");

        return true;
    }

    private function distributeDrainageStructure(Report $report, array $details, bool $matchLegacy): bool
    {
        $fields = $this->only($details, self::STRUCTURE_FIELDS);
        $structure = $this->find(DrainageStructure::class, $report, $matchLegacy);

        if ($structure) {
            if ($fields) {
                $structure->fill($fields)->save();
            }

            return false;
        }

        $structure = new DrainageStructure(array_merge([
            'commune_id' => $report->commune_id,
            'neighborhood_id' => $report->neighborhood_id,
            'latitude' => $report->latitude,
            'longitude' => $report->longitude,
            // Le signalement ne précise pas le type : le validateur le renseigne au traitement.
            'structure_type' => 'unknown',
            'condition' => $this->condition((int) $report->severity),
            'photo_url' => $report->photo_url,
            'validation_status' => 'validated',
            'comment' => $report->comment,
            'created_by' => $report->collector_id,
        ], $fields));
        $structure->id = $report->id;
        $structure->save();

        Log::info("Structure de drainage créée à partir du signalement {$report->id}");

        return true;
    }

    private function unknownCategory(Report $report): bool
    {
        Log::warning("Catégorie inconnue pour le signalement {$report->id}: {$report->category}");

        return false;
    }

    /**
     * Retrouve l'entité d'un signalement : par UUID, puis (rattrapage) parmi les entités
     * créées par l'ancienne version, qu'on rattache alors au signalement.
     *
     * @param  class-string<Model>  $modelClass
     */
    private function find(string $modelClass, Report $report, bool $matchLegacy): ?Model
    {
        $entity = $modelClass::find($report->id);

        if ($entity || ! $matchLegacy) {
            return $entity;
        }

        $legacy = $modelClass::query()
            ->whereNotIn('id', Report::query()->select('id'))
            ->where('commune_id', $report->commune_id)
            ->where('latitude', $report->latitude)
            ->where('longitude', $report->longitude)
            ->where('created_by', $report->collector_id)
            ->first();

        if (! $legacy) {
            return null;
        }

        $modelClass::query()->whereKey($legacy->getKey())->update(['id' => $report->id]);

        return $modelClass::find($report->id);
    }

    /** Ne garde que les champs fournis pour la catégorie, sans valeur nulle sur les colonnes obligatoires. */
    private function only(array $details, array $keys): array
    {
        $fields = Arr::only($details, $keys);

        foreach (self::NOT_NULL_FIELDS as $key) {
            if (array_key_exists($key, $fields) && $fields[$key] === null) {
                unset($fields[$key]);
            }
        }

        return $fields;
    }

    /** Gravité du signalement (1-5) -> niveau de risque. */
    private function riskLevel(int $severity): string
    {
        return match ($severity) {
            1, 2 => 'low',
            3 => 'medium',
            4 => 'high',
            5 => 'critical',
            default => 'medium',
        };
    }

    /** Gravité du signalement (1-5) -> état de l'ouvrage (valeurs de StructureCondition). */
    private function condition(int $severity): string
    {
        return match ($severity) {
            1 => 'functional',
            2, 3 => 'partially_obstructed',
            4, 5 => 'obstructed',
            default => 'partially_obstructed',
        };
    }
}
