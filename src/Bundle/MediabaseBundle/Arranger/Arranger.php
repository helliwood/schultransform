<?php

namespace Trollfjord\Bundle\MediaBaseBundle\Arranger;

use Symfony\Component\DependencyInjection\ContainerInterface;
use Trollfjord\Bundle\MediaBaseBundle\Storage\Store;

class Arranger
{
    /**
     * @var ArrangerInterface[]
     */
    private array $arrangers;

    public function __construct(array $mimeTypes, Store $store, ContainerInterface $container)
    {
        $this->arrangers = [];
        foreach ($mimeTypes as $mimeType => $mimeTypeOption)
        {
            if(!isset($mimeTypeOption['arranger'])) {
                break;
            }
            if (! is_subclass_of($mimeTypeOption['arranger'], ArrangerInterface::class)) {
                throw new \Exception($mimeTypeOption['arranger'].": Arranger must implement ArrangerInterface!");
            }
            $arranger = new $mimeTypeOption['arranger']();
            $arranger->setStorage($store->getStorage());
            $this->arrangers[str_replace("/", "\/", $mimeType)] = $arranger;

        }
    }

    /**
     * @return ArrangerInterface[]
     */
    public function getArrangers(): array
    {
        return $this->arrangers;
    }

}