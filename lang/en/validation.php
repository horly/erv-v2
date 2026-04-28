<?php

return [
    'array' => 'The :attribute field must be an array.',
    'confirmed' => 'The :attribute confirmation does not match.',
    'date' => 'The :attribute field must be a valid date.',
    'email' => 'The :attribute field must be a valid email address.',
    'exists' => 'The selected :attribute is invalid.',
    'file' => 'The :attribute field must be a file.',
    'in' => 'The selected :attribute is invalid.',
    'integer' => 'The :attribute field must be an integer.',
    'max' => [
        'array' => 'The :attribute field must not have more than :max items.',
        'file' => 'The :attribute field must not be greater than :max kilobytes.',
        'numeric' => 'The :attribute field must not be greater than :max.',
        'string' => 'The :attribute field must not be greater than :max characters.',
    ],
    'mimes' => 'The :attribute field must be a file of type: :values.',
    'min' => [
        'array' => 'The :attribute field must have at least :min items.',
        'file' => 'The :attribute field must be at least :min kilobytes.',
        'numeric' => 'The :attribute field must be at least :min.',
        'string' => 'The :attribute field must be at least :min characters.',
    ],
    'required' => 'The :attribute field is required.',
    'required_with' => 'The :attribute field is required when :values is present.',
    'string' => 'The :attribute field must be a string.',
    'unique' => 'The :attribute has already been taken.',
    'url' => 'The :attribute field must be a valid URL.',

    'password' => [
        'letters' => 'The :attribute field must contain at least one letter.',
        'mixed' => 'The :attribute field must contain at least one uppercase and one lowercase letter.',
        'numbers' => 'The :attribute field must contain at least one number.',
        'symbols' => 'The :attribute field must contain at least one symbol.',
        'uncompromised' => 'The given :attribute has appeared in a data leak. Please choose a different :attribute.',
    ],

    'custom' => [
        'accounts.*.account_number' => [
            'required_with' => 'The account number is required when the bank is filled in.',
        ],
        'accounts.*.currency' => [
            'required_with' => 'The currency is required when the account number is filled in.',
        ],
        'phones.*.label' => [
            'required_with' => 'The label is required when the phone number is filled in.',
        ],
        'phones.*.phone_number' => [
            'required_with' => 'The phone number is required when the label is filled in.',
        ],
    ],

    'attributes' => [
        'accounts.*.bank_name' => 'bank',
        'accounts.*.account_number' => 'account number',
        'accounts.*.currency' => 'currency',
        'address' => 'address',
        'admin_id' => 'administrator',
        'admin_email' => 'administrator email',
        'admin_name' => 'administrator name',
        'admin_subscription_id' => 'subscription',
        'country' => 'country',
        'email' => 'email',
        'expires_at' => 'expiration date',
        'logo' => 'logo',
        'name' => 'name',
        'password' => 'password',
        'password_confirmation' => 'password confirmation',
        'phones.*.label' => 'label',
        'phones.*.phone_number' => 'phone',
        'phone_number' => 'phone',
        'role' => 'role',
        'responsible_id' => 'responsible user',
        'modules' => 'modules',
        'modules.*' => 'module',
        'status' => 'status',
        'currency' => 'currency',
        'subscription_id' => 'subscription',
        'type' => 'type',
        'website' => 'website',
    ],
];
