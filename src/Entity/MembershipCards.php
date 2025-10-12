<?php

namespace App\Entity;

use App\Repository\MembershipCardsRepository;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: MembershipCardsRepository::class)]
class MembershipCards
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column(length: 50)]
    private ?string $cardnumberC = null;

    #[ORM\Column(type: Types::DATE_MUTABLE)]
    private ?\DateTime $issuedate = null;

    #[ORM\Column(type: Types::DATE_MUTABLE, nullable: true)]
    private ?\DateTime $expiry_date = null;

    #[ORM\Column]
    private ?bool $status = null;

    #[ORM\Column(length: 100, nullable: true)]
    private ?string $roleoncard = null;

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $photo = null;

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $pdfurl = null;

    #[ORM\ManyToOne(inversedBy: 'membershipCards')]
    private ?User $user = null;

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getCardnumberC(): ?string
    {
        return $this->cardnumberC;
    }

    public function setCardnumberC(string $cardnumberC): static
    {
        $this->cardnumberC = $cardnumberC;

        return $this;
    }

    public function getIssuedate(): ?\DateTime
    {
        return $this->issuedate;
    }

    public function setIssuedate(\DateTime $issuedate): static
    {
        $this->issuedate = $issuedate;

        return $this;
    }

    public function getExpiryDate(): ?\DateTime
    {
        return $this->expiry_date;
    }

    public function setExpiryDate(?\DateTime $expiry_date): static
    {
        $this->expiry_date = $expiry_date;

        return $this;
    }

    public function isStatus(): ?bool
    {
        return $this->status;
    }

    public function setStatus(bool $status): static
    {
        $this->status = $status;

        return $this;
    }

    public function getRoleoncard(): ?string
    {
        return $this->roleoncard;
    }

    public function setRoleoncard(?string $roleoncard): static
    {
        $this->roleoncard = $roleoncard;

        return $this;
    }

    public function getPhoto(): ?string
    {
        return $this->photo;
    }

    public function setPhoto(?string $photo): static
    {
        $this->photo = $photo;

        return $this;
    }

    public function getPdfurl(): ?string
    {
        return $this->pdfurl;
    }

    public function setPdfurl(?string $pdfurl): static
    {
        $this->pdfurl = $pdfurl;

        return $this;
    }

    public function getUser(): ?user
    {
        return $this->user;
    }

    public function setUser(?user $user): static
    {
        $this->user = $user;

        return $this;
    }
}
