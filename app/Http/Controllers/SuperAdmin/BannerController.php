<?php

namespace App\Http\Controllers\SuperAdmin;

use App\Http\Controllers\Controller;
use App\Models\Banner;
use App\Models\ActionTrail;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Log;
class BannerController extends Controller
{

    private function compressBannerImage($file)
    {
        $sourcePath = $file->getRealPath();

        $originalSize = filesize($sourcePath);

        $info = getimagesize($sourcePath);

        if (!$info) {
            throw new \Exception('Invalid image.');
        }

        $mime = $info['mime'];

        /* GIF Animated GIF ko convert nahi karenge. */
        if ($mime === 'image/gif') {
            return [
                'content'   => file_get_contents($sourcePath),
                'extension' => 'gif',
                'mime'      => 'image/gif',
                'original_size' => $originalSize,
                'compressed_size' => $originalSize,
            ];
        }

        /*Read image  */

        switch ($mime) {

            case 'image/jpeg':
                $image = imagecreatefromjpeg($sourcePath);
                break;

            case 'image/png':
                $image = imagecreatefrompng($sourcePath);

                // PNG transparency preserve
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

        /*WebP compression  */

        /* First try quality 75 */
        $qualities = [75, 65, 55, 45, 35, 25];

        $bestCompressed = null;
        $bestSize = PHP_INT_MAX;

        foreach ($qualities as $quality) {

            ob_start();

            imagewebp(
                $image,
                null,
                $quality
            );

            $compressed = ob_get_clean();

            $compressedSize = strlen($compressed);

            /* Keep smallest result*/
            if ($compressedSize < $bestSize) {

                $bestCompressed = $compressed;
                $bestSize = $compressedSize;
            }

            /* Original se kam mil gaya   */
            if ($compressedSize < $originalSize) {
                break;
            }
        }

        imagedestroy($image);

        /* Safety check Compressed output original se chhota nahi hua to original file hi save karenge. */

        if ($bestSize >= $originalSize) {

            /*  * Original se chhota output nahi mila. * Lekin normal JPG/PNG ke liye WebP ko  * quality 10 tak aggressively compress karenge.*/

            switch ($mime) {

                case 'image/jpeg':
                    $image = imagecreatefromjpeg($sourcePath);
                    break;

                case 'image/png':
                    $image = imagecreatefrompng($sourcePath);

                    imagealphablending($image, false);
                    imagesavealpha($image, true);
                    break;

                case 'image/webp':
                    $image = imagecreatefromwebp($sourcePath);
                    break;
            }

            ob_start();

            imagewebp(
                $image,
                null,
                10
            );

            $aggressiveCompressed = ob_get_clean();

            imagedestroy($image);

            if (strlen($aggressiveCompressed) < $originalSize) {

                $bestCompressed = $aggressiveCompressed;
                $bestSize = strlen($aggressiveCompressed);
            }
        }

        /* Final result */

        return [
            'content'        => $bestCompressed,
            'extension'      => 'webp',
            'mime'           => 'image/webp',
            'original_size'  => $originalSize,
            'compressed_size'=> $bestSize,
        ];
    }
    

        public function index(Request $request)
    {
        $tenantId = getSuperAdminTenantId();
        $perPage = $request->get('per_page', 10);
        // $banners = Banner::where('tenant_id', $tenantId)->latest()->paginate($perPage)
        // ->withQueryString();

         $banners = Banner::with(['tenant','user'])
        ->withCount([
            'viewHistories as total_views',
            'clickHistories as total_clicks',
        ])
        ->where('tenant_id', $tenantId)
         // Search Filter
        ->when($request->filled('search'), function ($query) use ($request) {
            $search = $request->search;

            $query->where(function ($q) use ($search) {
                $q->where('name', 'LIKE', "%{$search}%")
                  ->orWhere('url', 'LIKE', "%{$search}%")
                    ->orWhere('type', 'LIKE', "%{$search}%");
            });
        })

        // Status Filter
        ->when($request->filled('status'), function ($query) use ($request) {
            $query->where('status', $request->status);
        })
        ->latest()
        ->paginate($perPage)
        ->withQueryString();

        return view('superadmin.banner.index', compact('banners'));
    }

   


    public function store(Request $request)
    {
        $data = $this->validateBanner($request);
        $data['type'] = $request->input('type', []);


        $tenantId = getSuperAdminTenantId();

      
        if ($request->hasFile('banner_file')) {

                $file = $request->file('banner_file');

                if ($file && $file->isValid()) {

                    $compressedImage = $this->compressBannerImage($file);

                    $imageName = uniqid(time(), true) . '.' .
                        $compressedImage['extension'];

                    $imagePath = "banners/{$imageName}";

                    Storage::disk('s3')->put(
                        $imagePath,
                        $compressedImage['content'],
                        [
                            'visibility'  => 'public',
                            'ContentType' => $compressedImage['mime'],
                        ]
                    );

                    $data['banner_file'] = $imagePath;

                    /* Log::info('Banner Compression', [
                        'original_size'   => round($compressedImage['original_size'] / 1024, 2) . ' KB',
                        'compressed_size' => round($compressedImage['compressed_size'] / 1024, 2) . ' KB',
                    ]); */
                }
            }

        $data['tenant_id'] = $tenantId;
       
        // $data['user_id'] = null;
        $data['status'] = 'active';
        $banner = Banner::create($data);


        /* Action Trail*/

        ActionTrail::create([
            'tenant_id'          => $tenantId,
            'table_name'         => 'banners',
            'reference_id'       => $banner->id,
            'action'             => 'Created Banner',
            'updated_by'         => auth()->id(),
            'updated_by_role_id' => auth()->user()->role_id ?? null,
            'old_details'        => null,
            'details'            => json_encode([
                'name' => $banner->name,
                'url'  => $banner->url,
                 'type' => $banner->type,
            ]),
            'status'              => $banner->status,
        ]);

        return redirect()->route('superadmin.banners.index')->with('success', 'Banner created successfully.');
    }


    public function update(Request $request, Banner $banner)
    {
        $tenantId = getSuperAdminTenantId();

        /*| Make sure banner belongs to current tenant-------------*/

        if ($banner->tenant_id != $tenantId) {
            abort(404);
        }

        $data = $this->validateBanner($request, $banner->id);
            $data['type'] = $request->input('type', []);
        /*------ Old data for Action Trail */

        $oldDetails = [
            'name'   => $banner->name,
            'url'    => $banner->url,
           'type'   => $banner->type,
            'status' => $banner->status,
       
        ];

        /*|------------------------ New Image */

            if ($request->hasFile('banner_file')) {

                // Delete old image
                if ($banner->banner_file) {
                    try {
                        Storage::disk('s3')->delete($banner->banner_file);
                    } catch (\Exception $e) {
                        // Ignore
                    }
                }

                $file = $request->file('banner_file');

                if ($file && $file->isValid()) {

                    $compressedImage = $this->compressBannerImage($file);

                    $imageName = uniqid(time(), true) . '.' .
                        $compressedImage['extension'];

                    $imagePath = "banners/{$imageName}";

                    Storage::disk('s3')->put(
                        $imagePath,
                        $compressedImage['content'],
                        [
                            'visibility'  => 'public',
                            'ContentType' => $compressedImage['mime'],
                        ]
                    );

                    $data['banner_file'] = $imagePath;

               /* Log::info('Banner Compression Update', [
                        'original_size'   => round($compressedImage['original_size'] / 1024, 2) . ' KB',
                        'compressed_size' => round($compressedImage['compressed_size'] / 1024, 2) . ' KB',
                    ]);*/
                }
            }
       
        $banner->update($data);

        /* Action Trail*/

        ActionTrail::create([
            'tenant_id'          => $tenantId,
            'table_name'         => 'banners',
            'reference_id'       => $banner->id,
            'action'             => 'Updated Banner',
            'updated_by'         => auth()->id(),
            'updated_by_role_id' => auth()->user()->role_id ?? null,
            'old_details'        => json_encode($oldDetails),
            'details'            => json_encode([
                'name'   => $banner->name,
                'url'    => $banner->url,
                 'type'   => $banner->type,
                'status' => $banner->status,
            ]),
            'status'              => $banner->status,
        ]);

        return redirect()->route('superadmin.banners.index')->with('success', 'Banner updated successfully.');
    }


    private function validateBanner(Request $request, $id = null)
    {
        return $request->validate(['name' => 'required|string|max:255',
                 'banner_file' =>'nullable|image|mimes:jpg,jpeg,png,webp,gif',
                'url' => 'nullable|url|max:500',
                'type'   => 'required|array|min:1',
               'type.*' => 'required|in:all,home,join,wallet,redeem_request,yearly_leaderboard,quiz_leaderboard,quiz_answer_sheet,quiz_list,practices_list,practices_start,before_practice_count,practice_answer_sheet,practice_before_answer_sheet,latest_quiz,before_quiz_start_count,before_answer_count,before_leaderboard_count,quiz_history_leaderboard,quiz_history_answer_sheet,quiz_history_list,quiz_start_10s_count,question,fff_before_quiz_start_count,fff_quiz_start_10s_count,fff_before_answer_count,fff_quiz_leaderboard,fff_quiz_answer_sheet,fff_question',
        ],[
        'banner_file.image' => 'Please upload a valid image.',
        'banner_file.mimes' => 'Only JPG, JPEG, PNG, WEBP and GIF images are allowed.',
        'banner_file.max'   => 'Banner image size must be less than 10 MB.',
     
       ]);
    }


    public function destroy(Banner $banner)
    {
        $tenantId = getSuperAdminTenantId();

        if ($banner->tenant_id != $tenantId) {
            abort(404);
        }

        $bannerName = $banner->name;

        if ($banner->banner_file) {
            try {
                Storage::disk('s3')->delete(
                    $banner->banner_file
                );
            } catch (\Exception $e) {
                // Ignore
            }
        }

        $banner->delete();

        /* Action Trail*/

        ActionTrail::create([
            'tenant_id'          => $tenantId,
            'table_name'         => 'banners',
            'reference_id'       => $banner->id,
            'action'             => 'Deleted Banner',
            'updated_by'         => auth()->id(),
            'updated_by_role_id' => auth()->user()->role_id ?? null,
            'old_details'        => json_encode(['name' => $bannerName,]),
            'details'            => null,
            'status'             => 'deleted',
        ]);

        return back()->with('success', 'Banner deleted successfully.');
    }


    public function changeStatus(Request $request)
    {
        $request->validate([
            'id' => 'required|exists:banners,id',
            'status' => 'required|string|in:active,inactive',
        ]);

        $tenantId = getSuperAdminTenantId();

        $banner = Banner::where('tenant_id', $tenantId)->findOrFail($request->id);

        $oldStatus = $banner->status;

        $banner->update([
            'status' => $request->status,
        ]);

        /* Action Trail*/
        ActionTrail::create([
            'tenant_id'          => $tenantId,
            'table_name'         => 'banners',
            'reference_id'       => $banner->id,
            'action'             => 'Updated Banner Status',
            'updated_by'         => auth()->id(),
            'updated_by_role_id' => auth()->user()->role_id ?? null,
            'old_details'        => json_encode(['status' => $oldStatus,]),
            'details'            => json_encode(['status' => $banner->status,'name'   => $banner->name,]),
            'status'              => $banner->status,
        ]);

        return response()->json([
            'success' => true,
            'status'  => $banner->status,
        ]);
    }

  

}