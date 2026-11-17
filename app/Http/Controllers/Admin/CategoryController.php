<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\AuditLog;
use App\Models\Category;
use Illuminate\Http\Request;

class CategoryController extends Controller
{
    public function index(Request $request)
    {
        $perPage = $request->get('per_page', 10);
        $tab = $request->get('tab', 'category');

        $query = Category::with('parent')->withCount('questions');

        if ($tab == 'subcategory') {
            $query->whereNotNull('parent_id');
        } else {
            $query->whereNull('parent_id');
        }

        $categories = $query->orderBy('display_order')->paginate($perPage)
            ->withQueryString();  

        $parents = Category::whereNull('parent_id')->orderBy('name')->get();

        $editing = $request->edit
            ? Category::findOrFail($request->edit)
            : null;

        return view('admin.categories.index', compact(
            'categories',
            'parents',
            'editing',
            'tab'
        ));
    }

    public function store(Request $request)
    {
        $data = $this->validated($request);
        $data['tenant_id'] = getTenantId();
        $data['display_order'] = $data['display_order'] ?? (Category::max('display_order') + 1 ?: 1);
        $data['status'] = $data['status'] ?? 'active';

        Category::create($data);
        AuditLog::log('Created category', 'Content', $data['name']);

        return redirect()->route('admin.categories.index', [
            'tab' => $request->tab
        ])->with('success', 'Category created.');
    }

    public function update(Request $request, Category $category)
    {
        $data = $this->validated($request);
        $data['tenant_id'] = getTenantId();
        $data['display_order'] = $data['display_order'] ?? $category->display_order ?? 1;
        $data['status'] = $data['status'] ?? $category->status ?? 'active';

        $category->update($data);
        AuditLog::log('Updated category', 'Content', $category->name);

        return redirect()->route('admin.categories.index', [
            'tab' => $request->tab
        ])->with('success', 'Category updated.');
    }

    public function destroy(Request $request, Category $category)
    {
        $category->delete();
        AuditLog::log('Deleted category', 'Content', $category->name);

        return redirect()->route('admin.categories.index', [
            'tab' => $request->tab
        ])->with('success', 'Category deleted.');
    }

    private function validated(Request $request): array
    {
        return $request->validate([
            'name' => 'required|string|max:191',
            'parent_id' => 'nullable|exists:categories,id',
            'icon' => 'nullable|string|max:50',
            'display_order' => 'nullable|integer|min:1',
            'status' => 'nullable|in:active,inactive',
            'description' => 'nullable|string',
        ]);
    }

    public function updateStatus(Category $category)
    {
        $category->status = $category->status == 'active'
            ? 'inactive'
            : 'active';

        $category->save();

        AuditLog::log(
            'Updated Category Status',
            'Content',
            $category->name . ' (' . $category->status . ')'
        );

        return response()->json([
            'success' => true,
            'status'  => $category->status
        ]);
    }
}
