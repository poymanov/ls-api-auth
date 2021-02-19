<?php

return [
    'url' => env('FRONTEND_URL', 'http://localhost:8081'),
    'email_verify_url' => env('FRONTEND_EMAIL_VERIFY_URL', '/verify-email?queryUrl='),
    'reset_password_url' => env('FRONTEND_RESET_PASSWORD_URL', '/reset-password'),
];
