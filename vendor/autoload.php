<?php
/**
 * Lightweight PSR-4 autoloader for GODMINTON
 * Fallback when Composer is not installed on the system.
 */
spl_autoload_register(function ($class) {
    // Map PHPMailer namespace
    $prefix = 'PHPMailer\\PHPMailer\\';
    $base_dir = __DIR__ . '/PHPMailer/src/';
    $len = strlen($prefix);
    if (strncmp($prefix, $class, $len) === 0) {
        $relative_class = substr($class, $len);
        $file = $base_dir . str_replace('\\', '/', $relative_class) . '.php';
        if (file_exists($file)) {
            require_once $file;
            return;
        }
    }
});
