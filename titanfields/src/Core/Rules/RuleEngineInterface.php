<?php

namespace TitanFields\Core\Rules;

use TitanFields\Groups\FieldGroupInterface;

interface RuleEngineInterface
{
    /**
     * Evaluate if a given field group should be active/visible for the current context.
     *
     * @param FieldGroupInterface $group The field group containing the rules.
     * @param array $context The contextual data (e.g., current post ID, post type, user role, etc.).
     * @return bool True if the rules match the context, false otherwise.
     */
    public function evaluate(FieldGroupInterface $group, array $context): bool;

    /**
     * Register a new rule type (e.g., 'post_type', 'user_role', 'page_template').
     *
     * @param string $ruleName The name of the rule type.
     * @param callable $evaluator A callable that evaluates the rule against the context.
     */
    public function registerRuleType(string $ruleName, callable $evaluator): void;
}
