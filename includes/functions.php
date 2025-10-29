<?php
declare(strict_types=1);

/**
 * Funciones auxiliares del sistema
 */

/**
 * Sanitizar output HTML
 */
function h(?string $value): string
{
    return htmlspecialchars($value ?? '', ENT_QUOTES, 'UTF-8');
}

/**
 * Redirigir a una URL
 */
function redirect(string $url): void
{
    header("Location: " . $url);
    exit;
}

/**
 * Obtener URL base
 */
function base_url(string $path = ''): string
{
    $base = rtrim(APP_URL, '/');
    $path = ltrim($path, '/');
    return $base . ($path ? '/' . $path : '');
}

/**
 * Mostrar mensaje flash
 */
function setFlashMessage(string $type, string $message): void
{
    $_SESSION['flash_message'] = [
        'type' => $type,
        'message' => $message
    ];
}

/**
 * Obtener y limpiar mensaje flash
 */
function getFlashMessage(): ?array
{
    if (isset($_SESSION['flash_message'])) {
        $message = $_SESSION['flash_message'];
        unset($_SESSION['flash_message']);
        return $message;
    }
    return null;
}

/**
 * Formatear número como moneda
 */
function formatCurrency(float $amount, string $currency = 'MXN'): string
{
    $amount = (float)($amount ?? 0);
    $symbols = [
        'MXN' => '$',
        'USD' => 'US$',
        'EUR' => '€'
    ];
    
    $symbol = $symbols[$currency] ?? '$';
    return $symbol . number_format($amount, 2, '.', ',');
}

/**
 * Formatear fecha
 */
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

