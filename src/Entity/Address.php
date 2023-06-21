<?php
/**
 * Created by PhpStorm.
 * User: karg
 * Date: 23.06.21
 * Time: 11:00
 */

namespace Trollfjord\Entity;

use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Validator\Constraints as Assert;
use Trollfjord\Core\Entity\AbstractEntity;
use function is_null;

/**
 * Address Entity
 *
 * @author Maurice Karg <karg@helliwood.com>
 *
 * @ORM\Entity
 */
class Address extends AbstractEntity
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
    protected ?string $street;

    /**
     *
     * @var string|null
     * @Assert\NotBlank
     * @Assert\Length(max=50)
     * @ORM\Column(type="string", length=50, nullable=true)
     */
    protected ?string $postalcode;

    /**
     *
     * @var string|null
     * @Assert\Length(max=50)
     * @Assert\NotBlank
     * @ORM\Column(type="string", length=50, nullable=true)
     */
    protected ?string $city;

    /**
     *
     * @var string|null
     * @ORM\Column(type="string", length=100, nullable=true)
     */
    protected ?string $federalState;

    /**
     *
     * @var string|null
     * @Assert\Length(max=150)
     * @ORM\Column(type="string", length=150, nullable=true)
     */
    protected ?string $country = null;

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
    public function getStreet(): ?string
    {
        return $this->street;
    }

    /**
     * @param string|null $street
     */
    public function setStreet(?string $street): void
    {
        $this->street = $street;
    }

    /**
     * @return string|null
     */
    public function getPostalcode(): ?string
    {
        return $this->postalcode;
    }

    /**
     * @param string|null $postalcode
     */
    public function setPostalcode(?string $postalcode): void
    {
        $this->postalcode = $postalcode;
    }

    /**
     * @return string|null
     */
    public function getCity(): ?string
    {
        return $this->city;
    }

    /**
     * @param string|null $city
     */
    public function setCity(?string $city): void
    {
        $this->city = $city;
    }

    /**
     * @return string|null
     */
    public function getFederalState(): ?string
    {
        if (! is_null($this->country)) {
            return null;
        }
        return $this->federalState;
    }

    /**
     * @param string|null $federalState
     */
    public function setFederalState(?string $federalState): void
    {
        $this->federalState = $federalState;
    }

    /**
     * @return string|null
     */
    public function getCountry(): ?string
    {
        return $this->country;
    }

    /**
     * @param string|null $country
     * @return Address
     */
    public function setCountry(?string $country): Address
    {
        $this->country = $country;
        return $this;
    }

    /**
     * @return string
     */
    public function __toString()
    {
        return $this->getStreet() . "\n" . $this->getPostalcode() . " " . $this->getCity() . "\n" . $this->getFederalState() . ($this->getCountry() ? "\n" . $this->getFederalState() : "");
    }

    /**
     * @return void
     */
    public function __clone()
    {
        $this->id = null;
    }
}
