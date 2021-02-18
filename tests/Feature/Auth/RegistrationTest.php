<?php

declare(strict_types=1);

namespace Tests\Feature\Auth;

use App\Models\User;
use App\Notifications\VerifyEmail;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithFaker;
use Illuminate\Support\Facades\Notification;
use Symfony\Component\HttpFoundation\Response;
use Tests\TestCase;

class RegistrationTest extends TestCase
{
    use RefreshDatabase, WithFaker;

    private const BASE_URL = '/api/auth/registration';

    /**
     * Попытка регистрации с пустыми данными
     */
    public function test_validation_empty()
    {
        $response = $this->postJson(self::BASE_URL);
        $response->assertStatus(Response::HTTP_UNPROCESSABLE_ENTITY);
        $response->assertJsonFragment([
            'name'     => ['The name field is required.'],
            'email'    => ['The email field is required.'],
            'password' => ['The password field is required.'],
        ]);
    }

    /**
     * Попытка регистрации с некорректным email
     */
    public function test_validation_not_valid_email()
    {
        $response = $this->postJson(self::BASE_URL, ['email' => 'test']);
        $response->assertStatus(Response::HTTP_UNPROCESSABLE_ENTITY);
        $response->assertJsonFragment(['email' => ['The email must be a valid email address.']]);
    }

    /**
     * Попытка регистрации с уже существующим email
     */
    public function test_validation_existed_email()
    {
        $user = User::factory()->create(['email' => 'test@test.ru']);

        $response = $this->postJson(self::BASE_URL, ['email' => $user->email]);
        $response->assertStatus(Response::HTTP_UNPROCESSABLE_ENTITY);
        $response->assertJsonFragment(['email' => ['The email has already been taken.']]);
    }

    /**
     * Попытка регистрации со слишком коротким паролем
     */
    public function test_validation_too_short_password()
    {
        $response = $this->postJson(self::BASE_URL, ['password' => '123', 'password_confirmation' => '123']);
        $response->assertStatus(Response::HTTP_UNPROCESSABLE_ENTITY);
        $response->assertJsonFragment(['password' => ['The password must be at least 8 characters.']]);
    }

    /**
     * Попытка регистрации со слишком длинным именем
     */
    public function test_validation_too_long_name()
    {
        $response = $this->postJson(self::BASE_URL, ['name' => $this->faker->realText(1000)]);
        $response->assertStatus(Response::HTTP_UNPROCESSABLE_ENTITY);
        $response->assertJsonFragment(['name' => ['The name may not be greater than 255 characters.']]);
    }

    /**
     * Попытка регистрации со слишком длинным email
     */
    public function test_validation_too_long_email()
    {
        $response = $this->postJson(self::BASE_URL, ['email' => str_repeat('a', 64) . '@' . str_repeat('g', 255) . '.com']);
        $response->assertStatus(Response::HTTP_UNPROCESSABLE_ENTITY);
        $response->assertJsonFragment(['email' => ['The email may not be greater than 255 characters.']]);
    }

    /**
     * Попытка регистрации с неправильным подтверждением пароля
     */
    public function test_validation_not_valid_password_confirmation()
    {
        $response = $this->postJson(self::BASE_URL, ['password' => 'secret123', 'password_confirmation' => 'secret456']);
        $response->assertStatus(Response::HTTP_UNPROCESSABLE_ENTITY);
        $response->assertJsonFragment(['password' => ['The password confirmation does not match.']]);
    }

    /**
     * Успешная регистрация
     */
    public function test_success()
    {
        Notification::fake();

        $name  = 'test';
        $email = 'test@test.ru';

        $response = $this->postJson(self::BASE_URL, [
            'name'                  => $name,
            'email'                 => $email,
            'password'              => 'password',
            'password_confirmation' => 'password',
        ]);

        $response->assertStatus(Response::HTTP_CREATED);

        Notification::assertTimesSent(1, VerifyEmail::class);

        $this->assertDatabaseHas('users', [
            'name'              => $name,
            'email'             => $email,
            'email_verified_at' => null,
        ]);
    }
}
