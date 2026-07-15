<?php
/**
 * Plugin Name: TitanFields
 * Description: An enterprise-grade custom fields framework for WordPress.
 * Version: 1.0.0
 * Author: Jules
 * Text Domain: titanfields
 */

if (!defined('ABSPATH')) {
    exit; // Exit if accessed directly.
}

// Basic PSR-4 Autoloader for the plugin (in a real project, this would be Composer's vendor/autoload.php)
spl_autoload_register(function ($class) {
    $prefix = 'TitanFields\\';
    $base_dir = __DIR__ . '/src/';
    $len = strlen($prefix);

    if (strncmp($prefix, $class, $len) !== 0) {
        return;
    }

    $relative_class = substr($class, $len);
    $file = $base_dir . str_replace('\\', '/', $relative_class) . '.php';

    if (file_exists($file)) {
        require $file;
    }
});

// Boot the plugin
add_action('plugins_loaded', function () {
    \TitanFields\Core\Plugin::getInstance()->boot();
});
