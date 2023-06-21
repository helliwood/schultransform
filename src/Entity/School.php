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
use Symfony\Component\Validator\Constraints as Assert;
use Trollfjord\Bundle\PublicUserBundle\Entity\User;
use Trollfjord\Core\Entity\AbstractEntity;

/**
 * School Entity
 *
 * @ORM\Entity(repositoryClass="Trollfjord\Repository\SchoolRepository")
 */
class School extends AbstractEntity
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
     * @var string|null
     * @Assert\NotBlank
     * @Assert\Length(max=50)
     * @ORM\Column(type="string", length=255, nullable=false)
     */
    protected ?string $name;

    /**
     * @var SchoolType|null
     * @ORM\ManyToOne(targetEntity="\Trollfjord\Entity\SchoolType")
     * @ORM\JoinColumn(referencedColumnName="name", name="school_type", nullable=false, onDelete="RESTRICT")
     */
    protected ?SchoolType $schoolType = null;

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
    protected ?string $headmaster = null;

    /**
     * @var bool
     * @ORM\Column(type="boolean", nullable=false, options={"default":false})
     */
    protected bool $confirmed = false;

    /**
     * @var bool
     * @ORM\Column(type="boolean", nullable=false, options={"default":false})
     */
    protected bool $testSchool = false;

    /**
     * @var SchoolAuthority|null
     * @ORM\ManyToOne(targetEntity="SchoolAuthority", inversedBy="schools")
     * @ORM\JoinColumn(nullable=false, onDelete="RESTRICT")
     */
    protected ?SchoolAuthority $schoolAuthority = null;

    /**
     * @var Collection|User[]
     * @ORM\OneToMany(targetEntity="\Trollfjord\Bundle\PublicUserBundle\Entity\User", mappedBy="school")
     */

    protected Collection $users;

    protected int $userCount = 0;

    protected array $usersRelated = [];

    protected int $questionnairesFilledOut = 0;


    /**
     * @ORM\ManyToMany(targetEntity="SchoolTag")
     *  * @ORM\JoinTable(name="school_tags")
     */
    protected Collection $tags;


    /**
     * School constructor.
     */
    public function __construct()
    {
        $this->users = new ArrayCollection();
        $this->tags = new ArrayCollection();

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
     */
    public function setId(?int $id): void
    {
        $this->id = $id;
    }

    /**
     * @return string
     */
    public function getCode(): ?string
    {
        return $this->code;
    }

    /**
     * @param string $code
     */
    public function setCode(?string $code): void
    {
        $this->code = $code;
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
     * @return SchoolType|null
     */
    public function getSchoolType(): ?SchoolType
    {
        return $this->schoolType;
    }

    /**
     * @param SchoolType|null $schoolType
     * @return School
     */
    public function setSchoolType(?SchoolType $schoolType): School
    {
        $this->schoolType = $schoolType;
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
     */
    public function setAddress(?Address $address): void
    {
        $this->address = $address;
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
     */
    public function setPhoneNumber(?string $phoneNumber): void
    {
        $this->phoneNumber = $phoneNumber;
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
     */
    public function setFaxNumber(?string $faxNumber): void
    {
        $this->faxNumber = $faxNumber;
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
     */
    public function setEmailAddress(?string $emailAddress): void
    {
        $this->emailAddress = $emailAddress;
    }

    /**
     * @return string|null
     */
    public function getHeadmaster(): ?string
    {
        return $this->headmaster;
    }

    /**
     * @param string|null $headmaster
     */
    public function setHeadmaster(?string $headmaster): void
    {
        $this->headmaster = $headmaster;
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
     * @return School
     */
    public function setConfirmed(bool $confirmed): School
    {
        $this->confirmed = $confirmed;
        return $this;
    }

    /**
     * @return bool
     */
    public function isTestSchool(): bool
    {
        return $this->testSchool;
    }

    /**
     * @param bool $testSchool
     */
    public function setTestSchool(bool $testSchool): void
    {
        $this->testSchool = $testSchool;
    }

    /**
     * @return SchoolAuthority|null
     */
    public function getSchoolAuthority(): ?SchoolAuthority
    {
        return $this->schoolAuthority;
    }

    /**
     * @param SchoolAuthority|null $schoolAuthority
     */
    public function setSchoolAuthority(?SchoolAuthority $schoolAuthority): void
    {
        $this->schoolAuthority = $schoolAuthority;
    }

    /**
     * @return Collection|User[]
     */
    public function getUsers()
    {

        $counter = 0;
        for ($i = 0; $i < count($this->users); $i++) {
            if (in_array('ROLE_TEACHER', $this->users[$i]->getRoles())
                && !in_array('ROLE_SCHOOL', $this->users[$i]->getRoles())
                && !in_array('ROLE_SCHOOL_LITE', $this->users[$i]->getRoles())
                && !in_array('ROLE_SCHOOL_AUTHORITY', $this->users[$i]->getRoles())
                && !in_array('ROLE_SCHOOL_AUTHORITY_LITE', $this->users[$i]->getRoles())
                && $this->users[$i]->getCode() !== ''
                && is_int($this->users[$i]->getId())
            ) {
                $this->usersRelated[] = $this->users[$i]->getId();
                $counter++;
            }
        }
        if (!empty($this->usersRelated)) {
            $this->usersRelated = array_unique($this->usersRelated);
        }
        if (count($this->usersRelated) === $counter) {
            $this->userCount = $counter;
        } else {
            $this->userCount = count($this->usersRelated);
        }

        return $this->users;
    }

    public function getMainUser()
    {
        foreach ($this->getUsers() as $user_) {
            if (in_array('ROLE_SCHOOL', $user_->getRoles())
                || in_array('ROLE_SCHOOL_LITE', $user_->getRoles())) {
                return $user_;
            }
        }

    }

    /**
     * @param Collection|User[] $users
     */
    public function setUsers($users): void
    {
        $this->users = $users;
        //$this->userCount = count($this->users);
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
     * @return int
     */
    public function getUserCount(): int
    {
        return $this->userCount;
    }

    /**
     * @return array
     */
    public function getUsersRelated(): array
    {
        return $this->usersRelated;
    }

    /**
     * @return int
     */
    public function getQuestionnairesFilledOut(): int
    {
        return $this->questionnairesFilledOut;
    }

    /**
     * @param int $questionnairesFilledOut
     */
    public function setQuestionnairesFilledOut(int $questionnairesFilledOut): void
    {
        $this->questionnairesFilledOut = $questionnairesFilledOut;
    }


    /**
     * @return ArrayCollection|Collection|SchoolTag[]
     */
    public function getTags()
    {
        return $this->tags;
    }

    /**
     * @param ArrayCollection|Collection $tags
     */
    public function setTags($tags): void
    {
        $this->tags = $tags;
    }

    /**
     * @return ArrayCollection|Collection
     */
    public function addTag($tag){
        $this->tags->add($tag);
        return $this->tags;
    }

    /**
     * @return ArrayCollection|Collection
     */
    public function removeTag($tag){
        $this->tags->removeElement($tag);
        return $this->tags;
    }
}
