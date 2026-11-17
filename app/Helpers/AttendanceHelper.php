<?php

namespace App\Helpers;

use App\Models\Holiday;
use Carbon\CarbonPeriod;

class AttendanceHelper
{
    /**
     * Get Holiday Dates
     */
    public static function getHolidays($startDate, $endDate)
    {
        $tenantId = getTenantId();
        $holidays = Holiday::where(function ($q) use ($startDate, $endDate) {
            $q->whereBetween('from_date', [$startDate, $endDate])
              ->orWhereBetween('to_date', [$startDate, $endDate]);
        })->where('tenant_id', $tenantId)->get();

        $holidayDates = [];

        foreach ($holidays as $holiday) {
            $period = CarbonPeriod::create($holiday->from_date, $holiday->to_date);

            foreach ($period as $date) {
                $holidayDates[] = $date->format('Y-m-d');
            }
        }

        $holidayDates = array_unique($holidayDates);

        return [
            'holidayDates' => $holidayDates,
            'totalHoliday' => count($holidayDates), // ✔ better than count($holidays)
        ];
    }

    /**
     * Get WeekOff & Holiday Count
     */
    public static function getPeriodData($startDate, $endDate, $holidayDates, $monthAttendance)
    {
        $period = CarbonPeriod::create($startDate, $endDate);
        $dates = [];
        foreach ($period as $date) {
            $dates[] = $date->format('Y-m-d');
        }

        $week_off = $holiday = $totalWeekOff = $totalHoliday = 0;

        // Step 1: Pre-calculate the "Raw Status" of each day
        // 'candidate' = Sunday or Holiday
        // 'worked'    = Physically present or compensate/half day
        // 'not_worked' = Leave (even paid) or Absent
        $dayStatus = [];
        foreach ($dates as $key) {
            $isSunday  = \Carbon\Carbon::parse($key)->isSunday();
            $isHoliday = in_array($key, $holidayDates);

            $attendance = $monthAttendance[$key] ?? null;
            $status = $attendance->status ?? null;

            // Note: 'paid' status is treated as "not worked" for sandwich triggering 
            // because it is an approved leave.
            $isWorked = in_array($status, ['present', 'compensate', 'half_day']);

            if ($isSunday || $isHoliday) {
                $dayStatus[$key] = 'candidate';
            } elseif ($isWorked) {
                $dayStatus[$key] = 'worked';
            } else {
                $dayStatus[$key] = 'not_worked';
            }
        }

        // Step 2: Identify Sandwiched Candidates
        $isSandwiched = [];
        foreach ($dates as $index => $key) {
            if ($dayStatus[$key] === 'candidate') {
                // Find nearest preceding non-candidate
                $prevStatus = 'worked'; // Boundary default
                for ($i = $index - 1; $i >= 0; $i--) {
                    if ($dayStatus[$dates[$i]] !== 'candidate') {
                        $prevStatus = $dayStatus[$dates[$i]];
                        break;
                    }
                }

                // Find nearest succeeding non-candidate
                $nextStatus = 'worked'; // Boundary default
                for ($i = $index + 1; $i < count($dates); $i++) {
                    if ($dayStatus[$dates[$i]] !== 'candidate') {
                        $nextStatus = $dayStatus[$dates[$i]];
                        break;
                    }
                }

                $isSandwiched[$key] = ($prevStatus === 'not_worked' && $nextStatus === 'not_worked');
            } else {
                $isSandwiched[$key] = false;
            }
        }

        // Step 3: Accumulate counts, skipping sandwiched days
        foreach ($dates as $key) {
            if ($isSandwiched[$key]) {
                continue; // Skip: This day is effectively a "Working Day" now (will count as absent)
            }

            $dateObj = \Carbon\Carbon::parse($key);
            $isSunday  = $dateObj->isSunday();
            $isHoliday = in_array($key, $holidayDates);

            $attendance = $monthAttendance[$key] ?? null;
            $status = $attendance->status ?? null;

            $workedFull = in_array($status, ['present', 'paid', 'compensate']);
            
            $workedHalf = $status === 'half_day';
            $isHoliday = $status === 'holiday';

            // ===== HOLIDAY =====
            if ($isHoliday) {
                $totalHoliday++;
                if ($workedHalf) {
                    $holiday += 0.5;
                } elseif (!$workedFull) {
                    $holiday += 1;
                }
                continue; // prevent double count
            }

            // ===== SUNDAY =====
            if ($isSunday) {
                $totalWeekOff++;
                if ($workedHalf) {
                    $week_off += 0.5;
                } elseif (!$workedFull) {
                    $week_off += 1;
                }
            }
        }

        return [
            'totalWeekOff' => $totalWeekOff,
            'week_off' => $week_off,
            'totalHoliday' => $totalHoliday,
            'holiday' => $holiday,
        ];
    }

    public static function dateJoining($employee, $startDate, $endDate, $empTotalDays){
    
        if ($employee->date_of_joining) {
                
            $joiningDate = \Carbon\Carbon::parse($employee->date_of_joining);
            $start = \Carbon\Carbon::parse($startDate);
            $end = \Carbon\Carbon::parse($endDate);

            if ($joiningDate->between($start, $end)) {
                $startDate = $joiningDate->format('Y-m-d');
                $start = $joiningDate;
                $empTotalDays = $start->diffInDays($end) + 1;    
                
                $empTotalDays = round($empTotalDays);             
            } 
        }
        return array('empTotalDays' => $empTotalDays, 'startDate' => $startDate);
    }

    public static function getOffboarding($offboarding, $startDate, $endDate, $empTotalDays){
        $empEndDate = $endDate;
        if ($offboarding && $offboarding->last_working_day) {
                
            $lwd = \Carbon\Carbon::parse($offboarding->last_working_day);
            $start = \Carbon\Carbon::parse($startDate);
            $end = \Carbon\Carbon::parse($endDate);

            if ($lwd->between($start, $end)) {
                $empEndDate = $lwd->format('Y-m-d');
                $empTotalDays = $start->diffInDays($lwd) + 1;
                
                $empTotalDays = round($empTotalDays);
            } 
        }
        
        return array('empTotalDays' => $empTotalDays, 'empEndDate' => $empEndDate);
    }

    public static function formatMinutes($minutes)
    {
        if (!$minutes) {
            return '0h 0m';
        }

        $hours = floor($minutes / 60);
        $mins = $minutes % 60;

        return "{$hours} Hrs {$mins} Mins";
    }

    
    public static function isTodayHoliday()
    {
        $tenantId = getTenantId();
        $today = \Carbon\Carbon::today()->format('Y-m-d');

        $holiday = Holiday::whereDate('from_date', '<=', $today)
            ->whereDate('to_date', '>=', $today)
            ->where('tenant_id', $tenantId)
            ->where('status', 1)
            ->first();

        return $holiday;
    }
}