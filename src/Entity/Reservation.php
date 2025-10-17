<?php

namespace App\Entity;

use App\Repository\ReservationRepository;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: ReservationRepository::class)]
class Reservation
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $Nom = null;

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $email = null;

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $telephone = null;

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $paysresidence = null;

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $destination = null;

    #[ORM\Column(type: Types::DATE_MUTABLE, nullable: true)]
    private ?\DateTime $datedepart = null;

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $nbrvoyageurs = null;

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $typevoyage = null;

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $centreinteret = null;

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $budgetestime = null;

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $commentaire = null;

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $facturepdf = null;

    #[ORM\ManyToOne(targetEntity: Evenement::class, inversedBy: 'reservations')]
    #[ORM\JoinColumn(nullable: true)]
    private ?Evenement $evenement = null;

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $typereservation = null;

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getNom(): ?string
    {
        return $this->Nom;
    }

    public function setNom(?string $Nom): static
    {
        $this->Nom = $Nom;

        return $this;
    }

    public function getEmail(): ?string
    {
        return $this->email;
    }

    public function setEmail(?string $email): static
    {
        $this->email = $email;

        return $this;
    }

    public function getTelephone(): ?string
    {
        return $this->telephone;
    }

    public function setTelephone(?string $telephone): static
    {
        $this->telephone = $telephone;

        return $this;
    }

    public function getPaysresidence(): ?string
    {
        return $this->paysresidence;
    }

    public function setPaysresidence(?string $paysresidence): static
    {
        $this->paysresidence = $paysresidence;

        return $this;
    }

    public function getDestination(): ?string
    {
        return $this->destination;
    }

    public function setDestination(?string $destination): static
    {
        $this->destination = $destination;

        return $this;
    }

    public function getDatedepart(): ?\DateTime
    {
        return $this->datedepart;
    }

    public function setDatedepart(?\DateTime $datedepart): static
    {
        $this->datedepart = $datedepart;

        return $this;
    }

    public function getNbrvoyageurs(): ?string
    {
        return $this->nbrvoyageurs;
    }

    public function setNbrvoyageurs(?string $nbrvoyageurs): static
    {
        $this->nbrvoyageurs = $nbrvoyageurs;

        return $this;
    }

    public function getTypevoyage(): ?string
    {
        return $this->typevoyage;
    }

    public function setTypevoyage(?string $typevoyage): static
    {
        $this->typevoyage = $typevoyage;

        return $this;
    }

    public function getCentreinteret(): ?string
    {
        return $this->centreinteret;
    }

    public function setCentreinteret(?string $centreinteret): static
    {
        $this->centreinteret = $centreinteret;

        return $this;
    }

    public function getBudgetestime(): ?string
    {
        return $this->budgetestime;
    }

    public function setBudgetestime(?string $budgetestime): static
    {
        $this->budgetestime = $budgetestime;

        return $this;
    }

    public function getCommentaire(): ?string
    {
        return $this->commentaire;
    }

    public function setCommentaire(?string $commentaire): static
    {
        $this->commentaire = $commentaire;

        return $this;
    }

    public function getFacturepdf(): ?string
    {
        return $this->facturepdf;
    }

    public function setFacturepdf(?string $facturepdf): static
    {
        $this->facturepdf = $facturepdf;

        return $this;
    }

    public function getEvenement(): ?Evenement
    {
        return $this->evenement;
    }

    public function setEvenement(?Evenement $evenement): static
    {
        $this->evenement = $evenement;

        return $this;
    }

    public function getTypereservation(): ?string
    {
        return $this->typereservation;
    }

    public function setTypereservation(?string $typereservation): static
    {
        $this->typereservation = $typereservation;

        return $this;
    }
}
