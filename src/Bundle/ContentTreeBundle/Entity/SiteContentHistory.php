<?php

namespace Trollfjord\Bundle\ContentTreeBundle\Entity;

use DateTime;
use Doctrine\ORM\Mapping as ORM;
use Exception;
use Trollfjord\Core\Entity\AbstractEntity;
use Trollfjord\Core\Entity\User;
use function in_array;
use function is_array;
use function json_encode;

/**
 * SiteContentHistory Entity
 *
 * @author Maurice Karg <karg@helliwood.com>
 *
 * @package Trollfjord\Bundle\ContentTreeBundle\Entity
 *
 * @ORM\Entity()
 */
class SiteContentHistory extends AbstractEntity
{
    public const ACTION_ADDED = "added";
    public const ACTION_UPDATED = "updated";
    public const ACTION_CONTENT_UPDATED = "content_updated";
    public const ACTION_TEMPLATE_UPDATED = "template_updated";
    public const ACTION_DELETED = "deleted";

    /**
     * @var array|string[]
     */
    public static array $ACTIONS = [
        self::ACTION_ADDED,
        self::ACTION_UPDATED,
        self::ACTION_CONTENT_UPDATED,
        self::ACTION_TEMPLATE_UPDATED,
        self::ACTION_DELETED
    ];

    /**
     * @var int|null
     * @ORM\Id()
     * @ORM\GeneratedValue()
     * @ORM\Column(type="integer", options={"unsigned":true})
     */
    protected ?int $id;

    /**
     * @var int|null
     * @ORM\Column(type="integer", options={"unsigned":true}, nullable=false)
     */
    protected ?int $siteContentId;

    /**
     * @var Site|null
     * @ORM\ManyToOne(targetEntity="Site", inversedBy="content")
     * @ORM\JoinColumn(nullable=false, onDelete="CASCADE")
     */
    protected ?Site $site;

    /**
     * @var int|null
     * @ORM\Column(type="integer", options={"unsigned":true}, nullable=true)
     */
    protected ?int $parentId;

    /**
     * @var string|null
     * @ORM\Column(type="string", length=150, nullable=true, nullable=true)
     */
    protected ?string $area;

    /**
     * @var int|null
     * @ORM\Column(type="integer", options={"unsigned":true}, nullable=false)
     */
    protected ?int $snippetId;

    /**
     * @var bool
     * @ORM\Column(type="boolean", nullable=false, options={"default":true})
     */
    protected bool $form = true;

    /**
     * @var string|null
     * @ORM\Column(type="text", nullable=false)
     */
    protected ?string $template;

    /**
     * @var string|null
     * @ORM\Column(type="string", length=150, nullable=true)
     */
    protected ?string $name;

    /**
     * @var int|null
     * @ORM\Column(type="smallint", nullable=false)
     */
    protected ?int $position = 1;

    /**
     * @var string|null
     * @ORM\Column(type="text", nullable=true)
     */
    protected ?string $data;

    /**
     * @var string|null
     * @ORM\Column(type="string", length=150, nullable=false)
     */
    protected ?string $action;

    /**
     * @var User|null
     *
     * @ORM\ManyToOne(targetEntity="Trollfjord\Core\Entity\User", cascade={"persist"})
     * @ORM\JoinColumn(nullable=true, onDelete="RESTRICT")
     */
    protected ?User $user = null;

    /**
     * @var DateTime|null
     * @ORM\Column(name="`date`", type="datetime", nullable=false)
     */
    protected ?DateTime $date;

    /**
     * SiteContentHistory constructor.
     * @param SiteContent|null $siteContent
     * @param string|null      $action
     * @param User|null        $user
     * @throws Exception
     */
    public function __construct(?SiteContent $siteContent = null, ?string $action = null, ?User $user = null)
    {
        $this->date = new DateTime();
        if ($siteContent) {
            $this->setSiteContentId($siteContent->getId());
            $this->setSite($siteContent->getSite());
            $this->setParentId($siteContent->getParent() ? $siteContent->getParent()->getId() : null);
            $this->setArea($siteContent->getArea());
            $this->setSnippetId($siteContent->getSnippet()->getId());
            $this->setForm($siteContent->isForm());
            $this->setTemplate($siteContent->getTemplate());
            $this->setName($siteContent->getName());
            $this->setPosition($siteContent->getPosition());
            $this->setData($siteContent->getDataAsKeyValueArray());
        }
        if ($action) {
            $this->setAction($action);
        }
        if ($user) {
            $this->setUser($user);
        }
    }

