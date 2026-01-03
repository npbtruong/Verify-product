<?php

namespace App\Http\Requests;

use App\Models\EmailOtp;
use Illuminate\Foundation\Http\FormRequest;

class SendOwnerOtpRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    protected function prepareForValidation(): void
    {
        $this->merge([
            'email' => is_string($this->email) ? mb_strtolower(trim($this->email)) : $this->email,
            'purpose' => is_string($this->purpose) ? trim($this->purpose) : $this->purpose,
        ]);
    }

    public function rules(): array
    {
        return [
            'product_id' => ['required', 'integer', 'exists:products,id'],
            'email' => ['required', 'email', 'max:255'],
            'purpose' => ['required', 'in:' . EmailOtp::PURPOSE_UPDATE_OWNER],
        ];
    }
}
