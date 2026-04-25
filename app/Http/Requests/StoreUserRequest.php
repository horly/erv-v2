<?php

namespace App\Http\Requests;

use App\Models\User;
use App\Rules\StrongPassword;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreUserRequest extends FormRequest
{
    public function authorize(): bool
    {
        $actor = $this->user();

        return $actor?->isSuperadmin() || $actor?->isAdmin();
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'subscription_id' => ['nullable', 'exists:subscriptions,id'],
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'email', 'max:255', 'unique:users,email'],
            'password' => ['required', 'string', new StrongPassword()],
            'role' => ['required', Rule::in(User::ROLES)],
            'address' => ['nullable', 'string'],
            'phone_number' => ['nullable', 'string', 'max:32'],
            'grade' => ['nullable', 'string', 'max:255'],
        ];
    }
}
