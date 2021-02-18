<?php

declare(strict_types=1);

namespace App\Services\Auth;

use App\Models\User;
use Exception;
use Illuminate\Support\Facades\Auth;

class LoginService
{
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
            throw new Exception('These credentials do not match our records.');
        }

        if (!$user->hasVerifiedEmail()) {
            throw new Exception('Account not verified.');
        }

        if (!Auth::attempt(['email' => $email, 'password' => $password], false)) {
            throw new Exception('These credentials do not match our records.');
        }

        return $user->createToken('auth-token')->plainTextToken;
    }
}
