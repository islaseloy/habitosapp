<?php

namespace App\Http\Controllers;

use App\Models\Category;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class CategoryController extends Controller
{
    public function index(): JsonResponse
    {
        return response()->json(Category::query()->orderBy('nombre')->get());
    }

    public function store(Request $request): JsonResponse
    {
        $data = $request->validate([
            'nombre' => ['required', 'string', 'max:60'],
            'color_icono' => ['required', 'string', 'max:255'],
        ]);

        return response()->json(Category::create($data), 201);
    }
}
