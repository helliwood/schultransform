<?php

namespace Trollfjord\Bundle\PublicUserBundle\TemplateEngine\Element;

use DOMNodeList;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\HttpFoundation\Session\SessionInterface;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Symfony\Component\Security\Core\Security;
use Trollfjord\Bundle\ContentTreeBundle\TemplateEngine\Element\AbstractElement;
use Trollfjord\Bundle\ContentTreeBundle\TemplateEngine\Element\Attribute\Type\AttributeTypeEnum;
use Trollfjord\Bundle\ContentTreeBundle\TemplateEngine\Element\PublishVariablesInterface;
use Trollfjord\Bundle\ContentTreeBundle\TemplateEngine\Exception\Exception;
use Trollfjord\Bundle\PublicUserBundle\Entity\User as UserEntity;
use Trollfjord\Core\Entity\User as BackendUser;
use function header;

/**
 * Class Text
 *
 * @author  Tim Wettstein <wettstein@helliwood.com>
 *
 * @package Trollfjord\Bundle\PublicUserBundle\TemplateEngine\Element
 */
class User extends AbstractElement implements PublishVariablesInterface
{
    /**
     * @var string
     */
    protected static string $name = 'user';

    /**
     * @var string|null
     */
    protected ?string $data = null;

    /**
     * @var UrlGeneratorInterface
     */
    protected $router;

    /**
     * @var Security
     */
    protected Security $security;

    /**
     * @var UserEntity
     */
    protected $user;

    /**
     * @var bool
     */
    protected $inBackend = false;

    /**
     * SessionInterface
     */
    protected $session;

    /**
     * @var Request
     */
    protected $request;

    /**
     * Text constructor.
     */
    public function __construct(UrlGeneratorInterface $router, Security $security, SessionInterface $session, RequestStack $requestStack)
    {
        parent::__construct();
        $this->router = $router;
        $this->security = $security;
        $this->session = $session;
        $this->request = $requestStack->getCurrentRequest();
        $this->user = $this->security->getUser();
        if ($this->user instanceof BackendUser) $this->inBackend = true;
        if (! $this->user instanceof UserEntity) $this->user = null;

        $this->createAttribute("value", false, "code", new AttributeTypeEnum(["id", "code", "username", "email", "displayName"]));
        $this->createAttribute("check", false, "hasUser", new AttributeTypeEnum(["hasUser", "hasNoUser", "isNotBackend", "isBackend"]));
        $this->createAttribute("role", false);
        $this->createAttribute("notrole", false);
        $this->createAttribute("redirect", false);
        $this->createAttribute("notice", false);
    }

    /**
     * @return bool
     */
    public function hasSubContent(): bool
    {
        return true;
    }

    /**
     * @return bool
     * @throws Exception
     */
    private function checkHasUserForIf(): bool
    {
        if ($this->inBackend) return true;

        if ($this->getAttributeValue("check") == "hasUser" && $this->user !== null) return true;
        if ($this->getAttributeValue("check") == "isBackend" && $this->inBackend === true) return true;
        if ($this->getAttributeValue("check") == "isNotBackend" && $this->inBackend === false) return true;
        if ($this->getAttributeValue("check") == "hasNoUser" && $this->user === null) return true;
        if ($this->getAttributeValue("check") == "hasUser" || $this->getAttributeValue("check") == "hasNoUser") {
            return false;
        }
        return true;
    }

    /**
     * @return bool
     * @throws Exception
     */
    private function checkRoleForIf(): bool
    {
        if ($this->inBackend) return true;

        if ($this->getAttributeValue("role") != "") {
            if ($this->security->isGranted($this->getAttributeValue("role"))) {
                return true;
            }
            return false;
        }

        if ($this->getAttributeValue("notrole") != "") {
            if (! $this->security->isGranted($this->getAttributeValue("notrole"))) {
                return true;
            }
            return false;
        }

        return true;
    }

    /**
     * @return DOMNodeList|null
     */
    public function renderNodeList(): ?DOMNodeList
    {
        $notice = $this->getAttributeValue("notice");
        if (! $this->checkHasUserForIf() && $this->getAttributeValue("redirect") != "") {
            $routeName = $this->request->get('_route');
            $this->session->set("route_after_login", $routeName);
            if (! empty($notice)) {
                $this->session->getFlashBag()->add('notice', $notice);
            }
            header("Location: " . $this->router->generate($this->getAttributeValue("redirect")));
            exit;
        }
        if (! $this->checkRoleForIf() && $this->getAttributeValue("redirect") != "") {
            $routeName = $this->request->get('_route');
            $this->session->set("route_after_login", $routeName);
            if (! empty($notice)) {
                $this->session->getFlashBag()->add('notice', $notice);
            }
            header("Location: " . $this->router->generate($this->getAttributeValue("redirect")));
            exit;
        } elseif (! $this->checkRoleForIf()) {
            return null;
        }

        $value = $this->getAttributeValue("value");
        if ($nodeList = $this->getRenderChildes()) {
            return $nodeList;
        }
        if ($this->user) {
            switch ($value) {
                case "code":
                    $this->getDOMElement()->nodeValue = $this->user->getCode();
                    break;
                case "id":
                    $this->getDOMElement()->nodeValue = $this->user->getId();
                    break;
                case "username":
                    $this->getDOMElement()->nodeValue = $this->user->getUsername();
                    break;
                case "email":
                    $this->getDOMElement()->nodeValue = $this->user->getEmail();
                    break;
                case "displayName":
                    $this->getDOMElement()->nodeValue = $this->user->getDisplayName();
                    break;
            }
        }
        if ($this->checkHasUserForIf()) {
            return $this->getDOMElement()->childNodes;
        }
        return null;
    }

    /**
     * @return DOMNodeList|false
     */
    private function getRenderChildes()
    {
        if ($this->getDOMElement()->hasChildNodes() && $this->checkHasUserForIf()) {
            $quelltext = "";
            foreach ($this->getDOMElement()->childNodes as $node) {
                $quelltext .= $this->getDOMElement()->ownerDocument->saveHtml($node);
            }

            if ($this->user) {
                $quelltext = str_replace('@id', $this->user->getId(), $quelltext);
                $quelltext = str_replace('@code', $this->user->getCode(), $quelltext);
                $quelltext = str_replace('@username', $this->user->getUsername(), $quelltext);
                $quelltext = str_replace('@email', $this->user->getEmail(), $quelltext);
                $quelltext = str_replace('@displayName', $this->user->getDisplayName(), $quelltext);
            }

            $fragment = $this->getDOMElement()->ownerDocument->createDocumentFragment();
            $fragment->appendXML($quelltext);
            return $fragment->childNodes;

        }
        return false;
    }

    /**
     * @return FormBuilderInterface|null
     */
    public function getFormBuilderForChildren(): ?FormBuilderInterface
    {
        return null;
    }

    /**
     * @param $data
     */
    public function setData($data)
    {
        $this->data = $data;
    }

    /**
     * @return string[]
     * @throws Exception
     */
    public function publishVariables(): array
    {
        $id = $this->getScopedId();
        $_data = [];
        if ($this->user) {
            $_data = [
                $id . "_id" => $this->user->getId(),
                $id . "_value" => $this->user->getCode(),
                $id . "_code" => $this->user->getCode(),
                $id . "_username" => $this->user->getUsername(),
                $id . "_email" => $this->user->getEmail(),
                $id . "_displayName" => $this->user->getDisplayName(),
            ];
        }
        return $_data;
    }
}