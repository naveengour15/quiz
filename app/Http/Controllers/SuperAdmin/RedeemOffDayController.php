<?php

namespace App\Http\Controllers\SuperAdmin;

use App\Http\Controllers\Controller;
use App\Models\RedeemRequestOffDay;
use Illuminate\Http\Request;

class RedeemOffDayController extends Controller
{
    /**
     * Display the redeem off days settings page.
     */
    public function index(Request $request)
    {
        $tenantId = getSuperAdminTenantId();
        
        $selectedYear = (int) $request->input('year', date('Y'));
        $selectedMonth = (int) $request->input('month', date('n'));

        // Clamp year and month within sensible range
        if ($selectedMonth < 1 || $selectedMonth > 12) {
            $selectedMonth = (int) date('n');
        }
        if ($selectedYear < 2020 || $selectedYear > 2100) {
            $selectedYear = (int) date('Y');
        }
        
        $currentYear = $selectedYear;
        $currentMonth = $selectedMonth;
        
        // Month data for form
        $currentMonthData = RedeemRequestOffDay::where('tenant_id', $tenantId)
            ->where('year', $currentYear)
            ->where('month', $currentMonth)
            ->first();

        // All saved records history with pagination
        $perPage = (int) $request->input('per_page', 10);
        if ($perPage < 1 || $perPage > 500) {
            $perPage = 10;
        }

        $allOffDays = RedeemRequestOffDay::where('tenant_id', $tenantId)
            ->orderBy('year', 'desc')
            ->orderBy('month', 'desc')
            ->paginate($perPage)
            ->withQueryString();

        $users = $allOffDays; // Alias for views using $users


        $months = [
            1 => 'January', 2 => 'February', 3 => 'March', 4 => 'April',
            5 => 'May', 6 => 'June', 7 => 'July', 8 => 'August',
            9 => 'September', 10 => 'October', 11 => 'November', 12 => 'December'
        ];

        // Exact days in this month according to calendar (e.g. Feb 28 or 29, Apr 30, Oct 31)
        $monthDateStr = sprintf('%04d-%02d-01', $currentYear, $currentMonth);
        $daysInMonth = (int) date('t', strtotime($monthDateStr));
        
        // Day of week for 1st day (0 = Sunday, 1 = Monday, ..., 6 = Saturday)
        $firstDayOfWeek = (int) date('w', strtotime($monthDateStr));

        // Festivals for this month & year
        $festivals = $this->getFestivals($currentYear, $currentMonth);

        // Previous and Next month/year for smooth calendar navigation
        $prevDate = strtotime("-1 month", strtotime($monthDateStr));
        $prevYear = (int) date('Y', $prevDate);
        $prevMonth = (int) date('n', $prevDate);

        $nextDate = strtotime("+1 month", strtotime($monthDateStr));
        $nextYear = (int) date('Y', $nextDate);
        $nextMonth = (int) date('n', $nextDate);

        return view('superadmin.redeem-off-days.index', compact(
            'currentYear', 
            'currentMonth', 
            'currentMonthData', 
            'allOffDays', 
            'months',
            'daysInMonth',
            'firstDayOfWeek',
            'festivals',
            'prevYear',
            'prevMonth',
            'nextYear',
            'nextMonth',
            'users'
        ));
    }

    /**
     * Save or Update off days for a specific month.
     */
    public function store(Request $request)
    {
        $request->validate([
            'year' => 'required|integer|min:2020|max:2100',
            'month' => 'required|integer|min:1|max:12',
            'dates' => 'nullable|array',
            'dates.*' => 'integer|min:1|max:31',
            'status' => 'nullable|in:active,inactive'
        ]);

        $tenantId = getSuperAdminTenantId();
        
        $monthDateStr = sprintf('%04d-%02d-01', $request->year, $request->month);
        $maxDays = (int) date('t', strtotime($monthDateStr));

        // Filter and sort dates in ascending order
        $dates = [];
        if ($request->has('dates') && is_array($request->dates)) {
            $dates = array_map('intval', $request->dates);
            // Ensure no invalid days (e.g. > max days of that month)
            $dates = array_filter($dates, function ($d) use ($maxDays) {
                return $d >= 1 && $d <= $maxDays;
            });
            $dates = array_values(array_unique($dates));
            sort($dates);
        }

        RedeemRequestOffDay::updateOrCreate(
            [
                'tenant_id' => $tenantId,
                'year' => (int) $request->year,
                'month' => (int) $request->month,
            ],
            [
                'date' => $dates,
                'status' => $request->status ?? 'active',
            ]
        );

        $months = [
            1 => 'January', 2 => 'February', 3 => 'March', 4 => 'April',
            5 => 'May', 6 => 'June', 7 => 'July', 8 => 'August',
            9 => 'September', 10 => 'October', 11 => 'November', 12 => 'December'
        ];
        $monthName = $months[$request->month] ?? $request->month;

        return redirect()->route('superadmin.redeem-off-days.index', [
            'year' => $request->year,
            'month' => $request->month
        ])->with('success', "Off days updated successfully for {$monthName} {$request->year}!");
    }

