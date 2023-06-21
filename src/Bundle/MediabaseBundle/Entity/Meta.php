<?php


namespace Trollfjord\Bundle\MediaBaseBundle\Entity;

use Doctrine\ORM\Mapping as ORM;
use Doctrine\Common\Collections\ArrayCollection;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Trollfjord\Core\Entity\User;
use Symfony\Component\HttpFoundation\File\UploadedFile;
use Trollfjord\Core\Entity\AbstractEntity;
use Trollfjord\Bundle\MediaBaseBundle\Entity\Media;

/**
 * Media Entity
 *
 * @author Tim Wettstein <wettstein@helliwood.com>
 *
 * @ORM\Table(options={"collate"="utf8mb4_general_ci"})
 * @ORM\Entity()
 */
class Meta extends AbstractEntity
{
    /**
     * @var Media
     * @ORM\Id
     * @ORM\ManyToOne(targetEntity="Media", fetch="EAGER", inversedBy="metas")
     * @ORM\JoinColumn(name="media", referencedColumnName="id", nullable=true, onDelete="CASCADE")
     */
    protected ?Media $media = null;

    /**
     *
     * @var string
     * @ORM\Id
     * @ORM\Column(type="string", length=100, nullable=false)
     */
    protected $name;

    /**
     *
     * @var string
     * @ORM\Column(type="text", nullable=false)
     */
    protected $value;

    /**
     * @return Media
     */
    public function getMedia(): Media
    {
        return $this->media;
    }

    /**
     * @param Media $media
     * @return Meta
     */
    public function setMedia(Media $media): Meta
    {
        $this->media = $media;
        return $this;
    }

    /**
     * @return string
     */
    public function getName(): string
    {
        return $this->name;
    }

    /**
     * @param string $name
     * @return Meta
     */
    public function setName(string $name): Meta
    {
        $this->name = $name;
        return $this;
    }

    /**
     * @return string
     */
    public function getValue(): string
    {
        return $this->value;
    }

    /**
     * @param string $value
     * @return Meta
     */
    public function setValue(string $value): Meta
    {
        $this->value = $value;
        return $this;
    }

}