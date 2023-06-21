<?php

namespace Trollfjord\Bundle\CookieBundle\Service;

use Doctrine\ORM\EntityManagerInterface;
use Trollfjord\Bundle\CookieBundle\Entity\CookieItem;
use Trollfjord\Bundle\CookieBundle\Entity\CookieMain;
use Trollfjord\Bundle\CookieBundle\Entity\CookieVariation;

class DbTransactions
{
    private EntityManagerInterface $entityManager;

    /**
     * @param EntityManagerInterface $entityManager
     */
    public function __construct(EntityManagerInterface $entityManager)
    {
        $this->entityManager = $entityManager;
    }


    public function test()
    {

        return $this->entityManager->getRepository(CookieVariation::class)->findAll();

    }

    public function getNotSelectedOptions($itemId, $cookieMainId)
    {
        //select all items from a Main cookie Banner
        $itemsWithMainId = $this->entityManager->getRepository(CookieItem::class)->findBy(['cookieMain' => $cookieMainId]);
        //get the ids of the items
        $variations = [];

        //fill in the array with items ids for this record Main
        if (!empty($itemsWithMainId)) {
            foreach ($itemsWithMainId as $item) {
                if ($item->getId() == $itemId) {
                    continue;
                }
                $variations[] = $item->getVariations();
            }
        }

        $variationsIds = [];

        if (!empty($variations)) {
            foreach ($variations as $variation) {
                if (!$variation->isEmpty()) {
                    foreach ($variation->getValues() as $value)
                        $variationsIds[] = $value->getId();
                }
            }
        }

        //select only the options that were not used for the 'Cookie Banner'
        $queryBuilder2 = $this->entityManager->getRepository(CookieVariation::class)->createQueryBuilder('cv');

        if (!empty($variationsIds)) {
            $result2 = $queryBuilder2->where('cv.id NOT IN (' . implode(", ", $variationsIds) . ')');
        } else {
            $result2 = $queryBuilder2;
        }

        return $result2;
    }

    /**
     * @param $variationKey
     * @return int|null
     */
    public function getVariationId($variationKey): ?int
    {

        //no sensitive case checked
        $variation = $this->entityManager->getRepository(CookieVariation::class)->findOneBy(['key' => $variationKey]);

        if ($variation && (is_object($variation))) {

            if (method_exists($variation, 'getId')) {

                return $variation->getId();

            }

        }
        return null;
    }
    /**
     * @param $variationKey
     * @return mixed|object|CookieVariation|null
     */
    public function getVariation($variationKey): mixed
    {
        //no sensitive case checked
        return $this->entityManager->getRepository(CookieVariation::class)->findOneBy(['key' => $variationKey]);
    }

    public function getItemIdFromVariation($variationId, $cookieBannerId)
    {

        //select all items from a Main cookie Banner
        $items = $this->entityManager->getRepository(CookieItem::class)->findBy(['cookieMain' => $cookieBannerId]);

        $toReturn = null;

        //fill in the array with items ids for this record Main
        if (!empty($items)) {
            foreach ($items as $item) {
                $variation = $item->getVariations();
                if (!$variation->isEmpty()) {
                    foreach ($variation->getValues() as $value)
                        if ($value->getId() == $variationId) {
                            $toReturn = $item->getId();
                            break;
                        }
                }
            }

        }
        return $toReturn;
    }

    /**
     * @param $variationId
     * @return array
     */
    public function getItemsIdFromVariationId($variationId): array
    {

        //select all items from a Main cookie Banner
        $items = $this->entityManager->getRepository(CookieItem::class)->findAll();

        $toReturn = [];

        //if the variation was set in different banners(items)
        //fill in the array with items ids for this record Main

        if (!empty($items)) {
            foreach ($items as $item) {
                $variation = $item->getVariations();
                if (!$variation->isEmpty()) {
                    foreach ($variation->getValues() as $value)
                        if ($value->getId() == $variationId) {
                            $toReturn[] = $item->getId();
                        }
                }
            }

        }
        return $toReturn;
    }

    /**
     * @param $variationId
     * @return array
     */
    public function getInfoFromVariationId($variationId): array
    {

        //select all items from a Main cookie Banner
        $items = $this->entityManager->getRepository(CookieItem::class)->findAll();

        $toReturn = [];

        //if the variation was set in different banners(items)
        //fill in the array with items ids for this record Main

        if (!empty($items)) {
            foreach ($items as $item) {
                $variation = $item->getVariations();
                if (!$variation->isEmpty()) {
                    foreach ($variation->getValues() as $value)
                        if ($value->getId() == $variationId) {
                            $toReturn[] = [
                                'item' => [
                                    'id' => $item->getId(),
                                    'name' => $item->getName(),
                                    'regex' => $item->getRegex(),
                                    'title' => $item->getTitle(),
                                    'belongToCookieBanner' => [
                                        'id' =>$item->getCookieMain()->getId(),
                                        'name' =>$item->getCookieMain()->getName(),
                                    ]
                                ]
                            ];
                        }
                }
            }

        }
        return $toReturn;
    }

    public function getItemByName(string $name)
    {
        $item = $this->entityManager->getRepository(CookieItem::class)->findOneBy(['name' => $name]);

        return $item;
    }

    /**
     * @param string $name
     * @return array|object[]|CookieItem[]
     */
    public function getItemsByName(string $name): array
    {
        $item = $this->entityManager->getRepository(CookieItem::class)->findBy(['name' => $name]);

        return $item;
    }

}