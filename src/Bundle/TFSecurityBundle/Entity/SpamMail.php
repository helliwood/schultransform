<?php
/**
 * Created by PhpStorm.
 * User: karg
 * Date: 23.06.21
 * Time: 09:13
 */

namespace Trollfjord\Bundle\TFSecurityBundle\Entity;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Validator\Constraints as Assert;
use Trollfjord\Core\Entity\AbstractEntity;
use DateTime;

/**
 * SpamMail Entity
 *
 * @package Trollfjord\Bundle\TFSecurityBundle\Entity
 *
 * @ORM\Entity(repositoryClass="Trollfjord\Bundle\TFSecurityBundle\Repository\SpamMailRepository")
 */
class SpamMail extends AbstractEntity
{
    /**
     * @var int|null
     * @ORM\Id
     * @ORM\GeneratedValue
     * @ORM\Column(type="integer", nullable=false, options={"unsigned":true})
     */
    protected ?int $id;

    /**
     * @var text|null
     * @ORM\Column(type="text")
     */
    protected ?string $body;


    /**
     * @var text|null
     * @ORM\Column(type="text")
     */
    protected ?string $subject;


    /**
     * @var string|null
     * @ORM\Column(type="string", length=255, nullable=true)
     */
    protected ?string $emailAddress = null;


    /**
     * @var DateTime|null
     * @ORM\Column(type="datetime", nullable=false)
     */
    protected ?DateTime $creationDate;

    /**
     * @var int|null
     * @ORM\Column(type="integer", nullable=true, options={"unsigned":true})
     */
    protected ?int $status = null;


    /**
     * @var string|null
     * @ORM\Column(type="text", nullable=true)
     */
    protected ?string $params = null;


    /**
     * SpamMail constructor.
     */
    public function __construct()
    {
        $this->creationDate = new DateTime();
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
     * @return string|null
     */
    public function getBody(): ?string
    {
        return $this->body;
    }

    /**
     * @param string|null $body
     */
    public function setSubject(?string $subject): void
    {
        $this->subject = $subject;
    }

    /**
     * @return string|null
     */
    public function getSubject(): ?string
    {
        return $this->subject;
    }

    /**
     * @param string|null $body
     */
    public function setBody(?string $body): void
    {
        $this->body = $body;
    }

    /**
     * @return string|null
     */
    public function getEmailAddress(): ?string
    {
        return $this->emailAddress;
    }

    /**
     * @param string|null $adress
     */
    public function setEmailAddress(?string $adress): void
    {
        $this->emailAddress = $adress;
    }


    /**
     * @return DateTime|null
     */
    public function getCreationDate(): ?DateTime
    {
        return $this->creationDate;
    }

    /**
     * @param DateTime|null $creationDate
     * @return Result
     */
    public function setCreationDate(?DateTime $creationDate): SpamMail
    {
        $this->creationDate = $creationDate;
        return $this;
    }

    /**
     * @return int|null
     */
    public function getStatus(): ?int
    {
        return $this->status;
    }

    /**
     * @param int|null $status
     */
    public function setStatus(?int $status): void
    {
        $this->status = $status;
    }


    /**
     * @param array|null $params
     * @return SpamMail
     */
    public function setParams(?array $params): self
    {
        // Filter the input array to remove invalid or dangerous data
        $params = filter_input_array($params, FILTER_SANITIZE_STRING);

        // Convert the filtered input to JSON and set it as the params property
        if (is_array($params)) {
            $this->params = json_encode($params);
        } else {
            $this->params = null;
        }

        // Return $this to allow for method chaining
        return $this;
    }

    /**
     * @return array|null
     */
    public function getParams(): ?array
    {
        return $this->params ? json_decode($this->params, true) : null;
    }

    /**
     * @return void
     */
    public function __clone()
    {
        $this->id = null;
    }
}
