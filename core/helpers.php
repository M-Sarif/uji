<?php
/**
 * Fungsi bantuan umum (dipakai semua peran).
 */

/** Escape output supaya aman dari XSS */
function h(?string $value): string
{
    return htmlspecialchars((string) $value, ENT_QUOTES, 'UTF-8');
}

/** Redirect PRG (Post/Redirect/Get) ke layar tertentu lalu hentikan eksekusi */
function go_to(string $screen, array $extraParams = []): void
{
    $params = array_merge(['screen' => $screen], $extraParams);
    header('Location: index.php?' . http_build_query($params));
    exit;
}

/** Peran yang sedang aktif ('spbu' | 'amt' | null bila belum memilih) */
function current_role(): ?string
{
    $role = $_SESSION['role'] ?? null;
    return is_string($role) && isset(ROLES[$role]) ? $role : null;
}

/** Layar beranda untuk sebuah peran (role_select bila belum memilih peran) */
function role_home(?string $role): string
{
    return $role !== null && isset(ROLES[$role]) ? ROLES[$role]['home'] : 'role_select';
}
