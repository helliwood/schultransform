<?php

namespace Trollfjord\Bundle\CookieBundle\Entity;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Trollfjord\Bundle\CookieBundle\Repository\CookieItemRepository;
use Doctrine\ORM\Mapping as ORM;

/**
 * @ORM\Entity(repositoryClass=CookieItemRepository::class)
 */
class CookieItem implements \JsonSerializable
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
     * @ORM\Column(type="boolean", nullable=true)
     */
    private $necessary;

    /**
     * @ORM\Column(type="text", nullable=true)
     */
    private $content;

    /**
     * @ORM\Column(type="string", length=255, nullable=true)
     */
    private $icon;

    /**
     * @ORM\Column(type="string", length=255, nullable=true)
     */
    private $regex;

    /**
     * @ORM\Column(type="integer")
     */
    private $position;

    /**
     * @ORM\ManyToOne(targetEntity=CookieMain::class, inversedBy="item")
     * @ORM\JoinColumn(nullable=true)
     */
    private $cookieMain;

    /**
     * @ORM\ManyToMany(targetEntity=CookieVariation::class)
     */
    private $variations;

    public function __construct()
    {
        $this->variations = new ArrayCollection();
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

    public function getNecessary(): ?bool
    {
        return $this->necessary;
    }

    public function setNecessary(?bool $necessary): self
    {
        $this->necessary = $necessary;

        return $this;
    }

    public function getContent(): ?string
    {
        return $this->content;
    }

    public function setContent(?string $content): self
    {
        $this->content = $content;

        return $this;
    }

    public function getIcon(): ?string
    {
        return $this->icon;
    }

    public function setIcon(?string $icon): self
    {
        $this->icon = $icon;

        return $this;
    }

    public function getCookieMain(): ?CookieMain
    {
        return $this->cookieMain;
    }

    public function addCookieMain(CookieMain $cookieMain): self
    {

        if (!$this->cookieMain->contains($cookieMain)) {
            $this->cookieMain->addItem($cookieMain);
        }
        return $this;
    }

    public function setCookieMain(CookieMain $cookieMain): self
    {
        $this->cookieMain = $cookieMain;
        return $this;
    }

    /**
     * @return mixed
     */
    public function getPosition()
    {
        return $this->position;
    }

    /**
     * @param mixed $position
     */
    public function setPosition($position): void
    {
        $this->position = $position;
    }

    /**
     * @return Collection|CookieVariation[]
     */
    public function getVariations(): Collection
    {
        return $this->variations;
    }


    public function addVariations(CookieVariation $variation): self
    {
        if (!$this->variations->contains($variation)) {
            $this->variations[] = $variation;
        }

        return $this;
    }

    public function removeVariations(CookieVariation $variation): self
    {
        $this->variations->removeElement($variation);

        return $this;
    }

    /**
     * @return mixed
     */
    public function getRegex()
    {
        return $this->regex;
    }

    /**
     * @param mixed $regex
     */
    public function setRegex($regex): void
    {
        $this->regex = $regex;
    }


    public function jsonSerialize()
    {

        return [
            "id" => $this->getId(),
            "name" => $this->getName(),
            "title" => $this->getTitle(),
            "necessary" => $this->getNecessary(),
            "position" => $this->getPosition(),
            "icon" => $this->getIcon(),
            "regex" => $this->getRegex(),
            "variations" => $this->getVariations()->toArray(),
            "content" => nl2br($this->getContent()),
        ];
    }
}
