<?php

declare(strict_types=1);

namespace App\Services\Auth;

use Illuminate\Contracts\Auth\Authenticatable;

class LogoutService
{
    /**
     * Удаление авторизационных токенов пользователя
     *
     * @param Authenticatable $user Пользователь у которого необходимо удалить токены
     */
    public function deleteTokens(Authenticatable $user): void
    {
        $user->tokens()->delete();
    }
}
