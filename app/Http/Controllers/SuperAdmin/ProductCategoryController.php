<?php

namespace App\Http\Controllers\SuperAdmin;

use App\Http\Controllers\Controller;
use App\Models\ProductCategory;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Illuminate\View\View;

class ProductCategoryController extends Controller
{
    /**
     * Display product categories.
     */
    public function index(Request $request): View
    {
        $query = ProductCategory::query()
            ->with('parent');

        /*
        |--------------------------------------------------------------------------
        | Search
        |--------------------------------------------------------------------------
        */

        if ($request->filled('search')) {
            $search = trim($request->input('search'));

            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                    ->orWhere('slug', 'like', "%{$search}%");
            });
        }

        /*
        |--------------------------------------------------------------------------
        | Status Filter
        |--------------------------------------------------------------------------
        */

        if ($request->filled('status')) {
            $query->where(
                'status',
                $request->input('status')
            );
        }

        /*
        |--------------------------------------------------------------------------
        | Parent Category Filter
        |--------------------------------------------------------------------------
        */

        if ($request->filled('parent_id')) {
            $query->where(
                'parent_id',
                $request->input('parent_id')
            );
        }

        /*
        |--------------------------------------------------------------------------
        | Categories
        |--------------------------------------------------------------------------
        */

        $categories = $query
            ->orderByDesc('id')
            ->paginate(20)
            ->withQueryString();

        /*
        |--------------------------------------------------------------------------
        | Parent Categories
        |--------------------------------------------------------------------------
        */

        $parentCategories = ProductCategory::query()
            ->whereNull('parent_id')
            ->where('status', 'active')
            ->orderBy('name')
            ->get([
                'id',
                'name',
            ]);

        /*
        |--------------------------------------------------------------------------
        | Edit Category
        |--------------------------------------------------------------------------
        */

        $editing = null;

        if ($request->filled('edit')) {
            $editing = ProductCategory::find(
                $request->integer('edit')
            );
        }

        return view(
            'superadmin.product_categories.index',
            compact(
                'categories',
                'parentCategories',
                'editing'
            )
        );
    }

    /**
     * Store a new product category.
     */
    public function store(Request $request): RedirectResponse
    {
        /*
        |--------------------------------------------------------------------------
        | Validation
        |--------------------------------------------------------------------------
        */

        $validated = $request->validate([
            'name' => [
                'required',
                'string',
                'max:255',
            ],

            'slug' => [
                'nullable',
                'string',
                'max:255',
                'unique:product_categories,slug',
            ],

            'parent_id' => [
                'nullable',
                'integer',
                'exists:product_categories,id',
            ],

            'summary' => [
                'nullable',
                'string',
            ],

            'photo' => [
                'nullable',
                'image',
                'mimes:jpg,jpeg,png,webp',
                'max:2048',
            ],

            'status' => [
                'required',
                'in:active,inactive',
            ],
        ]);

        /*
        |--------------------------------------------------------------------------
        | Slug
        |--------------------------------------------------------------------------
        */

        $validated['slug'] = $this->generateUniqueSlug(
            $validated['slug'] ?? $validated['name']
        );

        /*
        |--------------------------------------------------------------------------
        | Photo Upload
        |--------------------------------------------------------------------------
        */

        if ($request->hasFile('photo')) {
            $validated['photo'] = $request
                ->file('photo')
                ->store(
                    'product-categories',
                    'public'
                );
        }

        /*
        |--------------------------------------------------------------------------
        | Create
        |--------------------------------------------------------------------------
        */

        ProductCategory::create($validated);

        return redirect()
            ->route('superadmin.product-categories.index')
            ->with(
                'success',
                'Product category created successfully.'
            );
    }

    /**
     * Update product category.
     */
    public function update(
        Request $request,
        ProductCategory $productCategory
    ): RedirectResponse {
        /*
        |--------------------------------------------------------------------------
        | Validation
        |--------------------------------------------------------------------------
        */

        $validated = $request->validate([
            'name' => [
                'required',
                'string',
                'max:255',
            ],

            'slug' => [
                'nullable',
                'string',
                'max:255',
                'unique:product_categories,slug,'
                    .$productCategory->id,
            ],

            'parent_id' => [
                'nullable',
                'integer',
                'exists:product_categories,id',
            ],

            'summary' => [
                'nullable',
                'string',
            ],

            'photo' => [
                'nullable',
                'image',
                'mimes:jpg,jpeg,png,webp',
                'max:2048',
            ],

            'status' => [
                'required',
                'in:active,inactive',
            ],
        ]);

        /*
        |--------------------------------------------------------------------------
        | Prevent Self Parent
        |--------------------------------------------------------------------------
        */

        if (
            ! empty($validated['parent_id']) &&
            (int) $validated['parent_id']
                === (int) $productCategory->id
        ) {
            return back()
                ->withErrors([
                    'parent_id' => 'A category cannot be its own parent.',
                ])
                ->withInput();
        }

        /*
        |--------------------------------------------------------------------------
        | Slug
        |--------------------------------------------------------------------------
        */

        $validated['slug'] = $this->generateUniqueSlug(
            $validated['slug'] ?? $validated['name'],
            $productCategory->id
        );

        /*
        |--------------------------------------------------------------------------
        | Photo Upload
        |--------------------------------------------------------------------------
        */

        if ($request->hasFile('photo')) {

            if (
                $productCategory->photo &&
                Storage::disk('public')->exists(
                    $productCategory->photo
                )
            ) {
                Storage::disk('public')->delete(
                    $productCategory->photo
                );
            }

            $validated['photo'] = $request
                ->file('photo')
                ->store(
                    'product-categories',
                    'public'
                );
        }

        /*
        |--------------------------------------------------------------------------
        | Update
        |--------------------------------------------------------------------------
        */

        $productCategory->update($validated);

        return redirect()
            ->route('superadmin.product-categories.index')
            ->with(
                'success',
                'Product category updated successfully.'
            );
    }

    /**
     * Delete product category.
     */
    public function destroy(
        ProductCategory $productCategory
    ): RedirectResponse {
        /*
        |--------------------------------------------------------------------------
        | Check Child Categories
        |--------------------------------------------------------------------------
        */

        if ($productCategory->children()->exists()) {
            return redirect()
                ->route('superadmin.product-categories.index')
                ->with(
                    'error',
                    'This category has sub-categories. '
                    .'Please remove them first.'
                );
        }

        /*
        |--------------------------------------------------------------------------
        | Delete Photo
        |--------------------------------------------------------------------------
        */

        if (
            $productCategory->photo &&
            Storage::disk('public')->exists(
                $productCategory->photo
            )
        ) {
            Storage::disk('public')->delete(
                $productCategory->photo
            );
        }

        /*
        |--------------------------------------------------------------------------
        | Soft Delete
        |--------------------------------------------------------------------------
        */

        $productCategory->delete();

        return redirect()
            ->route('superadmin.product-categories.index')
            ->with(
                'success',
                'Product category deleted successfully.'
            );
    }

    /**
     * Generate unique slug.
     */
    private function generateUniqueSlug(
        string $value,
        ?int $ignoreId = null
    ): string {
        $slug = Str::slug($value);

        if ($slug === '') {
            $slug = 'category';
        }

        $originalSlug = $slug;
        $counter = 1;

        while (
            ProductCategory::query()
                ->where('slug', $slug)
                ->when(
                    $ignoreId,
                    fn ($query) => $query->where(
                        'id',
                        '!=',
                        $ignoreId
                    )
                )
                ->exists()
        ) {
            $slug = $originalSlug.'-'.$counter;

            $counter++;
        }

        return $slug;
    }
}
