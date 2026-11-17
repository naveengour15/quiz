<?php

namespace App\Http\Controllers\SuperAdmin;

use App\Http\Controllers\Controller;
use App\Models\AuditLog;
use App\Models\Category;
use Illuminate\Http\Request;

class CategoryController extends Controller
{
   /* public function index(Request $request)
    {
        $categories = Category::with('parent')->withCount('questions')->orderBy('display_order')->get();
        $parents = Category::whereNull('parent_id')->get();
        $editing = $request->edit ? Category::findOrFail($request->edit) : null;

        return view('superadmin.categories.index', compact('categories', 'parents', 'editing'));
    }*/

    public function index(Request $request)
    {
        $perPage = $request->get('per_page', 10);
        $tab = $request->get('tab', 'category');

        $query = Category::where('tenant_id', getSuperAdminTenantId())->with('parent')->withCount('questions');

        if ($tab == 'subcategory') {
            $query->whereNotNull('parent_id');
        } else {
            $query->whereNull('parent_id');
        }

        // Search Filter
        if ($request->filled('search')) {
            $query->where('name', 'like', '%' . $request->search . '%');
        }

        // Status Filter
        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }

        $categories = $query->orderBy('display_order')->paginate($perPage)
        ->withQueryString();  

        $parents = Category::where('tenant_id', getSuperAdminTenantId())->whereNull('parent_id')->orderBy('name')->get();

        $editing = $request->edit
            ? Category::findOrFail($request->edit)
            : null;

        return view('superadmin.categories.index', compact(
            'categories',
            'parents',
            'editing',
            'tab'
        ));
    }

    public function store(Request $request)
    {
        $data = $this->validated($request);
        $data['tenant_id'] = getSuperAdminTenantId();
        Category::create($data);
        AuditLog::log('Created category', 'Content', $data['name']);

       // return redirect()->route('superadmin.categories.index')->with('success', 'Category created.');

        return redirect()->route('superadmin.categories.index', [
            'tab' => $request->tab
        ])->with('success', 'Category created.');

    }

    public function update(Request $request, Category $category)
    {
        $data = $this->validated($request);
        $data['tenant_id'] = getSuperAdminTenantId();
        $category->update($data);
        AuditLog::log('Updated category', 'Content', $category->name);

       // return redirect()->route('superadmin.categories.index')->with('success', 'Category updated.');

        return redirect()->route('superadmin.categories.index', [
            'tab' => $request->tab
        ])->with('success', 'Category updated.');

    }

    public function destroy(Request $request,Category $category)
    {
        $category->delete();
        AuditLog::log('Deleted category', 'Content', $category->name);

        // return back()->with('success', 'Category deleted.');

        return redirect()->route('superadmin.categories.index', [
        'tab' => $request->tab
             ])->with('success', 'Category deleted.');
    }

    private function validated(Request $request): array
    {
        return $request->validate([
            'name' => 'required|string|max:191',
            'parent_id' => 'nullable|exists:categories,id',
            'icon' => 'nullable|string|max:50',
            'display_order' => 'required|integer|min:1',
            'status' => 'required|in:active,inactive',
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
