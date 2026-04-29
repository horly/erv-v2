<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Contracts\Auth\Authenticatable;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;
use Illuminate\Validation\Rules\Password;
use Laravel\Fortify\Actions\ConfirmTwoFactorAuthentication;
use Laravel\Fortify\Actions\DisableTwoFactorAuthentication;
use Laravel\Fortify\Actions\EnableTwoFactorAuthentication;

class ProfileController extends Controller
{
    public function edit(): View
    {
        /** @var User&Authenticatable $user */
        $user = Auth::user();

        return view('profile.edit', [
            'user' => $user,
        ]);
    }

    public function updateInformation(Request $request): RedirectResponse
    {
        /** @var User&Authenticatable $user */
        $user = Auth::user();

        $validated = Validator::make($request->all(), [
            'name' => ['required', 'string', 'max:255'],
            'phone_number' => ['nullable', 'string', 'max:50'],
            'grade' => ['nullable', 'string', 'max:255'],
            'address' => ['nullable', 'string', 'max:1000'],
        ])->validateWithBag('updateProfileInformation');

        $user->forceFill($validated)->save();

        return back()->with('profile_status', 'information-updated');
    }

    public function updatePhoto(Request $request): RedirectResponse
    {
        /** @var User&Authenticatable $user */
        $user = Auth::user();

        $validated = Validator::make($request->all(), [
            'cropped_photo' => ['required', 'string'],
        ], [
            'cropped_photo.required' => __('profile.photo_required'),
        ])->validateWithBag('updateProfilePhoto');

        if (! preg_match('/^data:image\/(jpeg|jpg|png|webp);base64,/', $validated['cropped_photo'], $matches)) {
            return back()
                ->withErrors(['cropped_photo' => __('profile.photo_invalid')], 'updateProfilePhoto')
                ->withInput();
        }

        $binary = base64_decode(substr($validated['cropped_photo'], strpos($validated['cropped_photo'], ',') + 1), true);

        if ($binary === false || strlen($binary) > 4 * 1024 * 1024) {
            return back()
                ->withErrors(['cropped_photo' => __('profile.photo_invalid')], 'updateProfilePhoto')
                ->withInput();
        }

        $imageInfo = @getimagesizefromstring($binary);

        if ($imageInfo === false || ! in_array($imageInfo['mime'] ?? '', ['image/jpeg', 'image/png', 'image/webp'], true)) {
            return back()
                ->withErrors(['cropped_photo' => __('profile.photo_invalid')], 'updateProfilePhoto')
                ->withInput();
        }

        if ($user->profile_photo_path) {
            Storage::disk('public')->delete($user->profile_photo_path);
        }

        $extension = match ($imageInfo['mime']) {
            'image/png' => 'png',
            'image/webp' => 'webp',
            default => 'jpg',
        };
        $path = 'profile-photos/'.$user->id.'-'.uniqid().'.'.$extension;
        Storage::disk('public')->put($path, $binary);

        $user->forceFill([
            'profile_photo_path' => $path,
        ])->save();

        return back()->with('profile_status', 'photo-updated');
    }

    public function updateEmail(Request $request): RedirectResponse
    {
        /** @var User&Authenticatable $user */
        $user = Auth::user();

        $validated = Validator::make($request->all(), [
            'email' => [
                'required',
                'string',
                'email',
                'max:255',
                Rule::unique('users')->ignore($user->id),
            ],
            'current_password' => ['required', 'string', 'current_password:web'],
        ], [
            'current_password.current_password' => __('profile.current_password_error'),
        ])->validateWithBag('updateEmail');

        $user->forceFill([
            'email' => $validated['email'],
        ])->save();

        return back()->with('profile_status', 'email-updated');
    }

    public function updatePassword(Request $request): RedirectResponse
    {
        /** @var User&Authenticatable $user */
        $user = Auth::user();

        $validated = Validator::make($request->all(), [
            'current_password' => ['required', 'string', 'current_password:web'],
            'password' => [
                'required',
                'string',
                'confirmed',
                Password::min(12)->mixedCase()->letters()->numbers()->symbols(),
            ],
        ], [
            'current_password.current_password' => __('profile.current_password_error'),
        ])->validateWithBag('updatePassword');

        $user->forceFill([
            'password' => $validated['password'],
        ])->save();

        return back()->with('profile_status', 'password-updated');
    }

    public function enableTwoFactor(Request $request, EnableTwoFactorAuthentication $enableTwoFactorAuthentication): RedirectResponse
    {
        /** @var User&Authenticatable $user */
        $user = Auth::user();

        if ($user->hasEnabledTwoFactorAuthentication()) {
            return back()->with('profile_status', 'two-factor-enabled');
        }

        $enableTwoFactorAuthentication($user);

        return back()->with('profile_status', 'two-factor-started');
    }

    public function confirmTwoFactor(Request $request, ConfirmTwoFactorAuthentication $confirmTwoFactorAuthentication): RedirectResponse
    {
        /** @var User&Authenticatable $user */
        $user = Auth::user();
        $throttleKey = 'profile-two-factor-confirm:'.$user->getAuthIdentifier();

        if (RateLimiter::tooManyAttempts($throttleKey, 5)) {
            return back()
                ->withErrors(['code' => __('profile.two_factor_rate_limited')], 'confirmTwoFactorAuthentication')
                ->withInput();
        }

        $validated = Validator::make($request->all(), [
            'code' => ['required', 'digits:6'],
        ], [
            'code.required' => __('profile.two_factor_code_required'),
            'code.digits' => __('profile.two_factor_code_digits'),
        ])->validateWithBag('confirmTwoFactorAuthentication');

        try {
            $confirmTwoFactorAuthentication($user, $validated['code']);
        } catch (ValidationException) {
            RateLimiter::hit($throttleKey, 60);

            return back()
                ->withErrors(['code' => __('profile.two_factor_invalid_code')], 'confirmTwoFactorAuthentication')
                ->withInput();
        }

        RateLimiter::clear($throttleKey);

        return back()->with('profile_status', 'two-factor-enabled');
    }

    public function disableTwoFactor(Request $request, DisableTwoFactorAuthentication $disableTwoFactorAuthentication): RedirectResponse
    {
        /** @var User&Authenticatable $user */
        $user = Auth::user();

        Validator::make($request->all(), [
            'current_password' => ['required', 'string', 'current_password:web'],
        ], [
            'current_password.required' => __('profile.current_password_required'),
            'current_password.current_password' => __('profile.current_password_error'),
        ])->validateWithBag('disableTwoFactorAuthentication');

        $disableTwoFactorAuthentication($user);

        return back()->with('profile_status', 'two-factor-disabled');
    }
}
