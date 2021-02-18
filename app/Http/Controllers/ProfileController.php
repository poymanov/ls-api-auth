<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Http\Resources\UserResource;

class ProfileController extends Controller
{
    /**
     * @OA\Get(
     *     path="/api/profile",
     *     tags={"api"},
     *     security={
     *         {"bearerAuth": {}}
     *     },
     *     summary="Получение профиля авторизованного профиля",
     *     @OA\Response(response="200", description="Успешное получение профиля"),
     *     @OA\Response(response="403", description="Попытка получения профиля неавторизованным пользователем",
     *         @OA\JsonContent(
     *              @OA\Property(property="id", type="integer", example="1", description="Идентификатор пользователя"),
     *              @OA\Property(property="name", type="string", example="test", description="Имя пользователя"),
     *              @OA\Property(property="email", type="string", example="test@test.ru", description="Email пользователя"),
     *         ),
     *     ),
     * )
     *
     * @return UserResource
     */
    public function show()
    {
        return new UserResource(auth()->user());
    }
}
