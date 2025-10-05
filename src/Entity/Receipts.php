<?php

namespace App\Entity;

use App\Repository\ReceiptsRepository;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: ReceiptsRepository::class)]
class Receipts
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column(length: 255)]
    private ?string $receipt_number = null;

    #[ORM\Column]
    private ?\DateTime $issued_date = null;

    #[ORM\ManyToOne(inversedBy: 'receipts')]
    private ?Payments $payment = null;

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $pdfurl = null;

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getReceiptNumber(): ?string
    {
        return $this->receipt_number;
    }

    public function setReceiptNumber(string $receipt_number): static
    {
        $this->receipt_number = $receipt_number;

        return $this;
    }

    public function getIssuedDate(): ?\DateTime
    {
        return $this->issued_date;
    }

    public function setIssuedDate(\DateTime $issued_date): static
    {
        $this->issued_date = $issued_date;

        return $this;
    }

    public function getPayment(): ?Payments
    {
        return $this->payment;
    }

    public function setPayment(?Payments $payment): static
    {
        $this->payment = $payment;

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
}
