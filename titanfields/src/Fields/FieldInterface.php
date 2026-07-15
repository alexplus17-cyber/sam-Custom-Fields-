<?php

namespace TitanFields\Fields;

interface FieldInterface
{
    /**
     * Get the unique field key (e.g., field_60a3b2e5f1d4).
     */
    public function getKey(): string;

    /**
     * Get the field name used in the database.
     */
    public function getName(): string;

    /**
     * Get the field type (e.g., 'text', 'repeater', 'image').
     */
    public function getType(): string;

    /**
     * Format a raw database value into its final structured output for the frontend.
     *
     * @param mixed $value The raw value from the database.
     * @param int|string $entityId The ID of the entity.
     * @param string $entityType The type of the entity (e.g., 'post', 'user').
     * @return mixed The formatted value.
     */
    public function formatValue(mixed $value, int|string $entityId, string $entityType): mixed;

    /**
     * Validate the field value before saving.
     *
     * @param mixed $value The value to validate.
     * @return bool|string True if valid, or an error message string if invalid.
     */
    public function validate(mixed $value): bool|string;

    /**
     * Prepare a value for saving into the database.
     *
     * @param mixed $value The raw value.
     * @return mixed The prepared value.
     */
    public function updateValue(mixed $value): mixed;

    /**
     * Render the field configuration settings in the admin UI.
     * (Typically used by the React editor via REST/GraphQL schemas now, but standard for Field representation).
     *
     * @return array The configuration schema.
     */
    public function getConfigSchema(): array;
}
