<?php

namespace TitanFields\Core\Cache;

class CacheManager
{
    private string $cacheGroup = 'titanfields';

    public function registerHooks(): void
    {
        // Flush the TitanFields cache group when posts are saved or deleted.
        // This ensures the custom storage engine returns fresh data.
        add_action('save_post', [$this, 'flushCacheOnSave'], 99, 3);
        add_action('delete_post', [$this, 'flushCacheOnDelete'], 99, 2);
    }

    /**
     * Flush cache when a post is saved.
     *
     * @param int $postId
     * @param \WP_Post $post
     * @param bool $update
     */
    public function flushCacheOnSave(int $postId, \WP_Post $post, bool $update): void
    {
        // Avoid flushing during autosaves or revision creation (handled separately if needed)
        if (defined('DOING_AUTOSAVE') && DOING_AUTOSAVE) {
            return;
        }

        if (wp_is_post_revision($postId)) {
            return;
        }

        $this->flushTitanFieldsGroup();
    }

    /**
     * Flush cache when a post is deleted.
     *
     * @param int $postId
     * @param \WP_Post $post
     */
    public function flushCacheOnDelete(int $postId, \WP_Post $post): void
    {
        $this->flushTitanFieldsGroup();
    }

    /**
     * Helper to flush the entire 'titanfields' cache group.
     * Note: wp_cache_flush_group is available in WP 6.1+
     */
    private function flushTitanFieldsGroup(): void
    {
        if (function_exists('wp_cache_flush_group')) {
            wp_cache_flush_group($this->cacheGroup);
        } else {
            // Fallback for older WP versions: Since we can't delete by group or wildcard reliably,
            // the nuclear option is wp_cache_flush(). In a true enterprise environment pre-6.1,
            // you'd track keys or use Memcached/Redis specific tags.
            wp_cache_flush();
        }
    }
}
