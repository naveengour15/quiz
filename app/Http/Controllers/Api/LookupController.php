<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Interfaces\api\LookupRepositoryInterface;
use Illuminate\Http\Request;

class LookupController extends Controller
{
    protected LookupRepositoryInterface $lookupRepository;

    public function __construct(LookupRepositoryInterface $lookupRepository)
    {
        $this->lookupRepository = $lookupRepository;
    }

    /** GET /api/countries */
    public function countries()
    {
        return response()->json($this->lookupRepository->countries());
    }

    /** GET /api/states?country_id= */
    public function states(Request $request)
    {
        return response()->json(
            $this->lookupRepository->states($request->integer('country_id') ?: null)
        );
    }

    /** GET /api/cities?state_id= */
    public function cities(Request $request)
    {
        return response()->json(
            $this->lookupRepository->cities($request->integer('state_id') ?: null)
        );
    }

    /** GET /api/categories?parent_id= */
    public function categories(Request $request)
    {
       
        return response()->json(
            $this->lookupRepository->categories($request->integer('parent_id') ?: null)
        );
    }

    /** GET /api/categories/{category_id}/subcategories */
    public function subcategories(Request $request, $category_id = null)
    {
        $categoryId = $category_id ?? $request->input('category_id');
       
        return response()->json(
            $this->lookupRepository->subcategories($categoryId)
        );
    }


    /** GET /api/levels */
    public function levels()
    {
        return response()->json($this->lookupRepository->levels());
    }

    /** GET /api/quiz-types */
    public function quizTypes()
    {
        return response()->json($this->lookupRepository->quizTypes());
    }
}
