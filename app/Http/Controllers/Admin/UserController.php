<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\AuditLog;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Intervention\Image\Laravel\Facades\Image;
use Intervention\Image\Encoders\WebpEncoder;

class UserController extends Controller
{
    public function index(Request $request)
    {
        $tenantId = getTenantId();
        $perPage = $request->get('per_page', 10);
        
        $users = User::query()
            ->where('tenant_id', $tenantId)
            ->where('role', 'user')
            ->when($request->search, function ($q) use ($request) {
                $q->where(function ($query) use ($request) {
                    $query->where('name', 'like', "%{$request->search}%")
                        ->orWhere('email', 'like', "%{$request->search}%")
                        ->orWhere('mobile', 'like', "%{$request->search}%")
                        ->orWhere('referral_code', 'like', "%{$request->search}%");
                });
            })
            ->when($request->status, function ($q) use ($request) {
                $q->where('is_active', $request->status === 'active' ? 1 : 0);
            })
            ->latest()
            ->paginate($perPage)
            ->withQueryString();

        $editing = $request->edit ? User::where('tenant_id', $tenantId)->find($request->edit) : null;
        $states = \App\Models\State::where('status', 'active')->orderBy('name')->get();

        return view('admin.users.index', compact('users', 'editing', 'states'));
    }

    public function getCities($stateId)
    {
        $cities = \App\Models\City::where('state_id', $stateId)->orderBy('name')->get(['id', 'name']);
        return response()->json($cities);
    }

