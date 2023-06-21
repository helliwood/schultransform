<?php

namespace Trollfjord\Bundle\MediaBaseBundle\Entity;

use Doctrine\ORM\Mapping as ORM;
use Doctrine\Common\Collections\ArrayCollection;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Trollfjord\Core\Entity\User;
use Symfony\Component\HttpFoundation\File\UploadedFile;
use Trollfjord\Core\Entity\AbstractEntity;

/**
 * Media Entity
 *
 * @author Tim Wettstein <wettstein@helliwood.com>
 *
 * @ORM\Table(options={"collate"="utf8mb4_general_ci"})
 * @ORM\Entity(repositoryClass="Trollfjord\Bundle\MediaBaseBundle\Repository\MediaRepository")
 */
class Media extends AbstractEntity implements MediaInterface
{

    /**
     *
     * @var int Id
     * @ORM\Id
     * @ORM\Column(type="integer", options={"unsigned"=true})
     * @ORM\GeneratedValue
     */
    protected $id;

    /**
     *
     * @var string
     * @ORM\Column(type="string", length=100, nullable=true)
     */
    protected $name;

    /**
     * @var string
     */
    protected $changedName = null;

    /**
     *
     * @var int
     * @ORM\Column(type="bigint", nullable=false, options={"default":0})
     */
    protected $fileSize = 0;

    protected $formatFileSize = '';

    /**
     *
     * @var string
     * @ORM\Column(type="string", length=100, nullable=true)
     */
    protected $copyright;

    /**
     * @var string
     * @ORM\Column(type="text", nullable=true)
     */
    protected $description;

    /**
     *
     * @var bool
     * @ORM\Column(type="boolean", nullable=false)
     */
    protected $isDirectory = false;

    /**
     *
     * @var string
     * @ORM\Column(type="string", length=100, nullable=true)
     */
    protected $mimeType;

    /**
     *
     * @var string
     * @ORM\Column(type="string", length=45, nullable=true)
     */
    protected $extension;

    /**
     *
     * @var \DateTime
     * @ORM\Column(type="datetime", nullable=false)
     */
    protected $creationDate;

    /**
     *
     * @var Media
     * @ORM\ManyToOne(targetEntity="Media", fetch="EAGER", inversedBy="children")
     * @ORM\JoinColumn(name="parentId", referencedColumnName="id", nullable=true)
     */
    protected ?Media $parent = null;

    /**
     *
     * @var \Doctrine\Common\Collections\ArrayCollection|Media[]
     * @ORM\OneToMany(targetEntity="Media", mappedBy="parent", fetch="EXTRA_LAZY", cascade={"persist"})
     */
    protected $children;

    /**
     *
     * @var \Doctrine\Common\Collections\ArrayCollection|Meta[]
     * @ORM\OneToMany(targetEntity="Meta", mappedBy="media", fetch="EAGER", cascade={"ALL"})
     */
    protected $metas;

    /**
     *
     * @var User
     * @ORM\ManyToOne(targetEntity="\Trollfjord\Core\Entity\User")
     * @ORM\JoinColumn(name="owner", referencedColumnName="id", nullable=true)
     */
    protected $owner;

    /**
     * @var string
     */
    protected string $optViewName = "";

    /**
     * @var null|UploadedFile
     */
    protected $file = null;

    /**
     * @var null|UploadedFile
     */
    protected $thumbnail = null;

    protected $url = "/Backend/MediaBase/show/";

    /**
     * @var string
     */
    protected string $filepath = "";

    public function __construct()
    {
        $this->children = new ArrayCollection();
        $this->creationDate = new \DateTime();
    }

    /**
     * @return mixed
     */
    public function getUrl()
    {
        return $this->url . $this->id;
    }

    /**
     * @return number
     */
    public function getId()
    {
        return $this->id;
    }

    /**
     * @param number $id
     */
    public function setId($id)
    {
        $this->id = $id;
    }

    /**
     *
     * @return string
     */
    public function getName()
    {
        return $this->name;
    }

    /**
     *
     * @param string $name
     */
    public function setName($name)
    {
        if ($name != $this->changedName && $this->name != '') {
            $this->changedName = $this->name;
        }
        $this->name = $name;
    }

    /**
     * @return string|null
     */
    public function getOldName(): ?string
    {
        return $this->changedName;
    }

    /**
     * @param string $changedName
     * @return Media
     */
    public function setOldName(string $changedName): Media
    {
        $this->changedName = $changedName;
        return $this;
    }

    /**
     *
     * @return bool
     */
    public function getIsDirectory(): bool
    {
        return $this->isDirectory;
    }

    /**
     *
     * @param bool $isDirectory
     */
    public function setIsDirectory($isDirectory)
    {
        $this->isDirectory = $isDirectory;
    }

    /**
     *
     * @return string
     */
    public function getMimeType()
    {
        return $this->mimeType;
    }

    /**
     *
     * @param string $mimeType
     */
    public function setMimeType($mimeType)
    {
        $this->mimeType = $mimeType;
    }

    /**
     *
     * @return string
     */
    public function getExtension()
    {
        return $this->extension;
    }

