<?php

namespace Trollfjord\Bundle\GlossaryBundle\Entity;

use Doctrine\ORM\Mapping as ORM;
use Symfony\Bridge\Doctrine\Validator\Constraints\UniqueEntity;
use Trollfjord\Core\Entity\AbstractEntity;

/**
 * Glossary Entity
 *
 * @author Juan Mayoral <mayoral@helliwood.com>
 *
 * @ORM\Table(options={"collate"="utf8mb4_general_ci"})
 * @ORM\Entity(repositoryClass="Trollfjord\Bundle\GlossaryBundle\Repository\GlossaryRepository")
 * @UniqueEntity(fields="word")
 * @UniqueEntity(fields="slug")
 */
class Glossary extends AbstractEntity
{
    /**
     * @var int Id
     * @ORM\Id
     * @ORM\Column(type="integer", options={"unsigned"=true})
     * @ORM\GeneratedValue
     */
    protected int $id;

    /**
     * @var string|null word
     * @ORM\Column(type="string", length=255)
     */
    protected ?string $word;

    /**
     * @var string|null slug
     * @ORM\Column(type="string", length=255)
     */
    protected ?string $slug;

    /**
     * @var string|null $relatedWords
     * @ORM\Column(type="text", nullable=true)
     */
    protected ?string $relatedWords = null;

    /**
     * @var string|null shortDescription
     * @ORM\Column(type="text", nullable=true)
     */
    protected ?string $shortDescription = null;

    /**
     * @var string|null definition
     * @ORM\Column(type="text")
     */
    protected ?string $definition;

    /**
     * @var int|null image
     * @ORM\Column(type="integer", nullable=true)
     */
    protected ?int $image = null;

    /**
     * @var string|null theme
     * @ORM\Column(type="string", nullable=true)
     */
    protected ?string $theme = null;

    /**
     * @var int letterGroup
     * @ORM\Column(type="integer")
     */
    protected int $letterGroup;

    /**
     * @return int
     */
    public function getId(): int
    {
        return $this->id;
    }

    /**
     * @param int $id
     */
    public function setId(int $id): void
    {
        $this->id = $id;
    }

    /**
     * @return string|null
     */
    public function getWord(): ?string
    {
        return $this->word;
    }

    /**
     * @param string|null $word
     */
    public function setWord(?string $word): void
    {
        $this->word = $word;
    }

    /**
     * @return string|null
     */
    public function getSlug(): ?string
    {
        return $this->slug;
    }

    /**
     * @param string|null $slug
     */
    public function setSlug(?string $slug): void
    {
        $this->slug = $slug;
    }

    /**
     * @return string|null
     */
    public function getRelatedWords(): ?string
    {
        return $this->relatedWords;
    }

    /**
     * @param string|null $relatedWords
     */
    public function setRelatedWords(?string $relatedWords): void
    {
        $this->relatedWords = $relatedWords;
    }

    /**
     * @return string|null
     */
    public function getShortDescription(): ?string
    {
        return $this->shortDescription;
    }

    /**
     * @param string|null $shortDescription
     */
    public function setShortDescription(?string $shortDescription): void
    {
        $this->shortDescription = $shortDescription;
    }

    /**
     * @return string
     */
    public function getDefinition(): ?string
    {
        return $this->definition;
    }

    /**
     * @param string|null $definition
     */
    public function setDefinition(?string $definition): void
    {
        $this->definition = $definition;
    }

    /**
     * @return int|null
     */
    public function getImage(): ?int
    {
        return $this->image;
    }

    /**
     * @param int|null $image
     */
    public function setImage(?int $image): void
    {
        $this->image = $image;
    }

    /**
     * @return string|null
     */
    public function getTheme(): ?string
    {
        return $this->theme;
    }

    /**
     * @param string|null $theme
     */
    public function setTheme(?string $theme): void
    {
        $this->theme = $theme;
    }

    /**
     * @return int
     */
    public function getLetterGroup(): int
    {
        return $this->letterGroup;
    }

    /**
     * @param int $letterGroup
     */
    public function setLetterGroup(int $letterGroup): void
    {
        $this->letterGroup = $letterGroup;
    }

}