<?php

namespace App\Entity;

use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity]
#[ORM\Table(name: 'print_templates')]
class PrintTemplate
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column(length: 30, unique: true)]
    private string $code = '';

    #[ORM\Column(length: 80)]
    private string $label = '';

    #[ORM\Column(length: 100)]
    private string $brand = 'DONWOK';

    #[ORM\Column(length: 100)]
    private string $title = '';

    #[ORM\Column(length: 180)]
    private string $footer = '';

    #[ORM\Column(options: ['default' => true])]
    private bool $showDate = true;

    #[ORM\Column(options: ['default' => true])]
    private bool $showOrderNumber = true;

    #[ORM\Column(options: ['default' => true])]
    private bool $showOrderType = true;

    #[ORM\Column(options: ['default' => true])]
    private bool $showTableNumber = true;

    #[ORM\Column(options: ['default' => true])]
    private bool $showCustomer = true;

    #[ORM\Column(options: ['default' => true])]
    private bool $showDeliveryDetails = true;

    #[ORM\Column(options: ['default' => true])]
    private bool $showComponents = true;

    #[ORM\Column(options: ['default' => false])]
    private bool $showPrices = false;

    #[ORM\Column(options: ['default' => false])]
    private bool $showTotal = false;

    #[ORM\Column(options: ['default' => false])]
    private bool $showPaymentDetails = false;

    public function __construct(string $code = '', string $label = '')
    {
        $this->code = $code;
        $this->label = $label;
    }

    public function getId(): ?int { return $this->id; }
    public function getCode(): string { return $this->code; }
    public function getLabel(): string { return $this->label; }
    public function setLabel(string $value): self { $this->label = trim($value); return $this; }
    public function getBrand(): string { return $this->brand; }
    public function setBrand(string $value): self { $this->brand = trim($value); return $this; }
    public function getTitle(): string { return $this->title; }
    public function setTitle(string $value): self { $this->title = trim($value); return $this; }
    public function getFooter(): string { return $this->footer; }
    public function setFooter(string $value): self { $this->footer = trim($value); return $this; }
    public function showsDate(): bool { return $this->showDate; }
    public function setShowDate(bool $value): self { $this->showDate = $value; return $this; }
    public function showsOrderNumber(): bool { return $this->showOrderNumber; }
    public function setShowOrderNumber(bool $value): self { $this->showOrderNumber = $value; return $this; }
    public function showsOrderType(): bool { return $this->showOrderType; }
    public function setShowOrderType(bool $value): self { $this->showOrderType = $value; return $this; }
    public function showsTableNumber(): bool { return $this->showTableNumber; }
    public function setShowTableNumber(bool $value): self { $this->showTableNumber = $value; return $this; }
    public function showsCustomer(): bool { return $this->showCustomer; }
    public function setShowCustomer(bool $value): self { $this->showCustomer = $value; return $this; }
    public function showsDeliveryDetails(): bool { return $this->showDeliveryDetails; }
    public function setShowDeliveryDetails(bool $value): self { $this->showDeliveryDetails = $value; return $this; }
    public function showsComponents(): bool { return $this->showComponents; }
    public function setShowComponents(bool $value): self { $this->showComponents = $value; return $this; }
    public function showsPrices(): bool { return $this->showPrices; }
    public function setShowPrices(bool $value): self { $this->showPrices = $value; return $this; }
    public function showsTotal(): bool { return $this->showTotal; }
    public function setShowTotal(bool $value): self { $this->showTotal = $value; return $this; }
    public function showsPaymentDetails(): bool { return $this->showPaymentDetails; }
    public function setShowPaymentDetails(bool $value): self { $this->showPaymentDetails = $value; return $this; }
}
