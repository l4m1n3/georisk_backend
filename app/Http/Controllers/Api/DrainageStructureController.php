<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\DrainageStructure;
use Illuminate\Http\Request;

class DrainageStructureController extends Controller
{
    public function index(Request $request)
    {
        $query = DrainageStructure::query()->with(['commune:id,name', 'neighborhood:id,name']);

        $query->when($request->filled('commune_id'), fn ($q) => $q->where('commune_id', $request->commune_id));
        $query->when($request->filled('neighborhood_id'), fn ($q) => $q->where('neighborhood_id', $request->neighborhood_id));
        $query->when($request->filled('structure_type'), fn ($q) => $q->where('structure_type', $request->structure_type));
        $query->when($request->filled('condition'), fn ($q) => $q->where('condition', $request->condition));
        $query->when($request->filled('validation_status'), fn ($q) => $q->where('validation_status', $request->validation_status));

        $perPage = min(500, max(1, (int) $request->input('per_page', 20)));

        return response()->json($query->latest()->paginate($perPage));
    }

    public function show(DrainageStructure $drainageStructure)
    {
        return response()->json($drainageStructure->load(['commune:id,name', 'neighborhood:id,name']));
    }

    public function validate(DrainageStructure $drainageStructure)
    {
        $this->recordTransition($drainageStructure, 'validated');

        return response()->json($drainageStructure->fresh(['commune:id,name', 'neighborhood:id,name']));
    }

    public function reject(DrainageStructure $drainageStructure, Request $request)
    {
        $validated = $request->validate(['comment' => 'required|string|max:1000']);
        $this->recordTransition($drainageStructure, 'rejected', $validated['comment']);

        return response()->json($drainageStructure->fresh(['commune:id,name', 'neighborhood:id,name']));
    }

    private function recordTransition(DrainageStructure $structure, string $newStatus, ?string $comment = null): void
    {
        $oldStatus = $structure->validation_status;

        $structure->update([
            'validation_status' => $newStatus,
            'comment' => $comment ?? $structure->comment,
        ]);

        \DB::table('validation_history')->insert([
            'entity_type' => 'drainage_structure',
            'entity_id' => $structure->id,
            'user_id' => request()->user()->id,
            'old_status' => $oldStatus,
            'new_status' => $newStatus,
            'comment' => $comment,
            'created_at' => now(),
        ]);
    }
}
