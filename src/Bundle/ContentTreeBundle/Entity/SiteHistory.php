<?php

namespace Trollfjord\Bundle\ContentTreeBundle\Entity;

use DateTime;
use Doctrine\ORM\Mapping as ORM;
use Exception;
use Trollfjord\Core\Entity\AbstractEntity;
use Trollfjord\Core\Entity\User;
use function in_array;

/**
 * SiteHistory Entity
 *
 * @author  Maurice Karg <karg@helliwood.com>
 *
 * @package Trollfjord\Bundle\ContentTreeBundle\Entity
 *
 * @ORM\Entity(repositoryClass="Trollfjord\Bundle\ContentTreeBundle\Repository\SiteHistoryRepository")
 */
class SiteHistory extends AbstractEntity
{
    public const ACTION_ADDED = "added";
    public const ACTION_META_UPDATED = "meta_updated";
    public const ACTION_MOVED = "moved";
    public const ACTION_DELETED = "deleted";
    public const ACTION_CONTENT_CHANGED = "content_changed";
    public const ACTION_PUBLISHED = "published";

    /**
     * @var array|string[]
     */
    public static array $ACTIONS = [
        self::ACTION_ADDED,
        self::ACTION_META_UPDATED,
        self::ACTION_MOVED,
        self::ACTION_DELETED,
        self::ACTION_CONTENT_CHANGED,
        self::ACTION_PUBLISHED
    ];

    /**
     * @var int|null
     * @ORM\Id()
     * @ORM\GeneratedValue()
     * @ORM\Column(type="integer", options={"unsigned":true})
     */
    protected ?int $id = null;

    /**
     * @var Site|null
     * @ORM\ManyToOne(targetEntity="Site", inversedBy="history")
     * @ORM\JoinColumn(nullable=false, onDelete="CASCADE")
     */
    protected ?Site $site;

    /**
     * @var string|null
     * @ORM\Column(type="string", length=150, nullable=false)
     */
    protected ?string $action;

    /**
     * @ORM\Column(type="json", nullable=true)
     * @var array|null
     */
    protected ?array $changes = null;

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
     * Site constructor.
     * @param Site|null   $site
     * @param string|null $action
     * @param User|null   $user
     * @throws Exception
     */
    public function __construct(?Site $site = null, ?string $action = null, ?User $user = null)
    {
        $this->date = new DateTime();
        $this->setSite($site);
        $this->setAction($action);
        $this->setUser($user);
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
     * @return SiteHistory
     */
    public function setId(?int $id): SiteHistory
    {
        $this->id = $id;
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
     * @return SiteHistory
     */
    public function setSite(?Site $site): SiteHistory
    {
        $this->site = $site;
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
     * @return SiteHistory
     * @throws Exception
     */
    public function setAction(?string $action): SiteHistory
    {
        if (! in_array($action, self::$ACTIONS)) {
            throw new Exception('Action ' . $action . ' not found!');
        }
        $this->action = $action;
        return $this;
    }

    /**
     * @return string[]|null
     */
    public function getChanges(): ?array
    {
        return $this->changes;
    }

    /**
     * @param string[]|null $changes
     * @return SiteHistory
     */
    public function setChanges(?array $changes): SiteHistory
    {
        $this->changes = $changes;
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
     * @return SiteHistory
     */
    public function setUser(?User $user): SiteHistory
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
     * @return SiteHistory
     */
    public function setDate(?DateTime $date): SiteHistory
    {
        $this->date = $date;
        return $this;
    }
}
