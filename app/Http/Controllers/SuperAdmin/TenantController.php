<?php

namespace App\Http\Controllers\SuperAdmin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Tenant;
use App\Models\AuditLog;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Intervention\Image\Laravel\Facades\Image;
use Intervention\Image\Encoders\WebpEncoder;


use App\Imports\TenantRetailerImport;
use Maatwebsite\Excel\Facades\Excel;
use Illuminate\Support\Facades\Log;

class TenantController extends Controller
{
    public function index(Request $request)
    {
        $tenants = Tenant::query()
            ->when($request->search, function ($q) use ($request) {
                $q->where(function ($query) use ($request) {
                    $query->where('name', 'like', "%{$request->search}%")
                        ->orWhere('email', 'like', "%{$request->search}%")
                        ->orWhere('phone', 'like', "%{$request->search}%")
                        ->orWhere('slug', 'like', "%{$request->search}%");
                });
            })
            ->when($request->status, function ($q) use ($request) {
                $q->where('status', $request->status === 'active' ? 'active' : 'inactive');
            })
            ->latest()
            ->paginate(15)
            ->withQueryString();

        $editing = $request->edit ? Tenant::find($request->edit) : null;
        $states = \App\Models\State::where('status', 'active')->orderBy('name')->get();

        return view('superadmin.tenants.index', compact('tenants', 'editing', 'states'));
    }

    public function getCities($stateId)
    {
        $cities = \App\Models\City::where('state_id', $stateId)->orderBy('name')->get(['id', 'name']);
        return response()->json($cities);
    }

    public function store(Request $request)
    {
        $data = $this->validateTenant($request);

        // if ($request->hasFile('logo')) {
        //     $data['logo'] = $request->file('logo')->store('tenants', 'public');
        // }

        if ($request->hasFile('logo')) {

            $file = $request->file('logo');

            if ($file && $file->isValid()) {

                $imageName = uniqid(time(), true) . '.webp';

                $img = Image::decode($file);

                $encoded = $img->encode(
                    new WebpEncoder(quality: 90)
                );

                $imgPath = "logo/{$imageName}";

                Storage::disk('s3')->put(
                    $imgPath,
                    (string) $encoded,
                    'public'
                );

                $data['logo'] = $imgPath;
            }
        }

        if (empty($data['slug'])) {
            $data['slug'] = Str::slug($data['name']) . '-' . rand(100, 999);
        }

        if (!empty($data['address']) && empty($data['latitude']) && empty($data['longitude'])) {
            try {
                $response = \Illuminate\Support\Facades\Http::withoutVerifying()
                    ->withHeaders(['User-Agent' => 'QuizHubApp/1.0'])
                    ->get('https://nominatim.openstreetmap.org/search', [
                        'format' => 'json',
                        'q' => $data['address'],
                        'limit' => 1
                    ]);
                $result = $response->json();
                if (!empty($result) && isset($result[0])) {
                    $data['latitude'] = round((float) $result[0]['lat'], 8);
                    $data['longitude'] = round((float) $result[0]['lon'], 8);
                }
            } catch (\Exception $e) {}
        }

        $tenant = Tenant::create($data);
        AuditLog::log('Created tenant', 'Tenants', $tenant->name);

        return redirect()->route('superadmin.tenants.index')->with('success', 'Tenant created successfully.');
    }

    public function update(Request $request, Tenant $tenant)
    {
        $data = $this->validateTenant($request, $tenant->id);

        // if ($request->hasFile('logo')) {
        //     if ($tenant->logo && Storage::disk('public')->exists($tenant->logo)) {
        //         Storage::disk('public')->delete($tenant->logo);
        //     }
        //     $data['logo'] = $request->file('logo')->store('tenants', 'public');
        // }

        if ($request->hasFile('logo')) {

            // if ($tenant->logo && Storage::disk('s3')->exists($tenant->logo)) {
            //     Storage::disk('s3')->delete($tenant->logo);
            // }
            if ($tenant->logo) {
                try {
                    Storage::disk('s3')->delete($tenant->logo);
                } catch (\Exception $e) {
                    // Ignore if file doesn't exist
                }
            }

            $file = $request->file('logo');

            if ($file && $file->isValid()) {

                $imageName = uniqid(time(), true) . '.webp';

                $img = Image::decode($file);

                $encoded = $img->encode(
                    new WebpEncoder(quality: 90)
                );

                $imgPath = "logo/{$imageName}";

                Storage::disk('s3')->put(
                    $imgPath,
                    (string) $encoded,
                    'public'
                );

                $data['logo'] = $imgPath;
            }
        }

        if (!empty($data['address']) && empty($data['latitude']) && empty($data['longitude'])) {
            try {
                $response = \Illuminate\Support\Facades\Http::withoutVerifying()
                    ->withHeaders(['User-Agent' => 'QuizHubApp/1.0'])
                    ->get('https://nominatim.openstreetmap.org/search', [
                        'format' => 'json',
                        'q' => $data['address'],
                        'limit' => 1
                    ]);
                $result = $response->json();
                if (!empty($result) && isset($result[0])) {
                    $data['latitude'] = round((float) $result[0]['lat'], 8);
                    $data['longitude'] = round((float) $result[0]['lon'], 8);
                }
            } catch (\Exception $e) {}
        }

        $tenant->update($data);
        AuditLog::log('Updated tenant', 'Tenants', 'T-' . $tenant->id);

        return redirect()->route('superadmin.tenants.index')->with('success', 'Tenant updated successfully.');
    }

