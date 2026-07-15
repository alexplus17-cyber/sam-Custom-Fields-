<?php

namespace TitanFields\Tests;

use WP_UnitTestCase;
use TitanFields\Core\Storage\DatabaseStorageEngine;

class DatabaseStorageEngineTest extends WP_UnitTestCase
{
    private DatabaseStorageEngine $storageEngine;

    public function setUp(): void
    {
        parent::setUp();

        // Ensure cache is completely clear before tests
        wp_cache_flush();

        $this->storageEngine = new DatabaseStorageEngine();

        // Clean table to be safe
        global $wpdb;
        $wpdb->query("TRUNCATE TABLE {$wpdb->prefix}titanfields_data");
    }

    public function tearDown(): void
    {
        parent::tearDown();
        global $wpdb;
        $wpdb->query("TRUNCATE TABLE {$wpdb->prefix}titanfields_data");
        wp_cache_flush();
    }

    public function test_it_can_insert_a_simple_value()
    {
        $entityId = 123;
        $fieldKey = 'field_test_123';
        $value = 'Hello World';

        $result = $this->storageEngine->update($fieldKey, $value, $entityId, 'post');

        $this->assertTrue((bool)$result, 'Update method should return true on success.');

        // Verify it was inserted into DB
        global $wpdb;
        $dbValue = $wpdb->get_var($wpdb->prepare(
            "SELECT field_value FROM {$wpdb->prefix}titanfields_data WHERE field_key = %s AND entity_id = %d",
            $fieldKey, $entityId
        ));

        $this->assertEquals($value, $dbValue);
    }

    public function test_it_can_upsert_an_existing_value()
    {
        $entityId = 456;
        $fieldKey = 'field_upsert';

        // Insert initial
        $this->storageEngine->update($fieldKey, 'Initial Value', $entityId, 'post');

        // Upsert new
        $this->storageEngine->update($fieldKey, 'Updated Value', $entityId, 'post');

        global $wpdb;
        $count = $wpdb->get_var($wpdb->prepare(
            "SELECT COUNT(*) FROM {$wpdb->prefix}titanfields_data WHERE field_key = %s AND entity_id = %d",
            $fieldKey, $entityId
        ));

        // Should still only be 1 row
        $this->assertEquals(1, $count);

        $dbValue = $wpdb->get_var($wpdb->prepare(
            "SELECT field_value FROM {$wpdb->prefix}titanfields_data WHERE field_key = %s AND entity_id = %d",
            $fieldKey, $entityId
        ));

        $this->assertEquals('Updated Value', $dbValue);
    }

    public function test_get_populates_and_reads_from_object_cache()
    {
        $entityId = 789;
        $fieldKey = 'field_cache';
        $value = 'Cache Me';

        // Update should set the cache
        $this->storageEngine->update($fieldKey, $value, $entityId, 'post');

        global $wpdb;
        $startQueries = $wpdb->num_queries;

        // Get should read from cache (no query)
        $retrievedValue = $this->storageEngine->get($fieldKey, $entityId, 'post');

        $endQueries = $wpdb->num_queries;

        $this->assertEquals($value, $retrievedValue);
        $this->assertEquals($startQueries, $endQueries, 'Getting a cached value should not trigger a DB query.');
    }

    public function test_delete_purges_cache_and_removes_from_db()
    {
        $entityId = 101;
        $fieldKey = 'field_delete';

        $this->storageEngine->update($fieldKey, 'To Delete', $entityId, 'post');

        // Verify in cache
        $this->assertEquals('To Delete', $this->storageEngine->get($fieldKey, $entityId, 'post'));

        // Delete
        $this->storageEngine->delete($fieldKey, $entityId, 'post');

        global $wpdb;
        $count = $wpdb->get_var($wpdb->prepare(
            "SELECT COUNT(*) FROM {$wpdb->prefix}titanfields_data WHERE field_key = %s AND entity_id = %d",
            $fieldKey, $entityId
        ));

        $this->assertEquals(0, $count, 'Value should be removed from DB.');
        $this->assertNull($this->storageEngine->get($fieldKey, $entityId, 'post'), 'Value should be null after delete.');
    }

    public function test_it_handles_hierarchical_data()
    {
        $entityId = 202;

        // Insert Parent
        $parentId = $this->storageEngine->update('field_repeater', '3', $entityId, 'post');

        $this->assertIsNumeric($parentId);

        // Insert Children
        $this->storageEngine->update('field_sub_1', 'Row 1 Val', $entityId, 'post', (int)$parentId, 0);
        $this->storageEngine->update('field_sub_1', 'Row 2 Val', $entityId, 'post', (int)$parentId, 1);

        $children = $this->storageEngine->getChildren((int)$parentId);

        $this->assertCount(2, $children);
        $this->assertEquals('Row 1 Val', $children[0]['field_value']);
        $this->assertEquals('Row 2 Val', $children[1]['field_value']);
    }

    public function test_getmultiple_executes_single_query_and_populates_cache()
    {
        $entityId = 303;
        $keys = ['field_a', 'field_b', 'field_c'];

        foreach ($keys as $key) {
            $this->storageEngine->update($key, "Value {$key}", $entityId, 'post');
        }

        // Clear cache so getMultiple has to query
        wp_cache_flush();

        global $wpdb;
        $startQueries = $wpdb->num_queries;

        $results = $this->storageEngine->getMultiple($keys, $entityId, 'post');

        $endQueries = $wpdb->num_queries;

        $this->assertEquals(1, $endQueries - $startQueries, 'getMultiple should execute exactly 1 query.');
        $this->assertArrayHasKey('field_a', $results);
        $this->assertArrayHasKey('field_b', $results);
        $this->assertArrayHasKey('field_c', $results);
        $this->assertEquals('Value field_a', $results['field_a']);

        // Verify cache was populated
        $startQueriesAfter = $wpdb->num_queries;
        $this->storageEngine->get('field_b', $entityId, 'post'); // Should hit cache
        $this->assertEquals($startQueriesAfter, $wpdb->num_queries, 'Subsequent get() should hit cache populated by getMultiple.');
    }
}
