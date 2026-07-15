<?php

namespace TitanFields\Core\Storage;

class DatabaseStorageEngine implements StorageEngineInterface
{
    private string $tableName;
    private string $cacheGroup = 'titanfields';

    public function __construct()
    {
        global $wpdb;
        $this->tableName = $wpdb->prefix . 'titanfields_data';
    }

    /**
     * Generate a deterministic cache key.
     */
    private function getCacheKey(string $entityType, int|string $entityId, string $fieldKey, ?int $parentId, ?int $rowIndex): string
    {
        $p = $parentId ?? 'null';
        $r = $rowIndex ?? 'null';
        return "tf_{$entityType}_{$entityId}_{$fieldKey}_p{$p}_r{$r}";
    }

    /**
     * Check if a string is valid JSON.
     */
    private function isJson(string $string): bool
    {
        if (empty($string) || !is_string($string)) {
            return false;
        }
        json_decode($string);
        return json_last_error() === JSON_ERROR_NONE;
    }

    /**
     * Decode JSON if it's a valid JSON string.
     */
    private function maybeDecodeJson(mixed $value): mixed
    {
        if (is_string($value) && $this->isJson($value)) {
            return json_decode($value, true);
        }
        return $value;
    }

    /**
     * Encode arrays or objects to JSON.
     */
    private function maybeEncodeJson(mixed $value): mixed
    {
        if (is_array($value) || is_object($value)) {
            return wp_json_encode($value);
        }
        return $value;
    }

    public function get(string $fieldKey, int|string $entityId, string $entityType = 'post', ?int $parentId = null, ?int $rowIndex = null): mixed
    {
        global $wpdb;

        $cacheKey = $this->getCacheKey($entityType, $entityId, $fieldKey, $parentId, $rowIndex);
        $cachedValue = wp_cache_get($cacheKey, $this->cacheGroup);

        if ($cachedValue !== false) {
            return $this->maybeDecodeJson($cachedValue);
        }

        $query = $wpdb->prepare(
            "SELECT field_value FROM {$this->tableName} WHERE entity_id = %d AND entity_type = %s AND field_key = %s",
            $entityId,
            $entityType,
            $fieldKey
        );

        if ($parentId !== null) {
            $query .= $wpdb->prepare(" AND parent_id = %d", $parentId);
        } else {
            $query .= " AND parent_id IS NULL";
        }

        if ($rowIndex !== null) {
            $query .= $wpdb->prepare(" AND row_index = %d", $rowIndex);
        } else {
            $query .= " AND row_index IS NULL";
        }

        $value = $wpdb->get_var($query);

        if ($value !== null) {
            wp_cache_set($cacheKey, $value, $this->cacheGroup);
            return $this->maybeDecodeJson($value);
        }

        return null;
    }

    public function update(string $fieldKey, mixed $value, int|string $entityId, string $entityType = 'post', ?int $parentId = null, ?int $rowIndex = null): int|bool
    {
        global $wpdb;

        $encodedValue = $this->maybeEncodeJson($value);

        $args = [
            $entityId,
            $entityType,
            $fieldKey,
            $encodedValue
        ];

        if ($parentId !== null) {
            $args[] = $parentId;
            $parentSql = "%d";
        } else {
            $parentSql = "NULL";
        }

        if ($rowIndex !== null) {
            $args[] = $rowIndex;
            $rowSql = "%d";
        } else {
            $rowSql = "NULL";
        }

        $sql = $wpdb->prepare(
            "INSERT INTO {$this->tableName}
            (entity_id, entity_type, field_key, field_value, parent_id, row_index)
            VALUES (%d, %s, %s, %s, {$parentSql}, {$rowSql})
            ON DUPLICATE KEY UPDATE field_value = VALUES(field_value)",
            ...$args
        );

        $result = $wpdb->query($sql);

        if ($result !== false) {
            $cacheKey = $this->getCacheKey($entityType, $entityId, $fieldKey, $parentId, $rowIndex);
            wp_cache_set($cacheKey, $encodedValue, $this->cacheGroup);
            return $wpdb->insert_id ?: true;
        }

        return false;
    }

    public function delete(string $fieldKey, int|string $entityId, string $entityType = 'post', ?int $parentId = null, ?int $rowIndex = null): bool
    {
        global $wpdb;

        $query = $wpdb->prepare(
            "DELETE FROM {$this->tableName} WHERE entity_id = %d AND entity_type = %s AND field_key = %s",
            $entityId,
            $entityType,
            $fieldKey
        );

        if ($parentId !== null) {
            $query .= $wpdb->prepare(" AND parent_id = %d", $parentId);
        } else {
            $query .= " AND parent_id IS NULL";
        }

        if ($rowIndex !== null) {
            $query .= $wpdb->prepare(" AND row_index = %d", $rowIndex);
        } else {
            $query .= " AND row_index IS NULL";
        }

        $result = $wpdb->query($query);

        if ($result !== false) {
            $cacheKey = $this->getCacheKey($entityType, $entityId, $fieldKey, $parentId, $rowIndex);
            wp_cache_delete($cacheKey, $this->cacheGroup);
            return true;
        }

        return false;
    }

    public function getMultiple(array $fieldKeys, int|string $entityId, string $entityType = 'post'): array
    {
        global $wpdb;

        if (empty($fieldKeys)) {
            return [];
        }

        $placeholders = implode(',', array_fill(0, count($fieldKeys), '%s'));

        $query = $wpdb->prepare(
            "SELECT field_key, field_value, parent_id, row_index FROM {$this->tableName}
            WHERE entity_id = %d AND entity_type = %s AND field_key IN ($placeholders)",
            $entityId,
            $entityType,
            ...$fieldKeys
        );

        $results = $wpdb->get_results($query, ARRAY_A);

        $output = [];
        if ($results) {
            foreach ($results as $row) {
                $cacheKey = $this->getCacheKey($entityType, $entityId, $row['field_key'], $row['parent_id'], $row['row_index']);
                wp_cache_set($cacheKey, $row['field_value'], $this->cacheGroup);

                $decoded = $this->maybeDecodeJson($row['field_value']);

                if ($row['parent_id'] === null && $row['row_index'] === null) {
                    $output[$row['field_key']] = $decoded;
                } else {
                    if (!isset($output[$row['field_key']]) || !is_array($output[$row['field_key']])) {
                        $output[$row['field_key']] = [];
                    }
                    $output[$row['field_key']][] = [
                        'value' => $decoded,
                        'parent_id' => $row['parent_id'],
                        'row_index' => $row['row_index'],
                    ];
                }
            }
        }

        return $output;
    }

    public function getChildren(int $parentId): array
    {
        global $wpdb;

        $query = $wpdb->prepare(
            "SELECT * FROM {$this->tableName} WHERE parent_id = %d ORDER BY row_index ASC",
            $parentId
        );

        $results = $wpdb->get_results($query, ARRAY_A);

        $output = [];
        if ($results) {
            foreach ($results as $row) {
                $cacheKey = $this->getCacheKey($row['entity_type'], $row['entity_id'], $row['field_key'], $row['parent_id'], $row['row_index']);
                wp_cache_set($cacheKey, $row['field_value'], $this->cacheGroup);

                $output[] = [
                    'id' => $row['id'],
                    'entity_id' => $row['entity_id'],
                    'entity_type' => $row['entity_type'],
                    'field_key' => $row['field_key'],
                    'field_value' => $this->maybeDecodeJson($row['field_value']),
                    'parent_id' => $row['parent_id'],
                    'row_index' => $row['row_index'],
                ];
            }
        }

        return $output;
    }
}
