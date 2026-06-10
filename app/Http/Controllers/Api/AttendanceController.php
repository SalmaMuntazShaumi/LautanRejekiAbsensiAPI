<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;

use App\Models\Attendance;

class AttendanceController extends Controller
{
    // =========================
    // CLOCK IN
    // =========================
    public function clockIn(Request $request)
    {
        $request->validate([
            'photo' => 'required|image',
        ]);

        $user = $request->user();

        $today = now()->toDateString();

        $attendance = Attendance::firstOrCreate([
            'user_id' => $user->id,
            'date' => $today
        ]);

        if ($attendance->clock_in) {

            return response()->json([
                'message' => 'Sudah clock-in'
            ], 400);
        }

        // status attendance
        $status = now()->format('H:i') > '09:00'
            ? 'late'
            : 'on_time';

        // upload image
        $photoPath = null;

        if ($request->hasFile('photo')) {

            $path = $request->file('photo')->store(
                'attendance',
                'public'
            );

            $photoPath = asset('storage/' . $path);
        }

        $attendance->update([

            'clock_in' => now(),

            'clock_in_lat' => $request->latitude,
            'clock_in_long' => $request->longitude,

            'clock_in_photo' => $photoPath,

            'status' => $status,
        ]);

        return response()->json([
            'message' => 'Clock-in berhasil',
            'data' => $attendance
        ]);
    }

    // =========================
    // TODAY ATTENDANCE
    // =========================
    public function today(Request $request)
    {
        $attendance = Attendance::where(
                'user_id',
                $request->user()->id
            )
            ->whereDate(
                'date',
                now()->toDateString()
            )
            ->first();

        return response()->json([
            'data' => $attendance
        ]);
    }

    // =========================
    // CLOCK OUT
    // =========================
    public function clockOut(Request $request)
    {
        $user = $request->user();

        $today = now()->toDateString();

        $attendance = Attendance::where(
                'user_id',
                $user->id
            )
            ->where('date', $today)
            ->first();

        if (!$attendance || !$attendance->clock_in) {

            return response()->json([
                'message' => 'Belum clock-in'
            ], 400);
        }

        if ($attendance->clock_out) {

            return response()->json([
                'message' => 'Sudah clock-out'
            ], 400);
        }

        $isEarlyOut = now()->format('H:i') < '16:50';

        if ($isEarlyOut) {

            $request->validate([
                'early_out_reason' => 'required|string'
            ]);
        }

        // OPTIONAL PHOTO
        $photoPath = null;

        if ($request->hasFile('photo')) {

            $path = $request->file('photo')->store(
                'attendance',
                'public'
            );

            $photoPath = asset('storage/' . $path);
        }

        $attendance->update([

            'clock_out' => now(),

            'clock_out_lat' => $request->latitude,
            'clock_out_long' => $request->longitude,

            // optional
            'clock_out_photo' => $photoPath,

            'early_out_reason' =>
                $request->early_out_reason,
        ]);

        return response()->json([
            'message' => 'Clock-out berhasil',
            'data' => $attendance
        ]);
    }

    // =========================
    // USER HISTORY (untuk mobile)
    // =========================
    public function userHistory(Request $request)
    {
        $data = Attendance::where('user_id', $request->user()->id)
            ->orderBy('date', 'desc')
            ->get()
            ->map(function ($attendance) {
                return [
                    'id'               => $attendance->id,
                    'date'             => $attendance->date,
                    'status'           => $attendance->status,
                    'clock_in'         => $attendance->clock_in
                        ? \Carbon\Carbon::parse($attendance->clock_in)->format('H:i')
                        : null,
                    'clock_out'        => $attendance->clock_out
                        ? \Carbon\Carbon::parse($attendance->clock_out)->format('H:i')
                        : null,
                    'clock_in_photo'   => $attendance->clock_in_photo,
                    'clock_out_photo'  => $attendance->clock_out_photo,
                    'early_out_reason' => $attendance->early_out_reason,
                ];
            });

        return response()->json(['data' => $data]);
    }

    // =========================
    // HISTORY
    // =========================
    public function history(Request $request)
    {
        $query = Attendance::with('user')
            ->orderBy('date', 'desc');

        // Filter by date jika ada parameter
        if ($request->has('date')) {
            $query->whereDate('date', $request->date);
        } elseif ($request->has('start_date') && $request->has('end_date')) {
            $query->whereBetween('date', [$request->start_date, $request->end_date]);
        } elseif ($request->has('month')) {
            $query->whereYear('date', substr($request->month, 0, 4))
                ->whereMonth('date', substr($request->month, 5, 2));
        } elseif ($request->has('year')) {
            $query->whereYear('date', $request->year);
        } elseif ($request->has('week')) {
            [$year, $week] = explode('-W', $request->week);
            $date = \Carbon\Carbon::now()
                ->setISODate((int)$year, (int)$week)
                ->startOfWeek()
                ->toDateString();
            $query->whereRaw('YEARWEEK(date, 1) = YEARWEEK(?, 1)', [$date]);
        }

        $data = $query->get()->map(function ($attendance) {
            return [
                'id'               => $attendance->id,
                'date'             => $attendance->date,
                'status'           => $attendance->status,
                'nama'             => $attendance->user->name ?? '-',
                'clock_in'         => $attendance->clock_in
                    ? \Carbon\Carbon::parse($attendance->clock_in)->format('H:i')
                    : null,
                'clock_out'        => $attendance->clock_out
                    ? \Carbon\Carbon::parse($attendance->clock_out)->format('H:i')
                    : null,
                'clock_in_photo'   => $attendance->clock_in_photo,
                'clock_out_photo'  => $attendance->clock_out_photo,
                'early_out_reason' => $attendance->early_out_reason,
            ];
        });

        return response()->json(['data' => $data]);
    }
}