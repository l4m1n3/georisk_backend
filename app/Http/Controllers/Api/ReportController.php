<?php

namespace App\Http\Controllers\Api;

use App\Enums\MainCause;
use App\Enums\RiskLevel;
use App\Enums\StructureCondition;
use App\Enums\StructureType;
use App\Http\Controllers\Controller;
use App\Models\Report;
use App\Services\ReportDistributionService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Validation\Rule;

class ReportController extends Controller
{
    /**
     * Liste des signalements avec filtres.
     */
    public function index(Request $request)
    {
        $query = Report::query()
            ->with([
                'commune:id,name',
                'neighborhood:id,name',
                'collector:id,name',
            ]);

        $query->when(
            $request->filled('commune_id'),
            fn ($q) => $q->where('commune_id', $request->commune_id)
        );

        $query->when(
            $request->filled('neighborhood_id'),
            fn ($q) => $q->where('neighborhood_id', $request->neighborhood_id)
        );

        $query->when(
            $request->filled('category'),
            fn ($q) => $q->where('category', $request->category)
        );

        $query->when(
            $request->filled('severity'),
            fn ($q) => $q->where(
                'severity',
                '>=',
                (int) $request->severity
            )
        );

        $query->when(
            $request->filled('validation_status'),
            fn ($q) => $q->where(
                'validation_status',
                $request->validation_status
            )
        );

        $query->when(
            $request->filled('date_from'),
            fn ($q) => $q->whereDate(
                'created_at',
                '>=',
                $request->date_from
            )
        );

        $query->when(
            $request->filled('date_to'),
            fn ($q) => $q->whereDate(
                'created_at',
                '<=',
                $request->date_to
            )
        );

        // Permet à l'application mobile de redemander uniquement ses
        // signalements (par UUID) pour mettre à jour leur statut.
        $query->when(
            $request->filled('ids'),
            fn ($q) => $q->whereIn(
                'id',
                array_slice((array) $request->input('ids'), 0, 100)
            )
        );

        $perPage = min(100, max(1, (int) $request->input('per_page', 20)));

        return response()->json(
            $query->latest()->paginate($perPage)
        );
    }

