<?php

namespace TitanFields\Cli;

use WP_CLI;
use WP_CLI_Command;

class TitanFieldsCommand extends WP_CLI_Command
{
    private string $jsonDirectoryPath;

    public function __construct()
    {
        // Define standard location for JSON sync (can be made configurable later)
        $this->jsonDirectoryPath = trailingslashit(get_stylesheet_directory()) . 'titanfields-json';
    }

    /**
     * Initializes the CLI command if WP_CLI is present.
     */
    public static function init(): void
    {
        if (defined('WP_CLI') && WP_CLI) {
            WP_CLI::add_command('titanfields', self::class);
        }
    }

    /**
     * Imports all .json files from the Local JSON directory into the database.
     *
     * ## EXAMPLES
     *
     *     wp titanfields sync
     */
    public function sync(): void
    {
        if (!is_dir($this->jsonDirectoryPath)) {
            WP_CLI::error("Local JSON directory not found: {$this->jsonDirectoryPath}");
            return;
        }

        global $wpdb;
        $tableName = $wpdb->prefix . 'titanfields_groups';

        $files = glob($this->jsonDirectoryPath . '/*.json');

        if (empty($files)) {
            WP_CLI::success('No JSON files found to sync.');
            return;
        }

        $syncedCount = 0;

        foreach ($files as $file) {
            $content = file_get_contents($file);
            $decoded = json_decode($content, true);

            if (json_last_error() !== JSON_ERROR_NONE || !isset($decoded['group_key'])) {
                WP_CLI::warning("Invalid JSON file skipped: " . basename($file));
                continue;
            }

            $groupKey = $decoded['group_key'];
            $title = $decoded['title'] ?? '';
            $fields = isset($decoded['fields']) ? wp_json_encode($decoded['fields']) : '[]';
            $locationRules = isset($decoded['location_rules']) ? wp_json_encode($decoded['location_rules']) : '[]';
            $settings = isset($decoded['settings']) ? wp_json_encode($decoded['settings']) : '{}';
            $isActive = $decoded['is_active'] ?? 1;

            $sql = $wpdb->prepare(
                "INSERT INTO {$tableName} (group_key, title, fields, location_rules, settings, is_active)
                VALUES (%s, %s, %s, %s, %s, %d)
                ON DUPLICATE KEY UPDATE
                    title = VALUES(title),
                    fields = VALUES(fields),
                    location_rules = VALUES(location_rules),
                    settings = VALUES(settings),
                    is_active = VALUES(is_active)",
                $groupKey,
                $title,
                $fields,
                $locationRules,
                $settings,
                $isActive
            );

            $result = $wpdb->query($sql);

            if ($result !== false) {
                $syncedCount++;
            } else {
                WP_CLI::warning("Failed to sync group: {$groupKey}");
            }
        }

        WP_CLI::success("Successfully synced {$syncedCount} field groups.");
    }

    /**
     * Exports all active groups from the database into the Local JSON directory.
     *
     * ## EXAMPLES
     *
     *     wp titanfields export
     */
    public function export(): void
    {
        global $wpdb;
        $tableName = $wpdb->prefix . 'titanfields_groups';

        $results = $wpdb->get_results("SELECT * FROM {$tableName} WHERE is_active = 1", ARRAY_A);

        if (empty($results)) {
            WP_CLI::success('No active field groups found in the database to export.');
            return;
        }

        if (!is_dir($this->jsonDirectoryPath)) {
            if (!wp_mkdir_p($this->jsonDirectoryPath)) {
                WP_CLI::error("Failed to create Local JSON directory: {$this->jsonDirectoryPath}");
                return;
            }
        }

        $exportedCount = 0;

        foreach ($results as $row) {
            $data = [
                'group_key' => $row['group_key'],
                'title' => $row['title'],
                'fields' => json_decode($row['fields'], true) ?: [],
                'location_rules' => json_decode($row['location_rules'], true) ?: [],
                'settings' => json_decode($row['settings'], true) ?: [],
                'is_active' => (bool) $row['is_active'],
            ];

            $fileName = sanitize_key($row['group_key']) . '.json';
            $filePath = $this->jsonDirectoryPath . '/' . $fileName;

            $written = file_put_contents($filePath, wp_json_encode($data, JSON_PRETTY_PRINT));

            if ($written !== false) {
                $exportedCount++;
            } else {
                WP_CLI::warning("Failed to write JSON file for group: {$row['group_key']}");
            }
        }

        WP_CLI::success("Successfully exported {$exportedCount} field groups to JSON.");
    }
}

// Hook to initialize early
add_action('cli_init', ['TitanFields\Cli\TitanFieldsCommand', 'init']);
