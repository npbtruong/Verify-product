<?php

namespace App\Http\Controllers;

use App\Http\Requests\SendOwnerOtpRequest;
use App\Http\Requests\UpdateOwnerRequest;
use App\Models\Product;
use App\Services\EmailOtpService;
use Illuminate\Support\Facades\DB;

class OwnerController extends Controller
{
    public function __construct(private readonly EmailOtpService $emailOtpService)
    {
    }

    /**
     * POST /api/owner/send-otp
     * Send OTP to email (no DB update on products).
     */
    public function sendOtp(SendOwnerOtpRequest $request)
    {
        $product = Product::where('tag_id', (string) $request->input('tag_id'))->firstOrFail();

        $result = $this->emailOtpService->sendUpdateOwnerOtp(
            product: $product,
            email: (string) $request->input('email'),
        );

        return response()->json([
            'message' => 'OTP đã được gửi. Vui lòng kiểm tra email.',
            'data' => $result,
        ]);
    }

    /**
     * PUT /api/owner/update
     * Verify OTP then update owner info.
     */
    public function update(UpdateOwnerRequest $request)
    {
        $ownerName = (string) $request->input('owner_name');

        $product = Product::where('tag_id', (string) $request->input('tag_id'))->firstOrFail();

        $isChangeEmail = $request->filled('owner_email_new');

        $result = DB::transaction(function () use ($request, $product, $ownerName, $isChangeEmail) {
            // CASE 1: First time update (owner_email NULL)
            if (empty($product->owner_email)) {
                $ownerEmail = (string) $request->input('owner_email');
                $otpCode = (string) $request->input('otp_code');

                $verify = $this->emailOtpService->verifyUpdateOwnerOtp(
                    productId: $product->id,
                    email: $ownerEmail,
                    otpCode: $otpCode,
                );

                if (!$verify['ok']) {
                    return ['ok' => false, 'errors' => $verify['errors']];
                }

                $otp = $verify['otp'];

                $product->owner_name = $ownerName;
                $product->owner_email = $ownerEmail;
                $product->owner_email_verified_at = now();
                $product->save();

                $this->emailOtpService->markUsed($otp);

                return ['ok' => true, 'product' => $product];
            }

            // CASE 3: Change email (verify OTP for old and new)
            if ($isChangeEmail) {
                $ownerEmailOld = (string) $request->input('owner_email');
                $ownerEmailNew = (string) $request->input('owner_email_new');

                $verifyOld = $this->emailOtpService->verifyUpdateOwnerOtp(
                    productId: $product->id,
                    email: $ownerEmailOld,
                    otpCode: (string) $request->input('otp_code_old'),
                );

                if (!$verifyOld['ok']) {
                    return ['ok' => false, 'errors' => $verifyOld['errors']];
                }

                $verifyNew = $this->emailOtpService->verifyUpdateOwnerOtp(
                    productId: $product->id,
                    email: $ownerEmailNew,
                    otpCode: (string) $request->input('otp_code_new'),
                );

                if (!$verifyNew['ok']) {
                    return ['ok' => false, 'errors' => $verifyNew['errors']];
                }

                $otpOld = $verifyOld['otp'];
                $otpNew = $verifyNew['otp'];

                $product->owner_name = $ownerName;
                $product->owner_email = $ownerEmailNew;
                $product->owner_email_verified_at = now();
                $product->save();

                $this->emailOtpService->markUsed($otpOld);
                $this->emailOtpService->markUsed($otpNew);

                return ['ok' => true, 'product' => $product];
            }

            // CASE 2: Same email (verify current email OTP, update name only)
            $ownerEmail = (string) $request->input('owner_email');
            $otpCode = (string) $request->input('otp_code');

            $verify = $this->emailOtpService->verifyUpdateOwnerOtp(
                productId: $product->id,
                email: $ownerEmail,
                otpCode: $otpCode,
            );

            if (!$verify['ok']) {
                return ['ok' => false, 'errors' => $verify['errors']];
            }

            $otp = $verify['otp'];

            $product->owner_name = $ownerName;
            if (empty($product->owner_email_verified_at)) {
                $product->owner_email_verified_at = now();
            }
            $product->save();

            $this->emailOtpService->markUsed($otp);

            return ['ok' => true, 'product' => $product];
        });

        if (!$result['ok']) {
            return response()->json([
                'message' => 'Xác thực OTP thất bại',
                'errors' => $result['errors'],
            ], 422);
        }

        $updatedProduct = $result['product'];

        return response()->json([
            'message' => 'Cập nhật chủ sở hữu thành công',
            'product' => [
                'id' => $updatedProduct->id,
                'tag_id' => $updatedProduct->tag_id,
                'owner_name' => $updatedProduct->owner_name,
                'owner_email' => $updatedProduct->owner_email,
                'owner_email_verified_at' => optional($updatedProduct->owner_email_verified_at)->toIso8601String(),
            ],
        ]);
    }
}
