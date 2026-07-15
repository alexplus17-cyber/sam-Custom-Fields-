<?php

namespace TitanFields\API;

use TitanFields\Core\Storage\StorageEngineInterface;
use TitanFields\Core\Registry\GroupRegistry;
use TitanFields\Core\Registry\FieldTypeRegistry;

class PublicAPI
{
    private StorageEngineInterface $storageEngine;
    private GroupRegistry $groupRegistry;
    private FieldTypeRegistry $fieldTypeRegistry;

    public function __construct(StorageEngineInterface $storageEngine, GroupRegistry $groupRegistry, FieldTypeRegistry $fieldTypeRegistry)
    {
        $this->storageEngine = $storageEngine;
        $this->groupRegistry = $groupRegistry;
        $this->fieldTypeRegistry = $fieldTypeRegistry;
    }

    /**
     * Format a raw value using the appropriate FieldInterface instance.
     */
    private function formatValue(mixed $rawValue, \TitanFields\Fields\FieldInterface $fieldConfig, int $entityId, string $entityType): mixed
    {
        $type = $fieldConfig->getType();
        $class = $this->fieldTypeRegistry->getFieldClass($type);

        if ($class && class_exists($class)) {
            $formatter = new $class();
            if ($formatter instanceof \TitanFields\Fields\FieldInterface) {
                return $formatter->formatValue($rawValue, $entityId, $entityType);
            }
        }

        return $rawValue;
    }

    /**
     * Retrieve and format a single field value.
     */
    public function getField(string $fieldKey, int $entityId, string $entityType = 'post'): mixed
    {
        $rawValue = $this->storageEngine->get($fieldKey, $entityId, $entityType);

        $fieldConfig = $this->findFieldInActiveGroups($fieldKey, $entityId, $entityType);

        if ($fieldConfig) {
            return $this->formatValue($rawValue, $fieldConfig, $entityId, $entityType);
        }

        return $rawValue;
    }

    /**
     * Retrieve all formatted fields for a given entity.
     */
    public function getFields(int $entityId, string $entityType = 'post'): array
    {
        $activeGroups = $this->groupRegistry->getActiveGroups([
            'entity_id' => $entityId,
            'post_type' => $entityType === 'post' ? get_post_type($entityId) : '',
        ]);

        if (empty($activeGroups)) {
            return [];
        }

        $allFieldConfigs = [];
        $fieldKeysToFetch = [];

        foreach ($activeGroups as $group) {
            foreach ($group->getFields() as $field) {
                $fieldKeysToFetch[] = $field->getKey();
                $allFieldConfigs[$field->getKey()] = $field;
            }
        }

        if (empty($fieldKeysToFetch)) {
            return [];
        }

        // Optimize database access by using getMultiple
        $rawValues = $this->storageEngine->getMultiple($fieldKeysToFetch, $entityId, $entityType);

        $fieldsData = [];
        foreach ($allFieldConfigs as $key => $fieldConfig) {
            $rawValue = $rawValues[$key] ?? null;
            $fieldsData[$fieldConfig->getName()] = $this->formatValue($rawValue, $fieldConfig, $entityId, $entityType);
        }

        return $fieldsData;
    }

    /**
     * Helper to find a FieldInterface instance by key for a specific context.
     */
    private function findFieldInActiveGroups(string $fieldKey, int $entityId, string $entityType): ?\TitanFields\Fields\FieldInterface
    {
        $activeGroups = $this->groupRegistry->getActiveGroups([
            'entity_id' => $entityId,
            'post_type' => $entityType === 'post' ? get_post_type($entityId) : '',
        ]);

        foreach ($activeGroups as $group) {
            foreach ($group->getFields() as $field) {
                if ($field->getKey() === $fieldKey) {
                    return $field;
                }
            }
        }

        return null;
    }
}

// Global helper functions acting as proxies
if (!function_exists('tf_get_field')) {
    function tf_get_field(string $fieldKey, int $entityId, string $entityType = 'post'): mixed
    {
        /** @var PublicAPI $api */
        $api = \TitanFields\Core\Plugin::getInstance()->getContainer()->get(PublicAPI::class);
        return $api->getField($fieldKey, $entityId, $entityType);
    }
}

if (!function_exists('tf_get_fields')) {
    function tf_get_fields(int $entityId, string $entityType = 'post'): array
    {
        /** @var PublicAPI $api */
        $api = \TitanFields\Core\Plugin::getInstance()->getContainer()->get(PublicAPI::class);
        return $api->getFields($entityId, $entityType);
    }
}
