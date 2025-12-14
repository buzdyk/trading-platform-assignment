<?php

namespace App\Http\Controllers;

use App\Http\Resources\SymbolResource;
use App\Models\Symbol;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

class SymbolsController extends Controller
{
    public function index(): AnonymousResourceCollection
    {
        return SymbolResource::collection(Symbol::all());
    }
}
