<?php

declare(strict_types=1);

namespace App\Validators;

final class AuthValidator
{
    public static function validateLogin(array $payload): array
    {
        $errors = [];

        $username = trim((string) ($payload['username'] ?? ''));
        $password = (string) ($payload['password'] ?? '');

        if ($username === '') {
            $errors['username'] = 'Informe o usuário.';
        }

        if ($password === '') {
            $errors['password'] = 'Informe a senha.';
        }

        return [
            'valid' => empty($errors),
            'errors' => $errors,
            'username' => $username,
            'password' => $password,
        ];
    }

    public static function validatePasswordChange(array $payload): array
    {
        $errors = [];

        $currentPassword = (string) ($payload['current_password'] ?? '');
        $newPassword = (string) ($payload['new_password'] ?? '');
        $confirmPassword = (string) ($payload['confirm_password'] ?? '');

        if ($currentPassword === '') {
            $errors['current_password'] = 'Informe a senha atual.';
        }

        if (strlen($newPassword) < 8) {
            $errors['new_password'] = 'A nova senha deve ter pelo menos 8 caracteres.';
        }

        if (!preg_match('/[a-z]/', $newPassword) || !preg_match('/[A-Z]/', $newPassword) || !preg_match('/\d/', $newPassword)) {
            $errors['new_password'] = 'A nova senha deve conter letras maiúsculas, minúsculas e números.';
        }

        if ($newPassword !== '' && $confirmPassword !== $newPassword) {
            $errors['confirm_password'] = 'A confirmação da nova senha não confere.';
        }

        return [
            'valid' => empty($errors),
            'errors' => $errors,
        ];
    }
}
