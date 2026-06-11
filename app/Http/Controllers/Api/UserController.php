<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\Rule;


use App\Models\User;

class UserController extends Controller
{
    /* GET USER */
    public function user(Request $request)
{
    $user = $request->user();

    return response()->json([
        'name' => $user->name,
        'company' => $user->company,
        'role' => $user->role,
        'phone' => $user->phone,
        'email' => $user->email,
        'birthdate' => $user->birthdate,

        'photo_url' => $user->photo
            ? asset('storage/' . $user->photo)
            : null,
    ]);
}

    public function index(Request $request)
    {
        $users = User::where('company_id', $request->user()->company_id)
            ->orderBy('name')
            ->get()
            ->map(function (User $user) {
                return [
                    'id' => $user->id,
                    'company_id' => $user->company_id,
                    'name' => $user->name,
                    'role' => $user->role,
                    'email' => $user->email,
                    'phone' => $user->phone,
                    'birthdate' => $user->birthdate,
                    'photo_url' => $user->photo_url,
                ];
            });

        return response()->json([
            'data' => $users,
        ]);
    }

    public function updateProfile(Request $request)
    {
        $user = auth()->user();

        $request->validate([
            'name' => 'required|string|max:255',
            'phone' => [
                'nullable',
                'string',
                'max:20',
                Rule::unique('users', 'phone')
                    ->where('company_id', $user->company_id)
                    ->ignore($user->id),
            ],
            'birthdate' => 'nullable|date',
            'email' => [
                'required',
                'email',
                Rule::unique('users', 'email')
                    ->where('company_id', $user->company_id)
                    ->ignore($user->id),
            ],
            'password' => 'nullable|string|min:8|confirmed',
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
        $user->email = $request->email;
        if ($request->password) {
            $user->password = bcrypt($request->password);
        }
        $user->birthdate = $request->birthdate;

        $user->save();

        return response()->json([
            'message' => 'Profile updated successfully',

            'user' => [
                'name' => $user->name,
                'phone' => $user->phone,
                'birthdate' => $user->birthdate,
                'email' => $user->email,
                'photo_url' => $user->photo
                    ? asset('storage/' . $user->photo)
                    : null,
            ],
        ]);
    }
}
