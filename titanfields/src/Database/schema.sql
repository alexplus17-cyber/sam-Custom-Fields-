CREATE TABLE `wp_titanfields_groups` (
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
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE `wp_titanfields_data` (
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
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
