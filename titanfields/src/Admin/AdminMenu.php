<?php

namespace TitanFields\Admin;

class AdminMenu
{
    /**
     * Register admin hooks.
     */
    public function registerHooks(): void
    {
        add_action('admin_menu', [$this, 'addAdminMenu']);
        add_action('admin_enqueue_scripts', [$this, 'enqueueAssets']);
    }

    /**
     * Register the main menu and submenus.
     */
    public function addAdminMenu(): void
    {
        // Main menu (acting as a container)
        add_menu_page(
            'TitanFields',
            'TitanFields',
            'manage_options',
            'titanfields',
            [$this, 'renderAppShell'],
            'dashicons-layout', // Or custom icon
            80
        );

        // Submenus
        // The first submenu overlaps the main slug so the label reads "Field Groups" instead of the main menu name.
        add_submenu_page(
            'titanfields',
            'Field Groups',
            'Field Groups',
            'manage_options',
            'titanfields',
            [$this, 'renderAppShell']
        );

        add_submenu_page(
            'titanfields',
            'Post Types',
            'Post Types',
            'manage_options',
            'titanfields-post-types',
            [$this, 'renderAppShell']
        );

        add_submenu_page(
            'titanfields',
            'Taxonomies',
            'Taxonomies',
            'manage_options',
            'titanfields-taxonomies',
            [$this, 'renderAppShell']
        );

        add_submenu_page(
            'titanfields',
            'Options Pages',
            'Options Pages',
            'manage_options',
            'titanfields-options-pages',
            [$this, 'renderAppShell']
        );

        add_submenu_page(
            'titanfields',
            'Tools',
            'Tools',
            'manage_options',
            'titanfields-tools',
            [$this, 'renderAppShell']
        );
    }

    /**
     * The callback function to render the root div for React.
     */
    public function renderAppShell(): void
    {
        // Get the current page slug from the URL query string
        $currentPage = isset($_GET['page']) ? sanitize_text_field($_GET['page']) : 'titanfields';

        echo '<div class="wrap">';
        echo '<div id="titanfields-react-root" data-current-page="' . esc_attr($currentPage) . '"></div>';
        echo '</div>';
    }

    /**
     * Enqueue React build assets and localization data.
     *
     * @param string $hook The current admin page hook.
     */
    public function enqueueAssets(string $hook): void
    {
        // Only load on TitanFields pages
        if (strpos($hook, 'titanfields') === false) {
            return;
        }

        $pluginUrl = plugin_dir_url(dirname(__DIR__));

        // Define paths to the built assets
        $jsPath = $pluginUrl . 'build/admin-groups.build.js';
        $cssPath = $pluginUrl . 'build/admin-groups.build.css';

        // Enqueue CSS
        wp_enqueue_style(
            'titanfields-admin-css',
            $cssPath,
            ['wp-components'],
            '1.0.0'
        );

        // Enqueue JS
        wp_enqueue_script(
            'titanfields-admin-js',
            $jsPath,
            ['wp-element', 'wp-components', 'wp-data', 'wp-api-fetch'], // Core WP deps
            '1.0.0',
            true
        );

        // Localize script to pass nonce and API root
        wp_localize_script(
            'titanfields-admin-js',
            'titanfieldsData',
            [
                'restUrl' => esc_url_raw(rest_url()),
                'nonce'   => wp_create_nonce('wp_rest'),
            ]
        );
    }
}
