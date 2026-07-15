<?php

namespace TitanFields\Core\Storage;

interface StorageEngineInterface
{
    /**
     * Retrieve a field value from the storage engine.
     *
     * @param string $fieldKey The unique field key.
     * @param int|string $entityId The ID of the entity (e.g., post ID, user ID).
     * @param string $entityType The type of entity ('post', 'user', 'term', 'option').
     * @param int|null $parentId Optional parent ID for hierarchical fields.
     * @param int|null $rowIndex Optional row index.
     * @return mixed The raw field value.
     */
    public function get(string $fieldKey, int|string $entityId, string $entityType = 'post', ?int $parentId = null, ?int $rowIndex = null): mixed;

    /**
     * Save a field value to the storage engine.
     *
     * @param string $fieldKey The unique field key.
     * @param mixed $value The value to save.
     * @param int|string $entityId The ID of the entity.
     * @param string $entityType The type of entity.
     * @param int|null $parentId Optional parent ID for hierarchical fields.
     * @param int|null $rowIndex Optional row index.
     * @return int|bool The inserted/updated ID on success, false on failure.
     */
    public function update(string $fieldKey, mixed $value, int|string $entityId, string $entityType = 'post', ?int $parentId = null, ?int $rowIndex = null): int|bool;

    /**
     * Delete a field value from the storage engine.
     *
     * @param string $fieldKey The unique field key.
     * @param int|string $entityId The ID of the entity.
     * @param string $entityType The type of entity.
     * @param int|null $parentId Optional parent ID for hierarchical fields.
     * @param int|null $rowIndex Optional row index.
     * @return bool True on success, false on failure.
     */
    public function delete(string $fieldKey, int|string $entityId, string $entityType = 'post', ?int $parentId = null, ?int $rowIndex = null): bool;

    /**
     * Batch load multiple field values to prevent N+1 queries.
     *
     * @param array $fieldKeys Array of field keys to retrieve.
     * @param int|string $entityId The ID of the entity.
     * @param string $entityType The type of entity.
     * @return array Associative array of field keys to their values.
     */
    public function getMultiple(array $fieldKeys, int|string $entityId, string $entityType = 'post'): array;

    /**
     * Get children fields for a given parent ID (e.g. for repeaters).
     *
     * @param int $parentId The parent ID.
     * @return array The children fields.
     */
    public function getChildren(int $parentId): array;
}
