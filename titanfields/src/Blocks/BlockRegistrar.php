<?php

namespace TitanFields\Blocks;

class BlockRegistrar
{
    /**
     * Register a Gutenberg block connected to TitanFields.
     *
     * @param string $blockName The full block name (e.g. 'titanfields/hero').
     * @param array $args Arguments including 'field_group_key' and optional 'render_template'.
     */
    public function registerBlock(string $blockName, array $args = []): void
    {
        $fieldGroupKey = $args['field_group_key'] ?? '';
        $renderTemplate = $args['render_template'] ?? '';

        $blockArgs = [
            'api_version' => 2,
            'editor_script' => 'titanfields-block-editor', // Assuming the built script is enqueued with this handle
            'attributes' => [
                'field_group_key' => [
                    'type' => 'string',
                    'default' => $fieldGroupKey,
                ],
                'data' => [
                    'type' => 'object',
                    'default' => [],
                ]
            ],
            'render_callback' => function ($attributes, $content) use ($renderTemplate) {
                return $this->renderBlock($attributes, $content, $renderTemplate);
            }
        ];

        // Merge any additional native WP arguments
        if (isset($args['wp_args']) && is_array($args['wp_args'])) {
            $blockArgs = array_merge($blockArgs, $args['wp_args']);
        }

        register_block_type($blockName, $blockArgs);
    }

    /**
     * Internal render callback to process the block output.
     *
     * @param array $attributes Block attributes.
     * @param string $content Inner blocks content.
     * @param string $templatePath Path to the PHP template file.
     * @return string The rendered HTML.
     */
    private function renderBlock(array $attributes, string $content, string $templatePath): string
    {
        // Extract data for the template context
        $fields = $attributes['data'] ?? [];

        // Expose $fields to the template (similar to ACF's get_field)
        // Note: For a fully featured implementation, we would pass this data
        // through the FieldTypeRegistry classes to call `formatValue()` before rendering.

        if ($templatePath && file_exists($templatePath)) {
            ob_start();
            // Provide context to the template
            $is_preview = defined('REST_REQUEST') && REST_REQUEST;
            include $templatePath;
            return ob_get_clean();
        }

        // Fallback simple render if no template provided
        $output = '<div class="titanfields-block-fallback">';
        foreach ($fields as $key => $value) {
            $output .= sprintf('<div><strong>%s:</strong> %s</div>', esc_html($key), esc_html(is_array($value) ? wp_json_encode($value) : $value));
        }
        $output .= '</div>';

        return $output;
    }
}
