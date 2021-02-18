<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ProfileTest extends TestCase
{
    use RefreshDatabase;

    private const BASE_URL = '/api/profile';

    /**
     * Попытка получения профиля неавторизованным пользователем
     */
    public function test_unauthorized()
    {
        $response = $this->getJson(self::BASE_URL);
        $response->assertUnauthorized();
        $response->assertJson(['message' => 'Unauthenticated.']);
    }

    /**
     * Получение профиля авторизованного пользователя
     */
    public function test_get_profile()
    {
        $user = User::factory()->create();

        $this->actingAs($user);

        $response = $this->getJson(self::BASE_URL);
        $response->assertOk();
        $response->assertJson(['id' => $user->id, 'name' => $user->name, 'email' => $user->email]);
    }
}
