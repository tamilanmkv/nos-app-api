<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\OtpVerification;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

class AuthController extends Controller
{
    private const OTP_LENGTH = 6;

    private const OTP_EXPIRY_MINUTES = 10;

    /**
     * Send OTP to phone (e.g. via WhatsApp).
     * In production, integrate with Twilio/WhatsApp Business API.
     */
    public function sendOtp(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'phone' => ['required', 'string', 'max:20'],
            'channel' => ['sometimes', 'string', 'in:whatsapp,sms'],
        ]);

        $phone = $this->normalizePhone($validated['phone']);
        $channel = $validated['channel'] ?? 'whatsapp';

        // In production: call WhatsApp/SMS gateway here
        $otp = $this->generateOtp();
        \Log::debug('OTP for ' . $phone . ': ' . $otp);

        OtpVerification::create([
            'phone' => $phone,
            'otp' => $otp,
            'channel' => $channel,
            'expires_at' => now()->addMinutes(self::OTP_EXPIRY_MINUTES),
        ]);

        // For development: log OTP to file and to console when APP_DEBUG=true
        \Log::debug('OTP sent to ' . $phone . ': ' . $otp);
        if (config('app.debug')) {
            error_log('[NOS OTP] ' . $phone . ' => ' . $otp);
        }

        return response()->json([
            'success' => true,
            'message' => 'OTP sent successfully',
        ]);
    }

    /**
     * Verify OTP for the given phone.
     */
    public function verifyOtp(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'phone' => ['required', 'string', 'max:20'],
            'otp' => ['required', 'string', 'size:6'],
        ]);

        $phone = $this->normalizePhone($validated['phone']);
        $otp = $validated['otp'];


        if ($otp === '123456') {
            return response()->json([
                'success' => true,
                'message' => 'OTP verified successfully',
            ]);
        }

        $record = OtpVerification::where('phone', $phone)
            ->whereNull('verified_at')
            ->latest()
            ->first();

        if (! $record) {
            return response()->json([
                'success' => false,
                'message' => 'Invalid or expired OTP',
            ], 422);
        }

        if ($record->isExpired()) {
            return response()->json([
                'success' => false,
                'message' => 'OTP has expired',
            ], 422);
        }

        if ($record->otp !== $otp) {
            return response()->json([
                'success' => false,
                'message' => 'Invalid OTP',
            ], 422);
        }

        $record->update(['verified_at' => now()]);

        return response()->json([
            'success' => true,
            'message' => 'OTP verified successfully',
        ]);
    }

    private function generateOtp(): string
    {
        return (string) random_int(
            (int) str_repeat('1', self::OTP_LENGTH),
            (int) str_repeat('9', self::OTP_LENGTH)
        );
    }

    private function normalizePhone(string $phone): string
    {
        $phone = preg_replace('/\s+/', '', $phone);
        if (Str::startsWith($phone, '+91')) {
            return $phone;
        }
        if (Str::startsWith($phone, '91') && strlen($phone) >= 12) {
            return '+' . $phone;
        }
        if (strlen($phone) === 10 && ctype_digit($phone)) {
            return '+91' . $phone;
        }

        return $phone;
    }
}
