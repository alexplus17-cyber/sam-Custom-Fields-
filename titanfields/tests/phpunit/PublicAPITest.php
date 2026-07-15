<?php

namespace TitanFields\Tests;

use WP_UnitTestCase;
use TitanFields\API\PublicAPI;
use TitanFields\Core\Storage\DatabaseStorageEngine;
use TitanFields\Core\Registry\GroupRegistry;
use TitanFields\Core\Registry\FieldTypeRegistry;
use TitanFields\Core\Rules\StandardRuleEngine;
use TitanFields\Groups\FieldGroupInterface;
use TitanFields\Fields\FieldInterface;

class DummyFieldFormatter implements FieldInterface
{
    public function getKey(): string { return 'field_dummy'; }
    public function getName(): string { return 'dummy_name'; }
    public function getType(): string { return 'dummy'; }

    public function formatValue(mixed $value, int|string $entityId, string $entityType): mixed
    {
        return 'FORMATTED: ' . $value;
    }

    public function validate(mixed $value): bool|string { return true; }
    public function updateValue(mixed $value): mixed { return $value; }
    public function getConfigSchema(): array { return []; }
}

class PublicAPITest extends WP_UnitTestCase
{
    private PublicAPI $api;
    private DatabaseStorageEngine $storageEngine;

    public function setUp(): void
    {
        parent::setUp();

        $this->storageEngine = new DatabaseStorageEngine();
        $ruleEngine = new StandardRuleEngine();
        $groupRegistry = new GroupRegistry($ruleEngine);
        $fieldTypeRegistry = new FieldTypeRegistry();

        // Register Dummy Field Type
        $fieldTypeRegistry->registerFieldType('dummy', DummyFieldFormatter::class, 'DummyComponent');

        // Register Mock Group
        $mockGroup = $this->createMock(FieldGroupInterface::class);
        $mockGroup->method('isActive')->willReturn(true);
        $mockGroup->method('getKey')->willReturn('group_test');
        $mockGroup->method('getLocationRules')->willReturn([]); // Show everywhere

        $mockField = new DummyFieldFormatter();
        $mockGroup->method('getFields')->willReturn([$mockField]);

        $groupRegistry->registerGroup($mockGroup);

        $this->api = new PublicAPI($this->storageEngine, $groupRegistry, $fieldTypeRegistry);

        // Clean table
        global $wpdb;
        $wpdb->query("TRUNCATE TABLE {$wpdb->prefix}titanfields_data");
        wp_cache_flush();
    }

    public function test_get_field_formats_value()
    {
        $entityId = 123;
        $fieldKey = 'field_dummy';

        // Insert raw data
        $this->storageEngine->update($fieldKey, 'raw_data', $entityId, 'post');

        // Call API
        $result = $this->api->getField($fieldKey, $entityId, 'post');

        $this->assertEquals('FORMATTED: raw_data', $result, 'API should format value via FieldTypeRegistry.');
    }

    public function test_get_fields_batching_and_naming()
    {
        // Actually create a post so get_post_type() can cache it during our setup
        $entityId = self::factory()->post->create(['post_type' => 'post']);
        $fieldKey = 'field_dummy';

        // Insert raw data
        $this->storageEngine->update($fieldKey, 'batch_data', $entityId, 'post');

        // Clear cache to ensure data fetch hits DB, but we need to re-prime the post cache
        // to avoid get_post_type() causing an extra query during the test.
        wp_cache_flush();
        get_post($entityId);

        global $wpdb;
        $startQueries = $wpdb->num_queries;

        $results = $this->api->getFields($entityId, 'post');

        $endQueries = $wpdb->num_queries;

        // 1 query for getMultiple
        // Mock groups don't hit DB for rules here.
        $this->assertEquals(1, $endQueries - $startQueries, 'getFields should execute 1 batched query for data.');

        $this->assertArrayHasKey('dummy_name', $results, 'Results should be keyed by field name, not key.');
        $this->assertEquals('FORMATTED: batch_data', $results['dummy_name'], 'Batched results should be formatted.');
    }
}
