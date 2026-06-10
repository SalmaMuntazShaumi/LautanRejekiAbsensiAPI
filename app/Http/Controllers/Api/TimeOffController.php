<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Attendance;
use Carbon\Carbon;

use App\Models\TimeOff;

class TimeOffController extends Controller
{
    public function store(Request $request)
    {
        $request->validate([
            'type' => 'required|string',
            'start_date' => 'required|date',
            'end_date' => 'required|date|after_or_equal:start_date',
            'reason' => 'required|string',
        ]);

        $timeOff = TimeOff::create([
            'user_id' => auth()->id(),
            'type' => $request->type,
            'start_date' => $request->start_date,
            'end_date' => $request->end_date,
            'reason' => $request->reason,
            'status' => 'Menunggu Konfirmasi',
        ]);

        return response()->json([
            'message' => 'Time off submitted successfully',
            'data' => $timeOff,
        ], 201);
    }

    public function index()
    {
        $timeOffs = TimeOff::where(
            'user_id',
            auth()->id()
        )
            ->latest()
            ->get();

        return response()->json($timeOffs);
    }

    public function approve($id)
    {
        $timeOff = TimeOff::findOrFail($id);

        $timeOff->status = 'diterima';
        $timeOff->save();

        $startDate = Carbon::parse($timeOff->start_date);
        $endDate = Carbon::parse($timeOff->end_date);

        while ($startDate <= $endDate) {

            Attendance::firstOrCreate(

                [
                    'user_id' => $timeOff->user_id,
                    'date' => $startDate->format('Y-m-d'),
                ],

                [
                    'clock_in' => null,
                    'clock_out' => null,

                    'status' => 'leave',
                ]
            );

            $startDate->addDay();
        }

        return response()->json([
            'message' => 'Time off approved successfully'
        ]);
    }

    public function reject($id)
    {
        $timeOff = TimeOff::findOrFail($id);

        $timeOff->status = 'ditolak';
        $timeOff->save();

        return response()->json([
            'message' => 'Time off rejected successfully'
        ]);
    }
    
    public function adminIndex()
    {
        $timeOffs = TimeOff::with('user')
            ->latest()
            ->get()
            ->map(function ($t) {
                return [
                    'id'         => $t->id,
                    'nama'       => $t->user->name ?? '-',
                    'type'       => $t->type,
                    'start_date' => $t->start_date,
                    'end_date'   => $t->end_date,
                    'reason'     => $t->reason,
                    'status'     => $t->status,
                ];
            });
    
        return response()->json(['data' => $timeOffs]);
    }
}