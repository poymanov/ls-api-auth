<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use OpenApi\Annotations as OA;
use App\Http\Requests\Auth\RegistrationRequest;
use App\Services\AuthService;
use Illuminate\Contracts\Foundation\Application;
use Illuminate\Contracts\Routing\ResponseFactory;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Throwable;

/**
 * @OA\Schema(
 *     schema="SignupRequestBody",
 *     title="Signup Request Body",
 *     required={"name", "email", "password", "password_confirmation"},
 *     @OA\Property(property="name", type="string", example="Test", description="Имя пользователя", maxLength=255),
 *     @OA\Property(property="email", type="string", example="test@test.ru", description="Email пользователя", maxLength=255),
 *     @OA\Property(property="password", type="string", example="password", description="Пароль", minLength=8),
 *     @OA\Property(property="password_confirmation", type="string", example="password", description="Подтверждение пароля", minLength=6),
 * ),
 */
class AuthController extends Controller
{
    /** @var AuthService */
    private AuthService $authService;

    /**
     * @param AuthService $authService
     */
    public function __construct(AuthService $authService)
    {
        $this->authService = $authService;
    }

    /**
     * @OA\Post(
     *     path="/api/auth/registration",
     *     tags={"auth"},
     *     summary="Регистрация пользователя",
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(ref="#/components/schemas/SignupRequestBody")
     *     ),
     *     @OA\Response(response="201", description="Успешная регистрация"),
     *     @OA\Response(response="422", description="Ошибки валидации параметров",
     *         @OA\JsonContent(
     *              @OA\Property(property="message", type="string", example="Validation failed"),
     *              @OA\Property(property="errors", type="object",
     *                  @OA\Property(property="name", type="array",
     *                      @OA\Items(type="string", example="The name field is required.")
     *                  ),
     *                  @OA\Property(property="email", type="array",
     *                      @OA\Items(type="string", example="The email field is required.")
     *                  ),
     *                  @OA\Property(property="password", type="array",
     *                      @OA\Items(type="string", example="The password field is required.")
     *                  ),
     *              ),
     *          ),
     *     ),
     * )
     *
     * @param RegistrationRequest $request
     *
     * @return Application|ResponseFactory|JsonResponse|Response|object
     */
    public function registration(RegistrationRequest $request)
    {
        try {
            $this->authService->register(
                $request->get('name'),
                $request->get('email'),
                $request->get('password')
            );

            return response(null, Response::HTTP_CREATED);
        } catch (Throwable $e) {
            return response()->json(['message' => 'Registration failed'], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    /**
     * @OA\Get(
     *     path="/api/auth/verify-email/{id}/{hash}",
     *     tags={"auth"},
     *     @OA\Parameter(
     *         description="ID пользователя, для которого подтверждается email",
     *         in="path",
     *         name="id",
     *         required=true,
     *         @OA\Schema(
     *           type="integer",
     *         )
     *     ),
     *     @OA\Parameter(
     *         description="Hash-набор символов для подтверждения email",
     *         in="path",
     *         name="hash",
     *         required=true,
     *         @OA\Schema(
     *           type="string",
     *         )
     *     ),
     *     @OA\Parameter(
     *         description="Дата истечения действия ссылки для подтверждения email в формате timestamp",
     *         in="query",
     *         name="expires",
     *         required=true,
     *         @OA\Schema(
     *           type="integer",
     *         )
     *     ),
     *     @OA\Parameter(
     *         description="Подпись ссылки, необходимая для установления корректности ссылки подтверждения email",
     *         in="query",
     *         name="signature",
     *         required=true,
     *         @OA\Schema(
     *           type="string",
     *         )
     *     ),
     *     summary="Подтверждение почты пользователя",
     *     @OA\Response(response="200", description="Успешное подтверждение email"),
     *     @OA\Response(response="422", description="Ошибка подтверждения email",
     *         @OA\JsonContent(
     *              @OA\Property(property="message", type="string", example="No account found for confirmation."),
     *          ),
     *     ),
     * )
     *
     * @param Request $request
     *
     * @return Application|ResponseFactory|JsonResponse|Response|object
     */
    public function verifyEmail(Request $request)
    {
        $userId    = $request->route('id');
        $emailHash = $request->route('hash');

        try {
            $this->authService->verifyEmail((int) $userId, $emailHash);

            return response(null);
        } catch (Throwable $e) {
            return response()->json(['message' => $e->getMessage()], Response::HTTP_UNPROCESSABLE_ENTITY);
        }
    }

    /**
     * @OA\Get(
     *     path="/api/auth/resend-email-verification",
     *     tags={"auth"},
     *     @OA\Parameter(
     *         description="Email, для которого необходимо выслать повторное письмо для подтверждения адреса",
     *         in="query",
     *         name="email",
     *         required=true,
     *         @OA\Schema(
     *           type="string",
     *         )
     *     ),
     *     summary="Повторная отправка письма для подтверждения адреса",
     *     @OA\Response(response="200", description="Успешная отправка письма для подтверждения адреса"),
     *     @OA\Response(response="422", description="Ошибка отправки письма для подтверждения адреса",
     *         @OA\JsonContent(
     *              @OA\Property(property="message", type="string", example="No account found for confirmation."),
     *          ),
     *     ),
     * )
     *
     * @param Request $request
     *
     * @return Application|ResponseFactory|JsonResponse|Response
     */
    public function resendEmailVerification(Request $request)
    {
        $email = $request->get('email');

        try {
            $this->authService->resendVerifyEmail($email);

            return response(null);
        } catch (Throwable $e) {
            return response()->json(['message' => $e->getMessage()], Response::HTTP_UNPROCESSABLE_ENTITY);
        }
    }
}
