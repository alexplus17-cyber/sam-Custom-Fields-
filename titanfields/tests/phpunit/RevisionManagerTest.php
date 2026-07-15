<?php

namespace TitanFields\Tests;

use WP_UnitTestCase;
use TitanFields\Core\Revision\RevisionManager;
use TitanFields\Core\Storage\DatabaseStorageEngine;

class RevisionManagerTest extends WP_UnitTestCase
{
    private RevisionManager $revisionManager;
    private DatabaseStorageEngine $storageEngine;

    public function setUp(): void
    {
        parent::setUp();

        $this->revisionManager = new RevisionManager();
        $this->revisionManager->registerHooks();
        $this->storageEngine = new DatabaseStorageEngine();

        global $wpdb;
        $wpdb->query("TRUNCATE TABLE {$wpdb->prefix}titanfields_data");
    }

    public function tearDown(): void
    {
        parent::tearDown();
        global $wpdb;
        $wpdb->query("TRUNCATE TABLE {$wpdb->prefix}titanfields_data");
    }

    public function test_it_creates_revision_data()
    {
        // 1. Create a post
        $postId = self::factory()->post->create(['post_type' => 'post']);

        // 2. Add TitanFields data
        $this->storageEngine->update('test_field', 'Data V1', $postId, 'post');

        // 3. Create a revision
        $revisionId = _wp_put_post_revision(['ID' => $postId, 'post_content' => 'revised']);
        $this->assertIsInt($revisionId);
        $this->assertGreaterThan(0, $revisionId);

        // 4. Verify data was duplicated
        global $wpdb;
        $revisionData = $wpdb->get_var($wpdb->prepare(
            "SELECT field_value FROM {$wpdb->prefix}titanfields_data WHERE entity_id = %d AND field_key = %s",
            $revisionId, 'test_field'
        ));

        $this->assertEquals('Data V1', $revisionData, 'Data should be copied to the revision ID.');
    }

    public function test_it_restores_revision_data()
    {
        // 1. Create post and initial data
        $postId = self::factory()->post->create(['post_type' => 'post']);
        $this->storageEngine->update('test_field', 'Data V1', $postId, 'post');

        // 2. Create revision (saves Data V1 to revision ID)
        $revisionId = _wp_put_post_revision(['ID' => $postId, 'post_content' => 'revised']);

        // 3. Update parent post to Data V2
        $this->storageEngine->update('test_field', 'Data V2', $postId, 'post');

        // 4. Restore the revision
        wp_restore_post_revision($revisionId);

        // 5. Verify parent post data is back to Data V1
        $restoredData = $this->storageEngine->get('test_field', $postId, 'post');

        $this->assertEquals('Data V1', $restoredData, 'Restoring a revision should copy revision data back to the parent post.');
    }

    public function test_it_cleans_up_data_on_revision_deletion()
    {
        $postId = self::factory()->post->create(['post_type' => 'post']);
        $this->storageEngine->update('test_field', 'Data V1', $postId, 'post');

        $revisionId = _wp_put_post_revision(['ID' => $postId, 'post_content' => 'revised']);

        // Verify it exists first
        global $wpdb;
        $countBefore = $wpdb->get_var($wpdb->prepare(
            "SELECT COUNT(*) FROM {$wpdb->prefix}titanfields_data WHERE entity_id = %d",
            $revisionId
        ));
        $this->assertEquals(1, $countBefore);

        // Delete revision
        wp_delete_post_revision($revisionId);

        // Verify revision data is gone
        $countAfter = $wpdb->get_var($wpdb->prepare(
            "SELECT COUNT(*) FROM {$wpdb->prefix}titanfields_data WHERE entity_id = %d",
            $revisionId
        ));
        $this->assertEquals(0, $countAfter, 'Revision data should be deleted when the revision is deleted.');

        // Verify parent data remains
        $parentData = $this->storageEngine->get('test_field', $postId, 'post');
        $this->assertEquals('Data V1', $parentData, 'Parent post data should remain intact after revision deletion.');
    }
}
