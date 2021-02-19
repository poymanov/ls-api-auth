<?php

declare(strict_types=1);

namespace App\Notifications;

use Illuminate\Auth\Notifications\ResetPassword as ResetPasswordBase;

class ResetPassword extends ResetPasswordBase
{
    public static $createUrlCallback = [self::class, 'createActionUrl'];

    public static function createActionUrl($notifiable, $token)
    {
        return config('frontend.url')
            . config('frontend.reset_password_url')
            . '?token=' . $token . '&email=' . $notifiable->getEmailForPasswordReset();
    }
}
