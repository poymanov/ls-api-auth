<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use OpenApi\Annotations as OA;
use Illuminate\Http\JsonResponse;

class HomeController extends Controller
{
    /**
     * @OA\Get(
     *     path="/",
     *     tags={"api"},
     *     description="Главная страница API",
     *     @OA\Response(
     *         response="200",
     *         description="Успешный ответ",
     *         @OA\JsonContent(
     *             type="object",
     *             @OA\Property(property="version", type="string")
     *         )
     *     )
     * )
     *
     * @return JsonResponse
     */
    public function index()
    {
        return response()->json(['version' => '1.0']);
    }
}
