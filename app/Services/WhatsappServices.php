<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class WhatsappServices
{
    public static function sendOtp(string $phoneNumber, string $otp): bool
    {
        $userkey = env('ZENZIVA_USERKEY');
        $passkey = env('ZENZIVA_PASSKEY');
        $brand   = env('ZENZIVA_BRAND');

        $phone = self::formatPhone($phoneNumber);

        $response = Http::asForm()->post(
            'https://console.zenziva.net/waofficial/api/sendWAOfficial/',
            [
                'userkey' => $userkey,
                'passkey' => $passkey,
                'to'      => $phone,
                'brand'   => $brand,
                'otp'     => $otp,
            ]
        );

        // DEBUG LOG
        Log::info('ZENZIVA OTP RESPONSE', [
            'status_code' => $response->status(),
            'body'        => $response->body(),
            'json'        => $response->json(),
        ]);

        // Kalau request HTTP gagal
        if (!$response->successful()) {
            return false;
        }

        $result = $response->json();

        // Handle response Zenziva
        return isset($result['status']) &&
               (string)$result['status'] === '1';
    }

    private static function formatPhone(string $phone): string
    {
        $phone = preg_replace('/\D/', '', $phone);

        if (str_starts_with($phone, '0')) {
            $phone = '62' . substr($phone, 1);
        }

        return $phone;
    }
}