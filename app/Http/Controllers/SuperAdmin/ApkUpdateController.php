<?php

namespace App\Http\Controllers\SuperAdmin;

use App\Http\Controllers\Controller;
use App\Models\ApkUpdate;
use Illuminate\Http\Request;

class ApkUpdateController extends Controller
{
    public function index()
    {
        // Variable name '$update' kiya hai taaki Blade file se match ho jaye
        $update = ApkUpdate::where('tenant_id', 1)->first();
        
        return view('superadmin.apk-updates.index', compact('update'));
    }

    public function update(Request $request)
    {
        $request->validate([
            'version' => 'required|string|max:20',
            'build_number' => 'required|integer',
            'release_title' => 'required|string|max:100',
            'release_notes' => 'nullable|string',
            'apk_file' => 'required|file|extensions:apk|max:204800', // Max 200MB
        ], [
            'apk_file.extensions' => 'Sirf .apk file upload karein.',
            'apk_file.max' => 'APK file ka size 200MB se zyada nahi hona chahiye.',
        ]);

        $fileName = 'FSRQuiz.apk'; // Fixed Name
        $directory = public_path('apk'); // public/apk folder
        $filePath = $directory . '/' . $fileName;

        // 1. Agar 'apk' folder nahi hai, toh bana do
        if (!file_exists($directory)) {
            mkdir($directory, 0777, true);
        }

        // 2. Agar purani file exist karti hai, toh usko DELETE kar do
        if (file_exists($filePath)) {
            unlink($filePath); // Purana APK delete
        }

        // 3. Nayi file ko same naam se move kar do
        $request->file('apk_file')->move($directory, $fileName);

        // 4. Public URL generate karo (e.g., http://localhost:8000/apk/FSRQuiz.apk)
        $apkUrl = url('apk/' . $fileName);

        // 5. Database mein Update/Create karo
        ApkUpdate::updateOrCreate(
            ['tenant_id' => 1],
            [
                'version' => $request->version,
                'build_number' => $request->build_number,
                'release_title' => $request->release_title,
                'release_notes' => $request->release_notes,
                'apk_url' => $apkUrl,
            ]
        );

        return back()->with('success', '✅ APK successfully updated! Old file has been replaced.');
    }
    //**************Status update */
      
    public function updateStatus(Request $request)
    {
        $request->validate([
            'id' => 'required|integer|exists:apk_updates,id',
            'status' => 'required|in:active,inactive'
        ]);

        $update = ApkUpdate::findOrFail($request->id);
        $update->status = $request->status;
        $update->save();

        return response()->json([
            'success' => true,
            'message' => 'Status updated successfully!',
            'new_status' => $update->status
        ]);
    }
}