<?php

namespace TitanFields\Core\Revision;

class RevisionManager
{
    private string $tableName;

    public function __construct()
    {
        global $wpdb;
        $this->tableName = $wpdb->prefix . 'titanfields_data';
    }

    /**
     * Hook into WordPress revision actions.
     */
    public function registerHooks(): void
    {
        add_action('wp_insert_post', [$this, 'saveRevisionData'], 10, 2);
        add_action('wp_restore_post_revision', [$this, 'restoreRevisionData'], 10, 2);
        add_action('delete_post', [$this, 'deleteRevisionData']);
    }

    /**
     * Copy TitanFields data from the parent post to the new revision ID.
     * Fired during wp_insert_post when the post is a revision.
     *
     * @param int $postId
     * @param \WP_Post $post
     */
    public function saveRevisionData(int $postId, \WP_Post $post): void
    {
        if (!wp_is_post_revision($post)) {
            return;
        }

        $parentId = wp_is_post_revision($postId);
        if (!$parentId) {
            return;
        }

        global $wpdb;

        // Copy all titanfields data from parent_id to the new revision $postId
        // We use INSERT ... SELECT to duplicate the rows efficiently
        $sql = $wpdb->prepare(
            "INSERT INTO {$this->tableName} (entity_id, entity_type, field_key, field_value, parent_id, row_index)
            SELECT %d, entity_type, field_key, field_value, parent_id, row_index
            FROM {$this->tableName}
            WHERE entity_id = %d AND entity_type = 'post'",
            $postId,
            $parentId
        );

        $wpdb->query($sql);
    }

    /**
     * Copy TitanFields data from the revision ID back to the parent post ID.
     * Fired when a revision is restored.
     *
     * @param int $postId The parent post ID being restored.
     * @param int $revisionId The revision ID being restored from.
     */
    public function restoreRevisionData(int $postId, int $revisionId): void
    {
        global $wpdb;

        // 1. Delete current data for the parent post
        $wpdb->delete($this->tableName, [
            'entity_id' => $postId,
            'entity_type' => 'post'
        ]);

        // 2. Copy the revision data back to the parent post
        $sql = $wpdb->prepare(
            "INSERT INTO {$this->tableName} (entity_id, entity_type, field_key, field_value, parent_id, row_index)
            SELECT %d, entity_type, field_key, field_value, parent_id, row_index
            FROM {$this->tableName}
            WHERE entity_id = %d AND entity_type = 'post'",
            $postId,
            $revisionId
        );

        $wpdb->query($sql);

        // Note: Caching invalidation would ideally happen here by interacting with StorageEngineInterface
        // or by clearing the cache group related to this $postId.
        wp_cache_delete("tf_post_{$postId}_*", 'titanfields'); // Conceptual wildcard clear, actual implementation varies by cache backend
    }

    /**
     * Clean up TitanFields data when a post or revision is deleted.
     *
     * @param int $postId
     */
    public function deleteRevisionData(int $postId): void
    {
        global $wpdb;

        $wpdb->delete($this->tableName, [
            'entity_id' => $postId,
            'entity_type' => 'post'
        ]);
    }
}
