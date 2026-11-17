<?php

namespace App\Http\Controllers\SuperAdmin;

use App\Http\Controllers\Controller;
use App\Models\News;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Log;

class NewsController extends Controller
{
    public function index(Request $request)
    {
        $perPage = $request->get('per_page', 10);
        $query = News::where('tenant_id', getSuperAdminTenantId());

        // 1. Search Filter (Title & Category)
        if ($request->has('search') && $request->search != '') {
            $query->where(function ($q) use ($request) {
                $q->where('title', 'like', "%{$request->search}%")
                ->orWhere('category', 'like', "%{$request->search}%");
            });
        }

        // ✅ 2. Date Filter (From & To Date on created_at)
        $fromDate = $request->input('from_date');
        $toDate = $request->input('to_date');

        if (!empty($fromDate)) {
            $query->whereDate('created_at', '>=', $fromDate);
        }
        if (!empty($toDate)) {
            $query->whereDate('created_at', '<=', $toDate);
        }

        $news = $query->orderBy('id', 'desc')->paginate($perPage);
        $news->appends($request->except('page'));

        return view('superadmin.news.index', compact('news'));
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'title'       => 'required|string|max:255',
            'description' => 'nullable|string|max:500',
            'image'       => 'nullable|image|mimes:jpeg,png,jpg,webp|max:5120',
        ]);

        $validated['category'] = 'General'; // ✅ Default Category

        if ($request->hasFile('image')) {
            try {
                $validated['image'] = $request->file('image')->store('news', 's3'); 
            } catch (\Exception $e) {
                Log::error('News Image Upload Failed: ' . $e->getMessage());
                return redirect()->back()->with('error', 'Failed to upload image.')->withInput();
            }
        }

        $validated['tenant_id'] = getSuperAdminTenantId();
        $validated['status'] = 'active';

        News::create($validated);

        return redirect()->route('superadmin.news.index')->with('success', 'Bullet news added successfully!');
    }

    public function update(Request $request, $id)
    {
        $news = News::where('tenant_id', getSuperAdminTenantId())->findOrFail($id);

        $validated = $request->validate([
            'title'       => 'required|string|max:255',
            'description' => 'nullable|string|max:500',
            'image'       => 'nullable|image|mimes:jpeg,png,jpg,webp|max:5120',
        ]);

        $validated['category'] = 'General';

        if ($request->hasFile('image')) {
            try {
                if (!empty($news->image)) {
                    $oldImagePath = str_starts_with($news->image, 'news/') ? $news->image : 'news/' . $news->image;
                    if (Storage::disk('s3')->exists($oldImagePath)) {
                        Storage::disk('s3')->delete($oldImagePath);
                    }
                }
                $validated['image'] = $request->file('image')->store('news', 's3');
            } catch (\Exception $e) {
                Log::error('News Image Update Failed: ' . $e->getMessage());
                return redirect()->back()->with('error', 'Failed to update image.')->withInput();
            }
        }

        $news->update($validated);

        return redirect()->route('superadmin.news.index')->with('success', 'Bullet news updated successfully!');
    }

    public function destroy($id)
    {
        try {
            $news = News::where('tenant_id', getSuperAdminTenantId())->findOrFail($id);
            $news->delete();
            return redirect()->route('superadmin.news.index')->with('success', 'News deleted successfully!');
        } catch (\Exception $e) {
            Log::error('News Delete Failed: ' . $e->getMessage());
            return redirect()->back()->with('error', 'Failed to delete news.');
        }
    }

    public function toggleStatus($id)
    {
        try {
            $news = News::where('tenant_id', getSuperAdminTenantId())->findOrFail($id);
            $news->status = $news->status === 'active' ? 'inactive' : 'active';
            $news->save();

            return response()->json(['success' => true, 'status' => $news->status]);
        } catch (\Exception $e) {
            Log::error('News Toggle Status Failed: ' . $e->getMessage());
            return response()->json(['success' => false, 'message' => 'Failed to update status.'], 500);
        }
    }
}