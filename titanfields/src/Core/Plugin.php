<?php

namespace TitanFields\Core;

use TitanFields\Core\DependencyInjection\Container;
use TitanFields\Core\Storage\StorageEngineInterface;
use TitanFields\Core\Storage\DatabaseStorageEngine;
use TitanFields\Core\Rules\RuleEngineInterface;
use TitanFields\Core\Rules\StandardRuleEngine;
use TitanFields\Core\Registry\GroupRegistry;
use TitanFields\Core\Registry\FieldTypeRegistry;
use TitanFields\Core\Revision\RevisionManager;
use TitanFields\Core\Cache\CacheManager;
use TitanFields\GraphQL\GraphQLRegistrar;
use TitanFields\Blocks\BlockRegistrar;
use TitanFields\API\PublicAPI;
use TitanFields\Admin\AdminMenu;

class Plugin
{
    private static ?Plugin $instance = null;
    private Container $container;

    private function __construct()
    {
        $this->container = new Container();
        $this->bindInterfaces();
        $this->registerServices();
    }

    public static function getInstance(): Plugin
    {
        if (self::$instance === null) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    public function getContainer(): Container
    {
        return $this->container;
    }

    private function bindInterfaces(): void
    {
        $this->container->singleton(StorageEngineInterface::class, DatabaseStorageEngine::class);
        $this->container->singleton(RuleEngineInterface::class, StandardRuleEngine::class);
    }

    private function registerServices(): void
    {
        // Singleton registries
        $this->container->singleton(GroupRegistry::class, GroupRegistry::class);
        $this->container->singleton(FieldTypeRegistry::class, FieldTypeRegistry::class);

        // Subsystems
        $this->container->singleton(RevisionManager::class, RevisionManager::class);
        $this->container->singleton(CacheManager::class, CacheManager::class);
        $this->container->singleton(GraphQLRegistrar::class, GraphQLRegistrar::class);
        $this->container->singleton(BlockRegistrar::class, BlockRegistrar::class);
        $this->container->singleton(PublicAPI::class, PublicAPI::class);
        $this->container->singleton(AdminMenu::class, AdminMenu::class);
    }

    public function boot(): void
    {
        // Boot Cache Manager
        /** @var CacheManager $cacheManager */
        $cacheManager = $this->container->get(CacheManager::class);
        $cacheManager->registerHooks();

        // Boot Revision Manager
        /** @var RevisionManager $revisionManager */
        $revisionManager = $this->container->get(RevisionManager::class);
        $revisionManager->registerHooks();

        // Boot GraphQL
        /** @var GraphQLRegistrar $graphqlRegistrar */
        $graphqlRegistrar = $this->container->get(GraphQLRegistrar::class);
        $graphqlRegistrar->register();

        // Boot the Group Registry (Load Local JSON configs)
        /** @var GroupRegistry $groupRegistry */
        $groupRegistry = $this->container->get(GroupRegistry::class);
        $jsonDir = trailingslashit(get_stylesheet_directory()) . 'titanfields-json';
        $groupRegistry->loadFromLocalJson($jsonDir);

        // Explicitly resolve PublicAPI so the autoloader loads the file and exposes the global functions.
        $this->container->get(PublicAPI::class);

        // Boot Admin Menu
        if (is_admin()) {
            /** @var AdminMenu $adminMenu */
            $adminMenu = $this->container->get(AdminMenu::class);
            $adminMenu->registerHooks();
        }
    }
}
