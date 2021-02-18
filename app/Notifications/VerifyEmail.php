<?php

namespace App\Notifications;

use Illuminate\Auth\Notifications\VerifyEmail as VerifyEmailBase;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\URL;

class VerifyEmail extends VerifyEmailBase
{
    /**
     * @param mixed $notifiable
     *
     * @return string
     */
    protected function verificationUrl($notifiable): string
    {
        $frontendUrl = config('frontend.url') . config('frontend.email_verify_url');
        $baseUrl     = URL::temporarySignedRoute(
            'auth.verify',
            Carbon::now()->addMinutes(Config::get('auth.verification.expire', 60)),
            [
                'id'   => $notifiable->getKey(),
                'hash' => sha1($notifiable->getEmailForVerification()),
            ],
            false
        );

        $encodedUrl = urlencode($baseUrl);

        return $frontendUrl . $encodedUrl;
    }
}
