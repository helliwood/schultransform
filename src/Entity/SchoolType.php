<?php
/**
 * Created by PhpStorm.
 * User: karg
 * Date: 23.06.21
 * Time: 09:13
 */

namespace Trollfjord\Entity;

use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Validator\Constraints as Assert;
use Trollfjord\Core\Entity\AbstractEntity;

/**
 * SchoolType Entity
 *
 * @ORM\Entity
 */
class SchoolType extends AbstractEntity
{

    /**
     * @var string|null
     * @ORM\Id
     * @Assert\NotBlank
     * @Assert\Length(max=255)
     * @ORM\Column(type="string", length=255, nullable=false)
     */
    protected ?string $name;

    /**
     * @var int|null
     * @ORM\Column(type="smallint", nullable=false, options={"default":1})
     */
    protected ?int $position = 1;

    /**
     * SchoolType constructor.
     */
    public function __construct()
    {
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
     * @return SchoolType
     */
    public function setName(?string $name): SchoolType
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
     * @return SchoolType
     */
    public function setPosition(?int $position): SchoolType
    {
        $this->position = $position;
        return $this;
    }

    /**
     * @return string|null
     */
    public function __toString()
    {
        return $this->name;
    }
}