    /**
     * Delete a specific month's off days record.
     */
    public function destroy($id)
    {
        $offDay = RedeemRequestOffDay::where('id', $id)
            ->where('tenant_id', getSuperAdminTenantId())
            ->firstOrFail();

        $offDay->delete();

        return back()->with('success', 'Off days record deleted successfully.');
    }

    /**
     * Return Indian and major festivals/holidays calculated dynamically for ANY year and month.
     */
    protected function getFestivals(int $year, int $month): array
    {
        $festivals = [];

        // 1. Fixed Annual Observances / Festivals (Applies to any year)
        $fixed = $this->getFixedFestivals($month);
        foreach ($fixed as $day => $name) {
            $festivals[$day] = $name;
        }

        // 2. Dynamic Christian Holidays (Good Friday, Easter) for ANY year
        $christian = $this->calculateChristianFestivals($year);
        if (isset($christian[$month])) {
            foreach ($christian[$month] as $day => $name) {
                $this->addFestival($festivals, $day, $name);
            }
        }

        // 3. Dynamic Islamic Festivals (Eid-ul-Fitr, Bakrid, Muharram, Eid-e-Milad) for ANY year
        $islamic = $this->calculateIslamicFestivals($year);
        if (isset($islamic[$month])) {
            foreach ($islamic[$month] as $day => $name) {
                $this->addFestival($festivals, $day, $name);
            }
        }

        // 4. Dynamic Hindu Festivals (Diwali, Holi, Dussehra, Raksha Bandhan, etc.) for ANY year
        $hindu = $this->calculateHinduFestivals($year);
        if (isset($hindu[$month])) {
            foreach ($hindu[$month] as $day => $name) {
                $this->addFestival($festivals, $day, $name);
            }
        }

        ksort($festivals);
        return $festivals;
    }

    protected function addFestival(array &$list, int $day, string $name): void
    {
        if (isset($list[$day])) {
            if (strpos($list[$day], $name) === false) {
                $list[$day] .= ' / ' . $name;
            }
        } else {
            $list[$day] = $name;
        }
    }

    protected function getFixedFestivals(int $month): array
    {
        $fixed = [
            1 => [
                1 => 'New Year 🎉',
                14 => 'Makar Sankranti 🪁',
                26 => 'Republic Day 🇮🇳',
            ],
            2 => [
                14 => "Valentine's Day ❤️",
                28 => 'Science Day 🔬',
            ],
            3 => [
                8 => "Women's Day 🌸",
                23 => 'Shaheed Diwas 🇮🇳',
            ],
            4 => [
                14 => 'Ambedkar Jayanti 📜',
            ],
            5 => [
                1 => 'Labour Day 🛠️',
            ],
            6 => [
                21 => 'Yoga Day 🧘',
            ],
            8 => [
                15 => 'Independence Day 🇮🇳',
            ],
            10 => [
                2 => 'Gandhi Jayanti 🕊️',
            ],
            11 => [
                14 => "Children's Day 🎈",
            ],
            12 => [
                25 => 'Christmas 🎄',
                31 => "New Year's Eve 🎆",
            ],
        ];

        return $fixed[$month] ?? [];
    }

