<?php

namespace Trollfjord\Bundle\CookieBundle\Service;

use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\QueryBuilder;
use Trollfjord\Bundle\CookieBundle\Entity\CookieItem;
use Trollfjord\Bundle\CookieBundle\Entity\CookieMain;
use Trollfjord\Bundle\CookieBundle\Entity\CookieVariation;

class CookieBannerService
{
    /**
     * @var EntityManagerInterface
     */
    private EntityManagerInterface $entityManager;

    /**
     * @var DbTransactions
     */
    private DbTransactions $dbTransactions;

    /**
     * @param EntityManagerInterface $entityManager
     * @param DbTransactions $dbTransactions
     */
    public function __construct(EntityManagerInterface $entityManager, DbTransactions $dbTransactions)
    {
        $this->entityManager = $entityManager;
        $this->dbTransactions = $dbTransactions;
    }

    /**
     * @param string $key
     * @param string|null $regex
     * @param bool $createNewRecord
     * @return bool
     */
    public function isChecked(string $key, string $regex = null, bool $createNewRecord = false): bool
    {

        // 1) find if the key is already in the table variations
        // 2) find if it is associated with an item
        // 3) check if the name from the definition(title variation) is saved in a cookie in Browser

        //if key in not available in table insert in the variations table a new record if third argument is passed

        //find the item id that contains this variation(was set)
        //pass also the cookie Banner id
        $toReturn = false;

        try {

            //check if key (Variations Entity) exist in table.
            $variation = $this->entityManager->getRepository(CookieVariation::class)->findOneBy(['key' => $key]);
            /**@var CookieVariation $variation * */
            if (!empty($variation)) {
                $variationId = $variation->getId();
                //check if was set in the cookie
                if ($this->findVariation($variationId)) {
                    return true;
                }
            } else {
                //save a new record if the flag was passed
                if ($createNewRecord) {
                    $this->registerDefinition($key, $regex);
                }
            }
            //check if the definition(the title of an item) was saved in a cookie
            $item = $this->dbTransactions->getItemByName($key);
            if (!empty($item)) {
                $itemId = $item->getId();
                if ($this->checkIfSavedInACookie($itemId)) {
                    return true;
                }
            }

        }catch (\Exception $exception){

        }

        return $toReturn;
    }

    /**
     * @param string $variationKey
     * @return array
     */
    public function getInfoAboutKey(string $variationKey): array
    {
        $variationInfo = 'do not exist';
        $itemInfo = 'do not exist';
        //check if key (Variations Entity) exist in table.
        //check if key (Variations Entity) exist in table.
        $variation = $this->entityManager->getRepository(CookieVariation::class)->findOneBy(['key' => $variationKey]);
        /**@var CookieVariation $variation * */
        if (!empty($variation)) {
            $variationId = $variation->getId();
            $itemsInformation = $this->dbTransactions->getInfoFromVariationId($variationId);

            $variationInfo = [
                'id' => $variation->getId(),
                'key' => $variation->getKey(),
                'title' => $variation->getTitle(),
                'regex' => $variation->getRegex(),
                'belongToItems' => $itemsInformation,
                'isSavedInACookie' => $this->findVariation($variationId),
            ];
        }

        //check if the definition(the title of an item) was saved in a cookie
        $items = $this->dbTransactions->getItemsByName($variationKey);
        $itemInfoRaw = null;
        if (!empty($items)) {
            foreach ($items as $item) {
                //collect the variations if available
                $variationsAssigned = [];
                if ($item->getVariations()) {
                    foreach ($item->getVariations() as $variation) {
                        $variationsAssigned[] = [
                            'id' => $variation->getId(),
                            'key' => $variation->getKey(),
                            'title' => $variation->getTitle(),
                            'regex' => $variation->getRegex(),
                            'isSavedInACookie' => $this->findVariation($variation->getId()),
                        ];
                    }

                }

                $itemInfoRaw[] = [

                    'id' => $item->getId(),
                    'name' => $item->getName(),
                    'regex' => $item->getRegex(),
                    'title' => $item->getTitle(),
                    'variations' => $variationsAssigned,
                    'belongToCookieBanner' => [
                        'id' => $item->getCookieMain()->getId(),
                        'name' => $item->getCookieMain()->getName(),
                    ],
                    'isItemSavedInACookie' => $this->checkIfSavedInACookie($item->getId()),

                ];
            }

        }
        return [
            'searched-key' => $variationKey,
            'asVariation' => $variationInfo,
            'asItems' => $itemInfoRaw ?: $itemInfo,
        ];
    }

    /**
     * @param string $key
     * @param string|null $regex
     */
    public function registerDefinition(string $key, string $regex = null): void
    {

        //save in the table variation a new record
        $newVariationRecord = new CookieVariation();

        $newVariationRecord->setKey($key);
        $newVariationRecord->setTitle($key);
        if ($regex) {
            $newVariationRecord->setRegex($regex);
        }

        $this->entityManager->persist($newVariationRecord);
        $this->entityManager->flush();
    }

    /**
     * @param $variationId
     * @return bool
     */
    private function findVariation($variationId): bool
    {
        $toReturn = false;
        //find the item id that contains this variation(was set)
        //pass also the cookie Banner id
        $itemsIds = $this->dbTransactions->getItemsIdFromVariationId($variationId);

        //loop in case that more banners have the same variation
        if (!empty($itemsIds)) {
            foreach ($itemsIds as $itemId) {
                //check if exist the item with the variation assigned("$variationId")
                //check if cookie exist 'cookieControlPrefs'
                if ($this->checkIfSavedInACookie($itemId)) {
                    return true;
                }
            }
        }
        return $toReturn;
    }


    private function checkIfSavedInACookie($itemId)
    {
        //check if cookie exist 'cookieControlPrefs'
        if (isset($_COOKIE['cookieControlPrefs'])) {
            //check if in the 'cookieControlPrefs' the Item was set to true or false
            if (isset($_COOKIE['cookieControlPrefs'][$itemId])) {
                //check if exist the key in the cookie
                $cookie = (array)json_decode($_COOKIE['cookieControlPrefs']);
                if (isset($cookie[$itemId])) {
                    if (property_exists($cookie[$itemId], 'checked')) {
                        return $cookie[$itemId]->checked;
                    }
                }

            }
        }
        return false;
    }
}