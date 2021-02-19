<?php

declare(strict_types=1);

namespace App\Services\Auth;

use Exception;
use Illuminate\Support\Facades\Password;

class ForgotPasswordService
{
    /**
     * Отправка запроса на сброс пароля
     *
     * @param string $email
     *
     * @throws Exception
     */
    public function sendRequest(string $email): void
    {
        $status = Password::sendResetLink(['email' => $email]);

        if ($status === Password::RESET_THROTTLED) {
            throw new Exception('The password reset has been requested previously.');
        } elseif ($status !== Password::RESET_LINK_SENT) {
            throw new Exception('Error sending a link to create a new password.');
        }
    }
}