    protected function calculateChristianFestivals(int $year): array
    {
        $festivals = [];
        // Meeus/Butcher algorithm for Easter Sunday (works for any Gregorian year)
        $a = $year % 19;
        $b = intdiv($year, 100);
        $c = $year % 100;
        $d = intdiv($b, 4);
        $e = $b % 4;
        $f = intdiv($b + 8, 25);
        $g = intdiv($b - $f + 1, 3);
        $h = (19 * $a + $b - $d - $g + 15) % 30;
        $i = intdiv($c, 4);
        $k = $c % 4;
        $l = (32 + 2 * $e + 2 * $i - $h - $k) % 7;
        $m = intdiv($a + 11 * $h + 22 * $l, 451);
        $month = intdiv($h + $l - 7 * $m + 114, 31);
        $day = (($h + $l - 7 * $m + 114) % 31) + 1;

        $easterTime = strtotime(sprintf('%04d-%02d-%02d', $year, $month, $day));
        $goodFridayTime = strtotime('-2 days', $easterTime);

        $gfMonth = (int) date('n', $goodFridayTime);
        $gfDay = (int) date('j', $goodFridayTime);
        $festivals[$gfMonth][$gfDay] = 'Good Friday ✝️';

        return $festivals;
    }

    protected function calculateIslamicFestivals(int $year): array
    {
        $festivals = [];
        $approxHijri = (int) round(($year - 622) * (33 / 32));

        for ($hy = $approxHijri - 1; $hy <= $approxHijri + 1; $hy++) {
            $events = [
                ['name' => 'Eid-ul-Fitr 🌙', 'month' => 10, 'day' => 1],
                ['name' => 'Bakrid 🐑', 'month' => 12, 'day' => 10],
                ['name' => 'Muharram 🏴', 'month' => 1, 'day' => 10],
                ['name' => 'Eid-e-Milad 🕌', 'month' => 3, 'day' => 12],
            ];

            foreach ($events as $event) {
                $gDate = $this->islamicToGregorian($hy, $event['month'], $event['day']);
                $gParts = explode('-', $gDate);
                $gY = (int) $gParts[0];
                $gM = (int) $gParts[1];
                $gD = (int) $gParts[2];

                if ($gY === $year) {
                    $festivals[$gM][$gD] = $event['name'];
                }
            }
        }

        return $festivals;
    }

    protected function islamicToGregorian(int $hy, int $hm, int $hd): string
    {
        $jd = floor((11 * $hy + 3) / 30) + 354 * $hy + 30 * $hm - floor(($hm - 1) / 2) + $hd + 1948440 - 385;
        $l = $jd + 68569;
        $n = floor((4 * $l) / 146097);
        $l = $l - floor((146097 * $n + 3) / 4);
        $i = floor((4000 * ($l + 1)) / 1461001);
        $l = $l - floor((1461 * $i) / 4) + 31;
        $j = floor((80 * $l) / 2447);
        $d = $l - floor((2447 * $j) / 80);
        $l = floor($j / 11);
        $m = $j + 2 - (12 * $l);
        $y = 100 * ($n - 49) + $i + $l;
        return sprintf('%04d-%02d-%02d', (int)$y, (int)$m, (int)$d);
    }

