<?php

namespace App\Mail;

use App\Models\Product;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class OwnerOtpMail extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(
        public Product $product,
        public string $otpCode,
        public $expiresAt,
    ) {
    }

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: 'OTP xác thực cập nhật chủ sở hữu',
        );
    }

    public function content(): Content
    {
        return new Content(
            view: 'emails.owner_otp',
            with: [
                'product' => $this->product,
                'otpCode' => $this->otpCode,
                'expiresAt' => $this->expiresAt,
            ],
        );
    }
}
