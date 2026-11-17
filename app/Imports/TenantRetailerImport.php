<?php

namespace App\Imports;

use App\Models\Tenant;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use Maatwebsite\Excel\Concerns\OnEachRow;
use Maatwebsite\Excel\Concerns\SkipsEmptyRows;
use Maatwebsite\Excel\Concerns\WithHeadingRow;
use Maatwebsite\Excel\Row;

class TenantRetailerImport implements OnEachRow, WithHeadingRow, SkipsEmptyRows
{
    // ✅ Public counters — controller inko read karega
    public int $totalRows        = 0;
    public int $importedCount    = 0;
    public int $duplicateSkipped = 0;
    public int $invalidSkipped   = 0;
    public int $failedCount      = 0;
    public array $errors         = [];

    // ✅ Same file ke andar duplicates track karne ke liye
    private array $seenMobiles = [];
    private array $existingMobiles = [];

    public function __construct()
    {
        // Existing mobiles ko ek baar fetch kar lo (fast lookup ke liye)
        $this->existingMobiles = User::whereNotNull('mobile')
            ->pluck('mobile')
            ->map(fn ($m) => preg_replace('/\D/', '', (string) $m))
            ->filter()
            ->unique()
            ->flip()
            ->toArray();
    }

    public function onRow(Row $row): void
    {
        set_time_limit(0);

        $this->totalRows++;
        $rowNumber = $row->getIndex();
        $data = $row->toArray();

        try {

            // ✅ Numeric cells ko string mein cast karo
            $retailer = isset($data['retailer']) && is_scalar($data['retailer'])
                ? trim((string) $data['retailer']) : '';
            $mobile = isset($data['mobile']) && is_scalar($data['mobile'])
                ? preg_replace('/\D/', '', (string) $data['mobile']) : '';

            $email    = !empty($data['email'])     ? trim((string) $data['email'])     : null;
            $address1 = !empty($data['address_1']) ? trim((string) $data['address_1']) : null;
            $address2 = !empty($data['address_2']) ? trim((string) $data['address_2']) : null;
            $city     = !empty($data['city'])      ? ucwords(strtolower(trim((string) $data['city'])))  : null;
            $state    = !empty($data['state'])     ? ucwords(strtolower(trim((string) $data['state']))) : null;

            // --------------------------------------------------
            // VALIDATION (No logs for these — just skip & count)
            // --------------------------------------------------

            // Blank row skip
            if ($retailer === '' && $mobile === '') {
                $this->invalidSkipped++;
                return;
            }

            // Retailer name check
            if ($retailer === '') {
                $this->invalidSkipped++;
                return;
            }

            // Mobile validation (exactly 10 digits)
            if (strlen($mobile) !== 10) {
                $this->invalidSkipped++;
                return;
            }

            // --------------------------------------------------
            // DUPLICATE CHECK (DB + Same File) — No logs, just skip
            // --------------------------------------------------
            if (isset($this->existingMobiles[$mobile]) || isset($this->seenMobiles[$mobile])) {
                $this->duplicateSkipped++;
                return;
            }

            // --------------------------------------------------
            // CREATE TENANT + USER (inside transaction)
            // --------------------------------------------------
            DB::transaction(function () use (
                $retailer, $mobile, $email,
                $address1, $address2, $city, $state
            ) {

                // Agar tenant already phone se hai, skip
                $tenant = Tenant::where('phone', $mobile)->first();
                if ($tenant) {
                    $this->duplicateSkipped++;
                    return;
                }

                // Generate unique slug
                $slug = Str::slug($retailer);
                $originalSlug = $slug;
                $counter = 1;
                while (Tenant::where('slug', $slug)->exists()) {
                    $slug = $originalSlug . '-' . $counter;
                    $counter++;
                }

                // Create Tenant
                $tenant = Tenant::create([
                    'name'                    => $retailer,
                    'slug'                    => $slug,
                    'phone'                   => $mobile,
                    'email'                   => $email,
                    'address'                 => $address1,
                    'address1'                => $address2,
                    'city'                    => $city,
                    'state'                   => $state,
                    'status'                  => 'active',
                    'user_referral_limit'     => 5,
                    'quiz_max_player_limit'   => 50,
                    'quiz_max_question_limit' => 10,
                    'quiz_limit'              => 30,
                    'quiz_daily_play_limit'   => 2,
                ]);

                // Create Retailer User
                User::create([
                    'tenant_id'            => $tenant->id,
                    'name'                 => $retailer,
                    'mobile'               => $mobile,
                    'email'                => $email,
                    'password'             => Hash::make('password123'),
                    'role_id'              => 3,
                    'role'                 => 'retailer',
                    'city'                 => $city,
                    'state'                => $state,
                    'address'              => $address1,
                    'user_referral_limit'  => 5,
                    'create_quiz_limit'    => 5,
                    'available_quiz_limit' => 5,
                    'quiz_leave'           => 0,
                    'is_active'            => 1,
                    'can_login'            => 1,
                    'otp_enabled'          => 1,
                ]);
            });

            // ✅ Success — counters update (NO LOG)
            $this->importedCount++;
            $this->seenMobiles[$mobile]     = true;
            $this->existingMobiles[$mobile] = true;

        } catch (\Throwable $e) {

            // ❌ ONLY errors log hoti hain (one line per error)
            $this->failedCount++;
            $msg = "Row {$rowNumber} ({$mobile}): " . $e->getMessage();
            $this->errors[] = $msg;

            Log::error('Tenant retailer import failed', [
                'row'      => $rowNumber,
                'retailer' => $data['retailer'] ?? null,
                'mobile'   => $data['mobile']   ?? null,
                'error'    => $e->getMessage(),
            ]);
        }
    }
}