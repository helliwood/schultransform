<?php

namespace Trollfjord\Bundle\ContentTreeBundle\Entity;

use DateTime;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;
use Trollfjord\Core\Entity\AbstractEntity;

/**
 * Snippet Entity
 *
 * @author  Maurice Karg <karg@helliwood.com>
 *
 * @package Trollfjord\Bundle\ContentTreeBundle\Entity
 *
 * @ORM\Entity(repositoryClass="Trollfjord\Bundle\ContentTreeBundle\Repository\SnippetRepository")
 */
class Snippet extends AbstractEntity
{
    /**
     * @var int|null
     * @ORM\Id()
     * @ORM\Column(type="integer", options={"unsigned":true})
     */
    protected ?int $id;

    /**
     * @ORM\Column(type="json", nullable=true)
     * @var string[]|null
     */
    protected ?array $groups = null;

    /**
     * @var string|null
     * @ORM\Column(type="string", length=150, nullable=false)
     */
    protected ?string $name;

    /**
     * @var string|null
     * @ORM\Column(type="string", length=150, nullable=true)
     */
    protected ?string $originalName;

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
     * @ORM\Column(type="string", length=150, nullable=false)
     */
    protected ?string $file;

    /**
     * @var bool
     * @ORM\Column(type="boolean", nullable=false, options={"default":false})
     */
    protected bool $removed = false;

    /**
     * @var DateTime|null
     * @ORM\Column(type="datetime", nullable=false)
     */
    protected ?DateTime $createdAt;

    /**
     * @var DateTime|null
     * @ORM\Column(type="datetime", nullable=true)
     */
    protected ?DateTime $updatedAt;

    /**
     * @var SiteContent[]|Collection|null
     * @ORM\OneToMany(targetEntity="SiteContent", mappedBy="snippet", indexBy="id")
     * @ORM\OrderBy({"position":"ASC"})
     */
    protected ?Collection $content;

    /**
     * Snippet constructor.
     */
    public function __construct()
    {
        $this->content = new ArrayCollection();
        $this->createdAt = new DateTime();
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
     * @return Snippet
     */
    public function setId(?int $id): Snippet
    {
        $this->id = $id;
        return $this;
    }

    /**
     * @return string[]|null
     */
    public function getGroups(): ?array
    {
        return $this->groups;
    }

    /**
     * @param string[]|null $groups
     * @return Snippet
     */
    public function setGroups(?array $groups): Snippet
    {
        $this->groups = $groups;
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
     * @return Snippet
     */
    public function setName(?string $name): Snippet
    {
        $this->name = $name;
        return $this;
    }

    /**
     * @return string|null
     */
    public function getOriginalName(): ?string
    {
        return $this->originalName;
    }

    /**
     * @param string|null $originalName
     * @return Snippet
     */
    public function setOriginalName(?string $originalName): Snippet
    {
        $this->originalName = $originalName;
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
     * @return Snippet
     */
    public function setForm(bool $form): Snippet
    {
        $this->form = $form;
        return $this;
    }

    /**
     * @return string|null
     */
    public function getFile(): ?string
    {
        return $this->file;
    }

    /**
     * @param string|null $file
     * @return Snippet
     */
    public function setFile(?string $file): Snippet
    {
        $this->file = $file;
        return $this;
    }

    /**
     * @return bool
     */
    public function isRemoved(): bool
    {
        return $this->removed;
    }

    /**
     * @param bool $removed
     * @return Snippet
     */
    public function setRemoved(bool $removed): Snippet
    {
        $this->removed = $removed;
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
     * @return Snippet
     */
    public function setTemplate(?string $template): Snippet
    {
        $this->template = $template;
        return $this;
    }

    /**
     * @return DateTime|null
     */
    public function getCreatedAt(): ?DateTime
    {
        return $this->createdAt;
    }

    /**
     * @param DateTime|null $createdAt
     * @return Snippet
     */
    public function setCreatedAt(?DateTime $createdAt): Snippet
    {
        $this->createdAt = $createdAt;
        return $this;
    }

    /**
     * @return DateTime|null
     */
    public function getUpdatedAt(): ?DateTime
    {
        return $this->updatedAt;
    }

    /**
     * @param DateTime|null $updatedAt
     * @return Snippet
     */
    public function setUpdatedAt(?DateTime $updatedAt): Snippet
    {
        $this->updatedAt = $updatedAt;
        return $this;
    }

    /**
     * @return Collection|SiteContent[]|null
     */
    public function getContent()
    {
        return $this->content;
    }

    /**
     * @param Collection|SiteContent[]|null $content
     * @return Snippet
     */
    public function setContent($content): Snippet
    {
        $this->content = $content;
        return $this;
    }

    /**
     * @param int $relationDepth
     * @return string[]
     */
    public function toArray(int $relationDepth = 0): array
    {
        $data = parent::toArray($relationDepth);
        $data['uses'] = $this->getContent()->count();
        unset($data['template']);
        return $data;
    }
}
