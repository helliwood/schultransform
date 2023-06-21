<?php

namespace Trollfjord\Bundle\MediaBaseBundle\Storage;


use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Class Store
 * @package Trollfjord\Bundle\MediaBaseBundle\Storage
 * @author Tim Wettstein <wettstein@helliwood.com>
 */
class Store
{
    /**
     * @var StorageInterface
     */
    private StorageInterface $storage;

    /**
     * @var IndexCache
     */
    private IndexCache $storageCache;

    /**
     * @var array
     */
    private $mimeTypes = [];

    public function __construct(string $service, string $service_cache, $mimeTypes, ContainerInterface $container)
    {
        $this->storage = $container->get($service);
        $this->storageCache = $container->get($service_cache);
        $this->mimeTypes = $mimeTypes;
    }

    /**
     * @return StorageInterface
     */
    public function getStorage(): StorageInterface
    {
        return $this->storage;
    }

    /**
     * @return IndexCache
     */
    public function getStorageCache(): IndexCache
    {
        return $this->storageCache;
    }

    /**
     * @return array
     */
    public function getMimeTypes(): array
    {
        return $this->mimeTypes;
    }

    /**
     * @param array $mimeTypes
     * @return Store
     */
    public function setMimeTypes(array $mimeTypes): Store
    {
        $this->mimeTypes = $mimeTypes;
        return $this;
    }
}
