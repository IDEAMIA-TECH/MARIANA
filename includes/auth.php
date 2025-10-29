<?php
declare(strict_types=1);

/**
 * Verificación de autenticación y sesión
 */

require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../models/Database.php';

/**
 * Verificar si el usuario está autenticado
 */
function isAuthenticated(): bool
{
    return isset($_SESSION['user_id']) && !empty($_SESSION['user_id']);
}

/**
 * Requerir autenticación (redirige al login si no está autenticado)
 */
function requireAuth(): void
{
    if (!isAuthenticated()) {
        $_SESSION['redirect_after_login'] = $_SERVER['REQUEST_URI'] ?? '/';
        header('Location: ' . base_url('login.php'));
        exit;
    }
}

/**
 * Obtener información del usuario actual
 */
function getCurrentUser(): ?array
{
    if (!isAuthenticated()) {
        return null;
    }

    $user = Database::fetchOne(
        "SELECT id, nombre, email, rol, activo FROM users WHERE id = ? AND activo = 1",
        [$_SESSION['user_id']]
    );

    return $user;
}

/**
 * Verificar rol del usuario
 */
function hasRole(string $role): bool
{
    $user = getCurrentUser();
    return $user && $user['rol'] === $role;
}

/**
 * Requerir un rol específico
 */
function requireRole(string $role): void
{
    requireAuth();
    
    if (!hasRole($role)) {
        setFlashMessage('error', 'No tienes permisos para acceder a esta sección');
        header('Location: ' . base_url('index.php'));
        exit;
    }
}

/**
 * Verificar si el usuario tiene uno de los roles permitidos
 */
function hasAnyRole(array $roles): bool
{
    $user = getCurrentUser();
    if (!$user) {
        return false;
    }
    
    return in_array($user['rol'], $roles);
}

/**
 * Cerrar sesión
 */
function logout(): void
{
    $_SESSION = [];
    
    if (ini_get("session.use_cookies")) {
        $params = session_get_cookie_params();
        setcookie(session_name(), '', time() - 42000,
            $params["path"], $params["domain"],
            $params["secure"], $params["httponly"]
        );
    }
    
    session_destroy();
}

