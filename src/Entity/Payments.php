<?php

namespace App\Entity;

use App\Repository\PaymentsRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: PaymentsRepository::class)]
class Payments
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column(type: Types::DECIMAL, precision: 10, scale: 2)]
    private ?string $amount = null;

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $payment_method = null;

    #[ORM\Column]
    private ?\DateTime $paymentdate = null;

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $status = null;

    #[ORM\Column(length: 255)]
    private ?string $reference = null;

    #[ORM\ManyToOne(inversedBy: 'payments')]
    private ?User $user = null;

    /**
     * @var Collection<int, Receipts>
     */
    #[ORM\OneToMany(targetEntity: Receipts::class, mappedBy: 'payment')]
    private Collection $receipts;

    public function __construct()
    {
        $this->receipts = new ArrayCollection();
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getAmount(): ?string
    {
        return $this->amount;
    }

    public function setAmount(string $amount): static
    {
        $this->amount = $amount;

        return $this;
    }

    public function getPaymentMethod(): ?string
    {
        return $this->payment_method;
    }

    public function setPaymentMethod(?string $payment_method): static
    {
        $this->payment_method = $payment_method;

        return $this;
    }

    public function getPaymentdate(): ?\DateTime
    {
        return $this->paymentdate;
    }

    public function setPaymentdate(\DateTime $paymentdate): static
    {
        $this->paymentdate = $paymentdate;

        return $this;
    }

    public function getStatus(): ?string
    {
        return $this->status;
    }

    public function setStatus(?string $status): static
    {
        $this->status = $status;

        return $this;
    }

    public function getReference(): ?string
    {
        return $this->reference;
    }

    public function setReference(string $reference): static
    {
        $this->reference = $reference;

        return $this;
    }

    public function getUser(): ?User
    {
        return $this->user;
    }

    public function setUser(?User $user): static
    {
        $this->user = $user;

        return $this;
    }

    /**
     * @return Collection<int, Receipts>
     */
    public function getReceipts(): Collection
    {
        return $this->receipts;
    }

    public function addReceipt(Receipts $receipt): static
    {
        if (!$this->receipts->contains($receipt)) {
            $this->receipts->add($receipt);
            $receipt->setPayment($this);
        }

        return $this;
    }

    public function removeReceipt(Receipts $receipt): static
    {
        if ($this->receipts->removeElement($receipt)) {
            // set the owning side to null (unless already changed)
            if ($receipt->getPayment() === $this) {
                $receipt->setPayment(null);
            }
        }

        return $this;
    }
}