    private function validateTenant(Request $request, $id = null)
    {
        return $request->validate([
            'name' => 'required|string|max:255',
            'slug' => 'nullable|string|max:255|unique:tenants,slug,' . $id,
            // 'email' => 'nullable|email|max:255',
            'email' => 'nullable|email:rfc|max:255',
            'phone' => 'nullable|digits:10',
            'status' => 'required|string|in:active,inactive',
            'logo' => 'nullable|image|max:4096',
            'pan_number' => 'nullable|string|max:50',
            'gstin' => 'nullable|string|max:50',
            'address' => 'nullable|string',
            'state' => 'nullable|string|max:255',
            'city' => 'nullable|string|max:255',
            'pincode' => 'nullable|string|max:20',
            'latitude' => 'nullable|numeric',
            'longitude' => 'nullable|numeric',
        ]);
    }

    public function destroy(Tenant $tenant)
    {
        $tenant->update(['status' => 'inactive']);
        AuditLog::log('Suspended tenant', 'Tenants', 'T-' . $tenant->id);

        return back()->with('success', 'Tenant suspended.');
    }

    public function changeStatus(Request $request)
    {
        $request->validate([
            'id' => 'required|exists:tenants,id',
            'status' => 'required|string|in:active,inactive',
        ]);

        $tenant = Tenant::findOrFail($request->id);

        $tenant->update([
            'status' => $request->status
        ]);

        AuditLog::log(
            'Updated Tenant Status',
            'Tenants',
            $tenant->name.' ('.$request->status.')'
        );

        return response()->json(['success' => true]);
    }

    /*public function import(Request $request)
    {
        $request->validate([
            'file' => [
                'required',
                'file',
                'mimes:xlsx,xls,csv',
                'max:10240',
            ],
        ]);
        set_time_limit(0);
        Excel::import(
            new TenantRetailerImport,
            $request->file('file')
        );

        return redirect()
            ->back()
            ->with('success', 'Tenants and retailer users imported successfully.');
    }*/




    public function import(Request $request)
    {
        $request->validate([
            'file' => [
                'required',
                'file',
                'mimes:xlsx,xls,csv',
                'max:10240',
            ],
        ]);

        set_time_limit(0);
        $startTime = microtime(true);

        try {

            $import = new TenantRetailerImport();
            Excel::import($import, $request->file('file'));

            // Counters padho
            $totalRows        = $import->totalRows;
            $importedCount    = $import->importedCount;
            $duplicateSkipped = $import->duplicateSkipped;
            $invalidSkipped   = $import->invalidSkipped;
            $failedCount      = $import->failedCount;

            $duration = round(microtime(true) - $startTime, 2);

            // ✅ Sirf EK line summary log
            Log::info('Tenant Retailer Import Complete', [
                'total'      => $totalRows,
                'imported'   => $importedCount,
                'duplicates' => $duplicateSkipped,
                'invalid'    => $invalidSkipped,
                'failed'     => $failedCount,
                'time'       => $duration . 's',
            ]);

            AuditLog::log(
                'Imported retailers from Excel',
                'Tenants',
                "Total: {$totalRows} | Imported: {$importedCount} | Duplicates: {$duplicateSkipped} | Invalid: {$invalidSkipped} | Failed: {$failedCount} | Time: {$duration}s"
            );

            // ✅ Success message with ALL counts
            $message = sprintf(
                '✅ Import Complete in %ss — Total: %d | 🟢 Imported: %d | 🚫 Duplicates: %d | ⚠️ Invalid: %d | ❌ Failed: %d',
                $duration, $totalRows, $importedCount, $duplicateSkipped, $invalidSkipped, $failedCount
            );

            $redirect = redirect()->back()->with('success', $message);

            // Errors flash karo agar koi hain
            if (!empty($import->errors)) {
                $errorSample = array_slice($import->errors, 0, 5);
                $errorHtml   = implode('<br>', $errorSample);
                if (count($import->errors) > 5) {
                    $errorHtml .= '<br><em>... and ' . (count($import->errors) - 5) . ' more (see logs).</em>';
                }
                $redirect = $redirect->with('import_errors', $errorHtml);
            }

            return $redirect;

        } catch (\Throwable $e) {

            Log::error('Tenant Retailer Import Error', [
                'message' => $e->getMessage(),
            ]);

            return redirect()->back()
                ->with('error', 'Import failed: ' . $e->getMessage());
        }
    }

}
