<?php
namespace App\Http\Controllers\SuperAdmin;

use App\Http\Controllers\Controller;
use App\Models\Blog;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use Illuminate\Support\Facades\Log;
use Throwable;

class BlogController extends Controller
{
    
    private function compressBlogImage($file)
    {
        $sourcePath = $file->getRealPath();
        $originalSize = filesize($sourcePath);
        $info = getimagesize($sourcePath);

        if (!$info) {
            throw new \Exception('Invalid image.');
        }

        $mime = $info['mime'];

        // Animated GIF original format mein save hoga.
        if ($mime === 'image/gif') {
            return [
                'content' => file_get_contents($sourcePath),
                'extension' => 'gif',
                'mime' => 'image/gif',
                'original_size' => $originalSize,
                'compressed_size' => $originalSize,
            ];
        }

        // Read image
        switch ($mime) {
            case 'image/jpeg':
                $image = imagecreatefromjpeg($sourcePath);
                break;

            case 'image/png':
                $image = imagecreatefrompng($sourcePath);

                // PNG transparency preserve
                // Palette PNG ko truecolor mein convert karenge.
                if (!imageistruecolor($image)) {
                    $trueColorImage = imagecreatetruecolor(
                        imagesx($image),
                        imagesy($image)
                    );

                    imagealphablending($trueColorImage, false);
                    imagesavealpha($trueColorImage, true);

                    imagecopy(
                        $trueColorImage,
                        $image,
                        0,
                        0,
                        0,
                        0,
                        imagesx($image),
                        imagesy($image)
                    );

                    imagedestroy($image);
                    $image = $trueColorImage;
                }

                imagealphablending($image, false);
                imagesavealpha($image, true);
                break;

            case 'image/webp':
                $image = imagecreatefromwebp($sourcePath);
                break;

            default:
                throw new \Exception('Unsupported image format.');
        }

        if (!$image) {
            throw new \Exception('Unable to read image.');
        }

        // WebP compression
        $qualities = [75, 65, 55, 45, 35, 25];

        $bestCompressed = null;
        $bestSize = PHP_INT_MAX;

        foreach ($qualities as $quality) {

            ob_start();

            $encoded = imagewebp(
                $image,
                null,
                $quality
            );

            $compressed = ob_get_clean();

            if ($encoded && $compressed !== false && $compressed !== '') {

                $compressedSize = strlen($compressed);

                // Keep smallest result
                if ($compressedSize < $bestSize) {
                    $bestCompressed = $compressed;
                    $bestSize = $compressedSize;
                }

                // Original se kam mil gaya
                if ($compressedSize < $originalSize) {
                    break;
                }
            }
        }

        imagedestroy($image);

        if ($bestCompressed === null) {
            throw new \Exception('Image compression failed.');
        }

        // Original se chhota output nahi mila to quality 10 try karenge.
        if ($bestSize >= $originalSize) {

            switch ($mime) {
                case 'image/jpeg':
                    $image = imagecreatefromjpeg($sourcePath);
                    break;

                case 'image/png':
                    $image = imagecreatefrompng($sourcePath);
                    break;

                case 'image/webp':
                    $image = imagecreatefromwebp($sourcePath);
                    break;
            }

            if ($mime === 'image/png' && !imageistruecolor($image)) {
                $trueColorImage = imagecreatetruecolor(
                    imagesx($image),
                    imagesy($image)
                );

                imagealphablending($trueColorImage, false);
                imagesavealpha($trueColorImage, true);

                imagecopy(
                    $trueColorImage,
                    $image,
                    0,
                    0,
                    0,
                    0,
                    imagesx($image),
                    imagesy($image)
                );

                imagedestroy($image);
                $image = $trueColorImage;
            }

            if ($mime === 'image/png') {
                imagealphablending($image, false);
                imagesavealpha($image, true);
            }

            ob_start();

            imagewebp($image, null, 10);

            $aggressiveCompressed = ob_get_clean();

            imagedestroy($image);

            if (
                $aggressiveCompressed !== false &&
                strlen($aggressiveCompressed) < $originalSize
            ) {
                $bestCompressed = $aggressiveCompressed;
                $bestSize = strlen($aggressiveCompressed);
            }
        }

        return [
            'content' => $bestCompressed,
            'extension' => 'webp',
            'mime' => 'image/webp',
            'original_size' => $originalSize,
            'compressed_size' => $bestSize,
        ];
    }

    /*| Blog Listing  */

