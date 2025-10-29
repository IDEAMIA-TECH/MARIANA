<?php
declare(strict_types=1);

/**
 * Controlador de Autenticación
 */
class AuthController
{
    /**
     * Procesar login
     */
    public static function login(): void
    {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            return;
        }

        $email = $_POST['email'] ?? '';
        $password = $_POST['password'] ?? '';

        // Validación
        if (empty($email) || empty($password)) {
            setFlashMessage('error', 'Por favor completa todos los campos');
            return;
        }

        // Buscar usuario
        $user = User::findByEmail($email);
        
        if (!$user || !User::verifyPassword($password, $user['password_hash'])) {
            setFlashMessage('error', 'Email o contraseña incorrectos');
            return;
        }

        // Verificar que esté activo
        if (!$user['activo']) {
            setFlashMessage('error', 'Tu cuenta está desactivada. Contacta al administrador.');
            return;
        }

        // Iniciar sesión
        session_regenerate_id(true);
        $_SESSION['user_id'] = $user['id'];
        $_SESSION['user_email'] = $user['email'];
        $_SESSION['user_nombre'] = $user['nombre'];
        $_SESSION['user_rol'] = $user['rol'];

        // Actualizar último login
        User::updateLastLogin($user['id']);

        // Redirigir
        $redirect = $_SESSION['redirect_after_login'] ?? 'index.php';
        unset($_SESSION['redirect_after_login']);
        
        setFlashMessage('success', 'Bienvenido, ' . $user['nombre']);
        redirect(base_url($redirect));
    }

    /**
     * Procesar logout
     */
    public static function logout(): void
    {
        logout();
        setFlashMessage('success', 'Sesión cerrada correctamente');
        redirect(base_url('login.php'));
    }
}

