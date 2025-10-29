<?php
/**
 * Manejo de errores para debugging
 * Agregar esto temporalmente al inicio de index.php para ver errores
 */

ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

// Función de error handler personalizada
function customErrorHandler($errno, $errstr, $errfile, $errline) {
    $error = "Error [$errno]: $errstr en $errfile línea $errline\n";
    error_log($error);
    
    // Si no estamos en producción, mostrar el error
    if (ini_get('display_errors')) {
        echo "<div style='background: #f8d7da; color: #721c24; padding: 15px; margin: 10px; border: 1px solid #f5c6cb; border-radius: 5px;'>";
        echo "<strong>Error PHP:</strong><br>";
        echo htmlspecialchars($error);
        echo "</div>";
    }
    return true;
}

set_error_handler('customErrorHandler');

// Manejar excepciones no capturadas
function customExceptionHandler($exception) {
    $error = "Excepción: " . $exception->getMessage() . "\n";
    $error .= "Archivo: " . $exception->getFile() . " línea " . $exception->getLine() . "\n";
    $error .= "Stack trace:\n" . $exception->getTraceAsString();
    
    error_log($error);
    
    if (ini_get('display_errors')) {
        echo "<div style='background: #f8d7da; color: #721c24; padding: 15px; margin: 10px; border: 1px solid #f5c6cb; border-radius: 5px;'>";
        echo "<strong>Excepción:</strong><br>";
        echo "<pre>" . htmlspecialchars($error) . "</pre>";
        echo "</div>";
    }
}

set_exception_handler('customExceptionHandler');

