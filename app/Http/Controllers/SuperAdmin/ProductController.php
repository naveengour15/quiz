<?php

namespace App\Http\Controllers\SuperAdmin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Product;
use App\Models\ProductCategory;
use App\Models\ProductBrand;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use Intervention\Image\Laravel\Facades\Image;

use Intervention\Image\Encoders\WebpEncoder;

class ProductController extends Controller
{
    public function index(Request $request)
    {
        $query = Product::with([
            'category',
            'subcategory',
            'brand'
        ]);

        // Search
        if ($request->filled('search')) {
            $search = $request->search;

            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                    ->orWhere('sku', 'like', "%{$search}%")
                    ->orWhere('modelno', 'like', "%{$search}%")
                    ->orWhere('slug', 'like', "%{$search}%");
            });
        }

        // Category Filter
        if ($request->filled('cat_id')) {
            $query->where('cat_id', $request->cat_id);
        }

        // Sub Category Filter
        if ($request->filled('parent_id')) {
            $query->where('parent_id', $request->parent_id);
        }

        // Brand Filter
        if ($request->filled('brand_id')) {
            $query->where('brand_id', $request->brand_id);
        }

        // Status Filter
        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }

        // Sorting
        $sortBy = $request->get('sort_by', 'created_at');
        $order = $request->get('order', 'desc');

        $query->orderBy($sortBy, $order);

        // Pagination
        $perPage = $request->get('per_page', 10);

        $products = $query
            ->paginate($perPage)
            ->appends($request->all());

        // Categories
        $categories = ProductCategory::whereNull('parent_id')
            ->orderBy('name')
            ->get();

        // Brands
        $brands = ProductBrand::orderBy('name')
            ->get();

        // Selected category subcategories
        $subcategories = collect();

        if ($request->filled('cat_id')) {
            $subcategories = ProductCategory::where(
                'parent_id',
                $request->cat_id
            )
            ->orderBy('name')
            ->get();
        }

        return view('superadmin.product.index', compact(
            'products',
            'categories',
            'brands',
            'subcategories'
        ));
    }

    public function create()
    {
        // Parent Categories
        $categories = ProductCategory::whereNull('parent_id')
            ->where('status', 'active')
            ->orderBy('name', 'ASC')
            ->get();

        // Brands
        $brands = ProductBrand::where('status', 'active')
            ->orderBy('name', 'ASC')
            ->get();

        return view('superadmin.product.create', compact(
            'categories',
            'brands'
        ));
    }

    public function store(Request $request)
    {
        DB::beginTransaction();

        try {

            $request->validate([
                'name'                 => 'required|string|max:191',
                'summary'              => 'nullable|string',
                'description'          => 'nullable|string',
                'cat_id'               => 'nullable|integer',
                'parent_id'            => 'nullable|integer',
                'brand_id'             => 'nullable|integer',
                'modelno'              => 'nullable|string|max:191',
                'sku'                  => 'nullable|string|max:191',
                'isbn_upc_barcode'     => 'nullable|string|max:191',
                'sale_price'           => 'nullable|numeric|min:0',
                'compare_price'        => 'nullable|numeric|min:0',
                'buy_price'            => 'nullable|numeric|min:0',
                'available_stock'      => 'nullable|integer|min:0',
                'stock_status'         => 'nullable|in:in_stock,out_of_stock,pre_order,2-3days',
                'shipping_charge'      => 'nullable|numeric|min:0',
                'photos.*'             => 'nullable|image|mimes:jpg,jpeg,png,webp',
            ]);

            // JSON Data
            $specifications = $this->setSpecifications($request);
            $attributes     = $this->setAttributes($request);

            // Product Create
            $product = Product::create([
                'tenant_id'            => getSuperAdminTenantId(),
                'name'                 => $request->name,
                'slug'                 => Str::slug($request->name) . '-' . uniqid(),
                'summary'              => $request->summary,
                'description'          => $request->description,
                'attributes'           => $attributes,
                'specifications'       => $specifications,
                'modelno'              => $request->modelno,
                'sku'                  => $request->sku,
                'isbn_upc_barcode'     => $request->isbn_upc_barcode,
                'sale_price'           => $request->sale_price ?? 0,
                'compare_price'        => $request->compare_price ?? 0,
                'buy_price'            => $request->buy_price ?? 0,
                'item_size'            => $request->item_size,
                'available_stock'      => $request->available_stock ?? 1,
                'subtrack_stock'       => $request->subtrack_stock ?? 1,
                'stock_status'         => $request->stock_status ?? 'in_stock',
                'min_item_order'       => $request->min_item_order,
                'maximum_item_order'   => $request->maximum_item_order,
                'stock_avaliable_date' => $request->stock_avaliable_date,
                'is_variant'           => $request->is_variant ?? 0,
                'variant'              => $request->variant,
                'tags'                 => $request->tags,
                'shipping_charge'      => $request->shipping_charge ?? 0,
                'length'               => $request->length,
                'breadth'              => $request->breadth,
                'height'               => $request->height,
                'weight'               => $request->weight,
                'status'               => $request->status ?? 'active',
                'cat_id'               => $request->cat_id,
                'parent_id'            => $request->parent_id,
                'brand_id'             => $request->brand_id,
            ]);


                $photos = [];

                if ($request->hasFile('photos')) {

                    foreach ($request->file('photos') as $file) {

                        if (!$file || !$file->isValid()) {
                            continue;
                        }

                        $imageName = uniqid(time(), true) . '.webp';


                        $img = Image::decode($file);

                        $encoded = $img->encode(
                            new WebpEncoder(quality: 90)
                        );

                        $imagePath = "products/{$imageName}";

                        Storage::disk('s3')->put(
                            $imagePath,
                            (string) $encoded,
                            'public'
                        );
                        $thumbnail = Image::decode($file);

                        $thumbnail->resize(200, 200);

                        $thumbnailEncoded = $thumbnail->encode(
                            new WebpEncoder(quality: 90)
                        );

                        $thumbnailPath = "products/thumbnail/{$imageName}";

                        Storage::disk('s3')->put(
                            $thumbnailPath,
                            (string) $thumbnailEncoded,
                            'public'
                        );
                        $photos[] = [
                            'image' => $imagePath,
                            'thumbnail' => $thumbnailPath,
                        ];
                    }
                }

                $product->update([
                    'photos' => $photos,
                ]);

            DB::commit();

            return redirect()
                ->route('superadmin.product.index')
                ->with('success', 'Product created successfully.');

        } catch (\Exception $e) {

            DB::rollBack();

            Log::error('Product Store Error', [
                'message' => $e->getMessage(),
                'line'    => $e->getLine(),
                'file'    => $e->getFile(),
            ]);

            return back()
                ->withInput()
                ->with('error', $e->getMessage());
        }
    }

    public function edit(Product $product)
    {
        $categories = ProductCategory::whereNull('parent_id')
            ->where('status', 'active')
            ->orderBy('name')
            ->get();

        $subcategories = ProductCategory::where('parent_id', $product->cat_id)
            ->where('status', 'active')
            ->orderBy('name')
            ->get();

        $brands = ProductBrand::where('status', 'active')
            ->orderBy('name')
            ->get();

        return view('superadmin.product.edit', compact(
            'product',
            'categories',
            'subcategories',
            'brands'
        ));
    }

    public function update(Request $request, Product $product)
    {
        DB::beginTransaction();

        try {

            $request->validate([
                'name'             => 'required|string|max:191',
                'summary'          => 'nullable|string',
                'description'      => 'nullable|string',

                'cat_id'           => 'nullable|integer',
                'parent_id'        => 'nullable|integer',
                'brand_id'         => 'nullable|integer',

                'modelno'          => 'nullable|string|max:191',
                'sku'              => 'nullable|string|max:191',
                'isbn_upc_barcode' => 'nullable|string|max:191',
                'tags'             => 'nullable|string|max:191',

                'sale_price'       => 'nullable|numeric|min:0',
                'compare_price'    => 'nullable|numeric|min:0',
                'buy_price'        => 'nullable|numeric|min:0',
                'shipping_charge'  => 'nullable|numeric|min:0',

                'available_stock'  => 'nullable|integer|min:0',

                'stock_status'     => 'nullable|in:in_stock,out_of_stock,pre_order,2-3days',

                'photos.*'         => 'nullable|image|mimes:jpg,jpeg,png,webp',
            ]);

            $photos = $product->photos ?? [];

            if (!is_array($photos)) {
                $photos = [];
            }

            if ($request->hasFile('photos')) {

                foreach ($request->file('photos') as $file) {

                    if (!$file || !$file->isValid()) {
                        continue;
                    }

                    $imageName = uniqid(time(), true) . '.webp';

                    // Main Image
                    $img = Image::decode($file);

                    $encoded = $img->encode(
                        new WebpEncoder(quality: 90)
                    );

                    $imagePath = "products/{$imageName}";

                    Storage::disk('s3')->put(
                        $imagePath,
                        (string) $encoded,
                        'public'
                    );


                    // Thumbnail
                    $thumbnail = Image::decode($file);

                    $thumbnail->resize(200, 200);

                    $thumbnailEncoded = $thumbnail->encode(
                        new WebpEncoder(quality: 90)
                    );

                    $thumbnailPath = "products/thumbnail/{$imageName}";

                    Storage::disk('s3')->put(
                        $thumbnailPath,
                        (string) $thumbnailEncoded,
                        'public'
                    );


                    $photos[] = [
                        'image'     => $imagePath,
                        'thumbnail' => $thumbnailPath,
                    ];
                }
            }

            $product->update([

                // Basic Information
                'name'        => $request->name,
                'summary'     => $request->summary,
                'description' => $request->description,

                // Slug
                 'slug'      => Str::slug($request->name) . '-' . uniqid(),

                // Category / Brand
                'cat_id'      => $request->cat_id,
                'parent_id'   => $request->parent_id,
                'brand_id'    => $request->brand_id,

                // Product Information
                'modelno'          => $request->modelno,
                'sku'              => $request->sku,
                'isbn_upc_barcode' => $request->isbn_upc_barcode,
                'tags'             => $request->tags,

                // Pricing
                'sale_price'      => $request->sale_price ?? 0,
                'compare_price'   => $request->compare_price ?? 0,
                'buy_price'       => $request->buy_price ?? 0,
                'shipping_charge' => $request->shipping_charge ?? 0,

                // Stock
                'available_stock' => $request->available_stock ?? 1,
                'stock_status'    => $request->stock_status ?? 'in_stock',

                // JSON Data
                'attributes'     => $this->setAttributes($request),
                'specifications' => $this->setSpecifications($request),

                // Photos
                'photos' => $photos,

                // Status
                'status' => $request->status ?? 'active',
            ]);


            DB::commit();

            return redirect()
                ->route('superadmin.product.index')
                ->with('success', 'Product updated successfully.');

        } catch (\Exception $e) {

            DB::rollBack();

            Log::error('Product Update Error', [
                'message' => $e->getMessage(),
                'line'    => $e->getLine(),
                'file'    => $e->getFile(),
            ]);

            return back()
                ->withInput()
                ->with('error', $e->getMessage());
        }
    }

    public function destroy(Product $product)
    {
        $product->delete();

        return redirect()->back()
            ->with('success', 'Product deleted successfully.');
    }

   public function changeStatus(Request $request)
    {
        $request->validate([
            'id' => 'required|exists:products,id',
            'status' => 'required|string|in:active,inactive',
        ]);

        $product = Product::findOrFail($request->id);

        $product->update([
            'status' => $request->status
        ]);

        return response()->json([
            'success' => true
        ]);
    }


    public function getSubCategories($categoryId)
    {
        $subcategories = ProductCategory::where('parent_id', $categoryId)
            ->where('status', 'active')
            ->orderBy('name')
            ->get();

        return response()->json($subcategories);
    }

    public function deleteImage($id, $index)
    {
        $product = Product::findOrFail($id);

        $photos = $product->photos ?? [];

        if (!isset($photos[$index])) {
            return back()->with('error', 'Image not found.');
        }

        // Delete selected image from S3
        if (!empty($photos[$index]['image'])) {
            Storage::disk('s3')->delete($photos[$index]['image']);
        }

        if (!empty($photos[$index]['thumbnail'])) {
            Storage::disk('s3')->delete($photos[$index]['thumbnail']);
        }

        // Remove only selected image
        unset($photos[$index]);

        $product->photos = array_values($photos);
        $product->save();

        return redirect()->back()->with('success', 'Image deleted successfully.');
    }

   
    private function setSpecifications(Request $request)
    {
        $specifications = [];

        // Create aur Edit dono form support
        $labels = $request->spec_label ?? $request->label ?? [];
        $properties = $request->spec_property ?? $request->property ?? [];

        foreach ($labels as $key => $label) {

            if (!empty($label)) {
                $specifications[] = [
                    'label' => $label,
                    'property' => $properties[$key] ?? '',
                ];
            }
        }

        return $specifications;
    }

    private function setAttributes(Request $request)
    {
        $attributes = [];

        if ($request->has('attribute_type')) {
            foreach ($request->attribute_type as $key => $type) {

                if (!empty($type)) {
                    $attributes[] = [
                        'attribute_type' => $type,
                        'attribute_value' => $request->attribute_value[$key] ?? '',
                    ];
                }
            }
        }

        return $attributes;
    }
}
