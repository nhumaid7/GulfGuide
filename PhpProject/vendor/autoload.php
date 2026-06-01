<?php
spl_autoload_register(function ($class) {
    $prefixes = [
        'Dompdf\\' => __DIR__ . '/vendor/dompdf/src/',
        'FontLib\\' => __DIR__ . '/vendor/php-font-lib/src/FontLib/',
        'Svg\\' => __DIR__ . '/vendor/php-svg-lib/src/Svg/',
    ];

    foreach ($prefixes as $prefix => $base_dir) {
        $len = strlen($prefix);
        if (strncmp($prefix, $class, $len) !== 0) {
            continue;
        }

        $relative_class = substr($class, $len);

        $file = $base_dir . str_replace('\\', '/', $relative_class) . '.php';

        // If the file exists, require it
        if (file_exists($file)) {
            require_once $file;
            return;
        }
    }
});