    protected function calculateHinduFestivals(int $year): array
    {
        $festivals = [];
        $phases = $this->getMoonPhasesForYear($year);

        // Holi: Full moon between Feb 20 and Mar 28
        $holiTime = null;
        foreach ($phases as $p) {
            if ($p['type'] === 'full' && (($p['month'] == 3 && $p['day'] <= 30) || ($p['month'] == 2 && $p['day'] >= 20))) {
                $holiTime = strtotime($p['date']);
                $festivals[$p['month']][$p['day']] = 'Holi 🎨';
                break;
            }
        }

        // Maha Shivratri: ~15 days before Holi
        if ($holiTime) {
            $shivratriTime = strtotime('-15 days', $holiTime);
            $sm = (int) date('n', $shivratriTime);
            $sd = (int) date('j', $shivratriTime);
            $festivals[$sm][$sd] = 'Maha Shivratri 🔱';
        }

        // Raksha Bandhan: Full moon in August (or late July/early Sep)
        $rakhiTime = null;
        foreach ($phases as $p) {
            if ($p['type'] === 'full' && ($p['month'] == 8 || ($p['month'] == 7 && $p['day'] >= 28))) {
                $rakhiTime = strtotime($p['date']);
                $festivals[$p['month']][$p['day']] = 'Raksha Bandhan 🧵';
                break;
            }
        }

        // Janmashtami & Ganesh Chaturthi
        if ($rakhiTime) {
            $janmashtamiTime = strtotime('+8 days', $rakhiTime);
            $jm = (int) date('n', $janmashtamiTime);
            $jd = (int) date('j', $janmashtamiTime);
            $festivals[$jm][$jd] = 'Janmashtami 🦚';

            $ganeshTime = strtotime('+17 days', $rakhiTime);
            $gm = (int) date('n', $ganeshTime);
            $gd = (int) date('j', $ganeshTime);
            $festivals[$gm][$gd] = 'Ganesh Chaturthi 🐘';
        }

        // Diwali: New moon between Oct 15 and Nov 15
        $diwaliTime = null;
        foreach ($phases as $p) {
            if ($p['type'] === 'new' && (($p['month'] == 10 && $p['day'] >= 15) || ($p['month'] == 11 && $p['day'] <= 16))) {
                $diwaliTime = strtotime($p['date']);
                $festivals[$p['month']][$p['day']] = 'Diwali 🪔';
                break;
            }
        }

        if ($diwaliTime) {
            // Dussehra: 20 days before Diwali
            $dussehraTime = strtotime('-20 days', $diwaliTime);
            $dm = (int) date('n', $dussehraTime);
            $dd = (int) date('j', $dussehraTime);
            $festivals[$dm][$dd] = 'Dussehra 🏹';

            // Bhai Dooj: 2 days after Diwali
            $bhaiDoojTime = strtotime('+2 days', $diwaliTime);
            $bm = (int) date('n', $bhaiDoojTime);
            $bd = (int) date('j', $bhaiDoojTime);
            $festivals[$bm][$bd] = 'Bhai Dooj 👫';

            // Chhath Puja: 6 days after Diwali
            $chhathTime = strtotime('+6 days', $diwaliTime);
            $cm = (int) date('n', $chhathTime);
            $cd = (int) date('j', $chhathTime);
            $festivals[$cm][$cd] = 'Chhath Puja ☀️';

            // Guru Nanak Jayanti: 15 days after Diwali (Kartik Purnima)
            $guruNanakTime = strtotime('+15 days', $diwaliTime);
            $gm = (int) date('n', $guruNanakTime);
            $gd = (int) date('j', $guruNanakTime);
            $festivals[$gm][$gd] = 'Guru Nanak Jayanti ੴ';
        }

        return $festivals;
    }

    protected function getMoonPhasesForYear(int $year): array
    {
        $phases = [];
        $kStart = floor(($year - 2000) * 12.3685) - 2;
        $kEnd = $kStart + 16;

        for ($k = $kStart; $k <= $kEnd; $k += 0.5) {
            $T = $k / 1236.85;
            $T2 = $T * $T;
            $T3 = $T2 * $T;
            $T4 = $T3 * $T;

            $jde = 2451549.59268 + 29.530588861 * $k + 0.00015437 * $T2 - 0.000000150 * $T3 + 0.00000000073 * $T4;

            $M = deg2rad(2.5534 + 29.10535670 * $k - 0.0000014 * $T2 - 0.00000011 * $T3);
            $Mprime = deg2rad(201.5643 + 385.81693528 * $k + 0.0107588 * $T2 + 0.00001238 * $T3 - 0.000000058 * $T4);
            $F = deg2rad(160.7108 + 390.67050284 * $k - 0.0016118 * $T2 - 0.00000227 * $T3 + 0.000000011 * $T4);

            $isFullMoon = (fmod($k, 1.0) != 0);

            if (!$isFullMoon) {
                $corr = -0.40720 * sin($Mprime) + 0.17241 * sin($M) + 0.01608 * sin(2 * $Mprime) + 0.01039 * sin(2 * $F);
            } else {
                $corr = -0.40614 * sin($Mprime) + 0.17302 * sin($M) + 0.01614 * sin(2 * $Mprime) + 0.01043 * sin(2 * $F);
            }

            $trueJde = $jde + $corr;
            // Indian Standard Time (UTC + 5.5 hours)
            $unixTimestamp = ($trueJde - 2440587.5) * 86400 + (5.5 * 3600);
            $pYear = (int) date('Y', (int) $unixTimestamp);
            $pMonth = (int) date('n', (int) $unixTimestamp);
            $pDay = (int) date('j', (int) $unixTimestamp);

            if ($pYear == $year) {
                $phases[] = [
                    'type' => $isFullMoon ? 'full' : 'new',
                    'date' => sprintf('%04d-%02d-%02d', $pYear, $pMonth, $pDay),
                    'month' => $pMonth,
                    'day' => $pDay,
                ];
            }
        }
        return $phases;
    }
}