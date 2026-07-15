<?php

namespace TitanFields\Core\Rules;

use TitanFields\Groups\FieldGroupInterface;

class StandardRuleEngine implements RuleEngineInterface
{
    /**
     * @var array<string, callable>
     */
    private array $evaluators = [];

    public function __construct()
    {
        $this->registerDefaultEvaluators();
    }

    /**
     * Register default rule types for common WP scenarios.
     */
    private function registerDefaultEvaluators(): void
    {
        $this->registerRuleType('post_type', function ($value, $context) {
            $postId = $context['entity_id'] ?? 0;
            if (!$postId) {
                return false;
            }
            return get_post_type($postId) === $value;
        });

        $this->registerRuleType('page_template', function ($value, $context) {
            $postId = $context['entity_id'] ?? 0;
            if (!$postId) {
                return false;
            }
            return get_page_template_slug($postId) === $value;
        });

        $this->registerRuleType('user_role', function ($value, $context) {
            if (!is_user_logged_in()) {
                return false;
            }
            $user = wp_get_current_user();
            return in_array($value, (array) $user->roles, true);
        });
    }

    public function registerRuleType(string $ruleName, callable $evaluator): void
    {
        $this->evaluators[$ruleName] = $evaluator;
    }

    public function evaluate(FieldGroupInterface $group, array $context): bool
    {
        $rules = $group->getLocationRules();

        if (empty($rules) || !isset($rules['groups']) || empty($rules['groups'])) {
            return true; // No rules, default to show
        }

        $relation = strtoupper($rules['relation'] ?? 'OR');

        foreach ($rules['groups'] as $ruleGroup) {
            $groupMatches = true;

            foreach ($ruleGroup as $rule) {
                $param = $rule['param'] ?? '';
                $operator = $rule['operator'] ?? '==';
                $expectedValue = $rule['value'] ?? '';

                if (!isset($this->evaluators[$param])) {
                    $groupMatches = false; // Unknown param, fail this AND group
                    break;
                }

                $evaluator = $this->evaluators[$param];
                $result = $evaluator($expectedValue, $context);

                $ruleMatches = false;
                if ($operator === '==') {
                    $ruleMatches = $result === true;
                } elseif ($operator === '!=') {
                    $ruleMatches = $result === false;
                }

                if (!$ruleMatches) {
                    $groupMatches = false; // AND logic fails if one rule fails
                    break;
                }
            }

            if ($relation === 'OR' && $groupMatches) {
                return true; // OR logic passes if one group passes
            }

            if ($relation === 'AND' && !$groupMatches) {
                return false; // AND relation (custom extension)
            }
        }

        return $relation !== 'OR'; // If relation is OR, it didn't find any matching group
    }
}
