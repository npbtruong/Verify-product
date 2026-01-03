<?php

namespace Tests\Feature;

use App\Models\EmailOtp;
use App\Models\Product;
use App\Models\User;
use Carbon\CarbonImmutable;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class OwnerUpdateTest extends TestCase
{
    use RefreshDatabase;

    private function makeUser(): User
    {
        return User::factory()->create();
    }

    private function makeProduct(User $user, array $overrides = []): Product
    {
        return Product::create(array_merge([
            'tag_id' => Product::generateUniqueTagId(),
            'image_url' => '/storage/products/test.png',
            'describe' => null,
            'uploaded_by' => $user->id,
            'owner_name' => $overrides['owner_name'] ?? null,
            'owner_email' => $overrides['owner_email'] ?? null,
            'owner_email_verified_at' => $overrides['owner_email_verified_at'] ?? null,
        ], $overrides));
    }

    private function seedOtp(int $productId, string $email, string $code, array $overrides = []): EmailOtp
    {
        return EmailOtp::create(array_merge([
            'email' => mb_strtolower(trim($email)),
            'product_id' => $productId,
            'code_hash' => Hash::make($code),
            'purpose' => EmailOtp::PURPOSE_UPDATE_OWNER,
            'expires_at' => CarbonImmutable::now()->addMinutes(5),
            'attempt_count' => 0,
            'used_at' => null,
        ], $overrides));
    }

    public function test_case_1_first_time_update_owner_updates_name_and_email(): void
    {
        $user = $this->makeUser();
        $product = $this->makeProduct($user, [
            'owner_email' => null,
            'owner_name' => null,
        ]);

        $email = 'new.owner@example.com';
        $code = '123456';
        $otp = $this->seedOtp($product->id, $email, $code);

        $response = $this->putJson('/api/owner/update', [
            'tag_id' => $product->tag_id,
            'owner_name' => 'New Owner',
            'owner_email' => $email,
            'otp_code' => $code,
        ]);

        $response->assertStatus(200);

        $product->refresh();
        $otp->refresh();

        $this->assertSame('New Owner', $product->owner_name);
        $this->assertSame(mb_strtolower($email), $product->owner_email);
        $this->assertNotNull($product->owner_email_verified_at);
        $this->assertNotNull($otp->used_at);
    }

    public function test_case_2_same_email_only_updates_owner_name(): void
    {
        $user = $this->makeUser();
        $product = $this->makeProduct($user, [
            'owner_email' => 'current@example.com',
            'owner_name' => 'Old Name',
            'owner_email_verified_at' => null,
        ]);

        $code = '111111';
        $otp = $this->seedOtp($product->id, 'current@example.com', $code);

        $response = $this->putJson('/api/owner/update', [
            'tag_id' => $product->tag_id,
            'owner_name' => 'Updated Name',
            'owner_email' => 'current@example.com',
            'otp_code' => $code,
        ]);

        $response->assertStatus(200);

        $product->refresh();
        $otp->refresh();

        $this->assertSame('Updated Name', $product->owner_name);
        $this->assertSame('current@example.com', $product->owner_email);
        $this->assertNotNull($product->owner_email_verified_at);
        $this->assertNotNull($otp->used_at);
    }

    public function test_case_3_change_email_requires_two_otps_and_updates_email(): void
    {
        $user = $this->makeUser();
        $product = $this->makeProduct($user, [
            'owner_email' => 'old@example.com',
            'owner_name' => 'Old Name',
        ]);

        $otpOld = $this->seedOtp($product->id, 'old@example.com', '222222');
        $otpNew = $this->seedOtp($product->id, 'new@example.com', '333333');

        $response = $this->putJson('/api/owner/update', [
            'tag_id' => $product->tag_id,
            'owner_name' => 'New Name',
            'owner_email' => 'old@example.com',
            'owner_email_new' => 'new@example.com',
            'otp_code_old' => '222222',
            'otp_code_new' => '333333',
        ]);

        $response->assertStatus(200);

        $product->refresh();
        $otpOld->refresh();
        $otpNew->refresh();

        $this->assertSame('New Name', $product->owner_name);
        $this->assertSame('new@example.com', $product->owner_email);
        $this->assertNotNull($product->owner_email_verified_at);
        $this->assertNotNull($otpOld->used_at);
        $this->assertNotNull($otpNew->used_at);
    }

    public function test_expired_otp_is_rejected(): void
    {
        $user = $this->makeUser();
        $product = $this->makeProduct($user, [
            'owner_email' => null,
            'owner_name' => null,
        ]);

        $email = 'owner@example.com';
        $this->seedOtp($product->id, $email, '444444', [
            'expires_at' => CarbonImmutable::now()->subMinute(),
        ]);

        $response = $this->putJson('/api/owner/update', [
            'tag_id' => $product->tag_id,
            'owner_name' => 'Name',
            'owner_email' => $email,
            'otp_code' => '444444',
        ]);

        $response->assertStatus(422);
    }

    public function test_wrong_otp_increments_attempt_count_and_locks_after_limit(): void
    {
        $user = $this->makeUser();
        $product = $this->makeProduct($user, [
            'owner_email' => null,
            'owner_name' => null,
        ]);

        $email = 'owner2@example.com';
        $otp = $this->seedOtp($product->id, $email, '555555');

        for ($i = 1; $i <= 5; $i++) {
            $response = $this->putJson('/api/owner/update', [
                'tag_id' => $product->tag_id,
                'owner_name' => 'Name',
                'owner_email' => $email,
                'otp_code' => '000000',
            ]);

            $response->assertStatus(422);

            $otp->refresh();
            $this->assertSame($i, (int) $otp->attempt_count);
        }

        // 6th attempt should be blocked by attempt limit
        $response = $this->putJson('/api/owner/update', [
            'tag_id' => $product->tag_id,
            'owner_name' => 'Name',
            'owner_email' => $email,
            'otp_code' => '000000',
        ]);

        $response->assertStatus(422);
    }
}
