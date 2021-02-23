<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Http\Requests\Auth\ForgotPasswordRequest;
use App\Http\Requests\Auth\LoginRequest;
use App\Http\Requests\Auth\ResetPasswordRequest;
use App\Services\AuthService;
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
 * @OA\Schema(
 *     schema="ResendEmailVerificationRequestBody",
 *     title="Resend Email Verification Request Body",
 *     required={"email"},
 *     @OA\Property(property="email", type="string", example="test@test.ru", description="Email пользователя", maxLength=255)
 * )
 */
class AuthController extends Controller
{
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
     * @OA\Post(
     *     path="/api/auth/resend-email-verification",
     *     tags={"auth"},
     *     summary="Повторная отправка письма для подтверждения адреса",
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(ref="#/components/schemas/ResendEmailVerificationRequestBody")
     *     ),
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
            $accessToken = $this->authService->login($request->get('email'), $request->get('password'));

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
        $this->authService->logout(auth()->user());

        return response(null);
    }

    /**
     * @OA\Post(
     *     path="/api/auth/forgot-password",
     *     summary="Запрос на сброс пароля пользователя",
     *     tags={"auth"},
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *              @OA\Property(property="email", type="string", example="test@test.ru", description="Email пользователя, которому необходимо сбросить пароль", maxLength=255),
     *         )
     *     ),
     *     @OA\Response(response="200", description="Успешное завершение запроса на сброс пароля"),
     *     @OA\Response(response="422", description="Ошибки запроса на сброс пароля"),
     * )
     *
     * @param ForgotPasswordRequest $request
     *
     * @return Application|ResponseFactory|JsonResponse|Response
     */
    public function forgotPassword(ForgotPasswordRequest $request)
    {
        try {
            $this->authService->sendResetPasswordRequest($request->get('email'));

            return response(null);
        } catch (Throwable $e) {
            return response()->json(['message' => $e->getMessage()], Response::HTTP_UNPROCESSABLE_ENTITY);
        }
    }

    /**
     * @OA\Post(
     *     path="/api/auth/reset-password",
     *     summary="Сброс пароля пользователя",
     *     tags={"auth"},
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *              @OA\Property(property="email", type="string", example="test@test.ru", description="Email пользователя, которому необходимо сбросить пароль", maxLength=255),
     *              @OA\Property(property="password", type="string", example="password", description="Пароль", minLength=8),
     *              @OA\Property(property="password_confirmation", type="string", example="password", description="Подтверждение пароля", minLength=6)
     *         )
     *     ),
     *     @OA\Response(response="200", description="Успешный сброс пароля пользователя"),
     *     @OA\Response(response="422", description="Ошибки сброса пароля пользователя"),
     * )
     *
     * @param ResetPasswordRequest $request
     *
     * @return JsonResponse
     */
    public function resetPassword(ResetPasswordRequest $request): JsonResponse
    {
        try {
            $this->authService->resetPassword(
                $request->get('email'),
                $request->get('password'),
                $request->get('password_confirmation'),
                $request->get('token')
            );

            return response()->json();
        } catch (Throwable $e) {
            return response()->json(['message' => $e->getMessage()], Response::HTTP_UNPROCESSABLE_ENTITY);
        }
    }
}
