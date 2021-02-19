<?php

declare(strict_types=1);

namespace Tests\Feature\Auth;

use App\Models\User;
use App\Notifications\ResetPassword;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Notification;
use Symfony\Component\HttpFoundation\Response;
use Tests\TestCase;

class ForgotPasswordTest extends TestCase
{
    use RefreshDatabase;

    private const BASE_URL = '/api/auth/forgot-password';

    /**
     * Попытка запроса с пустыми данными
     */
    public function test_validation_empty()
    {
        $response = $this->postJson(self::BASE_URL);
        $response->assertStatus(Response::HTTP_UNPROCESSABLE_ENTITY);
        $response->assertJsonFragment(['email' => ['The email field is required.']]);
    }

    /**
     * Попытка запроса с некорректным email
     */
    public function test_validation_not_valid_email()
    {
        $response = $this->postJson(self::BASE_URL, ['email' => 'test']);
        $response->assertStatus(Response::HTTP_UNPROCESSABLE_ENTITY);
        $response->assertJsonFragment(['email' => ['The email must be a valid email address.']]);
    }

    /**
     * Попытка запроса для несуществующего пользователя
     */
    public function test_not_existed_user()
    {
        $response = $this->postJson(self::BASE_URL, ['email' => 'test@test.ru']);
        $response->assertStatus(Response::HTTP_UNPROCESSABLE_ENTITY);
        $response->assertJsonFragment(['email' => ['No account found for password reset.']]);
    }

    /**
     * Запрос пароля уже был создан ранее
     */
    public function test_already_requested()
    {
        $user = User::factory()->create();

        DB::table('password_resets')->insert([
            'email'      => $user->email,
            'token'      => 123,
            'created_at' => now(),
        ]);

        $response = $this->postJson(self::BASE_URL, ['email' => $user->email]);
        $response->assertStatus(Response::HTTP_UNPROCESSABLE_ENTITY);
        $response->assertJsonFragment(['message' => 'The password reset has been requested previously.']);
    }

    /**
     * Успешный запрос сброса пароля
     */
    public function test_success()
    {
        Notification::fake();

        $user = User::factory()->create();

        $response = $this->postJson(self::BASE_URL, ['email' => $user->email]);
        $response->assertOk();

        $this->assertDatabaseHas('password_resets', [
            'email' => $user->email,
        ]);

        Notification::assertTimesSent(1, ResetPassword::class);
    }
}
