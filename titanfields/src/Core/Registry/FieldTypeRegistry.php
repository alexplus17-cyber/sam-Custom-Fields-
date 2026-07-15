<?php

namespace TitanFields\Core\Registry;

class FieldTypeRegistry
{
    /**
     * Map of registered field types.
     * Format: 'type' => ['phpClass' => '...', 'reactComponent' => '...']
     *
     * @var array
     */
    private array $types = [];

    /**
     * Register a new field type.
     *
     * @param string $type The string identifier for the field type (e.g. 'text', 'repeater').
     * @param string $phpClass The fully qualified class name implementing FieldInterface.
     * @param string $reactComponent The registered name of the React component to use in the admin/block UI.
     */
    public function registerFieldType(string $type, string $phpClass, string $reactComponent): void
    {
        $this->types[$type] = [
            'phpClass' => $phpClass,
            'reactComponent' => $reactComponent,
        ];
    }

    /**
     * Get the PHP class associated with a given field type.
     *
     * @param string $type
     * @return string|null
     */
    public function getFieldClass(string $type): ?string
    {
        return $this->types[$type]['phpClass'] ?? null;
    }

    /**
     * Get the React component name associated with a given field type.
     *
     * @param string $type
     * @return string|null
     */
    public function getReactComponent(string $type): ?string
    {
        return $this->types[$type]['reactComponent'] ?? null;
    }

    /**
     * Get all registered field types.
     *
     * @return array
     */
    public function getRegisteredTypes(): array
    {
        return $this->types;
    }
}
