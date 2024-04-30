<?php

spl_autoload_register(function ($class) {
    if (file_exists($file = __DIR__ . '/' . $class . '.php')) {
        require($file);
    }
});
