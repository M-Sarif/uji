<?php

function h(?string $value): string
{
    return htmlspecialchars((string) $value, ENT_QUOTES, 'UTF-8');
}

function go_to(string $screen, array $extraParams = []): void
{
    header('Location: ' . app_screen_url($screen, $extraParams));
    exit;
}

function app_base_path(): string
{
    $dir = str_replace('\\', '/', dirname((string) ($_SERVER['SCRIPT_NAME'] ?? '/index.php')));
    return rtrim($dir, '/') . '/';
}

function app_screen_url(string $screen, array $params = []): string
{
    static $pretty = ['role_select' => 'home', 'amt_home' => 'amt', 'dashboard' => 'spbu', 'token_login' => 'token'];
    if (isset($pretty[$screen])) {
        $path = app_base_path() . $pretty[$screen];
    } else {
        $path = app_base_path();
        $params = array_merge(['screen' => $screen], $params);
    }
    return $path . ($params ? '?' . http_build_query($params) : '');
}

function current_role(): ?string
{
    $role = $_SESSION['role'] ?? null;
    return is_string($role) && isset(ROLES[$role]) ? $role : null;
}

function role_home(?string $role): string
{
    return $role !== null && isset(ROLES[$role]) ? ROLES[$role]['home'] : 'role_select';
}