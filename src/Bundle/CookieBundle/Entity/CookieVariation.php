<?php

namespace Trollfjord\Bundle\CookieBundle\Entity;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Trollfjord\Bundle\CookieBundle\Repository\CookieVariationRepository;
use Doctrine\ORM\Mapping as ORM;

/**
 * @ORM\Entity(repositoryClass=CookieVariationRepository::class)
 */
class CookieVariation implements \JsonSerializable
{
    /**
     * @ORM\Id
     * @ORM\GeneratedValue
     * @ORM\Column(type="integer")
     */
    private $id;

    /**
     * @ORM\Column(name="`key`",type="string", length=255)
     */
    private $key;

    /**
     * @ORM\Column(type="string", length=255)
     */
    private $title;

    /**
     * @ORM\Column(type="string", length=255, nullable=true)
     */
    private $regex;

    public function getId(): ?int
    {
        return $this->id;
    }

    /**
     * @return mixed
     */
    public function getKey()
    {
        return $this->key;
    }

    /**
     * @param mixed $key
     */
    public function setKey($key): void
    {
        $this->key = $key;
    }

    public function getRegex(): ?string
    {
        return $this->regex;
    }

    public function setRegex(string $regex): self
    {
        $this->regex = $regex;

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


    public function jsonSerialize()
    {
        return [
            "id" => $this->getRegex(),
            "key" => $this->getKey(),
            "regex" => $this->getRegex(),
            "title" => $this->getTitle(),
        ];
    }

}
