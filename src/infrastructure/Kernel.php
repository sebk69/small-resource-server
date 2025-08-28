<?php
/*
 * This file is a part of Small Swoole Resource Server
 * Copyright 2025 - Sébastien Kus
 * Under MIT Licence
 */

namespace Infrastructure;

use Infrastructure\Orm\Manager\ResourceDataManager;
use Infrastructure\Orm\Manager\ResourceManager;
use Small\CleanApplication\Facade;
use Small\Env\Env;
use Small\SwooleEntityManager\Factory\ConnectionFactory;
use Small\SwooleEntityManager\Factory\EntityManagerFactory;
use Small\SwooleEntityManager\Layers\LayerCollection;
use Small\SwoolePatterns\Manager\Connection\MysqlClientManager;
use Small\SwoolePatterns\Manager\StoredListManager\UnifiedTableStoredListManager;
use Small\SwoolePatterns\Resource\ResourceFactory;

/**
 * @codeCoverageIgnore
 */
class Kernel
{

    public static ConnectionFactory $connectionFactory;
    public static EntityManagerFactory $entityManagerFactory;
    public static Env $env;
    public static ResourceFactory $resourceFactory;
    public static bool $test = false;

    public function boot(string $env = 'prod'): self
    {

        self::$env = new Env();
        if ($env == 'test') {
            Kernel::$env->set('RESOURCE_READ', 'read');
            Kernel::$env->set('RESOURCE_READ_LOCK', 'read_lock');
            Kernel::$env->set('RESOURCE_WRITE', 'write');
        }

        if ($env == 'prod') {
            $this->initMysql();
            self::$entityManagerFactory = new EntityManagerFactory(
                self::$connectionFactory,
            );
        } else {
            self::$test = true;
            self::$entityManagerFactory = new EntityManagerFactory(null);
        }


        $this->initCleanApplication();

        $this->initResources();

        return $this;

    }

    private function initMysql(): void {

        new MysqlClientManager(
            'sys',
            self::$env->get('MYSQL_HOST', '127.0.0.1'),
            self::$env->get('MYSQL_PORT','3306'),
            'utf8',
            self::$env->get('MYSQL_USER', 'root'),
            self::$env->get('MYSQL_PASSWORD', ''),
        )   ->create()
            ->exec(
                'create schema if not exists `' .
                static::$env->get('mysql_database', 'small_swoole_resource') .
                '`;'
            );

        self::$connectionFactory = new ConnectionFactory(
            [
                'default' => [
                    'type' => 'mysql',
                    'host' => self::$env->get('MYSQL_HOST', '127.0.0.1'),
                    'encoding' => 'utf8',
                    'port' => self::$env->get('MYSQL_PORT','3306'),
                    'database' => self::$env->get('MYSQL_DATABASE', 'small_swoole_resource'),
                    'user' => self::$env->get('MYSQL_USER', 'root'),
                    'password' => self::$env->get('MYSQL_PASSWORD', ''),
                ]
            ], 'default'
        );

        if (!is_dir($dir = __DIR__ . '/../databaseLayers')) {
            throw new \Exception('Database layers directory does not exist (' . $dir . ')');
        }
        new LayerCollection()
            ->setConnectionFactory(self::$connectionFactory)
            ->setParameterBag([
                'app' => $dir
            ])
            ->loadPath('app', $dir)
            ->execute();

    }

    private function initCleanApplication(): void
    {

        Facade::setParameter(
            'resourceManager',
            self::$entityManagerFactory
                ->get(ResourceManager::class)
        );

        Facade::setParameter(
            'resourceDataManager',
            self::$entityManagerFactory
                ->get(ResourceDataManager::class)
        );

    }

    private function initResources(): void
    {

        UnifiedTableStoredListManager::masterInit(1024*1024);
        self::$resourceFactory = new ResourceFactory([], new UnifiedTableStoredListManager());

    }

}