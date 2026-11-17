<?php

namespace App\Http\Controllers\SuperAdmin;

use App\Http\Controllers\Controller;
use App\Models\AuditLog;
use App\Models\CmsPage;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;

class PrivacyPolicyController extends Controller
{
    /**
     * Display a listing of privacy policy & CMS pages.
     */
    public function index(Request $request)
    {
        // Ensure the 3 required default pages exist (with title & slug only)
        $this->ensureDefaultPagesExist();

        $pages = CmsPage::orderBy('id', 'asc')->get();

        // Determine active page
        $activeSlug = $request->query('slug');
        $activeId = $request->query('id');

        if ($activeSlug) {
            $activePage = $pages->firstWhere('slug', $activeSlug);
        } elseif ($activeId) {
            $activePage = $pages->firstWhere('id', (int) $activeId);
        } else {
            $activePage = $pages->first();
        }

        if (!$activePage && $pages->isNotEmpty()) {
            $activePage = $pages->first();
        }

        // Icons for common policy pages
        $icons = [
            'privacy-policy' => '🛡️',
            'terms-conditions' => '📜',
            'delete-account' => '🗑️',
            'contact-us' => '📞',
            'about-us' => 'ℹ️',
            'refund-policy' => '💸',
        ];

        return view('superadmin.privacy-policy.index', compact('pages', 'activePage', 'icons'));
    }

    /**
     * Store a newly created CMS page.
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'title' => 'required|string|max:191',
            'slug' => 'nullable|string|max:191|unique:cms_pages,slug',
            'status' => ['required', Rule::in(['draft', 'published'])],
            'meta_title' => 'nullable|string|max:191',
            'meta_description' => 'nullable|string|max:255',
            'content' => 'nullable|string',
        ]);

        $slug = !empty($validated['slug'])
            ? Str::slug($validated['slug'])
            : Str::slug($validated['title']);

        // Ensure unique slug
        $originalSlug = $slug;
        $counter = 1;
        while (CmsPage::where('slug', $slug)->exists()) {
            $slug = $originalSlug . '-' . $counter;
            $counter++;
        }

        $validated['slug'] = $slug;
        $validated['tenant_id'] = $validated['tenant_id'] ?? 1;

        $page = CmsPage::create($validated);

        if (class_exists(AuditLog::class)) {
            AuditLog::log('Created Privacy Policy page', 'Policy', $page->title);
        }

        return redirect()
            ->route('superadmin.privacy-policy.index', ['slug' => $page->slug])
            ->with('success', 'Page "' . $page->title . '" created successfully.');
    }

    /**
     * Update the specified CMS page.
     */
    public function update(Request $request, $id)
    {
        $page = CmsPage::findOrFail($id);

        $validated = $request->validate([
            'title' => 'required|string|max:191',
            'slug' => [
                'required',
                'string',
                'max:191',
                Rule::unique('cms_pages', 'slug')->ignore($page->id),
            ],
            'status' => ['required', Rule::in(['draft', 'published'])],
            'meta_title' => 'nullable|string|max:191',
            'meta_description' => 'nullable|string|max:255',
            'content' => 'nullable|string',
        ]);

        $validated['slug'] = Str::slug($validated['slug']);

        $page->update($validated);

        if (class_exists(AuditLog::class)) {
            AuditLog::log('Updated Privacy Policy page', 'Policy', $page->title);
        }

        return redirect()
            ->route('superadmin.privacy-policy.index', ['slug' => $page->slug])
            ->with('success', $page->title . ' updated successfully.');
    }

    /**
     * Remove the specified CMS page.
     */
    public function destroy($id)
    {
        $page = CmsPage::findOrFail($id);
        $title = $page->title;

        // Prevent deleting the 3 core pages
        $coreSlugs = ['privacy-policy', 'terms-conditions', 'delete-account'];
        if (in_array($page->slug, $coreSlugs, true)) {
            return redirect()
                ->route('superadmin.privacy-policy.index', ['slug' => $page->slug])
                ->with('error', 'Core policy page "' . $title . '" cannot be deleted.');
        }

        $page->delete();

        if (class_exists(AuditLog::class)) {
            AuditLog::log('Deleted Privacy Policy page', 'Policy', $title);
        }

        return redirect()
            ->route('superadmin.privacy-policy.index')
            ->with('success', 'Page "' . $title . '" deleted successfully.');
    }

    /**
     * Change status via AJAX.
     */
    public function changeStatus(Request $request)
    {
        $request->validate([
            'id' => 'required|exists:cms_pages,id',
            'status' => 'required|in:draft,published',
        ]);

        $page = CmsPage::findOrFail($request->id);
        $page->update(['status' => $request->status]);

        if (class_exists(AuditLog::class)) {
            AuditLog::log('Updated Privacy Policy status', 'Policy', $page->title . ' (' . $request->status . ')');
        }

        return response()->json([
            'success' => true,
            'message' => 'Status updated successfully.',
            'status' => $page->status,
        ]);
    }

    /**
     * Ensure the 3 required default pages exist with title & slug only (no default description/content).
     */
    private function ensureDefaultPagesExist(): void
    {
        $defaults = [
            [
                'tenant_id' => 1,
                'title' => 'FSR Quiz — Privacy Policy.',
                'slug' => 'privacy-policy',
                'content' => null,
                'meta_title' => null,
                'meta_description' => null,
                'status' => 'published',
            ],
            [
                'tenant_id' => 1,
                'title' => 'FSR Quiz — Terms and Conditions',
                'slug' => 'terms-conditions',
                'content' => null,
                'meta_title' => null,
                'meta_description' => null,
                'status' => 'published',
            ],
            [
                'tenant_id' => 1,
                'title' => 'FSR Quiz — Delete your account.',
                'slug' => 'delete-account',
                'content' => null,
                'meta_title' => null,
                'meta_description' => null,
                'status' => 'published',
            ],
        ];

        foreach ($defaults as $item) {
            $exists = CmsPage::where('slug', $item['slug'])->exists();
            if (!$exists) {
                CmsPage::create($item);
            }
        }
    }
}