    public function index(Request $request)
    {
        $perPage = $request->get('per_page', 10);

        $blogs = Blog::query()
            ->when($request->filled('search'), function ($query) use ($request) {
                $search = $request->search;

                $query->where(function ($q) use ($search) {
                    $q->where('title', 'LIKE', "%{$search}%")
                        ->orWhere('slug', 'LIKE', "%{$search}%");
                });
            })
            ->latest()
            ->paginate($perPage)
            ->withQueryString();

        return view('superadmin.blog.index', compact('blogs'));
    }

    /*Store Blog */

    public function store(Request $request)
    {
        $data = $request->validate([
            'title' => 'required|string|max:255',
            'description' => 'nullable|string',
            'image' => 'nullable|image|mimes:jpg,jpeg,png,webp,gif|max:10240',
        ]);

        // Generate unique slug
        $baseSlug = Str::slug($data['title']);
        $baseSlug = $baseSlug ?: 'blog';

        $slug = $baseSlug;
        $counter = 2;

        while (Blog::where('slug', $slug)->exists()) {
            $slug = $baseSlug . '-' . $counter++;
        }

        $data['slug'] = $slug;

        // Image upload to S3
        if ($request->hasFile('image')) {

            $file = $request->file('image');

            if ($file && $file->isValid()) {

                $compressedImage = $this->compressBlogImage($file);

                $imageName = uniqid(time(), true) . '.' .
                    $compressedImage['extension'];

                $imagePath = "blog/{$imageName}";

                Storage::disk('s3')->put(
                    $imagePath,
                    $compressedImage['content'],
                    [
                        'visibility' => 'public',
                        'ContentType' => $compressedImage['mime'],
                    ]
                );

                $data['image'] = $imagePath;
            }
        }

        Blog::create($data);

        return redirect()
            ->route('superadmin.blogs.index')
            ->with('success', 'Blog created successfully.');
    }

    /*Update Blog  */

    public function update(Request $request, Blog $blog)
    {
        $data = $request->validate([
            'title' => 'required|string|max:255',
            'description' => 'nullable|string',
            'image' => 'nullable|image|mimes:jpg,jpeg,png,webp,gif|max:10240',
        ]);

        // Generate unique slug excluding current blog
        $baseSlug = Str::slug($data['title']);
        $baseSlug = $baseSlug ?: 'blog';

        $slug = $baseSlug;
        $counter = 2;

        while (
            Blog::where('slug', $slug)
                ->where('id', '!=', $blog->id)
                ->exists()
        ) {
            $slug = $baseSlug . '-' . $counter++;
        }

        $data['slug'] = $slug;

        $oldImage = $blog->image;
        $newImagePath = null;

        // Upload new image first
        if ($request->hasFile('image')) {

            $file = $request->file('image');

            if ($file && $file->isValid()) {

                $compressedImage = $this->compressBlogImage($file);

                $imageName = uniqid(time(), true) . '.' .
                    $compressedImage['extension'];

                $newImagePath = "blog/{$imageName}";

                Storage::disk('s3')->put(
                    $newImagePath,
                    $compressedImage['content'],
                    [
                        'visibility' => 'public',
                        'ContentType' => $compressedImage['mime'],
                    ]
                );

                $data['image'] = $newImagePath;
            }
        }

        try {
            $blog->update($data);
        } catch (Throwable $e) {

            // Remove newly uploaded image if update fails
            if ($newImagePath) {
                try {
                    Storage::disk('s3')->delete($newImagePath);
                } catch (Throwable $deleteError) {
                    Log::warning('Blog new image cleanup failed.', [
                        'path' => $newImagePath,
                        'error' => $deleteError->getMessage(),
                    ]);
                }
            }

            throw $e;
        }

        // Delete old image after successful update
        if ($newImagePath && $oldImage) {
            try {
                Storage::disk('s3')->delete($oldImage);
            } catch (Throwable $e) {
                Log::warning('Old blog image deletion failed.', [
                    'path' => $oldImage,
                    'error' => $e->getMessage(),
                ]);
            }
        }

        return redirect()->route('superadmin.blogs.index')->with('success', 'Blog updated successfully.');
    }

    /* Delete Blog*/

    public function destroy(Blog $blog)
    {
        if ($blog->image) {
            try {
                Storage::disk('s3')->delete($blog->image);
            } catch (Throwable $e) {
                Log::warning('Blog image deletion failed.', [
                    'path' => $blog->image,
                    'error' => $e->getMessage(),
                ]);
            }
        }

        $blog->delete();

        return redirect()->route('superadmin.blogs.index')->with('success', 'Blog deleted successfully.');
    }
}
