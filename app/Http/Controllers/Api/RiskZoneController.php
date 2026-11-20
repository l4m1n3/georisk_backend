<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\RiskZone;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class RiskZoneController extends Controller
{
    public function index(Request $request)
    {
        $query = RiskZone::query()->with(['commune:id,name', 'neighborhood:id,name']);

        $query->when($request->filled('commune_id'), fn ($q) => $q->where('commune_id', $request->commune_id));
        $query->when($request->filled('neighborhood_id'), fn ($q) => $q->where('neighborhood_id', $request->neighborhood_id));
        $query->when($request->filled('risk_level'), fn ($q) => $q->where('risk_level', $request->risk_level));
        $query->when($request->filled('validation_status'), fn ($q) => $q->where('validation_status', $request->validation_status));

        $perPage = min(500, max(1, (int) $request->input('per_page', 20)));

        return response()->json($query->latest()->paginate($perPage));
    }

    public function show(RiskZone $riskZone)
    {
        return response()->json($riskZone->load(['commune:id,name', 'neighborhood:id,name']));
    }

    public function validate(RiskZone $riskZone)
    {
        $this->recordTransition($riskZone, 'validated');

        return response()->json($riskZone->fresh(['commune:id,name', 'neighborhood:id,name']));
    }

    public function reject(RiskZone $riskZone, Request $request)
    {
        $validated = $request->validate(['comment' => 'required|string|max:1000']);
        $this->recordTransition($riskZone, 'rejected', $validated['comment']);

        return response()->json($riskZone->fresh(['commune:id,name', 'neighborhood:id,name']));
    }

    private function recordTransition(RiskZone $zone, string $newStatus, ?string $comment = null): void
    {
        $oldStatus = $zone->validation_status;

        $zone->update([
            'validation_status' => $newStatus,
            'comment' => $comment ?? $zone->comment,
        ]);

        DB::table('validation_history')->insert([
            'entity_type' => 'risk_zone',
            'entity_id' => $zone->id,
            'user_id' => request()->user()->id,
            'old_status' => $oldStatus,
            'new_status' => $newStatus,
            'comment' => $comment,
            'created_at' => now(),
        ]);
    }
}
