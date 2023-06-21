<?php


namespace Trollfjord\Bundle\MediaBaseBundle\Storage;


class IndexCache extends \Symfony\Component\Cache\Adapter\FilesystemAdapter
{
    public function setItem(string $key, $value) {
        $item = $this->getItem($key);
        $item->set($value);
    }
}