    /**
     * @return int|null
     */
    public function getId(): ?int
    {
        return $this->id;
    }

    /**
     * @param int|null $id
     * @return SiteContentHistory
     */
    public function setId(?int $id): SiteContentHistory
    {
        $this->id = $id;
        return $this;
    }

    /**
     * @return int|null
     */
    public function getSiteContentId(): ?int
    {
        return $this->siteContentId;
    }

    /**
     * @param int|null $siteContentId
     * @return SiteContentHistory
     */
    public function setSiteContentId(?int $siteContentId): SiteContentHistory
    {
        $this->siteContentId = $siteContentId;
        return $this;
    }

    /**
     * @return Site|null
     */
    public function getSite(): ?Site
    {
        return $this->site;
    }

    /**
     * @param Site|null $site
     * @return SiteContentHistory
     */
    public function setSite(?Site $site): SiteContentHistory
    {
        $this->site = $site;
        return $this;
    }

    /**
     * @return int|null
     */
    public function getParentId(): ?int
    {
        return $this->parentId;
    }

    /**
     * @param int|null $parentId
     * @return SiteContentHistory
     */
    public function setParentId(?int $parentId): SiteContentHistory
    {
        $this->parentId = $parentId;
        return $this;
    }

    /**
     * @return string|null
     */
    public function getArea(): ?string
    {
        return $this->area;
    }

    /**
     * @param string|null $area
     * @return SiteContentHistory
     */
    public function setArea(?string $area): SiteContentHistory
    {
        $this->area = $area;
        return $this;
    }

    /**
     * @return int|null
     */
    public function getSnippetId(): ?int
    {
        return $this->snippetId;
    }

    /**
     * @param int|null $snippetId
     * @return SiteContentHistory
     */
    public function setSnippetId(?int $snippetId): SiteContentHistory
    {
        $this->snippetId = $snippetId;
        return $this;
    }

    /**
     * @return bool
     */
    public function isForm(): bool
    {
        return $this->form;
    }

    /**
     * @param bool $form
     * @return SiteContentHistory
     */
    public function setForm(bool $form): SiteContentHistory
    {
        $this->form = $form;
        return $this;
    }

    /**
     * @return string|null
     */
    public function getTemplate(): ?string
    {
        return $this->template;
    }

    /**
     * @param string|null $template
     * @return SiteContentHistory
     */
    public function setTemplate(?string $template): SiteContentHistory
    {
        $this->template = $template;
        return $this;
    }

    /**
     * @return string|null
     */
    public function getName(): ?string
    {
        return $this->name;
    }

    /**
     * @param string|null $name
     * @return SiteContentHistory
     */
    public function setName(?string $name): SiteContentHistory
    {
        $this->name = $name;
        return $this;
    }

    /**
     * @return int|null
     */
    public function getPosition(): ?int
    {
        return $this->position;
    }

    /**
     * @param int|null $position
     * @return SiteContentHistory
     */
    public function setPosition(?int $position): SiteContentHistory
    {
        $this->position = $position;
        return $this;
    }

    /**
     * @return string|null
     */
    public function getData(): ?string
    {
        return $this->data;
    }

    /**
     * @param string|string[]|null $data
     * @return SiteContentHistory
     */
    public function setData($data): SiteContentHistory
    {
        if (is_array($data)) {
            $data = json_encode($data);
        }
        $this->data = $data;
        return $this;
    }

    /**
     * @return string|null
     */
    public function getAction(): ?string
    {
        return $this->action;
    }

    /**
     * @param string|null $action
     * @return SiteContentHistory
     * @throws Exception
     */
    public function setAction(?string $action): SiteContentHistory
    {
        if (! in_array($action, self::$ACTIONS)) {
            throw new Exception('Action ' . $action . ' not found!');
        }
        $this->action = $action;
        return $this;
    }

    /**
     * @return User|null
     */
    public function getUser(): ?User
    {
        return $this->user;
    }

    /**
     * @param User|null $user
     * @return SiteContentHistory
     */
    public function setUser(?User $user): SiteContentHistory
    {
        $this->user = $user;
        return $this;
    }

    /**
     * @return DateTime|null
     */
    public function getDate(): ?DateTime
    {
        return $this->date;
    }

    /**
     * @param DateTime|null $date
     * @return SiteContentHistory
     */
    public function setDate(?DateTime $date): SiteContentHistory
    {
        $this->date = $date;
        return $this;
    }
}
