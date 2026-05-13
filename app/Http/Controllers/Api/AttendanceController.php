<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;

use App\Models\Attendance;

class AttendanceController extends Controller
{
    public function clockIn(Request $request)
    {
        $user = $request->user();

        $today = now()->toDateString();

        $attendance = Attendance::firstOrCreate(
            [
                'user_id' => $user->id,
                'date' => $today
            ]
        );

        if ($attendance->clock_in) {

            return response()->json([
                'message' => 'Sudah clock-in'
            ], 400);
        }

        // status attendance
        $status = now()->format('H:i') > '08:30'
        ? 'Telat'
        : 'Tepat Waktu';

        $attendance->update([
            'clock_in' => now(),
            'clock_in_lat' => $request->latitude,
            'clock_in_long' => $request->longitude,
            'clock_in_photo' => $request->photo,
            'status' => $status,
        ]);

        return response()->json([
            'message' => 'Clock-in berhasil',
            'data' => $attendance
        ]);
    }

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

        $attendance->update([

            'clock_out' => now(),

            'clock_out_lat' => $request->latitude,
            'clock_out_long' => $request->longitude,

            // ✅ NEW
            'clock_out_photo' => $request->photo,
            'early_out_reason' => $request->early_out_reason,
        ]);

        return response()->json([
            'message' => 'Clock-out berhasil',
            'data' => $attendance
        ]);
    }

    // ✅ HISTORY
    public function history(Request $request)
    {
        $data = Attendance::where(
                'user_id',
                $request->user()->id
            )
            ->orderBy('date', 'desc')
            ->get()
            ->map(function ($attendance) {

                return [

                    'id' => $attendance->id,

                    'date' => $attendance->date,

                    'status' => $attendance->status,

                    'clock_in' => $attendance->clock_in
                        ? \Carbon\Carbon::parse(
                            $attendance->clock_in
                        )->format('H:i')
                        : null,

                    'clock_out' => $attendance->clock_out
                        ? \Carbon\Carbon::parse(
                            $attendance->clock_out
                        )->format('H:i')
                        : null,

                    'early_out_reason' =>
                        $attendance->early_out_reason,
                ];
            });

        return Attendance::where('user_id', auth()->id())
            ->latest()
            ->get();
        }
}
