<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Http\Requests\Auth\LoginRequest;
use App\Services\Auth\LoginService;
use App\Services\Auth\LogoutService;
use App\Services\Auth\RegisterService;
use OpenApi\Annotations as OA;
use App\Http\Requests\Auth\RegistrationRequest;
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
 * )
 * @OA\Schema(
 *     schema="LoginRequestBody",
 *     title="Login Request Body",
 *     required={"email", "password"},
 *     @OA\Property(property="email", type="string", example="test@test.ru", description="Email пользователя", maxLength=255),
 *     @OA\Property(property="password", type="string", example="password", description="Пароль"),
 * ),
 */
class AuthController extends Controller
{
    /** @var RegisterService */
    private RegisterService $registerService;

    /** @var LoginService */
    private LoginService $loginService;

    /** @var LogoutService */
    private LogoutService $logoutService;

    /**
     * @param RegisterService $registerService
     * @param LoginService    $loginService
     * @param LogoutService   $logoutService
     */
    public function __construct(RegisterService $registerService, LoginService $loginService, LogoutService $logoutService)
    {
        $this->registerService = $registerService;
        $this->loginService    = $loginService;
        $this->logoutService   = $logoutService;
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
            $this->registerService->register(
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
            $this->registerService->verifyEmail((int) $userId, $emailHash);

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
            $this->registerService->resendVerifyEmail($email);

            return response(null);
        } catch (Throwable $e) {
            return response()->json(['message' => $e->getMessage()], Response::HTTP_UNPROCESSABLE_ENTITY);
        }
    }

    /**
     * @OA\Post(
     *     path="/api/auth/login",
     *     tags={"auth"},
     *     summary="Авторизация пользователя",
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(ref="#/components/schemas/LoginRequestBody")
     *     ),
     *     @OA\Response(response="200", description="Успешная авторизация",
     *          @OA\JsonContent(@OA\Property(property="access_token", type="string", example="1|n0m8wCCX1yr8mXsrSvQVeJGgI7d1lr5OICTYxPpU"))
     *     ),
     *     @OA\Response(response="422", description="Ошибки валидации параметров",
     *         @OA\JsonContent(
     *              @OA\Property(property="errors", type="object",
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
     * @param LoginRequest $request
     *
     * @return JsonResponse
     */
    public function login(LoginRequest $request): JsonResponse
    {
        try {
            $accessToken = $this->loginService->login($request->get('email'), $request->get('password'));

            return response()->json(['access_token' => $accessToken]);
        } catch (Throwable $e) {
            return response()->json(['message' => $e->getMessage()], Response::HTTP_UNPROCESSABLE_ENTITY);
        }
    }

    /**
     * @OA\Post(
     *     path="/api/auth/logout",
     *     tags={"auth"},
     *     security={
     *         {"bearerAuth": {}}
     *     },
     *     summary="Завершение пользовательского сеанса",
     *     @OA\Response(response="200", description="Успешное завершение пользовательского сеанса"),
     *     @OA\Response(response="403", description="Попытка завершения пользовательского сеанса неавторизованным пользователем"),
     * )
     *
     * @return Application|ResponseFactory|Response
     */
    public function logout()
    {
        $this->logoutService->deleteTokens(auth()->user());

        return response(null);
    }
}
