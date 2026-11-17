<?php

namespace App\Http\Controllers\SuperAdmin;

use App\Http\Controllers\Controller;
use App\Models\HowItWorks;
use App\Models\AuditLog;
use Illuminate\Http\Request;

class HowItWorksController extends Controller
{
    /**
     * Show the single page with all How It Works items
     */
    public function index()
    {
        // Saare steps ko order mein fetch karein
        $items = HowItWorks::orderBy('id', 'asc')->get();

        return view('superadmin.how_it_works.index', compact('items'));
    }

    /**
     * Update all items in a single request
     */
    public function update(Request $request)
    {
        // Validation: Ensure items array exists and each has valid data
        $validated = $request->validate([
            'items' => 'required|array',
            'items.*.id' => 'required|exists:how_it_works,id',
            'items.*.title' => 'required|string|max:255',
            'items.*.description' => 'required|string',
        ]);

        // Loop through each item and update it
        foreach ($validated['items'] as $data) {
            HowItWorks::where('id', $data['id'])->update([
                'title' => $data['title'],
                'description' => $data['description'],
            ]);
        }

        // Audit log save karein
        AuditLog::log('Updated How It Works steps', 'HowItWorks');

        return back()->with('success', 'How It Works updated successfully.');
    }
}