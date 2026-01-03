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
            'tag_id' => is_string($this->tag_id) ? trim($this->tag_id) : $this->tag_id,
            'email' => is_string($this->email) ? mb_strtolower(trim($this->email)) : $this->email,
            'purpose' => is_string($this->purpose) ? trim($this->purpose) : $this->purpose,
        ]);
    }

    public function rules(): array
    {
        return [
            'tag_id' => ['required', 'string', 'max:255', 'exists:products,tag_id'],
            'email' => ['required', 'email', 'max:255'],
            'purpose' => ['required', 'in:' . EmailOtp::PURPOSE_UPDATE_OWNER],
        ];
    }
}
