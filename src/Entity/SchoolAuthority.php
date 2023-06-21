<?php
/**
 * Created by PhpStorm.
 * User: karg
 * Date: 23.06.21
 * Time: 09:13
 */

namespace Trollfjord\Entity;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;
use ReflectionException;
use Symfony\Component\Validator\Constraints as Assert;
use Trollfjord\Core\Entity\AbstractEntity;

/**
 * SchoolAuthority Entity
 *
 * @ORM\Entity(repositoryClass="Trollfjord\Repository\SchoolAuthorityRepository")
 */
class SchoolAuthority extends AbstractEntity
{
    /**
     * @var int|null
     * @ORM\Id
     * @ORM\GeneratedValue
     * @ORM\Column(type="integer", nullable=false, options={"unsigned":true})
     */
    protected ?int $id = null;

    /**
     * @var string|null code
     * @ORM\Column(type="string", length=100, nullable=true, unique=true)
     */
    protected ?string $code;

    /**
     * @var string
     * @Assert\NotBlank
     * @Assert\Length(max=255)
     * @ORM\Column(type="string", length=255, nullable=false)
     */
    protected string $name;

    /**
     * @var Address|null
     *
     * @ORM\OneToOne(targetEntity="\Trollfjord\Entity\Address", cascade={"persist", "remove"}, fetch="EAGER")
     * @ORM\JoinColumn(name="addressId", referencedColumnName="id", nullable=true, onDelete="RESTRICT")
     */
    protected ?Address $address = null;

    /**
     * @var string|null
     * @ORM\Column(type="string", length=255, nullable=true)
     */
    protected ?string $phoneNumber = null;

    /**
     * @var string|null
     * @ORM\Column(type="string", length=255, nullable=true)
     */
    protected ?string $faxNumber = null;

    /**
     * @var string|null
     * @ORM\Column(type="string", length=255, nullable=true)
     */
    protected ?string $emailAddress = null;

    /**
     * @var string|null
     * @ORM\Column(type="string", length=255, nullable=true)
     */
    protected ?string $contactPerson = null;

    /**
     * @var Collection|School[]
     * @ORM\OneToMany(targetEntity="School", mappedBy="schoolAuthority")
     */
    protected Collection $schools;

    /**
     * @var Collection|School[]
     * @ORM\OneToMany(targetEntity="\Trollfjord\Bundle\PublicUserBundle\Entity\User", mappedBy="schoolAuthority")
     */
    protected Collection $users;

    /**
     * @var bool
     * @ORM\Column(type="boolean", nullable=false, options={"default":false})
     */
    protected bool $confirmed = false;

    /**
     * @var bool
     * @ORM\Column(type="boolean", nullable=false, options={"default":false})
     */
    protected bool $testSchoolAuthority = false;

    protected int $schoolCount = 0;

    /**
     * School constructor.
     */
    public function __construct()
    {
        $this->schools = new ArrayCollection();
        $this->users = new ArrayCollection();
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
     * @return SchoolAuthority
     */
    public function setId(?int $id): SchoolAuthority
    {
        $this->id = $id;
        return $this;
    }

    /**
     * @return string|null
     */
    public function getCode(): ?string
    {
        return $this->code;
    }

    /**
     * @param string|null $code
     * @return SchoolAuthority
     */
    public function setCode(?string $code): SchoolAuthority
    {
        $this->code = $code;
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
     * @return SchoolAuthority
     */
    public function setName(string $name): SchoolAuthority
    {
        $this->name = $name;
        return $this;
    }

    /**
     * @return Address|null
     */
    public function getAddress(): ?Address
    {
        return $this->address;
    }

    /**
     * @param Address|null $address
     * @return SchoolAuthority
     */
    public function setAddress(?Address $address): SchoolAuthority
    {
        $this->address = $address;
        return $this;
    }

    /**
     * @return string|null
     */
    public function getPhoneNumber(): ?string
    {
        return $this->phoneNumber;
    }

    /**
     * @param string|null $phoneNumber
     * @return SchoolAuthority
     */
    public function setPhoneNumber(?string $phoneNumber): SchoolAuthority
    {
        $this->phoneNumber = $phoneNumber;
        return $this;
    }

    /**
     * @return string|null
     */
    public function getFaxNumber(): ?string
    {
        return $this->faxNumber;
    }

    /**
     * @param string|null $faxNumber
     * @return SchoolAuthority
     */
    public function setFaxNumber(?string $faxNumber): SchoolAuthority
    {
        $this->faxNumber = $faxNumber;
        return $this;
    }

    /**
     * @return string|null
     */
    public function getEmailAddress(): ?string
    {
        return $this->emailAddress;
    }

    /**
     * @param string|null $emailAddress
     * @return SchoolAuthority
     */
    public function setEmailAddress(?string $emailAddress): SchoolAuthority
    {
        $this->emailAddress = $emailAddress;
        return $this;
    }

    /**
     * @return string|null
     */
    public function getContactPerson(): ?string
    {
        return $this->contactPerson;
    }

    /**
     * @param string|null $contactPerson
     * @return SchoolAuthority
     */
    public function setContactPerson(?string $contactPerson): SchoolAuthority
    {
        $this->contactPerson = $contactPerson;
        return $this;
    }

    /**
     * @return Collection|School[]
     */
    public function getSchools()
    {
        $this->schoolCount = count($this->schools);
        return $this->schools;
    }

    /**
     * @param Collection|School[] $schools
     * @return SchoolAuthority
     */
    public function setSchools($schools): SchoolAuthority
    {
        $this->schools = $schools;
        $this->schoolCount = count($this->schools);
        return $this;
    }

    /**
     * @return Collection|School[]
     */
    public function getUsers()
    {
        return $this->users;
    }

    /**
     * @param Collection|School[] $users
     * @return SchoolAuthority
     */
    public function setUsers($users): SchoolAuthority
    {
        $this->users = $users;
        return $this;
    }

    /**
     * @return bool
     */
    public function isConfirmed(): bool
    {
        return $this->confirmed;
    }

    /**
     * @param bool $confirmed
     * @return SchoolAuthority
     */
    public function setConfirmed(bool $confirmed): SchoolAuthority
    {
        $this->confirmed = $confirmed;
        return $this;
    }

    /**
     * @return bool
     */
    public function isTestSchoolAuthority(): bool
    {
        return $this->testSchoolAuthority;
    }

    /**
     * @param bool $testSchoolAuthority
     */
    public function setTestSchoolAuthority(bool $testSchoolAuthority): void
    {
        $this->testSchoolAuthority = $testSchoolAuthority;
    }

    /**
     * @return int
     */
    public function getSchoolCount(): int
    {
        return $this->schoolCount;
    }

    /**
     * @return void
     */
    public function __clone()
    {
        $this->id = null;
        $this->address = clone $this->address;
    }

    /**
     * @return array|mixed
     * @throws ReflectionException
     */
    public function jsonSerialize()
    {
        $data = parent::jsonSerialize();
        $data['in_use'] = $this->getUsers()->count() > 0;
        return $data;
    }
}
