<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class SendMessageRequest extends FormRequest
{
    public function authorize(): bool
    {
        // Authenticated check is handled by route middleware ('auth')
        // Additional denial check is in the controller
        return true;
    }

    public function rules(): array
    {
        return [
            'receiver_id' => [
                'required',
                'integer',
                'exists:users,id',
                // Cannot send a message to yourself
                function ($attribute, $value, $fail) {
                    if ((int) $value === $this->user()->id) {
                        $fail('You cannot send a message to yourself.');
                    }
                },
            ],
            'body' => [
                'required',
                'string',
                'min:1',
                'max:5000',   // raw input max; sanitizer will trim further
            ],
        ];
    }

    public function messages(): array
    {
        return [
            'receiver_id.exists'   => 'The selected recipient does not exist.',
            'receiver_id.required' => 'A recipient is required.',
            'body.required'        => 'Message body cannot be empty.',
            'body.max'             => 'Message is too long (max 5000 characters).',
        ];
    }
}
