<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

function isLoggedIn(): bool
{
    return isset($_SESSION['user']);
}

function currentUser(): ?array
{
    return $_SESSION['user'] ?? null;
}

function redirect(string $url): void
{
    header("Location: $url");
    exit;
}

function requireLogin(): void
{
    if (!isLoggedIn()) {
        redirect('login.php');
    }
}

function roleName(): string
{
    return $_SESSION['user']['role_name'] ?? '';
}

function hasRole(array $roles): bool
{
    return in_array(roleName(), $roles, true);
}

function requireRole(array $roles): void
{
    requireLogin();
    if (!hasRole($roles)) {
        redirect('dashboard.php?error=No tienes permiso para acceder a esa sección');
    }
}

function e(?string $value): string
{
    return htmlspecialchars($value ?? '', ENT_QUOTES, 'UTF-8');
}

function spanishRole(string $role): string
{
    return match ($role) {
        'Administrator' => 'Administrador',
        'Librarian' => 'Bibliotecario',
        'Reader' => 'Lector',
        default => $role
    };
}
