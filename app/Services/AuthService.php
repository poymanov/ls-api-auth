<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\User;
use Exception;
use Illuminate\Auth\Events\PasswordReset;
use Illuminate\Auth\Events\Registered;
use Illuminate\Auth\Events\Verified;
use Illuminate\Contracts\Auth\Authenticatable;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Lang;
use Illuminate\Support\Facades\Password;
use Illuminate\Support\Str;
use Throwable;

class AuthService
{
    /**
     * Создания нового пользователя
     *
     * @param string $name     Имя нового пользователя
     * @param string $email    Email нового пользователя
     * @param string $password Пароль нового пользователя
     */
    public function register(string $name, string $email, string $password): void
    {
        $user = User::create([
            'name'     => $name,
            'email'    => $email,
            'password' => Hash::make($password),
        ]);

        event(new Registered($user));
    }

    /**
     * Подтверждение email пользователя
     *
     * @param int    $userId    Идентификатор пользователя, адрес которого необходимо подтвердить
     * @param string $emailHash Хэш адреса почты, который необходимо подтвердить
     *
     * @throws Exception
     */
    public function verifyEmail(int $userId, string $emailHash): void
    {
        // Если пользователь не найден
        try {
            $user = User::findOrFail($userId);
        } catch (Throwable $e) {
            throw new Exception(Lang::get('auth.verify_email_no_account'));
        }

        // Если адрес уже подтвержден
        if ($user->hasVerifiedEmail()) {
            throw new Exception(Lang::get('auth.verify_email_already_confirmed'));
        }

        // Неправильный email hash
        if (!hash_equals($emailHash,
            sha1($user->getEmailForVerification()))) {
            throw new Exception(Lang::get('auth.verify_email_invalid_hash'));
        }

        if ($user->markEmailAsVerified()) {
            event(new Verified($user));
        } else {
            throw new Exception(Lang::get('auth.verify_email_failed'));
        }
    }

    /**
     * Отправка повторого письма для подтверждения учетной записи пользователя
     *
     * @param string $email Адрес пользователя, который необходимо подтвердить
     *
     * @throws Exception
     */
    public function resendVerifyEmail(string $email)
    {
        // Если пользователь не найден
        try {
            $user = User::where('email', $email)->firstOrFail();
        } catch (Exception $e) {
            throw new Exception(Lang::get('auth.resend_verification_email_no_account'));
        }

        // Если адрес уже подтвержден
        if ($user->hasVerifiedEmail()) {
            throw new Exception(Lang::get('auth.resend_verification_email_already_confirmed'));
        }

        $user->sendEmailVerificationNotification();
    }

    /**
     * Отправка запроса на сброс пароля
     *
     * @param string $email
     *
     * @throws Exception
     */
    public function sendResetPasswordRequest(string $email): void
    {
        $status = Password::sendResetLink(['email' => $email]);

        if ($status === Password::RESET_THROTTLED) {
            throw new Exception(Lang::get('auth.reset_password_request_throttled'));
        } elseif ($status !== Password::RESET_LINK_SENT) {
            throw new Exception(Lang::get('auth.reset_password_request_failed'));
        }
    }

    /**
     * @param string $email
     * @param string $password
     *
     * @return string
     * @throws Exception
     */
    public function login(string $email, string $password): string
    {
        $user = User::where('email', $email)->first();

        if (!$user) {
            throw new Exception(Lang::get('auth.login_invalid_credentials'));
        }

        if (!$user->hasVerifiedEmail()) {
            throw new Exception(Lang::get('auth.login_user_not_verified'));
        }

        if (!Auth::attempt(['email' => $email, 'password' => $password], false)) {
            throw new Exception(Lang::get('auth.login_invalid_credentials'));
        }

        return $user->createToken('auth-token')->plainTextToken;
    }

    /**
     * Сброс старого и добавление нового пароля пользователя
     *
     * @param string $email
     * @param string $password
     * @param string $passwordConfirmation
     * @param string $token
     *
     * @throws Exception
     */
    public function resetPassword(string $email, string $password, string $passwordConfirmation, string $token)
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
            throw new Exception(Lang::get('auth.reset_password_invalid_token'));
        } else {
            if ($status !== Password::PASSWORD_RESET) {
                throw new Exception(Lang::get('auth.reset_password_failed'));
            }
        }
    }

    /**
     * Удаление авторизационных токенов пользователя
     *
     * @param Authenticatable $user Пользователь у которого необходимо удалить токены
     */
    public function logout(Authenticatable $user): void
    {
        $user->tokens()->delete();
    }
}
