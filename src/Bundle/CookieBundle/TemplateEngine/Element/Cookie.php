<?php

namespace Trollfjord\Bundle\CookieBundle\TemplateEngine\Element;

use DOMNodeList;
use Trollfjord\Bundle\ContentTreeBundle\TemplateEngine\Element\AbstractElement;
use Trollfjord\Bundle\ContentTreeBundle\TemplateEngine\Element\PublishVariablesInterface;
use Trollfjord\Bundle\ContentTreeBundle\TemplateEngine\Exception\Exception;
use Trollfjord\Bundle\CookieBundle\Repository\CookieVariationRepository;
use Trollfjord\Bundle\CookieBundle\Service\DbTransactions;
use Twig\Environment;


/**
 * Class Cookie
 *
 * @author  Juan Mayoral <mayoral@helliwood.com>
 *
 * @package Trollfjord\Bundle\CookieBundle\TemplateEngine\Element
 */
class Cookie extends AbstractElement implements PublishVariablesInterface
{

    /**
     * @var DbTransactions
     */
    protected DbTransactions $dbTransactions;

    /**
     * @var string
     */
    protected static string $name = 'cookie';

    /**
     * @var array
     */
    protected array $data = [];

    /**
     * @var CookieVariationRepository
     */
    protected CookieVariationRepository $variationRepository;

    /**
     * @var Environment
     */
    protected Environment $twig;

    /**
     * Text constructor.
     * @param CookieVariationRepository $variationRepository
     * @param Environment $twig
     * @param DbTransactions $dbTransactions
     */
    public function __construct(CookieVariationRepository $variationRepository, Environment $twig, DbTransactions $dbTransactions)
    {

        parent::__construct();
        $this->createAttribute("id", true);
        $this->createAttribute("key", true);
        $this->createAttribute("cookie-banner-id", true);

        $this->variationRepository = $variationRepository;
        $this->dbTransactions = $dbTransactions;
        $this->twig = $twig;
    }

    /**
     * @return DOMNodeList|null
     * @throws Exception
     */
    public function renderNodeList(): ?DOMNodeList
    {
        //Cookie read services / method
        //if cookie set?

        if ($this->isCookieSet()) {
            return $this->getDOMElement()->childNodes;
        }

        return null;
    }

    /**
     * @return bool[]
     * @throws Exception
     */
    public function publishVariables(): array
    {

        $id = $this->getScopedId();
        return [$id => $this->isCookieSet()];

    }

    /**
     * @return bool
     * @throws Exception
     */
    private function isCookieSet(): bool
    {

        // dd( $this->getAttributeValue("keyId"));
        $key = $this->getAttributeValue("key");
        $cookieBannerId = $this->getAttributeValue("cookie-banner-id");

        $toReturn = false;

        //check if key (Variations Entity) exist in table.
        $variationId = $this->dbTransactions->getVariationId($key);

        if ($variationId) {
            $this->data['variationId'] = $variationId;
            //find the item id that contains this variation(was set)
            //pass also the cookie Banner id
            $itemId = $this->dbTransactions->getItemIdFromVariation($variationId, $cookieBannerId);

            //check if exist the item with the variation assigned("$variationId")
            if ($itemId) {
                $this->data['itemId'] = $itemId;
                //check if cookie exist 'cookieControlPrefs'
                if (isset($_COOKIE['cookieControlPrefs'])) {
                    //check if in the 'cookieControlPrefs' the Item was set to true or false
                    if (isset($_COOKIE['cookieControlPrefs'][$itemId])) {
                        //check if exist the key in the cookie
                        $cookie = (array)json_decode($_COOKIE['cookieControlPrefs']);
                        if (isset($cookie[$itemId])) {
                            if (property_exists($cookie[$itemId], 'checked')) {
                                $toReturn = $cookie[$itemId]->checked;
                            }
                        }

                    }
                }
            }

        }

        return $toReturn;
    }
}