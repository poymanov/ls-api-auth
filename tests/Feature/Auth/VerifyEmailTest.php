<?php

declare(strict_types=1);

namespace Tests\Feature\Auth;

use App\Models\User;
use App\Notifications\VerifyEmail;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\URL;
use Symfony\Component\HttpFoundation\Response;
use Tests\TestCase;

class VerifyEmailTest extends TestCase
{
    use RefreshDatabase;

    private const RESEND_URL = '/api/auth/resend-email-verification';

    /**
     * Попытка подтверждения email для несуществующего пользователя
     */
    public function test_not_existed_user()
    {
        $url = $this->getVerificationUrl(99, 'test@test.ru');

        $response = $this->get($url);
        $response->assertStatus(Response::HTTP_UNPROCESSABLE_ENTITY);
        $response->assertJsonFragment(['message' => 'No account found for confirmation.']);
    }

    /**
     * Попытка подтверждения уже подтвержденного email
     */
    public function test_already_verified()
    {
        $user = User::factory()->create();
        $url  = $this->getVerificationUrl($user->id, $user->email);

        $response = $this->get($url);
        $response->assertStatus(Response::HTTP_UNPROCESSABLE_ENTITY);
        $response->assertJsonFragment(['message' => 'The account has already been confirmed.']);
    }

    /**
     * Попытка подтверждения email с неправильным хэшэм
     */
    public function test_wrong_hash()
    {
        $user = User::factory()->unverified()->create();
        $url  = $this->getVerificationUrl($user->id, 'test@test.ru');

        $response = $this->get($url);
        $response->assertStatus(Response::HTTP_UNPROCESSABLE_ENTITY);
        $response->assertJsonFragment(['message' => 'Incorrect data to confirm the account.']);
    }

    /**
     * Успешное подтверждение почты
     */
    public function test_success()
    {
        $user = User::factory()->unverified()->create();
        $url  = $this->getVerificationUrl($user->id, $user->email);

        $response = $this->get($url);
        $response->assertOk();

        $this->assertDatabaseMissing('users', [
            'id'                => $user->id,
            'email_verified_at' => null,
        ]);
    }

    /**
     * Попытка отправки повторного письма для подтверждения email для несуществующего пользователя
     */
    public function test_resend_not_existed_user()
    {
        $response = $this->post(self::RESEND_URL, ['email' => 'test@test.ru']);
        $response->assertStatus(Response::HTTP_UNPROCESSABLE_ENTITY);
        $response->assertJsonFragment(['message' => 'No account found for confirmation.']);
    }

    /**
     * Попытка отправки повторного письма для уже подтвержденного email
     */
    public function test_resend_already_verified()
    {
        $user = User::factory()->create();

        $response = $this->post(self::RESEND_URL, ['email' => $user->email]);
        $response->assertStatus(Response::HTTP_UNPROCESSABLE_ENTITY);
        $response->assertJsonFragment(['message' => 'The account has already been confirmed.']);
    }

    /**
     * Успешная отправка повторного письма для подтвержденния email
     */
    public function test_resend_success()
    {
        Notification::fake();

        $user = User::factory()->unverified()->create();

        $response = $this->post(self::RESEND_URL, ['email' => $user->email]);
        $response->assertOk();

        Notification::assertTimesSent(1, VerifyEmail::class);
    }

    /**
     * Формирование email для подтверждения почты пользователя
     *
     * @param int    $userId Идентификатор пользователя, email которого необходимо подтвердить
     * @param string $email  Адрес, который необходимо подтвердить
     *
     * @return string
     */
    private function getVerificationUrl(int $userId, string $email): string
    {
        return URL::temporarySignedRoute(
            'auth.verify',
            Carbon::now()->addMinutes(Config::get('auth.verification.expire', 60)),
            [
                'id'   => $userId,
                'hash' => sha1($email),
            ],
            false
        );
    }
}
