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
class SchoolTag extends AbstractEntity
{

    /**
     * @var int|null
     * @ORM\Id
     * @ORM\GeneratedValue
     * @ORM\Column(type="integer", nullable=false, options={"unsigned":true})
     */
    protected ?int $id;


    /**
     *
     * @var string|null
     * @Assert\Length(max=50)
     * @Assert\NotBlank
     * @ORM\Column(type="string", length=50, nullable=true)
     */
    protected ?string $name;

    /**
     *
     * @var string|null
     * @Assert\Length(max=50)
     * @ORM\Column(type="string", length=50, nullable=true)
     */
    protected ?string $space;



    /**
     * @return int|null
     */
    public function getId(): ?int
    {
        return $this->id;
    }

    /**
     * @param int|null $id
     */
    public function setId(?int $id): void
    {
        $this->id = $id;
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
     */
    public function setName(?string $name): void
    {
        $this->name = $name;
    }

    /**
     * @return string|null
     */
    public function getSpace(): ?string
    {
        return $this->space;
    }

    /**
     * @param string|null $space
     */
    public function setSpace(?string $space): void
    {
        $this->space = $space;
    }


}
