<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;


use App\Models\User;

class UserController extends Controller
{
    /* GET USER */
    public function user(Request $request)
{
    $user = $request->user();

    return response()->json([
        'name' => $user->name,
        'email' => $user->email,
        'role' => $user->role,
        'phone' => $user->phone,
        'birthdate' => $user->birthdate,

        'photo_url' => $user->photo
            ? asset('storage/' . $user->photo)
            : null,
    ]);
}

    public function updateProfile(Request $request)
    {
        $user = auth()->user();

        $request->validate([
            'name' => 'required|string|max:255',
            'phone' => 'nullable|string|max:20',
            'birthdate' => 'nullable|date',
            'photo' => 'nullable|image|mimes:jpg,jpeg,png|max:2048',
        ]);

        if ($request->hasFile('photo')) {

            // delete old image
            if ($user->photo) {
                Storage::disk('public')->delete($user->photo);
            }

            $path = $request->file('photo')->store(
                'profiles',
                'public'
            );

            $user->photo = $path;
        }

        $user->name = $request->name;
        $user->phone = $request->phone;
        $user->birthdate = $request->birthdate;

        $user->save();

        return response()->json([
            'message' => 'Profile updated successfully',

            'user' => [
                'name' => $user->name,
                'email' => $user->email,
                'phone' => $user->phone,
                'birthdate' => $user->birthdate,

                'photo_url' => $user->photo
                    ? asset('storage/' . $user->photo)
                    : null,
            ],
        ]);
    }
}