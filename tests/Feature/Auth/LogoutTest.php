<?php

declare(strict_types=1);

namespace Tests\Feature\Auth;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class LogoutTest extends TestCase
{
    use RefreshDatabase;

    private const BASE_URL = '/api/auth/logout';

    /**
     * Попытка завершения пользовательского сеанса неавторизованным пользователем
     */
    public function test_unauthorized()
    {
        $response = $this->postJson(self::BASE_URL);
        $response->assertUnauthorized();
        $response->assertJson(['message' => 'Unauthenticated.']);
    }

    /**
     * Успешное завершение пользовательского сеанса
     */
    public function test_success()
    {
        $user = User::factory()->create();
        $user->createToken('auth-token');

        $this->actingAs($user);

        $response = $this->postJson(self::BASE_URL);
        $response->assertOk();

        $this->assertDatabaseCount('personal_access_tokens', 0);
    }
}
