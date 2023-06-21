<?php

namespace Trollfjord\Bundle\CookieBundle\Entity;

use Trollfjord\Bundle\CookieBundle\Repository\CookieMainRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;

/**
 * @ORM\Entity(repositoryClass=CookieMainRepository::class)
 */
class CookieMain implements \JsonSerializable
{
    /**
     * @ORM\Id
     * @ORM\GeneratedValue
     * @ORM\Column(type="integer")
     */
    private $id;

    /**
     * @ORM\Column(type="string", length=255)
     */
    private $name;

    /**
     * @ORM\Column(type="string", length=255)
     */
    private $title;

    /**
     * @ORM\Column(type="text")
     */
    private $content;

    /**
     * @ORM\Column(type="string", length=255)
     */
    private $privacylinktext;

    /**
     * @ORM\Column(type="string", length=255)
     */
    private $privacylinkpage;

    /**
     * @ORM\Column(type="string", length=255)
     */
    private $btnmainsettings;

    /**
     * @ORM\Column(type="string", length=255)
     */
    private $btnmainall;

    /**
     * @ORM\Column(type="string", length=255)
     */
    private $btnsubsettings;

    /**
     * @ORM\Column(type="string", length=255, nullable=true)
     */
    private $btnsuball;

    /**
     * @ORM\Column(type="string", length=255)
     */
    private $titlesub;

    /**
     * @ORM\Column(type="text")
     */
    private $contentsub;

    /**
     * @ORM\Column(type="integer")
     */
    private $cookieDuration;

    /**
     * @ORM\OneToMany(targetEntity=CookieItem::class, mappedBy="cookieMain", orphanRemoval=true, cascade={"persist"})
     * @ORM\OrderBy({"position"="ASC"})
     */
    private $item;

    public function __construct()
    {
        $this->item = new ArrayCollection();
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getName(): ?string
    {
        return $this->name;
    }

    public function setName(string $name): self
    {
        $this->name = $name;

        return $this;
    }

    public function getTitle(): ?string
    {
        return $this->title;
    }

    public function setTitle(string $title): self
    {
        $this->title = $title;

        return $this;
    }

    public function getContent(): ?string
    {
        return $this->content;
    }

    public function setContent(string $content): self
    {
        $this->content = $content;

        return $this;
    }

    public function getPrivacylinktext(): ?string
    {
        return $this->privacylinktext;
    }

    public function setPrivacylinktext(string $privacylinktext): self
    {
        $this->privacylinktext = $privacylinktext;

        return $this;
    }

    public function getPrivacylinkpage(): ?string
    {
        return $this->privacylinkpage;
    }

    public function setPrivacylinkpage(string $privacylinkpage): self
    {
        $this->privacylinkpage = $privacylinkpage;

        return $this;
    }

    public function getBtnmainsettings(): ?string
    {
        return $this->btnmainsettings;
    }

    public function setBtnmainsettings(string $btnmainsettings): self
    {
        $this->btnmainsettings = $btnmainsettings;

        return $this;
    }

    public function getBtnmainall(): ?string
    {
        return $this->btnmainall;
    }

    public function setBtnmainall(string $btnmainall): self
    {
        $this->btnmainall = $btnmainall;

        return $this;
    }

    public function getBtnsubsettings(): ?string
    {
        return $this->btnsubsettings;
    }

    public function setBtnsubsettings(string $btnsubsettings): self
    {
        $this->btnsubsettings = $btnsubsettings;

        return $this;
    }

    public function getBtnsuball(): ?string
    {
        return $this->btnsuball;
    }

    public function setBtnsuball(?string $btnsuball): self
    {
        $this->btnsuball = $btnsuball;

        return $this;
    }

    public function getTitlesub(): ?string
    {
        return $this->titlesub;
    }

    public function setTitlesub(string $titlesub): self
    {
        $this->titlesub = $titlesub;

        return $this;
    }

    public function getContentsub(): ?string
    {
        return $this->contentsub;
    }

    public function setContentsub(string $contentsub): self
    {
        $this->contentsub = $contentsub;

        return $this;
    }

    /**
     * @return Collection|CookieItem[]
     */
    public function getItem(): Collection
    {
        return $this->item;
    }

    public function addItem(CookieItem $item): self
    {
        if (!$this->item->contains($item)) {
            $this->item[] = $item;
            $item->setCookieMain($this);
        }
        return $this;
    }

    public function removeItem(CookieItem $item): self
    {

        if ($this->item->removeElement($item)) {
            // set the owning side to null (unless already changed)
            if ($item->getCookieMain() === $this) {
                $item->setCookieMain(null);
            }
        }

        return $this;
    }

    /**
     * @return mixed
     */
    public function getCookieDuration()
    {
        return $this->cookieDuration;
    }

    /**
     * @param mixed $cookieDuration
     */
    public function setCookieDuration($cookieDuration): void
    {
        $this->cookieDuration = $cookieDuration;
    }


    public function jsonSerialize()
    {

        return [
            "id" => $this->getId(),
            "name" => $this->getName(),
            "title" => $this->getTitle(),
            "titlesub" => $this->getTitlesub(),
            "cookieDuration" => $this->getCookieDuration(),
            "item" => $this->getItem()->toArray(),
            "btnmainall" => $this->getBtnmainall(),
            "btnmainsettings" => $this->getBtnmainsettings(),
            "btnsuball" => $this->getBtnsuball(),
            "btnsubsettings" => $this->getBtnsubsettings(),
            "content" => $this->getContent(),
            "contentsub" => $this->getContentsub(),
            "privacylinkpage" => $this->getPrivacylinkpage(),
            "privacylinktext" => $this->getPrivacylinktext(),
        ];
    }
}
