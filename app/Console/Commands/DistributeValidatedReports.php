<?php

namespace App\Console\Commands;

use App\Models\DrainageStructure;
use App\Models\Report;
use App\Services\ReportDistributionService;
use Illuminate\Console\Command;

class DistributeValidatedReports extends Command
{
    protected $signature = 'reports:distribute';

    protected $description = 'Crée les zones à risque et ouvrages de drainage manquants pour les signalements déjà validés, et rattache les anciens à leur signalement';

    public function handle(ReportDistributionService $service): int
    {
        // Anciennes valeurs d'état écrites par une version précédente du code.
        $legacyConditions = [
            'good' => 'functional',
            'fair' => 'partially_obstructed',
            'poor' => 'obstructed',
        ];
        foreach ($legacyConditions as $old => $new) {
            DrainageStructure::where('condition', $old)->update(['condition' => $new]);
        }

        $created = 0;
        $existing = 0;

        Report::where('validation_status', 'validated')
            ->orderBy('created_at')
            ->each(function (Report $report) use ($service, &$created, &$existing) {
                $service->distribute($report, [], matchLegacy: true) ? $created++ : $existing++;
            });

        $this->info("Créés : {$created} · déjà présents (rattachés au signalement si besoin) : {$existing}");

        return self::SUCCESS;
    }
}
