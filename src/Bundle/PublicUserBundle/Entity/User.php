<?php

namespace Trollfjord\Bundle\PublicUserBundle\Entity;

use DateTime;
use Doctrine\ORM\Mapping as ORM;
use JMS\Serializer\Annotation as Serializer;
use Symfony\Bridge\Doctrine\Validator\Constraints\UniqueEntity;
use Symfony\Component\Security\Core\User\UserInterface;
use Trollfjord\Core\Entity\AbstractEntity;
use Trollfjord\Entity\School;
use Trollfjord\Entity\SchoolAuthority;
use Trollfjord\Entity\SchoolType;
use function in_array;

/**
 * Media Entity
 *
 * @author Tim Wettstein <wettstein@helliwood.com>
 *
 * @ORM\Table(options={"collate"="utf8mb4_general_ci"})
 * @ORM\Entity(repositoryClass="Trollfjord\Bundle\PublicUserBundle\Repository\UserRepository")
 * @UniqueEntity("email")
 * @UniqueEntity("code")
 */
class User extends AbstractEntity implements UserInterface
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
     * @var string code
     * @ORM\Column(type="string", length=100, nullable=true, unique=true)
     */
    protected $code;

    /**
     * @var SchoolType|null
     * @ORM\ManyToOne(targetEntity="\Trollfjord\Entity\SchoolType")
     * @ORM\JoinColumn(referencedColumnName="name", name="school_type", nullable=false, onDelete="RESTRICT", columnDefinition="VARCHAR(255) DEFAULT NULL COLLATE utf8mb4_unicode_ci")
     */
    protected ?SchoolType $schoolType = null;

    /**
     * @var string username
     * @ORM\Column(type="string", length=255, nullable=true)
     */
    protected $username;

    /**
     * @var string
     */
    protected $displayName;

    /**
     * @var string $email
     * @ORM\Column(type="string", length=255, nullable=true, unique=true)
     */
    protected $email;

    /**
     * @var string password
     * @ORM\Column(type="string", length=255, nullable=true)
     * @Serializer\Exclude()
     */
    protected $password;

    /**
     * @var string|null
     * @ORM\Column(type="string", length=32, nullable=true, unique=true)
     */
    protected $resetPasswordHash;

    /**
     * @var DateTime|null
     * @ORM\Column(type="datetime", nullable=true)
     */
    protected $hashExpirationDate;

    /**
     * @var DateTime|null
     * @ORM\Column(type="datetime", nullable=true)
     * @Serializer\Type("DateTime<'d.m.Y'>")
     */
    protected $lastLogin;

    /**
     * @var DateTime|null
     * @ORM\Column(type="datetime", nullable=true)
     * @Serializer\Type("DateTime<'d.m.Y'>")
     */
    protected $currentLogin;

    /**
     * @var DateTime|null
     * @ORM\Column(type="datetime", nullable=true)
     */
    protected $registrationDate;

    /**
     *
     * @var int|null
     * @ORM\Column(type="integer", nullable=true, options={"default":0})
     * @ORM\GeneratedValue
     */
    protected $numberOfReminders;

    /**
     * @var DateTime|null
     * @ORM\Column(type="datetime", nullable=true)
     */
    protected $reminderEmailDate;

    /**
     * @var bool
     * @ORM\Column(type="boolean", nullable=false, options={"default":false})
     * @Serializer\Exclude()
     */
    private $tempPassword = false;

    /**
     * @var string|null
     */
    private $newPassword;


    /**
     * @ORM\Column(type="json")
     */
    private $roles = [];

    /**
     * @var string|null
     * @ORM\Column(type="string", length=100, nullable=true)
     */
    protected ?string $salutation;

    /**
     * @var string|null
     * @ORM\Column(type="string", length=100, nullable=true)
     */
    protected ?string $firstName;

    /**
     * @var string|null
     * @ORM\Column(type="string", length=100, nullable=true)
     */
    protected ?string $lastName;

    /**
     * @var string|null
     * @ORM\Column(type="string", length=255, nullable=true)
     */
    protected ?string $hash = null;

    /**
     * @var SchoolAuthority|null
     * @ORM\ManyToOne(targetEntity="\Trollfjord\Entity\SchoolAuthority", inversedBy="users")
     * @ORM\JoinColumn(nullable=true, onDelete="RESTRICT")
     */
    protected ?SchoolAuthority $schoolAuthority = null;

    /**
     * @var School|null
     * @ORM\ManyToOne(targetEntity="\Trollfjord\Entity\School", inversedBy="users")
     * @ORM\JoinColumn(nullable=true, onDelete="RESTRICT")
     */
    protected ?School $school = null;

    /**
     * @return int
     */
    public function getId(): int
    {
        return $this->id;
    }

    /**
     * @param int $id
     * @return User
     */
    public function setId(int $id): User
    {
        $this->id = $id;
        return $this;
    }


    public function getUserIcon(): string
    {
        $type = $this->getUserType();
        switch ($type) {
            case 'TEACHER':
                return 'fa-user';
            case 'SCHOOL':
                return 'fa-school';
            case 'SCHOOL_AUTHORITY':
                return 'fa-building';
            default:
                return 'fa-building';
        }
    }


    public function getUserType(): string
    {
        $roles = $this->getRoles();
        $mainRole = "";

        foreach ($roles as $role) {
            switch ($role) {
                case 'ROLE_PUBLIC':
                    break;
                case 'ROLE_TEACHER':
                    return "TEACHER";
                    break;
                case 'ROLE_SCHOOL_LITE':
                case 'ROLE_SCHOOL':
                    return 'SCHOOL';
                    break;
                case 'ROLE_SCHOOL_AUTHORITY_LITE':
                case 'ROLE_SCHOOL_AUTHORITY':
                    return 'SCHOOL_AUTHORITY';
            }
        }
        return $mainRole;
    }

    public function getRoles(): array
    {
        $roles = $this->roles;
        // guarantee every user at least has ROLE_USER
        $roles[] = 'ROLE_PUBLIC';
        if (! empty($this->getCode()) &&
            ! in_array("ROLE_SCHOOL_AUTHORITY", $roles) &&
            ! in_array("ROLE_SCHOOL_AUTHORITY_LITE", $roles) &&
            ! in_array("ROLE_SCHOOL", $roles) &&
            ! in_array("ROLE_SCHOOL_LITE", $roles)) {
            $roles[] = 'ROLE_TEACHER';
        }

        return array_unique($roles);
    }

    /**
     * @param string $role
     * @return bool
     */
    public function hasRole(string $role): bool
    {
        return in_array($role, $this->getRoles());
    }

    public function setRoles(array $roles): self
    {
        $this->roles = $roles;

        return $this;
    }

    /**
     * @param string|null $password
     * @return User
     */
    public function setPassword(?string $password): User
    {
        $this->password = $password;
        return $this;
    }

    /**
     * @return string
     */
    public function getPassword(): ?string
    {
        if ($this->password != "") {
            return (string)$this->password;
        }
        return (string)$this->code;
    }

    /**
     * @return bool
     */
    public function hasPassword(): bool
    {
        return ! empty($this->password);
    }

    /**
     * @param string|null $code
     * @return User
     */
    public function setCode(?string $code): User
    {
        if ($this->displayName == "") $this->displayName = $code;
        $this->code = $code;
        return $this;
    }

    public function getCode(): string
    {
        if ($this->displayName == "") $this->displayName = (string)$this->code;
        return (string)$this->code;
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
     * @return User
     */
    public function setSchoolType(?SchoolType $schoolType): User
    {
        $this->schoolType = $schoolType;
        return $this;
    }

    /**
     * @param string|null $username
     * @return User
     */
    public function setUsername(?string $username): User
    {
        if ($username != "") $this->displayName = $username;
        $this->username = $username;
        return $this;
    }


    /**
     * @return string|null
     */
    public function getUsername(): ?string
    {
        if ($this->username != "") $this->displayName = $this->username;
        return $this->username;
    }

    /**
     * @return string
     */
    public function getEmail(): ?string
    {
        if ($this->email != "") $this->displayName = $this->email;
        return $this->email;
    }

    /**
     * @param string|null $email
     * @return User
     */
    public function setEmail(?string $email): User
    {
        $this->email = $email;
        return $this;
    }

    /**
     * @return string|null
     */
    public function getResetPasswordHash(): ?string
    {
        return $this->resetPasswordHash;
    }

    /**
     * @param string|null $resetPasswordHash
     * @return User
     */
    public function setResetPasswordHash(?string $resetPasswordHash): User
    {
        $this->resetPasswordHash = $resetPasswordHash;
        return $this;
    }

    /**
     * @return DateTime|null
     */
    public function getHashExpirationDate(): ?DateTime
    {
        return $this->hashExpirationDate;
    }

    /**
     * @param DateTime|null $hashExpireDate
     * @return User
     */
    public function setHashExpirationDate(?DateTime $hashExpireDate): User
    {
        $this->hashExpirationDate = $hashExpireDate;
        return $this;
    }

    /**
     * @return DateTime|null
     */
    public function getLastLogin(): ?DateTime
    {
        return $this->lastLogin;
    }

    /**
     * @param DateTime|null $lastLogin
     */
    public function setLastLogin(?DateTime $lastLogin): void
    {
        $this->lastLogin = $lastLogin;
    }

    /**
     * @return DateTime|null
     */
    public function getCurrentLogin(): ?DateTime
    {
        return $this->currentLogin;
    }

    /**
     * @param DateTime|null $currentLogin
     */
    public function setCurrentLogin(?DateTime $currentLogin): void
    {
        $this->currentLogin = $currentLogin;
    }

    /**
     * @return DateTime|null
     */
    public function getRegistrationDate(): ?DateTime
    {
        return $this->registrationDate;
    }

    /**
     * @param DateTime|null $registrationDate
     */
    public function setRegistrationDate(?DateTime $registrationDate): void
    {
        $this->registrationDate = $registrationDate;
    }

    /**
     * @return int|null
     */
    public function getNumberOfReminders(): ?int
    {
        return $this->numberOfReminders;
    }

    /**
     * @param int|null $numberOfReminders
     */
    public function setNumberOfReminders(?int $numberOfReminders): void
    {
        $this->numberOfReminders = $numberOfReminders;
    }

    /**
     * @return DateTime|null
     */
    public function getReminderEmailDate(): ?DateTime
    {
        return $this->reminderEmailDate;
    }

    /**
     * @param DateTime|null $reminderEmailDate
     */
    public function setReminderEmailDate(?DateTime $reminderEmailDate): void
    {
        $this->reminderEmailDate = $reminderEmailDate;
    }

    /**
     * @return bool
     */
    public function isTempPassword(): bool
    {
        return $this->tempPassword;
    }

    /**
     * @param bool $tempPassword
     * @return User
     */
    public function setTempPassword(bool $tempPassword): User
    {
        $this->tempPassword = $tempPassword;
        return $this;
    }

    /**
     * @return string|null
     */
    public function getNewPassword(): ?string
    {
        return $this->newPassword;
    }

    /**
     * @param string|null $newPassword
     * @return User
     */
    public function setNewPassword(?string $newPassword): User
    {
        $this->newPassword = $newPassword;
        return $this;
    }

    public function getSalt()
    {
        // TODO: Implement getSalt() method.
    }

    public function getDisplayName(): ?string
    {
        return $this->username ?? $this->code;
    }

    public function eraseCredentials()
    {
    }

    /**
     * @return string|null
     */
    public function getSalutation(): ?string
    {
        return $this->salutation;
    }

    /**
     * @param string|null $salutation
     */
    public function setSalutation(?string $salutation): void
    {
        $this->salutation = $salutation;
    }

    /**
     * @return string|null
     */
    public function getFirstName(): ?string
    {
        return $this->firstName;
    }

    /**
     * @param string|null $firstName
     */
    public function setFirstName(?string $firstName): void
    {
        $this->firstName = $firstName;
    }

    /**
     * @return string|null
     */
    public function getLastName(): ?string
    {
        return $this->lastName;
    }

    /**
     * @param string|null $lastName
     */
    public function setLastName(?string $lastName): void
    {
        $this->lastName = $lastName;
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
     * @return School|null
     */
    public function getSchool(): ?School
    {
        return $this->school;
    }

    /**
     * @param School|null $school
     */
    public function setSchool(?School $school): void
    {
        $this->school = $school;
    }

    /**
     * @return string|null
     */
    public function getHash(): ?string
    {
        return $this->hash;
    }

    /**
     * @param string|null $hash
     * @return User
     */
    public function setHash(?string $hash): User
    {
        $this->hash = $hash;
        return $this;
    }

    public function getArrayCopy(): array
    {
        $arr = get_object_vars($this);
        unset($arr["password"]);
        return $arr;
    }

}
