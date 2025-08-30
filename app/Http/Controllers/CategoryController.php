<?php

namespace App\Http\Controllers;

use App\Models\Category;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Database\Eloquent\ModelNotFoundException;

class CategoryController extends Controller
{
    /**
     * Display a listing of all categories with ticket counts.
     *
     * Retrieves all categories from the database including the count of tickets
     * associated with each category. This endpoint is primarily used for
     * populating dropdown lists and dashboard statistics in the frontend.
     *
     * @return JsonResponse JSON response containing all categories with ticket counts
     */
    public function index(): JsonResponse
    {
        $categories = Category::withCount('tickets')->get();

        return response()->json([
            'success' => true,
            'data' => $categories
        ]);
    }


}
