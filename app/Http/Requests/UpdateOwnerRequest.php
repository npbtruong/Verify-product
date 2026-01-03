<?php

namespace App\Http\Requests;

use App\Models\Product;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Validator;

class UpdateOwnerRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    protected function prepareForValidation(): void
    {
        $this->merge([
            'tag_id' => is_string($this->tag_id) ? trim($this->tag_id) : $this->tag_id,
            'owner_email' => is_string($this->owner_email) ? mb_strtolower(trim($this->owner_email)) : $this->owner_email,
            'owner_email_new' => is_string($this->owner_email_new) ? mb_strtolower(trim($this->owner_email_new)) : $this->owner_email_new,
        ]);
    }

    public function rules(): array
    {
        $isChangeEmail = $this->filled('owner_email_new');

        $rules = [
            'tag_id' => ['required', 'string', 'max:255', 'exists:products,tag_id'],
            'owner_name' => ['required', 'string', 'max:255'],
        ];

        if ($isChangeEmail) {
            $rules['owner_email'] = ['required', 'email', 'max:255']; // old/current
            $rules['owner_email_new'] = ['required', 'email', 'max:255', 'different:owner_email'];
            $rules['otp_code_old'] = ['required', 'digits:6'];
            $rules['otp_code_new'] = ['required', 'digits:6'];
        } else {
            $rules['owner_email'] = ['required', 'email', 'max:255'];
            $rules['otp_code'] = ['required', 'digits:6'];
        }

        return $rules;
    }

    public function withValidator(Validator $validator): void
    {
        $validator->after(function (Validator $validator) {
            if ($validator->errors()->isNotEmpty()) {
                return;
            }

            /** @var Product|null $product */
            $product = Product::where('tag_id', $this->input('tag_id'))->first();
            if (!$product) {
                return;
            }

            $isChangeEmail = $this->filled('owner_email_new');

            // CASE 1: first time set email (DB owner_email = NULL) => must NOT send owner_email_new
            if (empty($product->owner_email)) {
                if ($isChangeEmail) {
                    $validator->errors()->add('owner_email_new', 'Sản phẩm chưa có email chủ sở hữu. Không thể dùng luồng đổi email.');
                }

                return;
            }

            // CASE 2 or 3: DB already has owner_email => owner_email must match current
            if ($this->input('owner_email') !== $product->owner_email) {
                $validator->errors()->add('owner_email', 'Email hiện tại không khớp với chủ sở hữu đang lưu.');
            }
        });
    }
}
