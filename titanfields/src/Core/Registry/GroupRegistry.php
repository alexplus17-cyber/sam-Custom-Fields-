<?php

namespace TitanFields\Core\Registry;

use TitanFields\Groups\FieldGroupInterface;
use TitanFields\Core\Rules\RuleEngineInterface;

class GroupRegistry
{
    /**
     * @var FieldGroupInterface[]
     */
    private array $groups = [];

    /**
     * @var RuleEngineInterface
     */
    private RuleEngineInterface $ruleEngine;

    public function __construct(RuleEngineInterface $ruleEngine)
    {
        $this->ruleEngine = $ruleEngine;
    }

    /**
     * Register a single field group.
     */
    public function registerGroup(FieldGroupInterface $group): void
    {
        $this->groups[$group->getKey()] = $group;
    }

    /**
     * Retrieve all registered groups.
     *
     * @return FieldGroupInterface[]
     */
    public function getGroups(): array
    {
        return $this->groups;
    }

    /**
     * Retrieve a specific group by key.
     */
    public function getGroup(string $key): ?FieldGroupInterface
    {
        return $this->groups[$key] ?? null;
    }

    /**
     * Load field groups from Local JSON files in the specified directory.
     *
     * @param string $jsonDirectoryPath
     */
    public function loadFromLocalJson(string $jsonDirectoryPath): void
    {
        if (!is_dir($jsonDirectoryPath)) {
            return;
        }

        $files = glob(rtrim($jsonDirectoryPath, '/') . '/*.json');

        foreach ($files as $file) {
            $content = file_get_contents($file);
            if ($content !== false) {
                $decoded = json_decode($content, true);
                if (json_last_error() === JSON_ERROR_NONE && is_array($decoded)) {
                    // Assuming we have a factory or standard way to hydrate JSON into a FieldGroupInterface.
                    // For now, we mock the dependency or assume a generic hydration process will be implemented.
                    // A proper implementation would convert $decoded into a concrete FieldGroupInterface.
                    // $group = FieldGroupFactory::createFromArray($decoded);
                    // $this->registerGroup($group);
                }
            }
        }
    }

    /**
     * Resolve and return only the groups that should be active for the given context.
     *
     * @param array $context The context array (e.g. ['entity_id' => 123, 'post_type' => 'post'])
     * @return FieldGroupInterface[]
     */
    public function getActiveGroups(array $context): array
    {
        $activeGroups = [];

        foreach ($this->groups as $group) {
            if ($group->isActive() && $this->ruleEngine->evaluate($group, $context)) {
                $activeGroups[$group->getKey()] = $group;
            }
        }

        return $activeGroups;
    }
}
