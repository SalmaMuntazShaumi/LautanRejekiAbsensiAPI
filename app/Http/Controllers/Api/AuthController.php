<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Company;
use App\Models\User;
use App\Services\WhatsappServices;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;

class AuthController extends Controller
{
    public function register(Request $request)
    {
        $company = $this->resolveCompany($request);

        $request->validate([
            'name' => 'required|string',
            'email' => [
                'required',
                'email',
                Rule::unique('users', 'email')->where('company_id', $company->id),
            ],
            'phone' => [
                'required',
                'string',
                Rule::unique('users', 'phone')->where('company_id', $company->id),
            ],
            'password' => 'required|min:6',
            'role' => 'required|in:Driver,Employee,Supervisor,Admin',
            'birthdate' => 'required|date',
        ]);

        $user = User::create([
            'company_id' => $company->id,
            'name' => $request->name,
            'email' => $request->email,
            'phone' => $request->phone,
            'password' => Hash::make($request->password),
            'role' => $request->role,
            'birthdate' => $request->birthdate,
        ]);

        $token = $user->createToken('token')->plainTextToken;

        return response()->json([
            'success' => true,
            'message' => 'Register berhasil',
            'token' => $token,
            'user' => $user,
            'company' => $company,
        ], 201);
    }

    public function login(Request $request)
    {
        $company = $this->resolveCompany($request);

        $request->validate([
            'login' => 'required|string',
            'password' => 'required|string',
        ]);

        $field = filter_var($request->login, FILTER_VALIDATE_EMAIL) ? 'email' : 'phone';

        $user = User::where('company_id', $company->id)
            ->where($field, $request->login)
            ->first();

        if (!$user || !Hash::check($request->password, $user->password)) {
            return response()->json([
                'success' => false,
                'message' => 'Login gagal',
            ], 401);
        }

        $user->tokens()->delete();
        $token = $user->createToken('token')->plainTextToken;

        return response()->json([
            'success' => true,
            'message' => 'Login berhasil',
            'token' => $token,
            'user' => $user,
            'company' => $company,
        ]);
    }

    public function requestOtp(Request $request)
    {
        $company = $this->resolveCompany($request);

        $validator = Validator::make($request->all(), [
            'phone' => 'required|string',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => $validator->errors(),
            ], 422);
        }

        $phone = $request->phone;

        $user = User::where('company_id', $company->id)
            ->where(function ($query) use ($phone) {
                $query->where('phone', $phone)
                    ->orWhere('phone', '62' . ltrim($phone, '0'))
                    ->orWhere('phone', '0' . ltrim(ltrim($phone, '62'), '0'));
            })
            ->first();

        if (!$user) {
            return response()->json([
                'success' => false,
                'message' => 'Nomor telepon tidak terdaftar.',
            ], 404);
        }

        $otp = str_pad(random_int(0, 999999), 6, '0', STR_PAD_LEFT);

        $user->update([
            'otp_code' => $otp,
            'otp_expires_at' => Carbon::now()->addMinutes(5),
        ]);

        $sent = WhatsappServices::sendOtp($user->phone, $otp);

        if (!$sent) {
            return response()->json([
                'success' => false,
                'message' => 'Gagal mengirim OTP. Coba lagi.',
            ], 500);
        }

        return response()->json([
            'success' => true,
            'message' => 'OTP berhasil dikirim ke WhatsApp kamu.',
            'otp' => $otp,
        ], 200);
    }

    public function verifyOtp(Request $request)
    {
        $company = $this->resolveCompany($request);

        $validator = Validator::make($request->all(), [
            'phone' => 'required|string',
            'otp' => 'required|string|size:6',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => $validator->errors(),
            ], 422);
        }

        $user = User::where('company_id', $company->id)
            ->where('phone', $request->phone)
            ->first();

        if (!$user) {
            return response()->json([
                'success' => false,
                'message' => 'Nomor telepon tidak terdaftar.',
            ], 404);
        }

        if ($user->otp_code !== $request->otp) {
            return response()->json([
                'success' => false,
                'message' => 'OTP tidak valid.',
            ], 401);
        }

        if (!$user->otp_expires_at || Carbon::now()->isAfter($user->otp_expires_at)) {
            return response()->json([
                'success' => false,
                'message' => 'OTP sudah kedaluwarsa. Silakan minta OTP baru.',
            ], 401);
        }

        $user->update([
            'otp_code' => null,
            'otp_expires_at' => null,
        ]);

        $user->tokens()->delete();
        $token = $user->createToken('token')->plainTextToken;

        return response()->json([
            'success' => true,
            'message' => 'Login berhasil.',
            'token' => $token,
            'user' => $user,
            'company' => $company,
        ], 200);
    }

    private function resolveCompany(Request $request): Company
    {
        $company = $request->route('company');

        if ($company instanceof Company) {
            abort_unless($company->is_active, 403, 'Perusahaan tidak aktif.');

            return $company;
        }

        if (is_string($company) && $company !== '') {
            $resolvedCompany = Company::where('is_active', true)
                ->where('slug', $company)
                ->firstOrFail();

            return $resolvedCompany;
        }

        $companyQuery = Company::query()->where('is_active', true);

        if ($request->filled('company_slug')) {
            return $companyQuery->where('slug', $request->company_slug)->firstOrFail();
        }

        if ($request->filled('company_id')) {
            return $companyQuery->where('id', $request->company_id)->firstOrFail();
        }

        abort(422, 'company_slug atau company_id wajib diisi, atau gunakan endpoint /companies/{slug}.');
    }
}
