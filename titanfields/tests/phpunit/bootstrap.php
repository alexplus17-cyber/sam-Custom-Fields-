<?php
/**
 * PHPUnit bootstrap file
 *
 * @package Titanfields
 */

$_tests_dir = getenv('WP_TESTS_DIR');

if (!$_tests_dir) {
    $_tests_dir = rtrim(sys_get_temp_dir(), '/\\') . '/wordpress-tests-lib';
}

if (!file_exists($_tests_dir . '/includes/functions.php')) {
    echo "Could not find $_tests_dir/includes/functions.php, have you run bin/install-wp-tests.sh ?" . PHP_EOL;
    exit(1);
}

// Give access to tests_add_filter() function.
require_once $_tests_dir . '/includes/functions.php';

/**
 * Manually load the plugin being tested.
 */
function _manually_load_plugin() {
    require dirname(dirname(__DIR__)) . '/titanfields.php';
}
tests_add_filter('muplugins_loaded', '_manually_load_plugin');

// Start up the WP testing environment.
require $_tests_dir . '/includes/bootstrap.php';

// Setup custom tables for tests
function _setup_titanfields_tables() {
    global $wpdb;

    require_once ABSPATH . 'wp-admin/includes/upgrade.php';

    // We can read our schema file directly to ensure tests match production schema
    $schemaPath = dirname(dirname(__DIR__)) . '/src/Database/schema.sql';

    if (file_exists($schemaPath)) {
        $sql = file_get_contents($schemaPath);

        // The schema uses generic table names. We should replace them with the WP prefix for tests.
        // Or simply execute them if dbDelta handles it well (though dbDelta is finicky, raw query is safer for tests if they drop later)

        // For tests, it's often safer to execute the CREATE statements directly if we know the schema.
        $wpdb->query("DROP TABLE IF EXISTS {$wpdb->prefix}titanfields_data");
        $wpdb->query("DROP TABLE IF EXISTS {$wpdb->prefix}titanfields_groups");

        $createGroups = "CREATE TABLE `{$wpdb->prefix}titanfields_groups` (
            `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
            `group_key` varchar(100) NOT NULL,
            `title` varchar(255) NOT NULL,
            `fields` longtext NOT NULL,
            `location_rules` longtext NOT NULL,
            `settings` longtext,
            `is_active` tinyint(1) NOT NULL DEFAULT 1,
            `created_at` datetime DEFAULT CURRENT_TIMESTAMP,
            `updated_at` datetime DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            PRIMARY KEY (`id`),
            UNIQUE KEY `group_key` (`group_key`)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;";

        $createData = "CREATE TABLE `{$wpdb->prefix}titanfields_data` (
            `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
            `entity_id` bigint(20) unsigned NOT NULL,
            `entity_type` varchar(50) NOT NULL,
            `field_key` varchar(100) NOT NULL,
            `field_value` longtext,
            `parent_id` bigint(20) unsigned DEFAULT NULL,
            `row_index` int(11) unsigned DEFAULT NULL,
            PRIMARY KEY (`id`),
            KEY `entity_idx` (`entity_id`, `entity_type`),
            KEY `field_key_idx` (`field_key`),
            KEY `parent_idx` (`parent_id`, `row_index`),
            UNIQUE KEY `entity_field_parent_idx` (`entity_id`, `entity_type`, `field_key`, `parent_id`, `row_index`)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;";

        $wpdb->query($createGroups);
        $wpdb->query($createData);
    }
}

// Hook table creation after plugin is loaded and DB is ready
_setup_titanfields_tables();