    public function store(Request $request)
    {
        $data = $this->validateUser($request);
        $tenantId = getTenantId();

        if ($request->hasFile('profile_photo')) {
            $file = $request->file('profile_photo');
            if ($file && $file->isValid()) {
                $imageName = uniqid(time(), true) . '.webp';
                $img = Image::decode($file);
                $encoded = $img->encode(new WebpEncoder(quality: 90));
                $path = "users/{$imageName}";

                Storage::disk('s3')->put($path, (string) $encoded, 'public');
                $data['profile_photo'] = $path;
            }
        }

        if (empty($data['password'])) {
            $data['password'] = Hash::make('123456');
        } else {
            $data['password'] = Hash::make($data['password']);
        }

        $data['tenant_id'] = $tenantId;
        $data['login_method'] = $data['login_method'] ?? 'otp';
        $data['can_login'] = $data['is_active'] ?? 1;

        if (!empty($data['role'])) {
            $data['role_id'] = \App\Models\Role::where('slug', $data['role'])
                ->orWhere('name', $data['role'])
                ->value('id');
        }

        // Auto Generate Lat Long if address is provided
        if (!empty($data['address']) && empty($data['lat']) && empty($data['long'])) {
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
                    $data['lat'] = round((float) $result[0]['lat'], 8);
                    $data['long'] = round((float) $result[0]['lon'], 8);
                }
            } catch (\Exception $e) {
                \Illuminate\Support\Facades\Log::error('Geocode failed: ' . $e->getMessage());
            }
        } else {
            if (isset($data['lat'])) $data['lat'] = round((float) $data['lat'], 8);
            if (isset($data['long'])) $data['long'] = round((float) $data['long'], 8);
        }

        $user = User::create($data);

        if (empty($user->referral_code)) {
            $user->referral_code = 'T' . $tenantId . 'U' . $user->id . strtoupper(Str::random(4));
            $user->save();
        }

        AuditLog::log('Created user', 'Users', $user->name);

        return redirect()->route('admin.users.index')->with('success', 'User created successfully.');
    }

    public function update(Request $request, User $user)
    {
        $tenantId = getTenantId();
        if ($user->tenant_id != $tenantId) {
            abort(403, 'Unauthorized tenant access.');
        }

        $data = $this->validateUser($request, $user->id);

        if ($request->hasFile('profile_photo')) {
            if ($user->profile_photo) {
                try {
                    Storage::disk('s3')->delete($user->profile_photo);
                } catch (\Exception $e) {
                    \Log::warning($e->getMessage());
                }
            }

            $file = $request->file('profile_photo');
            if ($file && $file->isValid()) {
                $imageName = uniqid(time(), true) . '.webp';
                $img = Image::decode($file);
                $encoded = $img->encode(new WebpEncoder(quality: 90));
                $path = "users/{$imageName}";

                Storage::disk('s3')->put($path, (string) $encoded, 'public');
                $data['profile_photo'] = $path;
            }
        }

        if (empty($data['password'])) {
            unset($data['password']);
        } else {
            $data['password'] = Hash::make($data['password']);
        }

        unset($data['referral_code']);
        $data['tenant_id'] = $tenantId;
        $data['can_login'] = $data['is_active'] ?? 1;

        if (!empty($data['role'])) {
            $data['role_id'] = \App\Models\Role::where('slug', $data['role'])
                ->orWhere('name', $data['role'])
                ->value('id');
        }

        if (!empty($data['address']) && empty($data['lat']) && empty($data['long'])) {
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
                    $data['lat'] = round((float) $result[0]['lat'], 8);
                    $data['long'] = round((float) $result[0]['lon'], 8);
                }
            } catch (\Exception $e) {
                \Illuminate\Support\Facades\Log::error('Geocode failed in update: ' . $e->getMessage());
            }
        } else {
            if (isset($data['lat'])) $data['lat'] = round((float) $data['lat'], 8);
            if (isset($data['long'])) $data['long'] = round((float) $data['long'], 8);
        }

        $user->update($data);

        if (empty($user->referral_code)) {
            $user->referral_code = 'T' . $tenantId . 'U' . $user->id . strtoupper(Str::random(4));
            $user->save();
        }

        AuditLog::log('Updated user', 'Users', 'U-' . $user->id);

        return redirect()->route('admin.users.index')->with('success', 'User updated successfully.');
    }

    private function validateUser(Request $request, $id = null)
    {
        return $request->validate([
            'name' => 'required|string|max:191',
            'email' => [
                    'nullable',
                    'regex:/^[A-Za-z0-9._%+-]+@[A-Za-z0-9.-]+\.(com|in|org|net|co\.in)$/i',
                    'max:191',
                ],
            'mobile' => 'nullable|digits:10',
            'alternative_mobile' => 'nullable|digits:10|different:mobile',
            'password' => 'nullable|string|min:6',
            'role' => 'required|string',
            'login_method' => 'nullable|string|in:password,otp,inactive',
            'gender' => 'nullable|string|in:male,female,other',
            'date_of_birth' => 'nullable|date',
            'favorite_color' => 'nullable|string|max:191',
            'hobbies' => 'nullable|string',
            'games' => 'nullable|string',
            'interest' => 'nullable|string',
            'state' => 'nullable|string|max:191',
            'city' => 'nullable|string|max:191',
            'address' => 'nullable|string',
            'lat' => 'nullable|numeric',
            'long' => 'nullable|numeric',
            'create_quiz_limit' => 'nullable|integer|min:0',
            'available_quiz_limit' => 'nullable|integer|min:0',
            'quiz_leave' => 'nullable|integer|min:0',
            'quiz_rank' => 'nullable|integer|min:0',
            'wallet_balance' => 'nullable|numeric|min:0',
            'verify_mobile' => 'nullable|boolean',
            'verify_email' => 'nullable|boolean',
            'is_active' => 'required|boolean',
            'profile_photo' => 'nullable|image|max:4096',
        ]);
    }

    public function destroy(User $user)
    {
        $tenantId = getTenantId();
        if ($user->tenant_id != $tenantId) {
            abort(403, 'Unauthorized tenant access.');
        }

        $user->update(['is_active' => 0, 'can_login' => 0]);
        AuditLog::log('Suspended user', 'Users', 'U-' . $user->id);

        return back()->with('success', 'User suspended.');
    }

    public function changeStatus(Request $request)
    {
        $request->validate([
            'id' => 'required|exists:users,id',
            'status' => 'required|boolean',
        ]);

        $tenantId = getTenantId();
        $user = User::where('tenant_id', $tenantId)->findOrFail($request->id);

        $user->update([
            'is_active' => $request->status,
            'can_login' => $request->status,
        ]);

        AuditLog::log(
            'Updated User Status',
            'Users',
            $user->name.' ('.($request->status ? 'Active' : 'Suspended').')'
        );

        return response()->json([
            'success' => true
        ]);
    }
}
