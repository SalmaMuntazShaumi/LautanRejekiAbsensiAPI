<?php
namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\DriverLocation;
use App\Models\User;
use Illuminate\Http\Request;

class DriverLocationController extends Controller
{
    public function start(Request $request)
    {
        $request->validate([
            'latitude' => 'required|numeric',
            'longitude' => 'required|numeric',
        ]);
    
        $location = DriverLocation::updateOrCreate(
            ['user_id' => $request->user()->id],
            [
                'start_latitude'  => $request->latitude,  // ← simpan start location
                'start_longitude' => $request->longitude,
                'latitude'        => $request->latitude,
                'longitude'       => $request->longitude,
                'status'          => 'on_delivery',
                'started_at'      => now(),
                'arrived_at'      => null,
            ]
        );
    
        return response()->json(['success' => true, 'data' => $location]);
    }

    // Driver update lokasi (dipanggil periodik)
    public function update(Request $request)
    {
        $request->validate([
            'latitude' => 'required|numeric',
            'longitude' => 'required|numeric',
        ]);

        $location = DriverLocation::updateOrCreate(
            ['user_id' => $request->user()->id],
            [
                'latitude' => $request->latitude,
                'longitude' => $request->longitude,
            ]
        );

        return response()->json(['success' => true, 'data' => $location]);
    }

    // Driver selesai
    public function finish(Request $request)
    {
        $location = DriverLocation::where('user_id', $request->user()->id)->first();

        if ($location) {
            $location->update([
                'status' => 'arrived',
                'arrived_at' => now(),
            ]);
        }

        return response()->json(['success' => true, 'data' => $location]);
    }

    // Admin: lihat semua driver aktif
    public function activeDrivers()
    {
        $drivers = DriverLocation::with('user')
            ->where('status', 'on_delivery')
            ->get()
            ->map(fn($loc) => [
                'driver_id' => $loc->user_id,
                'name' => $loc->user->name,
                'latitude' => $loc->latitude,
                'longitude' => $loc->longitude,
                'started_at' => $loc->started_at,
                'status' => $loc->status,
            ]);

        return response()->json(['success' => true, 'data' => $drivers]);
    }

    // Admin: lihat lokasi driver tertentu
    public function driverLocation($driverId)
    {
        $location = DriverLocation::with('user')
            ->where('user_id', $driverId)
            ->first();

        if (!$location) {
            return response()->json(['success' => false, 'message' => 'Driver not found'], 404);
        }

        return response()->json(['success' => true, 'data' => $location]);
    }
    
    // Tambah method history
    public function history(Request $request)
    {
        $history = DriverLocation::with('user')
            ->whereNotNull('started_at')
            ->orderBy('started_at', 'desc')
            ->get()
            ->map(fn($loc) => [
                'id'           => $loc->id,
                'driver_id'    => $loc->user_id,
                'name'         => $loc->user->name,
                'start_lat'    => $loc->start_latitude ?? $loc->latitude,
                'start_lng'    => $loc->start_longitude ?? $loc->longitude,
                'end_lat'      => $loc->latitude,
                'end_lng'      => $loc->longitude,
                'status'       => $loc->status,
                'started_at'   => $loc->started_at,
                'arrived_at'   => $loc->arrived_at,
            ]);
    
        return response()->json(['success' => true, 'data' => $history]);
    }
}