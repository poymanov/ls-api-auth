<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class HomeTest extends TestCase
{
    /**
     * Запрос по корневому пути успешно выполняется
     */
    public function test_root_successful()
    {
        $response = $this->get('/api');

        $response->assertOk();

        $response->assertJsonFragment(['version' => '1.0']);
    }
}
