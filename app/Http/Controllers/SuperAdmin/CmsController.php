<?php

namespace App\Http\Controllers\SuperAdmin;

use App\Http\Controllers\Controller;
use App\Models\AuditLog;
use App\Models\CmsPage;
use Illuminate\Http\Request;

class CmsController extends Controller
{
    public function index(Request $request)
    {
        $pages = CmsPage::latest()->get();
        $editing = $request->edit ? CmsPage::findOrFail($request->edit) : null;

        return view('superadmin.cms.index', compact('pages', 'editing'));
    }

    public function store(Request $request)
    {
        $data = $this->validated($request);
        CmsPage::create($data);
        AuditLog::log('Created CMS page', 'CMS', $data['title']);

        return redirect()->route('superadmin.cms.index')->with('success', 'Page saved.');
    }

    public function update(Request $request, CmsPage $page)
    {
        $data = $this->validated($request);
        $page->update($data);
        AuditLog::log('Updated CMS page', 'CMS', $page->title);

        return redirect()->route('superadmin.cms.index')->with('success', 'Page updated.');
    }

    private function validated(Request $request): array
    {
        return $request->validate([
            'title' => 'required|string|max:191',
            'slug' => 'required|string|max:191',
            'content' => 'required|string',
            'meta_title' => 'nullable|string|max:191',
            'meta_description' => 'nullable|string|max:255',
            'status' => 'required|in:draft,published',
        ]);
    }

    public function changeStatus(Request $request)
    {
        $request->validate([
             'id' => 'required|exists:cms_pages,id',
            'status' => 'required|in:draft,published',
        ]);

        $page = CmsPage::findOrFail($request->id);

        $page->update([
            'status' => $request->status
        ]);

        AuditLog::log(
            'Updated CMS page status',
            'CMS',
            $page->title
        );

        return response()->json([
            'success' => true,
            'message' => 'Status updated successfully.'
        ]);
    }


}
