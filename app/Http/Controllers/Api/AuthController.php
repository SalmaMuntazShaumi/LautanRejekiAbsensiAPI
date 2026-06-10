<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;    
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Validator;
use App\Models\User;
use App\Services\WhatsappServices;
use Carbon\Carbon;

class AuthController extends Controller
{
    /* REGISTER */
    public function register(Request $request)
    {
        $request->validate([
            'name'      => 'required|string',
            'email'     => 'required|email|unique:users,email',
            'phone'     => 'required|string|unique:users,phone',
            'password'  => 'required|min:6',

            'role'      => 'required|in:Driver,Employee,Supervisor,Admin',
            'birthdate' => 'required|date',
        ]);

        $user = User::create([
            'name'      => $request->name,
            'email'     => $request->email,
            'phone'     => $request->phone,
            'password'  => Hash::make($request->password),

            'role'      => $request->role,
            'birthdate' => $request->birthdate,
        ]);

        $token = $user->createToken('token')->plainTextToken;

        return response()->json([
            'success' => true,
            'message' => 'Register berhasil',
            'token'   => $token,
            'user'    => $user,
        ], 201);
    }

    public function login(Request $request)
    {
        $request->validate([
            'login' => 'required|string',
            'password' => 'required|string',
        ]);

        $login = $request->login;

        // cek apakah email atau phone
        $field = filter_var($login, FILTER_VALIDATE_EMAIL)
            ? 'email'
            : 'phone';

        $user = User::where($field, $login)->first();

        if (!$user || !Hash::check($request->password, $user->password)) {

            return response()->json([
                'success' => false,
                'message' => 'Login gagal',
            ], 401);
        }

        // hapus token lama
        $user->tokens()->delete();

        // buat token baru
        $token = $user->createToken('token')->plainTextToken;

        return response()->json([
            'success' => true,
            'message' => 'Login berhasil',
            'token' => $token,
            'user' => $user,
        ]);
    }

    /* STEP 1: Kirim OTP */
    public function requestOtp(Request $request)
    {
        \Log::info('=== requestOtp dipanggil ===', $request->all());

        $validator = Validator::make($request->all(), [
            'phone' => 'required|string',
        ]);

        if ($validator->fails()) {
            \Log::info('Validasi gagal', $validator->errors()->toArray());
            return response()->json([
                'success' => false,
                'message' => $validator->errors(),
            ], 422);
        }

        $phone = $request->phone;

        // Normalisasi: cari dengan berbagai format
        $user = User::where('phone', $phone)
            ->orWhere('phone', '62' . ltrim($phone, '0'))
            ->orWhere('phone', '0' . ltrim(ltrim($phone, '62'), '0'))
            ->first();
        \Log::info('User ditemukan', ['user' => $user]);

        \Log::info('Phone received', ['phone' => $request->phone]);
        \Log::info('User query', ['result' => User::where('phone', $request->phone)->first()]);

        if (!$user) {
            return response()->json([
                'success' => false,
                'message' => 'Nomor telepon tidak terdaftar.',
            ], 404);
        }

        $otp = str_pad(random_int(0, 999999), 6, '0', STR_PAD_LEFT);
        \Log::info('OTP generated', ['otp' => $otp]);

        $user->update([
            'otp_code'       => $otp,
            'otp_expires_at' => Carbon::now()->addMinutes(5),
        ]);

        \Log::info('Sebelum kirim Zenziva');
        // DEBUG TEMPORARY
        \Log::channel('single')->info('About to call Zenziva', ['phone' => $user->phone, 'otp' => $otp]);
        $sent = WhatsappServices::sendOtp($user->phone, $otp);
        \Log::channel('single')->info('Zenziva result', ['sent' => $sent]);
        $sent = WhatsappServices::sendOtp($user->phone, $otp);
        \Log::info('Setelah kirim Zenziva', ['sent' => $sent]);

        if (!$sent) {
            return response()->json([
                'success' => false,
                'message' => 'Gagal mengirim OTP. Coba lagi.',
            ], 500);
        }

        return response()->json([
            'success' => true,
            'message' => 'OTP berhasil dikirim ke WhatsApp kamu.',
            'otp' => $otp, // sementara untuk debug, hapus nanti
        ], 200);
    }

    /* STEP 2: Verifikasi OTP */
    public function verifyOtp(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'phone' => 'required|string', // ← ganti phone_number → phone
            'otp'   => 'required|string|size:6',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => $validator->errors(),
            ], 422);
        }

        // Cari user berdasarkan nomor telepon
        $user = User::where('phone', $request->phone)->first();

        if (!$user) {
            return response()->json([
                'success' => false,
                'message' => 'Nomor telepon tidak terdaftar.',
            ], 404);
        }

        // Cek apakah OTP cocok
        if ($user->otp_code !== $request->otp) {
            return response()->json([
                'success' => false,
                'message' => 'OTP tidak valid.',
            ], 401);
        }

        // Cek apakah OTP sudah kedaluwarsa
        if (Carbon::now()->isAfter($user->otp_expires_at)) {
            return response()->json([
                'success' => false,
                'message' => 'OTP sudah kedaluwarsa. Silakan minta OTP baru.',
            ], 401);
        }

        // OTP valid — hapus OTP dari database
        $user->update([
            'otp_code'       => null,
            'otp_expires_at' => null,
        ]);

        // Hapus token lama, buat token baru
        $user->tokens()->delete();
        $token = $user->createToken('token')->plainTextToken;

        return response()->json([
            'success' => true,
            'message' => 'Login berhasil.',
            'token'   => $token,
            'user'    => $user,
        ], 200);
    }
}