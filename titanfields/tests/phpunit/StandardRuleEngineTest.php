<?php

namespace TitanFields\Tests;

use WP_UnitTestCase;
use TitanFields\Core\Rules\StandardRuleEngine;
use TitanFields\Groups\FieldGroupInterface;

class StandardRuleEngineTest extends WP_UnitTestCase
{
    private StandardRuleEngine $ruleEngine;

    public function setUp(): void
    {
        parent::setUp();
        $this->ruleEngine = new StandardRuleEngine();

        // Register some simple dummy evaluators for testing
        $this->ruleEngine->registerRuleType('test_param_1', function ($value, $context) {
            return ($context['test_val_1'] ?? '') === $value;
        });

        $this->ruleEngine->registerRuleType('test_param_2', function ($value, $context) {
            return ($context['test_val_2'] ?? '') === $value;
        });
    }

    private function getMockGroup(array $rules): FieldGroupInterface
    {
        $mock = $this->createMock(FieldGroupInterface::class);
        $mock->method('getLocationRules')->willReturn($rules);
        return $mock;
    }

    public function test_it_evaluates_or_logic_correctly()
    {
        // Group 1 matches, Group 2 fails
        $rules = [
            'relation' => 'OR',
            'groups' => [
                [
                    [ 'param' => 'test_param_1', 'operator' => '==', 'value' => 'match_me' ]
                ],
                [
                    [ 'param' => 'test_param_2', 'operator' => '==', 'value' => 'fail_me' ]
                ]
            ]
        ];

        $group = $this->getMockGroup($rules);
        $context = [
            'test_val_1' => 'match_me',
            'test_val_2' => 'something_else'
        ];

        $result = $this->ruleEngine->evaluate($group, $context);

        $this->assertTrue($result, 'OR logic should return true if at least one rule group matches.');
    }

    public function test_it_evaluates_and_logic_correctly()
    {
        // Rule 1 matches, Rule 2 fails inside the same group
        $rules = [
            'relation' => 'OR', // Default top level
            'groups' => [
                [
                    [ 'param' => 'test_param_1', 'operator' => '==', 'value' => 'match_me' ],
                    [ 'param' => 'test_param_2', 'operator' => '==', 'value' => 'match_me_too' ]
                ]
            ]
        ];

        $group = $this->getMockGroup($rules);

        $context = [
            'test_val_1' => 'match_me',
            'test_val_2' => 'fail'
        ];

        $result = $this->ruleEngine->evaluate($group, $context);

        $this->assertFalse($result, 'AND logic within a group should return false if any rule fails.');
    }

    public function test_it_fails_safely_on_unknown_params()
    {
        $rules = [
            'relation' => 'OR',
            'groups' => [
                [
                    [ 'param' => 'unknown_param', 'operator' => '==', 'value' => 'test' ]
                ]
            ]
        ];

        $group = $this->getMockGroup($rules);
        $context = [];

        $result = $this->ruleEngine->evaluate($group, $context);

        $this->assertFalse($result, 'Rule groups with unknown parameters should fail safely.');
    }

    public function test_it_evaluates_not_equal_operator()
    {
        $rules = [
            'relation' => 'OR',
            'groups' => [
                [
                    [ 'param' => 'test_param_1', 'operator' => '!=', 'value' => 'dont_match_me' ]
                ]
            ]
        ];

        $group = $this->getMockGroup($rules);

        $contextPass = [
            'test_val_1' => 'something_else'
        ];
        $resultPass = $this->ruleEngine->evaluate($group, $contextPass);
        $this->assertTrue($resultPass, '!= operator should pass if values are different.');

        $contextFail = [
            'test_val_1' => 'dont_match_me'
        ];
        $resultFail = $this->ruleEngine->evaluate($group, $contextFail);
        $this->assertFalse($resultFail, '!= operator should fail if values are the same.');
    }
}
