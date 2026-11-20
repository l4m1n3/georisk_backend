<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Commune;

class CommuneController extends Controller
{
    public function index()
    {
        return response()->json(Commune::orderBy('name')->get());
    }

    public function neighborhoods(Commune $commune)
    {
        return response()->json($commune->neighborhoods()->orderBy('name')->get());
    }
}