    /**
     *
     * @param string $extension
     */
    public function setExtension($extension)
    {
        $this->extension = $extension;
    }

    /**
     *
     * @return \DateTime
     */
    public function getCreationDate()
    {
        return $this->creationDate;
    }

    /**
     *
     * @param \DateTime $creationDate
     */
    public function setCreationDate($creationDate)
    {
        $this->creationDate = $creationDate;
    }

    /**
     *
     * @return Media
     */
    public function getParent(): ?Media
    {
        return $this->parent;
    }

    /**
     *
     * @param Media $parent
     */
    public function setParent(?MediaInterface $parent)
    {
        $this->parent = $parent;
    }

    /**
     *
     * @return multitype:Media
     */
    public function getChildren()
    {
        return $this->children;
    }

    /**
     *
     * @param multitype:Media $children
     */
    public function setChildren(array $children)
    {
        $this->children = $children;
    }

    /**
     * @return ArrayCollection|Meta[]
     */
    public function getMetas()
    {
        return $this->metas;
    }

    /**
     * @param ArrayCollection|Meta[] $metas
     * @return Media
     */
    public function setMetas($metas)
    {
        $this->metas = $metas;
        return $this;
    }

    /**
     * @return \Trollfjord\Core\Entity\User
     */
    public function getOwner()
    {
        return $this->owner;
    }

    /**
     * @param \Trollfjord\Core\Entity\User $owner
     */
    public function setOwner($owner)
    {
        $this->owner = $owner;
    }

    /**
     *
     * @return string
     */
    public function getCopyright()
    {
        return $this->copyright;
    }

    /**
     *
     * @return string
     */
    public function getDescription()
    {
        return $this->description;
    }

    /**
     *
     * @param string $copyright
     * @return Media
     */
    public function setCopyright($copyright)
    {
        $this->copyright = $copyright;
        return $this;
    }

    /**
     *
     * @param string $description
     * @return Media
     */
    public function setDescription($description)
    {
        $this->description = $description;
        return $this;
    }

    /**
     *
     * @return int
     */
    public function getFileSize()
    {
        return $this->fileSize;
    }

    /**
     *
     * @param int $fileSize
     * @return Media
     */
    public function setFileSize($fileSize)
    {
        $this->fileSize = $fileSize;
        return $this;
    }

    static public function formateFileSize($bytes)
    {
        $bytes = (int)$bytes;
        if ($bytes <= 0) {
            return '0 KB';
        }
        $names = array(
            'B',
            'KB',
            'MB',
            'GB',
            'TB'
        );
        $values = array(
            1,
            1024,
            1048576,
            1073741824,
            1099511627776
        );
        $i = floor(log($bytes) / 6.9314718055994530941723212145818);
        return number_format($bytes / $values[$i]) . ' ' . $names[$i];
    }

    public function getFormatFileSize()
    {
        $this->formatFileSize = self::formateFileSize($this->fileSize);
        return $this->formatFileSize;
    }

    public function __toString()
    {
        return (string)$this->getId();
    }

    public function getArrayCopy()
    {
        return get_object_vars($this);
    }

    /**
     * @return string
     */
    public function getOptViewName(): string
    {
        return $this->optViewName;
    }

    /**
     * @param string $optViewName
     * @return Media
     */
    public function setOptViewName(string $optViewName): Media
    {
        $this->optViewName = $optViewName;
        return $this;
    }

    /**
     * @return UploadedFile|null
     */
    public function getFile(): ?UploadedFile
    {
        return $this->file;
    }

    /**
     * @param UploadedFile|null $file
     * @return Media
     */
    public function setFile(?UploadedFile $file): Media
    {
        $this->file = $file;
        return $this;
    }

    /**
     * @return UploadedFile|null
     */
    public function getThumbnail(): ?UploadedFile
    {
        return $this->thumbnail;
    }

    /**
     * @param UploadedFile|null $thumbnail
     * @return Media
     */
    public function setThumbnail(?UploadedFile $thumbnail): Media
    {
        $this->thumbnail = $thumbnail;
        return $this;
    }

    /**
     * @return string
     */
    public function getDownloadFilename(): string
    {
        $_name = explode('.', $this->name);
        if (strtolower(end($_name)) != strtolower($this->extension)) {
            return $this->name . '.' . $this->extension;
        }
        return $this->name;
    }

    /**
     * @return string
     */
    public function getFilepath(): string
    {
        $parentName = '';

        if ($this->getParent()) {
            $parentName .= $this->recursionParent($this->getParent());
        }
        if ($parentName == '') {
            $sanitizeParentName = $parentName;
        } else {
            $sanitizeParentName = $parentName . '/';
        }
        return '/'.$sanitizeParentName;
    }

    private function recursionParent($parent): string
    {

        $parentName = '';

        if ($parent->getParent()) {
            $parentName .=  $this->recursionParent($parent->getParent());
        }
        if ($parentName === '') {
            $sanitizeParentName = $parentName;
        } else {
            $sanitizeParentName = $parentName . '/';
        }
        return $sanitizeParentName. $parent->name;

    }

    /**
     * @param string $filepath
     */
    public function setFilepath(string $filepath): void
    {
        $this->filepath = $filepath;
    }

}
