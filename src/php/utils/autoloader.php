<?php
// src/php/utils/autoloader.php
spl_autoload_register(function($class) {
    $paths = [
        __DIR__ . "/../classes/",
    ];
    foreach ($paths as $path) {
        $file = $path . $class . ".class.php";
        if (file_exists($file)) {
            require_once $file;
            return;
        }
    }
});
?>
