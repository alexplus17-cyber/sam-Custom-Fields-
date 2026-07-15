<?php

namespace TitanFields\GraphQL;

use TitanFields\Core\Registry\GroupRegistry;
use TitanFields\Core\Storage\StorageEngineInterface;

class GraphQLRegistrar
{
    private GroupRegistry $groupRegistry;
    private StorageEngineInterface $storageEngine;

    public function __construct(GroupRegistry $groupRegistry, StorageEngineInterface $storageEngine)
    {
        $this->groupRegistry = $groupRegistry;
        $this->storageEngine = $storageEngine;
    }

    /**
     * Hook into WPGraphQL to register TitanFields types and fields.
     */
    public function register(): void
    {
        // Check if WPGraphQL is active by checking for the action hook
        add_action('graphql_register_types', [$this, 'registerTypesAndFields']);
    }

    /**
     * Callback for 'graphql_register_types'.
     */
    public function registerTypesAndFields(): void
    {
        if (!function_exists('register_graphql_object_type')) {
            return; // Safety check in case the hook runs but function isn't available
        }

        // Register a generic type to hold our field group data
        register_graphql_object_type('TitanFieldGroup', [
            'description' => 'A field group from TitanFields',
            'fields' => [
                'groupKey' => [
                    'type' => 'String',
                    'description' => 'The unique key of the field group',
                ],
                'title' => [
                    'type' => 'String',
                    'description' => 'The title of the field group',
                ],
                'fieldsData' => [
                    'type' => 'String', // Returning as a JSON string for simplicity in phase 5. Real implementation might define nested types.
                    'description' => 'JSON encoded string of the field values for this group',
                ],
            ],
        ]);

        // Register the field on the Post object
        register_graphql_field('Post', 'titanFields', [
            'type' => ['list_of' => 'TitanFieldGroup'],
            'description' => 'TitanFields data associated with this post',
            'resolve' => function ($post, $args, $context, $info) {
                // Get the real post ID
                $postId = $post->databaseId ?? $post->ID ?? 0;
                $postType = $post->postType ?? get_post_type($postId);

                if (!$postId) {
                    return null;
                }

                // Resolve which groups are active for this post
                $activeGroups = $this->groupRegistry->getActiveGroups([
                    'entity_id' => $postId,
                    'post_type' => $postType,
                ]);

                if (empty($activeGroups)) {
                    return [];
                }

                $resolvedData = [];

                foreach ($activeGroups as $group) {
                    $groupData = [
                        'groupKey' => $group->getKey(),
                        'title' => $group->getTitle(),
                    ];

                    $fieldValues = [];
                    foreach ($group->getFields() as $field) {
                        // Retrieve value from DB using our StorageEngine
                        $rawVal = $this->storageEngine->get($field->getKey(), $postId, 'post');

                        // Pass through the field's formatter
                        $fieldValues[$field->getName()] = $field->formatValue($rawVal, $postId, 'post');
                    }

                    // For simplicity, encode the associative array of values into a JSON string
                    // A true enterprise implementation might dynamically register GraphQL types per FieldGroup
                    $groupData['fieldsData'] = wp_json_encode($fieldValues);

                    $resolvedData[] = $groupData;
                }

                return $resolvedData;
            }
        ]);
    }
}
