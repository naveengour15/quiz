<?php

namespace App\Repositories\api;

use App\Interfaces\api\LookupRepositoryInterface;
use App\Models\Category;
use App\Models\City;
use App\Models\Country;
use App\Models\Level;
use App\Models\QuizType;
use App\Models\State;
use Illuminate\Support\Facades\Log;

class LookupRepository implements LookupRepositoryInterface
{
    
    public function countries(): array
    {
        try {
            return [
                'success' => true,
                'data' => Country::where('status', 'active')->get(),
            ];
        } catch (\Exception $e) {
            Log::error('Failed to fetch countries', [
                'message' => $e->getMessage(),
                'file'    => $e->getFile(),
                'line'    => $e->getLine(),
            ]);

            return [
                'success' => false,
                'message' => 'Unable to fetch countries.',
                'data'    => [],
            ];
        }
    }
    public function states(?int $countryId): array
    {
        try {
            $query = State::where('status', 'active');

            if ($countryId) {
                $query->where('country_id', $countryId);
            }

            return [
                'success' => true,
                'data'    => $query->get(),
            ];

        } catch (\Exception $e) {

            Log::error('Failed to fetch states', [
                'country_id' => $countryId,
                'message'    => $e->getMessage(),
                'file'       => $e->getFile(),
                'line'       => $e->getLine(),
            ]);

            return [
                'success' => false,
                'message' => 'Unable to fetch states.',
                'data'    => [],
            ];
        }
    }

    public function cities(?int $stateId): array
    {
        try {
            $query = City::where('status', 'active');

            if ($stateId) {
                $query->where('state_id', $stateId);
            }

            return [
                'success' => true,
                'data'    => $query->get(),
            ];

        } catch (\Exception $e) {

            Log::error('Failed to fetch cities', [
                'state_id' => $stateId,
                'message'  => $e->getMessage(),
                'file'     => $e->getFile(),
                'line'     => $e->getLine(),
            ]);

            return [
                'success' => false,
                'message' => 'Unable to fetch cities.',
                'data'    => [],
            ];
        }
    }

    public function categories(?int $parentId): array
    {
        
        try {

            $query = Category::where('status', 'active')
                ->orderBy('display_order');

            if ($parentId) {
                $query->where('parent_id', $parentId);
            } else {
                $query->whereNull('parent_id');
            }

            return [
                'success' => true,
                'data'    => $query->get(),
            ];

        } catch (\Exception $e) {

            Log::error('Category fetch failed', [
                'parent_id' => $parentId,
                'message'   => $e->getMessage(),
                'file'      => $e->getFile(),
                'line'      => $e->getLine(),
                'trace'     => $e->getTraceAsString(),
            ]);

            return [
                'success' => false,
                'message' => 'Unable to fetch categories.',
                'data'    => [],
            ];
        }
    } 

    public function subcategories(?int $categoryId = null): array
    {
      
        try {
            $data = Category::where('status', 'active')
            ->where('parent_id', $categoryId)
            ->get();
           
            return [
                'success' => true,
                'data'    => $data,
            ];
        } catch (\Exception $e) {
            Log::error('Failed to fetch subcategories', [
                'category_id' => $categoryId,
                'message'     => $e->getMessage(),
                'file'        => $e->getFile(),
                'line'        => $e->getLine(),
            ]);

            return [
                'success' => false,
                'message' => 'Failed to fetch subcategories.',
                'data'    => [],
            ];
        }
    }


   
    public function levels(): array
    {
        try {

            $levels = Level::where('status', 'active')->get();

            return [
                'success' => true,
                'data'    => $levels,
            ];

        } catch (\Exception $e) {

            Log::error('Failed to fetch levels', [
                'message' => $e->getMessage(),
                'file'    => $e->getFile(),
                'line'    => $e->getLine(),
            ]);

            return [
                'success' => false,
                'message' => 'Unable to fetch levels.',
                'data'    => [],
            ];
        }
    }

    public function quizTypes(): array
    {
        try {

            $quizTypes = QuizType::where('status', 'active')->get();

            return [
                'success' => true,
                'data'    => $quizTypes,
            ];

        } catch (\Exception $e) {

            Log::error('Quiz Types Fetch Failed', [
                'message' => $e->getMessage(),
                'file'    => $e->getFile(),
                'line'    => $e->getLine(),
            ]);

            return [
                'success' => false,
                'message' => 'Unable to fetch quiz types.',
                'data'    => [],
            ];
        }
    }

    /************************************* END *************************************/ 
}
