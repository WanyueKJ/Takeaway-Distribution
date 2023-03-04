<?php

declare(strict_types=1);

namespace Imi\Config\DotEnv;

use Dotenv\Loader\Loader;
use Dotenv\Repository\RepositoryInterface;
use Dotenv\Store\StoreBuilder;

class DotEnv
{
    private function __construct()
    {
    }

    public static function load(array $paths): void
    {
        $repository = \Dotenv\Repository\RepositoryBuilder::createWithNoAdapters()
            ->addAdapter(\Dotenv\Repository\Adapter\EnvConstAdapter::class)
            ->addWriter(\Dotenv\Repository\Adapter\PutenvAdapter::class)
            ->immutable()
            ->make();
        $dotenv = self::create($repository, $paths);
        $dotenv->safeLoad();
    }

    /**
     * Create a new dotenv instance.
     *
     * @param string|string[]      $paths
     * @param string|string[]|null $names
     */
    public static function create(RepositoryInterface $repository, $paths, $names = null, bool $shortCircuit = true, string $fileEncoding = null): \Dotenv\Dotenv
    {
        $builder = null === $names ? StoreBuilder::createWithDefaultName() : StoreBuilder::createWithNoNames();

        if ($paths)
        {
            foreach ((array) $paths as $path)
            {
                $builder = $builder->addPath($path);
            }
        }

        if ($names)
        {
            foreach ((array) $names as $name)
            {
                $builder = $builder->addName($name);
            }
        }

        if ($shortCircuit)
        {
            $builder = $builder->shortCircuit();
        }

        return new \Dotenv\Dotenv($builder->fileEncoding($fileEncoding)->make(), new Parser(), new Loader(), $repository);
    }
}
