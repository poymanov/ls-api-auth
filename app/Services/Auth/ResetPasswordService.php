<?php

declare(strict_types=1);

namespace App\Services\Auth;

use Exception;
use Illuminate\Auth\Events\PasswordReset;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Password;
use Illuminate\Support\Str;

class ResetPasswordService
{
    public function reset(string $email, string $password, string $passwordConfirmation, string $token)
    {
        $status = Password::reset(
            [
                'email'                 => $email,
                'password'              => $password,
                'password_confirmation' => $passwordConfirmation,
                'token'                 => $token,
            ],
            function ($user) use ($password) {
                $user->forceFill([
                    'password'       => Hash::make($password),
                    'remember_token' => Str::random(60),
                ])->save();

                event(new PasswordReset($user));
            }
        );

        if ($status === Password::INVALID_TOKEN) {
            throw new Exception('Invalid reset token.');
        } else if ($status !== Password::PASSWORD_RESET) {
            throw new Exception('Error setting a new password.');
        }
    }
}
