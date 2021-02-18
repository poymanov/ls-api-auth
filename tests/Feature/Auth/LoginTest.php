<?php

declare(strict_types=1);

namespace Tests\Feature\Auth;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Symfony\Component\HttpFoundation\Response;
use Tests\TestCase;

class LoginTest extends TestCase
{
    use RefreshDatabase;

    private const BASE_URL = '/api/auth/login';

    /**
     * Попытка авторизации с пустыми данными
     */
    public function test_validation_empty()
    {
        $this->postJson(self::BASE_URL);

        $response = $this->postJson(self::BASE_URL);
        $response->assertStatus(Response::HTTP_UNPROCESSABLE_ENTITY);
        $response->assertJsonFragment([
            'email'    => ['The email field is required.'],
            'password' => ['The password field is required.'],
        ]);
    }

    /**
     * Попытка авторизации с некорректным email
     */
    public function test_validation_not_valid_email()
    {
        $this->postJson(self::BASE_URL);

        $response = $this->postJson(self::BASE_URL, ['email' => 'test']);
        $response->assertStatus(Response::HTTP_UNPROCESSABLE_ENTITY);
        $response->assertJsonFragment([
            'email' => ['The email must be a valid email address.'],
        ]);
    }

    /**
     * Попытка авторизации несуществующим пользователем
     */
    public function test_not_existed_user()
    {
        $response = $this->postJson(self::BASE_URL, ['email' => 'test@test.ru', 'password' => '123qwe']);
        $response->assertStatus(Response::HTTP_UNPROCESSABLE_ENTITY);
        $response->assertJsonFragment([
            'message' => 'These credentials do not match our records.',
        ]);
    }

    /**
     * Попытка авторизации с неправильными паролем
     */
    public function test_wrong_password()
    {
        $user = User::factory()->create();

        $response = $this->postJson(self::BASE_URL, ['email' => $user->email, 'password' => '123qwe']);
        $response->assertStatus(Response::HTTP_UNPROCESSABLE_ENTITY);
        $response->assertJsonFragment([
            'message' => 'These credentials do not match our records.',
        ]);
    }

    /**
     * Попытка авторизации пользователем с неподтвержденным email
     */
    public function test_not_confirmed_email()
    {
        $user = User::factory()->unverified()->create();

        $response = $this->postJson(self::BASE_URL, ['email' => $user->email, 'password' => 'password']);
        $response->assertStatus(Response::HTTP_UNPROCESSABLE_ENTITY);
        $response->assertJsonFragment([
            'message' => 'Account not verified.',
        ]);
    }

    /**
     * Успешная авторизация
     */
    public function test_success()
    {
        $user = User::factory()->create();

        $response = $this->postJson(self::BASE_URL, ['email' => $user->email, 'password' => 'password']);
        $response->assertOk();
        $response->assertJsonStructure(['access_token']);
    }
}
