<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Resources\NeighborhoodResource;
use App\Models\Neighborhood;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

class NeighborhoodController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index(Request $request): AnonymousResourceCollection
    {
        $query = Neighborhood::query();

        if ($request->has('commune_id')) {
            $query->where('commune_id', $request->input('commune_id'));
        }

        return NeighborhoodResource::collection($query->get());
    }

    /**
     * Display the specified resource.
     */
    public function show(Neighborhood $neighborhood): NeighborhoodResource
    {
        return new NeighborhoodResource($neighborhood->load('commune'));
    }
}
