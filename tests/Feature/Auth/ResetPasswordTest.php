<?php

declare(strict_types=1);

namespace Tests\Feature\Auth;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Password;
use Tests\TestCase;

class ResetPasswordTest extends TestCase
{
    use RefreshDatabase;

    private const BASE_URL = '/api/auth/reset-password';

    /**
     * Попытка сброса с пустыми данными
     */
    public function test_validation_empty()
    {
        $response = $this->postJson(self::BASE_URL);
        $response->assertStatus(Response::HTTP_UNPROCESSABLE_ENTITY);
        $response->assertJsonFragment([
            'email'    => ['The email field is required.'],
            'password' => ['The password field is required.'],
        ]);
    }

    /**
     * Попытка сброса с некорректным email
     */
    public function test_validation_not_valid_email()
    {
        $response = $this->postJson(self::BASE_URL, ['email' => 'test']);
        $response->assertStatus(Response::HTTP_UNPROCESSABLE_ENTITY);
        $response->assertJsonFragment([
            'email' => ['The email must be a valid email address.'],
        ]);
    }

    /**
     * Попытка сброса со слишком коротким паролем
     */
    public function test_validation_too_short_password()
    {
        $response = $this->postJson(self::BASE_URL, ['password' => '123', 'password_confirmation' => '123']);
        $response->assertStatus(Response::HTTP_UNPROCESSABLE_ENTITY);
        $response->assertJsonFragment([
            'password' => ['The password must be at least 8 characters.'],
        ]);
    }

    /**
     * Попытка сброса с неправильным подтверждением пароля
     */
    public function test_validation_not_valid_password_confirmation()
    {
        $response = $this->postJson(self::BASE_URL, ['password' => 'password', 'password_confirmation' => '123']);
        $response->assertStatus(Response::HTTP_UNPROCESSABLE_ENTITY);
        $response->assertJsonFragment([
            'password' => ['The password confirmation does not match.'],
        ]);
    }

    /**
     * Попытка запроса для несуществующего пользователя
     */
    public function test_not_existed_user()
    {
        $response = $this->postJson(self::BASE_URL, [
            'email'                 => 'test@test.ru',
            'token'                 => '123',
            'password'              => 'password',
            'password_confirmation' => 'password',
        ]);
        $response->assertStatus(Response::HTTP_UNPROCESSABLE_ENTITY);
        $response->assertJsonFragment([
            'email' => ['No account found for password reset.'],
        ]);
    }

    /**
     * Неправильный токен при сбросе пароля
     */
    public function test_wrong_token()
    {
        $user = User::factory()->create();

        $token = Password::createToken($user);

        DB::table('password_resets')->insert([
            'email'      => $user->email,
            'token'      => $token,
            'created_at' => now(),
        ]);

        $response = $this->postJson(self::BASE_URL, [
            'email'                 => $user->email,
            'token'                 => '123',
            'password'              => 'password',
            'password_confirmation' => 'password',
        ]);
        $response->assertStatus(Response::HTTP_UNPROCESSABLE_ENTITY);
        $response->assertJsonFragment(['message' => 'Invalid reset token.']);
    }

    /**
     * Успешный сброс пароля
     */
    public function test_success()
    {
        $user  = User::factory()->create();
        $email = $user->email;

        $token = Password::createToken($user);

        DB::table('password_resets')->insert([
            'email'      => $email,
            'token'      => $token,
            'created_at' => now(),
        ]);

        $response = $this->postJson(self::BASE_URL, [
            'email'                 => $email,
            'token'                 => $token,
            'password'              => 'password',
            'password_confirmation' => 'password',
        ]);

        $response->assertOk();

        $this->assertDatabaseMissing('password_resets', ['email' => $email]);
    }
}
