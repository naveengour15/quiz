<?php

namespace App\Http\Controllers\SuperAdmin;

use App\Http\Controllers\Controller;
use App\Models\ProductBrand;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Intervention\Image\Encoders\WebpEncoder;
use Intervention\Image\Laravel\Facades\Image;
use Exception;
class ProductBrandController extends Controller
{
    /**
     * Display Product Brand Listing
     */
    public function index(Request $request)
    {
        $query = ProductBrand::query();

        // Search
        if ($request->filled('search')) {
            $query->where('name', 'like', '%' . $request->search . '%');
        }

        // Status Filter
        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }

        $brands = $query->latest()->paginate(10)->withQueryString();

        $editing = null;

        if ($request->filled('edit')) {
            $editing = ProductBrand::findOrFail($request->edit);
        }

        return view('superadmin.product_brand.index', compact(
            'brands',
            'editing'
        ));
    }


    public function store(Request $request)
    {
        $data = $request->validate([
            'tenant_id' => 'nullable|integer',
            'name'      => 'required|string|max:255',
            'slug'      => 'nullable|string|max:255|unique:product_brands,slug',
            'summary'   => 'nullable|string',
            'photo'     => 'nullable|image|mimes:jpg,jpeg,png,webp',
            'status'    => 'required|in:active,inactive',
        ]);


        if (empty($data['slug'])) {
            $data['slug'] = Str::slug($data['name']) . '-' . rand(100, 999);
        } else {
            $data['slug'] = Str::slug($data['slug']);
        }

        if ($request->hasFile('photo')) {

            $file = $request->file('photo');

            if ($file && $file->isValid()) {

                // Always save as WebP
                $imageName = uniqid(time(), true) . '.webp';

                // Decode uploaded image
                $img = Image::decode($file);

                // Encode as WebP
                $encoded = $img->encode(
                    new WebpEncoder(quality: 90)
                );

                // S3 path
                $imgPath = "product_brands/{$imageName}";

                // Save image to S3
                Storage::disk('s3')->put(
                    $imgPath,
                    (string) $encoded,
                    'public'
                );

                // Save S3 path in database
                $data['photo'] = $imgPath;
            }
        }


        $brand = ProductBrand::create($data);

        return redirect()->route('superadmin.product-brands.index')->with('success', 'Product Brand created successfully.');
    }



     public function update(Request $request, ProductBrand $product_brand)
    {
        $data = $request->validate([
            'tenant_id' => 'nullable|integer',
            'name'      => 'required|string|max:255',
            'slug'      => 'nullable|string|max:255|unique:product_brands,slug,' . $product_brand->id,
            'summary'   => 'nullable|string',
            'photo'     => 'nullable|image|mimes:jpg,jpeg,png,webp',
            'status'    => 'required|in:active,inactive',
        ]);

        if (empty($data['slug'])) {
            $data['slug'] = Str::slug($data['name']) . '-' . rand(100, 999);
        } else {
            $data['slug'] = Str::slug($data['slug']);
        }

        if ($request->hasFile('photo')) {
            if ($product_brand->photo) {

                try {
                    Storage::disk('s3')->delete(
                        $product_brand->photo
                    );
                } catch (\Exception $e) {
                    // Ignore if old image doesn't exist
                }
            }

            $file = $request->file('photo');

            if ($file && $file->isValid()) {

                // Always save as WebP
                $imageName = uniqid(time(), true) . '.webp';

                // Decode uploaded image
                $img = Image::decode($file);

                // Encode as WebP
                $encoded = $img->encode(
                    new WebpEncoder(quality: 90)
                );

                // S3 path
                $imgPath = "product_brands/{$imageName}";

                // Save to S3
                Storage::disk('s3')->put(
                    $imgPath,
                    (string) $encoded,
                    'public'
                );

                // Save S3 path
                $data['photo'] = $imgPath;
            }
        }
        $product_brand->update($data);

        return redirect()->route('superadmin.product-brands.index') ->with('success', 'Product Brand updated successfully.');
    }

  
    public function destroy(ProductBrand $product_brand)
    {
        // Delete Image From S3
         if (!empty($product_brand->photo)) {
        try {
            Storage::disk('s3')->delete($product_brand->photo);
        } catch (Exception $e) {
            // Ignore if file does not exist
        }
    }

        // Soft Delete
        $product_brand->delete();

        return redirect()
            ->route('superadmin.product-brands.index')
            ->with('success', 'Product Brand deleted successfully.');
    }

    public function changeStatus(Request $request)
    {
        $brand = ProductBrand::findOrFail($request->id);

        $brand->status = $brand->status === 'active'
            ? 'inactive'
            : 'active';

        $brand->save();

        return response()->json([
            'success' => true,
            'status' => $brand->status,
            'message' => 'Status updated successfully.'
        ]);
    }
}