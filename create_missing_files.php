<?php
/**
 * Script para crear archivos faltantes en el servidor
 * Ejecutar en el servidor una sola vez
 */

error_reporting(E_ALL);
ini_set('display_errors', 1);

echo "=== Creando Archivos Faltantes ===\n\n";

$base_dir = __DIR__;

// Crear directorios si no existen
$dirs = [
    'config',
    'models',
    'controllers',
    'includes',
    'views',
    'views/auth',
    'logs'
];

foreach ($dirs as $dir) {
    $full_path = $base_dir . '/' . $dir;
    if (!is_dir($full_path)) {
        if (mkdir($full_path, 0755, true)) {
            echo "✅ Directorio creado: $dir/\n";
        } else {
            echo "❌ Error creando directorio: $dir/\n";
        }
    }
}

echo "\n";

// Definir archivos a crear
$files = [
    'config/database.php' => <<<'PHP'
<?php
declare(strict_types=1);

/**
 * Configuración de conexión a base de datos
 */

define('DB_HOST', '173.231.22.109');
define('DB_NAME', 'ideamiadev_marina');
define('DB_USER', 'ideamiadev_mariana');
define('DB_PASS', '3G$qaHNHc5i5HdA$');
define('DB_CHARSET', 'utf8mb4');
PHP,
    
    'config/constants.php' => <<<'PHP'
<?php
declare(strict_types=1);

/**
 * Constantes del sistema
 */

// Roles de usuario
define('ROLE_ADMIN', 'admin');
define('ROLE_PM', 'pm');
define('ROLE_ALMACEN', 'almacen');
define('ROLE_VIEWER', 'viewer');

// Estados de proyecto
define('PROJECT_PLANNING', 'planning');
define('PROJECT_ACTIVE', 'active');
define('PROJECT_ON_HOLD', 'on_hold');
define('PROJECT_COMPLETED', 'completed');

// Monedas soportadas
define('CURRENCY_MXN', 'MXN');
define('CURRENCY_USD', 'USD');
define('CURRENCY_EUR', 'EUR');
PHP,

    'models/Database.php' => <<<'PHP'
<?php
declare(strict_types=1);

/**
 * Clase base para conexión a base de datos
 */
class Database
{
    private static ?PDO $connection = null;

    public static function getConnection(): PDO
    {
        if (self::$connection === null) {
            try {
                self::$connection = new PDO(
                    "mysql:host=" . DB_HOST . ";dbname=" . DB_NAME . ";charset=" . DB_CHARSET,
                    DB_USER,
                    DB_PASS,
                    [
                        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                        PDO::ATTR_EMULATE_PREPARES => false,
                        PDO::MYSQL_ATTR_INIT_COMMAND => "SET NAMES " . DB_CHARSET
                    ]
                );
            } catch (PDOException $e) {
                error_log("Error de conexión a BD: " . $e->getMessage());
                throw new Exception("Error de conexión a la base de datos");
            }
        }
        return self::$connection;
    }

    public static function query(string $sql, array $params = []): PDOStatement
    {
        $stmt = self::getConnection()->prepare($sql);
        $stmt->execute($params);
        return $stmt;
    }

    public static function fetchOne(string $sql, array $params = []): ?array
    {
        $stmt = self::query($sql, $params);
        $result = $stmt->fetch();
        return $result ?: null;
    }

    public static function fetchAll(string $sql, array $params = []): array
    {
        $stmt = self::query($sql, $params);
        return $stmt->fetchAll();
    }
}
PHP,

    'models/User.php' => <<<'PHP'
<?php
declare(strict_types=1);

require_once __DIR__ . '/../config/constants.php';

class User
{
    public static function findByEmail(string $email): ?array
    {
        return Database::fetchOne(
            "SELECT * FROM users WHERE email = ? AND activo = 1",
            [$email]
        );
    }

    public static function findById(int $id): ?array
    {
        return Database::fetchOne(
            "SELECT id, nombre, email, rol, activo, created_at FROM users WHERE id = ? AND activo = 1",
            [$id]
        );
    }

    public static function verifyPassword(string $password, string $hash): bool
    {
        return password_verify($password, $hash);
    }

    public static function hashPassword(string $password): string
    {
        return password_hash($password, PASSWORD_BCRYPT);
    }

    public static function updateLastLogin(int $userId): bool
    {
        try {
            Database::query(
                "UPDATE users SET last_login = NOW() WHERE id = ?",
                [$userId]
            );
            return true;
        } catch (Exception $e) {
            error_log("Error actualizando last_login: " . $e->getMessage());
            return false;
        }
    }

    public static function create(array $data): ?int
    {
        try {
            $pdo = Database::getConnection();
            $stmt = $pdo->prepare("
                INSERT INTO users (nombre, email, password_hash, rol)
                VALUES (?, ?, ?, ?)
            ");
            
            $stmt->execute([
                $data['nombre'],
                $data['email'],
                $data['password_hash'],
                $data['rol'] ?? ROLE_VIEWER
            ]);
            
            return (int)$pdo->lastInsertId();
        } catch (Exception $e) {
            error_log("Error creando usuario: " . $e->getMessage());
            return null;
        }
    }

    public static function all(): array
    {
        return Database::fetchAll(
            "SELECT id, nombre, email, rol, activo, last_login, created_at 
             FROM users 
             ORDER BY nombre ASC"
        );
    }
}
PHP,

    'controllers/AuthController.php' => <<<'PHP'
<?php
declare(strict_types=1);

class AuthController
{
    public static function login(): void
    {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            return;
        }

        $email = $_POST['email'] ?? '';
        $password = $_POST['password'] ?? '';

        if (empty($email) || empty($password)) {
            setFlashMessage('error', 'Por favor completa todos los campos');
            return;
        }

        $user = User::findByEmail($email);
        
        if (!$user || !User::verifyPassword($password, $user['password_hash'])) {
            setFlashMessage('error', 'Email o contraseña incorrectos');
            return;
        }

        if (!$user['activo']) {
            setFlashMessage('error', 'Tu cuenta está desactivada. Contacta al administrador.');
            return;
        }

        session_regenerate_id(true);
        $_SESSION['user_id'] = $user['id'];
        $_SESSION['user_email'] = $user['email'];
        $_SESSION['user_nombre'] = $user['nombre'];
        $_SESSION['user_rol'] = $user['rol'];

        User::updateLastLogin($user['id']);

        $redirect = $_SESSION['redirect_after_login'] ?? 'index.php';
        unset($_SESSION['redirect_after_login']);
        
        setFlashMessage('success', 'Bienvenido, ' . $user['nombre']);
        redirect(base_url($redirect));
    }

    public static function logout(): void
    {
        logout();
        setFlashMessage('success', 'Sesión cerrada correctamente');
        redirect(base_url('login.php'));
    }
}
PHP,

    'includes/functions.php' => <<<'PHP'
<?php
declare(strict_types=1);

function h(?string $value): string
{
    return htmlspecialchars($value ?? '', ENT_QUOTES, 'UTF-8');
}

function redirect(string $url): void
{
    header("Location: " . $url);
    exit;
}

function base_url(string $path = ''): string
{
    $base = rtrim(APP_URL, '/');
    $path = ltrim($path, '/');
    return $base . ($path ? '/' . $path : '');
}

function setFlashMessage(string $type, string $message): void
{
    $_SESSION['flash_message'] = [
        'type' => $type,
        'message' => $message
    ];
}

function getFlashMessage(): ?array
{
    if (isset($_SESSION['flash_message'])) {
        $message = $_SESSION['flash_message'];
        unset($_SESSION['flash_message']);
        return $message;
    }
    return null;
}

function formatCurrency(float $amount, string $currency = 'MXN'): string
{
    $symbols = [
        'MXN' => '$',
        'USD' => 'US$',
        'EUR' => '€'
    ];
    
    $symbol = $symbols[$currency] ?? '$';
    return $symbol . number_format($amount, 2, '.', ',');
}

function formatDate(?string $date, string $format = 'd/m/Y'): string
{
    if (empty($date)) {
        return '';
    }
    $dateObj = DateTime::createFromFormat('Y-m-d H:i:s', $date);
    if (!$dateObj) {
        $dateObj = DateTime::createFromFormat('Y-m-d', $date);
    }
    return $dateObj ? $dateObj->format($format) : $date;
}
PHP,

    'includes/auth.php' => <<<'PHP'
<?php
declare(strict_types=1);

function isAuthenticated(): bool
{
    return isset($_SESSION['user_id']) && !empty($_SESSION['user_id']);
}

function requireAuth(): void
{
    if (!isAuthenticated()) {
        $_SESSION['redirect_after_login'] = $_SERVER['REQUEST_URI'] ?? '/';
        header('Location: ' . base_url('login.php'));
        exit;
    }
}

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

function hasRole(string $role): bool
{
    $user = getCurrentUser();
    return $user && $user['rol'] === $role;
}

function requireRole(string $role): void
{
    requireAuth();
    
    if (!hasRole($role)) {
        setFlashMessage('error', 'No tienes permisos para acceder a esta sección');
        header('Location: ' . base_url('index.php'));
        exit;
    }
}

function hasAnyRole(array $roles): bool
{
    $user = getCurrentUser();
    if (!$user) {
        return false;
    }
    
    return in_array($user['rol'], $roles);
}

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
PHP
];

// Crear archivos
$created = 0;
$skipped = 0;

foreach ($files as $file_path => $content) {
    $full_path = $base_dir . '/' . $file_path;
    
    if (file_exists($full_path)) {
        echo "ℹ️  Ya existe: $file_path\n";
        $skipped++;
    } else {
        if (file_put_contents($full_path, $content)) {
            echo "✅ Creado: $file_path\n";
            $created++;
        } else {
            echo "❌ Error creando: $file_path\n";
        }
    }
}

echo "\n" . str_repeat("=", 50) . "\n";
echo "Resumen:\n";
echo "✅ Archivos creados: $created\n";
echo "ℹ️  Archivos que ya existían: $skipped\n";
echo "\n🎉 Proceso completado!\n";

