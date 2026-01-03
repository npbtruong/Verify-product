<?php

namespace App\Services;

use App\Mail\OwnerOtpMail;
use App\Models\EmailOtp;
use App\Models\Product;
use Carbon\CarbonImmutable;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Mail;
use Illuminate\Validation\ValidationException;

class EmailOtpService
{
    private const OTP_TTL_MINUTES = 5;
    private const OTP_RESEND_WINDOW_MINUTES = 10;
    private const OTP_MAX_SEND_PER_WINDOW = 3;
    private const OTP_MAX_VERIFY_ATTEMPTS = 5;

    public function sendUpdateOwnerOtp(Product $product, string $email): array
    {
        $normalizedEmail = $this->normalizeEmail($email);

        $now = CarbonImmutable::now();
        $windowStart = $now->subMinutes(self::OTP_RESEND_WINDOW_MINUTES);

        $sentCount = EmailOtp::query()
            ->where('email', $normalizedEmail)
            ->where('purpose', EmailOtp::PURPOSE_UPDATE_OWNER)
            ->where('created_at', '>=', $windowStart)
            ->count();

        if ($sentCount >= self::OTP_MAX_SEND_PER_WINDOW) {
            throw ValidationException::withMessages([
                'email' => ['Bạn đã yêu cầu OTP quá nhiều lần. Vui lòng thử lại sau.'],
            ]);
        }

        $otpCode = $this->generateOtpCode();

        $otp = EmailOtp::create([
            'email' => $normalizedEmail,
            'product_id' => $product->id,
            'code_hash' => Hash::make($otpCode),
            'purpose' => EmailOtp::PURPOSE_UPDATE_OWNER,
            'expires_at' => $now->addMinutes(self::OTP_TTL_MINUTES),
            'attempt_count' => 0,
            'used_at' => null,
        ]);

        Mail::to($normalizedEmail)->send(new OwnerOtpMail(
            product: $product,
            otpCode: $otpCode,
            expiresAt: $otp->expires_at,
        ));

        return [
            'email' => $normalizedEmail,
            'expires_at' => $otp->expires_at?->toIso8601String(),
        ];
    }

    /**
     * Verify OTP without consuming it.
     * Returns a structured result so callers can commit attempt_count updates even when OTP is invalid.
     */
    public function verifyUpdateOwnerOtp(int $productId, string $email, string $otpCode): array
    {
        $normalizedEmail = $this->normalizeEmail($email);
        $now = CarbonImmutable::now();

        /** @var EmailOtp|null $otp */
        $otp = EmailOtp::query()
            ->where('email', $normalizedEmail)
            ->where('product_id', $productId)
            ->where('purpose', EmailOtp::PURPOSE_UPDATE_OWNER)
            ->whereNull('used_at')
            ->where('expires_at', '>=', $now)
            ->orderByDesc('created_at')
            ->lockForUpdate()
            ->first();

        if (!$otp) {
            return [
                'ok' => false,
                'errors' => [
                    'otp' => ['OTP không hợp lệ hoặc đã hết hạn.'],
                ],
            ];
        }

        if ($otp->attempt_count >= self::OTP_MAX_VERIFY_ATTEMPTS) {
            return [
                'ok' => false,
                'errors' => [
                    'otp' => ['Bạn đã nhập sai OTP quá nhiều lần. Vui lòng yêu cầu OTP mới.'],
                ],
            ];
        }

        if (!Hash::check($otpCode, $otp->code_hash)) {
            $otp->attempt_count = (int) $otp->attempt_count + 1;
            $otp->save();

            return [
                'ok' => false,
                'errors' => [
                    'otp' => ['OTP không đúng.'],
                ],
            ];
        }

        return [
            'ok' => true,
            'otp' => $otp,
        ];
    }

    public function markUsed(EmailOtp $otp): void
    {
        $otp->used_at = CarbonImmutable::now();
        $otp->save();
    }

    private function generateOtpCode(): string
    {
        return str_pad((string) random_int(0, 999999), 6, '0', STR_PAD_LEFT);
    }

    private function normalizeEmail(string $email): string
    {
        return mb_strtolower(trim($email));
    }
}