    /**
     * Créer un nouveau signalement.
     */
    public function store(Request $request)
    {
        try {
            $validated = $request->validate([
                // UUID créé sur le téléphone (optionnel).
                'id' => [
                    'nullable',
                    'uuid',
                ],

                'commune_id' => [
                    'required',
                    'integer',
                    'exists:communes,id',
                ],

                'neighborhood_id' => [
                    'nullable',
                    'integer',
                    'exists:neighborhoods,id',
                ],

                'category' => [
                    'required',
                    'string',
                    'max:255',
                ],

                'severity' => [
                    'required',
                    'integer',
                    'min:1',
                    'max:5',
                ],

                'comment' => [
                    'nullable',
                    'string',
                ],

                'gps_accuracy' => [
                    'nullable',
                    'numeric',
                    'min:0',
                ],

                'latitude' => [
                    'required',
                    'numeric',
                    'between:-90,90',
                ],

                'longitude' => [
                    'required',
                    'numeric',
                    'between:-180,180',
                ],

                'photo' => [
                    'nullable',
                    'image',
                    'max:5120',
                ],
            ]);

            /*
             * Envoi idempotent : si le téléphone renvoie un signalement déjà
             * enregistré (réponse perdue, coupure réseau), on renvoie
             * l'existant au lieu de créer un doublon.
             */
            if (! empty($validated['id'])) {
                $existing = Report::find($validated['id']);

                if ($existing) {
                    if ((int) $existing->collector_id !== (int) $request->user()->id) {
                        return response()->json([
                            'message' => 'Identifiant de signalement déjà utilisé.',
                        ], 409);
                    }

                    $existing->load([
                        'commune:id,name',
                        'neighborhood:id,name',
                        'collector:id,name',
                    ]);

                    return response()->json([
                        'message' => 'Signalement déjà enregistré.',
                        'data' => $existing,
                    ], 200);
                }
            }

            $report = DB::transaction(function () use ($request, $validated) {

                /*
                 * L'utilisateur connecté devient automatiquement
                 * le collecteur du signalement.
                 */
                $validated['collector_id'] = $request->user()->id;

                /*
                 * Statut initial du signalement.
                 */
                $validated['validation_status'] = 'pending';

                /*
                 * Gestion de la photo.
                 */
                if ($request->hasFile('photo')) {
                    $validated['photo_url'] = $request
                        ->file('photo')
                        ->store('reports', 'public');
                }

                return Report::create($validated);
            });

            $report->load([
                'commune:id,name',
                'neighborhood:id,name',
                'collector:id,name',
            ]);

            return response()->json([
                'message' => 'Signalement créé avec succès.',
                'data' => $report,
            ], 201);
        } catch (\Exception $e) {
            Log::error('Erreur lors de la création du signalement : '.$e->getMessage(), [
                'exception' => $e,
                'request_data' => $request->all(),
            ]);

            return response()->json([
                'message' => 'Erreur lors de la création du signalement.',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Afficher un signalement.
     */
    public function show(Report $report)
    {
        $report->load([
            'commune:id,name',
            'neighborhood:id,name',
            'collector:id,name',
        ]);

        return response()->json($this->withEntity($report));
    }

    /**
     * Valider un signalement et le traiter : la zone à risque ou l'ouvrage de
     * drainage correspondant est créé, avec les précisions du validateur.
     *
     * Appelé sur un signalement déjà validé, il met à jour ces précisions.
     *
     *  - zone à risque : niveau, population exposée, dernière inondation, cause principale ;
     *  - ouvrage de drainage : type, état, matériau, dernier entretien.
     */
    public function validateReport(Report $report, Request $request)
    {
        $details = $this->validateTreatment($request, $report);

        DB::transaction(function () use ($report, $details) {
            if ($report->validation_status !== 'validated') {
                $this->recordTransition($report, 'validated');
            }

            $this->distributeReport($report, $details);
        });

        return response()->json(
            $this->withEntity(
                $report->fresh([
                    'commune:id,name',
                    'neighborhood:id,name',
                    'collector:id,name',
                ])
            )
        );
    }

    /**
     * Rejeter un signalement.
     */
    public function reject(
        Report $report,
        Request $request
    ) {
        $validated = $request->validate([
            'comment' => [
                'required',
                'string',
                'max:1000',
            ],
        ]);

        $this->recordTransition(
            $report,
            'rejected',
            $validated['comment']
        );

        return response()->json(
            $report->fresh([
                'commune:id,name',
                'neighborhood:id,name',
                'collector:id,name',
            ])
        );
    }

    /**
     * Distribuer un signalement validé vers la table appropriée
     * (zone à risque ou ouvrage de drainage).
     */
    private function distributeReport(Report $report, array $details = []): void
    {
        app(ReportDistributionService::class)->distribute($report, $details);
    }

    /**
     * Champs de traitement acceptés selon la catégorie du signalement.
     */
    private function validateTreatment(Request $request, Report $report): array
    {
        $rules = match ($report->category) {
            'risk_zone' => [
                'risk_level' => ['sometimes', 'required', Rule::enum(RiskLevel::class)],
                'estimated_exposed_population' => ['sometimes', 'nullable', 'integer', 'min:0'],
                'last_flood_date' => ['sometimes', 'nullable', 'date', 'before_or_equal:today'],
                'main_cause' => ['sometimes', 'nullable', Rule::enum(MainCause::class)],
            ],
            'drainage_structure' => [
                'structure_type' => ['sometimes', 'required', Rule::enum(StructureType::class)],
                'condition' => ['sometimes', 'required', Rule::enum(StructureCondition::class)],
                'material' => ['sometimes', 'nullable', 'string', 'max:100'],
                'last_maintenance_date' => ['sometimes', 'nullable', 'date', 'before_or_equal:today'],
            ],
            default => [],
        };

        return $request->validate($rules);
    }

    /**
     * Joint au signalement la zone ou l'ouvrage créé à sa validation (clé `entity`).
     */
    private function withEntity(Report $report): Report
    {
        $report->setRelation(
            'entity',
            app(ReportDistributionService::class)->entityFor($report)
        );

        return $report;
    }

    /**
     * Enregistrer une transition de statut.
     */
    private function recordTransition(
        Report $report,
        string $newStatus,
        ?string $comment = null
    ): void {
        DB::transaction(function () use (
            $report,
            $newStatus,
            $comment
        ) {
            $oldStatus = $report->validation_status;

            $report->update([
                'validation_status' => $newStatus,
                'comment' => $comment ?? $report->comment,
            ]);

            DB::table('validation_history')->insert([
                'entity_type' => 'report',
                'entity_id' => $report->id,
                'user_id' => request()->user()->id,
                'old_status' => $oldStatus,
                'new_status' => $newStatus,
                'comment' => $comment,
                'created_at' => now(),
            ]);
        });
    }
}