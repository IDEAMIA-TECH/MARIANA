<?php
/**
 * QUICK FIX: Crear archivos críticos faltantes inmediatamente
 * Ejecutar en el servidor si falta config/database.php u otros archivos básicos
 */

error_reporting(E_ALL);
ini_set('display_errors', 1);

$base_dir = __DIR__;

echo "=== Quick Fix: Creando Archivos Críticos ===\n\n";

// Crear directorios esenciales
$dirs = ['config', 'models', 'controllers', 'includes', 'views', 'views/layouts', 'views/auth', 'logs'];
foreach ($dirs as $dir) {
    $path = $base_dir . '/' . $dir;
    if (!is_dir($path)) {
        mkdir($path, 0755, true);
        echo "✅ Directorio: $dir/\n";
    }
}

echo "\n";

// Archivo CRÍTICO: config/database.php
$database_file = $base_dir . '/config/database.php';
if (!file_exists($database_file)) {
    file_put_contents($database_file, "<?php
declare(strict_types=1);

define('DB_HOST', '173.231.22.109');
define('DB_NAME', 'ideamiadev_marina');
define('DB_USER', 'ideamiadev_mariana');
define('DB_PASS', '3G\$qaHNHc5i5HdA\$');
define('DB_CHARSET', 'utf8mb4');
");
    echo "✅ CREADO: config/database.php\n";
} else {
    echo "ℹ️  Ya existe: config/database.php\n";
}

// Archivo CRÍTICO: config/config.php
$config_file = $base_dir . '/config/config.php';
if (!file_exists($config_file)) {
    file_put_contents($config_file, "<?php
declare(strict_types=1);

require_once __DIR__ . '/database.php';

define('APP_NAME', 'Control de Materiales');
define('APP_URL', 'http://" . $_SERVER['HTTP_HOST'] . dirname($_SERVER['PHP_SELF']) . "');
define('TIMEZONE', 'America/Mexico_City');
date_default_timezone_set(TIMEZONE);

define('SESSION_LIFETIME', 3600);
if (session_status() === PHP_SESSION_NONE) {
    ini_set('session.cookie_httponly', 1);
    ini_set('session.use_only_cookies', 1);
    session_start();
}
");
    echo "✅ CREADO: config/config.php\n";
} else {
    echo "ℹ️  Ya existe: config/config.php\n";
}

// Archivo CRÍTICO: config/constants.php
$constants_file = $base_dir . '/config/constants.php';
if (!file_exists($constants_file)) {
    file_put_contents($constants_file, "<?php
declare(strict_types=1);

define('ROLE_ADMIN', 'admin');
define('ROLE_PM', 'pm');
define('ROLE_ALMACEN', 'almacen');
define('ROLE_VIEWER', 'viewer');
define('PROJECT_PLANNING', 'planning');
define('PROJECT_ACTIVE', 'active');
define('PROJECT_ON_HOLD', 'on_hold');
define('PROJECT_COMPLETED', 'completed');
define('CURRENCY_MXN', 'MXN');
define('CURRENCY_USD', 'USD');
define('CURRENCY_EUR', 'EUR');
");
    echo "✅ CREADO: config/constants.php\n";
} else {
    echo "ℹ️  Ya existe: config/constants.php\n";
}

// Archivo CRÍTICO: models/Database.php
$db_model = $base_dir . '/models/Database.php';
if (!file_exists($db_model)) {
    file_put_contents($db_model, "<?php
declare(strict_types=1);

class Database
{
    private static ?PDO \$connection = null;

    public static function getConnection(): PDO
    {
        if (self::\$connection === null) {
            try {
                self::\$connection = new PDO(
                    \"mysql:host=\" . DB_HOST . \";dbname=\" . DB_NAME . \";charset=\" . DB_CHARSET,
                    DB_USER,
                    DB_PASS,
                    [
                        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                        PDO::ATTR_EMULATE_PREPARES => false,
                        PDO::MYSQL_ATTR_INIT_COMMAND => \"SET NAMES \" . DB_CHARSET
                    ]
                );
            } catch (PDOException \$e) {
                error_log(\"Error de conexión a BD: \" . \$e->getMessage());
                throw new Exception(\"Error de conexión a la base de datos\");
            }
        }
        return self::\$connection;
    }

    public static function query(string \$sql, array \$params = []): PDOStatement
    {
        \$stmt = self::getConnection()->prepare(\$sql);
        \$stmt->execute(\$params);
        return \$stmt;
    }

    public static function fetchOne(string \$sql, array \$params = []): ?array
    {
        \$stmt = self::query(\$sql, \$params);
        \$result = \$stmt->fetch();
        return \$result ?: null;
    }

    public static function fetchAll(string \$sql, array \$params = []): array
    {
        \$stmt = self::query(\$sql, \$params);
        return \$stmt->fetchAll();
    }
}
");
    echo "✅ CREADO: models/Database.php\n";
} else {
    echo "ℹ️  Ya existe: models/Database.php\n";
}

// Archivo CRÍTICO: includes/functions.php
$functions_file = $base_dir . '/includes/functions.php';
if (!file_exists($functions_file)) {
    file_put_contents($functions_file, "<?php
declare(strict_types=1);

function h(?string \$value): string
{
    return htmlspecialchars(\$value ?? '', ENT_QUOTES, 'UTF-8');
}

function redirect(string \$url): void
{
    header(\"Location: \" . \$url);
    exit;
}

function base_url(string \$path = ''): string
{
    \$base = rtrim(APP_URL, '/');
    \$path = ltrim(\$path, '/');
    return \$base . (\$path ? '/' . \$path : '');
}

function setFlashMessage(string \$type, string \$message): void
{
    \$_SESSION['flash_message'] = ['type' => \$type, 'message' => \$message];
}

function getFlashMessage(): ?array
{
    if (isset(\$_SESSION['flash_message'])) {
        \$msg = \$_SESSION['flash_message'];
        unset(\$_SESSION['flash_message']);
        return \$msg;
    }
    return null;
}

function formatCurrency(float \$amount, string \$currency = 'MXN'): string
{
    \$symbols = ['MXN' => '\$', 'USD' => 'US\$', 'EUR' => '€'];
    \$symbol = \$symbols[\$currency] ?? '\$';
    return \$symbol . number_format(\$amount, 2, '.', ',');
}

function formatDate(?string \$date, string \$format = 'd/m/Y'): string
{
    if (empty(\$date)) return '';
    \$dateObj = DateTime::createFromFormat('Y-m-d H:i:s', \$date);
    if (!\$dateObj) \$dateObj = DateTime::createFromFormat('Y-m-d', \$date);
    return \$dateObj ? \$dateObj->format(\$format) : \$date;
}
");
    echo "✅ CREADO: includes/functions.php\n";
} else {
    echo "ℹ️  Ya existe: includes/functions.php\n";
}

// Archivo CRÍTICO: includes/auth.php  
$auth_file = $base_dir . '/includes/auth.php';
if (!file_exists($auth_file)) {
    file_put_contents($auth_file, "<?php
declare(strict_types=1);

function isAuthenticated(): bool
{
    return isset(\$_SESSION['user_id']) && !empty(\$_SESSION['user_id']);
}

function requireAuth(): void
{
    if (!isAuthenticated()) {
        \$_SESSION['redirect_after_login'] = \$_SERVER['REQUEST_URI'] ?? '/';
        header('Location: ' . base_url('login.php'));
        exit;
    }
}

function getCurrentUser(): ?array
{
    if (!isAuthenticated()) return null;
    return Database::fetchOne(
        \"SELECT id, nombre, email, rol, activo FROM users WHERE id = ? AND activo = 1\",
        [\$_SESSION['user_id']]
    );
}

function hasRole(string \$role): bool
{
    \$user = getCurrentUser();
    return \$user && \$user['rol'] === \$role;
}

function requireRole(string \$role): void
{
    requireAuth();
    if (!hasRole(\$role)) {
        setFlashMessage('error', 'No tienes permisos');
        header('Location: ' . base_url('index.php'));
        exit;
    }
}

function hasAnyRole(array \$roles): bool
{
    \$user = getCurrentUser();
    return \$user && in_array(\$user['rol'], \$roles);
}

function logout(): void
{
    \$_SESSION = [];
    if (ini_get(\"session.use_cookies\")) {
        \$params = session_get_cookie_params();
        setcookie(session_name(), '', time() - 42000,
            \$params[\"path\"], \$params[\"domain\"],
            \$params[\"secure\"], \$params[\"httponly\"]
        );
    }
    session_destroy();
}
");
    echo "✅ CREADO: includes/auth.php\n";
} else {
    echo "ℹ️  Ya existe: includes/auth.php\n";
}

echo "\n" . str_repeat("=", 50) . "\n";
echo "✅ Archivos críticos creados!\n\n";
echo "📝 AHORA:\n";
echo "   1. Sube TODOS los demás archivos del proyecto\n";
echo "   2. O ejecuta 'create_missing_files.php' para más archivos\n";
echo "   3. Verifica con 'check_server_files.php'\n";

