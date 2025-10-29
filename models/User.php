<?php
declare(strict_types=1);

require_once __DIR__ . '/../config/constants.php';

/**
 * Modelo de Usuario
 */
class User
{
    /**
     * Buscar usuario por email
     */
    public static function findByEmail(string $email): ?array
    {
        return Database::fetchOne(
            "SELECT * FROM users WHERE email = ? AND activo = 1",
            [$email]
        );
    }

    /**
     * Buscar usuario por ID
     */
    public static function findById(int $id): ?array
    {
        return Database::fetchOne(
            "SELECT id, nombre, email, rol, activo, created_at FROM users WHERE id = ? AND activo = 1",
            [$id]
        );
    }

    /**
     * Verificar password
     */
    public static function verifyPassword(string $password, string $hash): bool
    {
        return password_verify($password, $hash);
    }

    /**
     * Hash password
     */
    public static function hashPassword(string $password): string
    {
        return password_hash($password, PASSWORD_BCRYPT);
    }

    /**
     * Actualizar último login
     */
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

    /**
     * Crear nuevo usuario
     */
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

    /**
     * Obtener todos los usuarios
     */
    public static function all(): array
    {
        return Database::fetchAll(
            "SELECT id, nombre, email, rol, activo, last_login, created_at 
             FROM users 
             ORDER BY nombre ASC"
        );
    }
}

