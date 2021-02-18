<?php

declare(strict_types=1);

namespace App\Services\Auth;

use App\Models\User;
use Exception;
use Illuminate\Auth\Events\Registered;
use Illuminate\Auth\Events\Verified;
use Illuminate\Support\Facades\Hash;
use Throwable;

class RegisterService
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
            throw new Exception('No account found for confirmation.');
        }

        // Если адрес уже подтвержден
        if ($user->hasVerifiedEmail()) {
            throw new Exception('The account has already been confirmed.');
        }

        // Неправильный email hash
        if (!hash_equals($emailHash,
            sha1($user->getEmailForVerification()))) {
            throw new Exception('Incorrect data to confirm the account.');
        }

        if ($user->markEmailAsVerified()) {
            event(new Verified($user));
        } else {
            throw new Exception('Account confirmation error.');
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
            throw new Exception('No account found for confirmation.');
        }

        // Если адрес уже подтвержден
        if ($user->hasVerifiedEmail()) {
            throw new Exception('The account has already been confirmed.');
        }

        $user->sendEmailVerificationNotification();
    }
}
