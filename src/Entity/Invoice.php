<?php

namespace Mohamedaladdin\InvoiceImporter\Entity;

use InvalidArgumentException;

/**
 * Invoice Entity
 * Represents an invoice with customer and items
 */
class Invoice
{
    private ?int $invoiceId;
    private int $invoiceNumber;
    private \DateTime $invoiceDate;
    private ?int $customerId;
    private ?Customer $customer;
    private float $grandTotal;
    private array $items = [];
    private \DateTime $createdAt;
    private \DateTime $updatedAt;

    public function __construct(
        int $invoiceNumber,
        \DateTime $invoiceDate,
        ?int $customerId = null,
        ?Customer $customer = null,
        ?int $invoiceId = null,
        ?\DateTime $createdAt = null,
        ?\DateTime $updatedAt = null
    ) {
        $this->setInvoiceNumber($invoiceNumber);
        $this->setInvoiceDate($invoiceDate);
        $this->customerId = $customerId;
        $this->customer = $customer;
        $this->invoiceId = $invoiceId;
        $this->createdAt = $createdAt ?? new \DateTime();
        $this->updatedAt = $updatedAt ?? new \DateTime();
        $this->grandTotal = 0.0;
    }

    public function getInvoiceId(): ?int
    {
        return $this->invoiceId;
    }

    public function setInvoiceId(?int $invoiceId): void
    {
        $this->invoiceId = $invoiceId;
    }

    public function getInvoiceNumber(): int
    {
        return $this->invoiceNumber;
    }

    public function setInvoiceNumber(int $invoiceNumber): void
    {
        if ($invoiceNumber <= 0) {
            throw new InvalidArgumentException('Invoice number must be greater than 0');
        }
        $this->invoiceNumber = $invoiceNumber;
    }

    public function getInvoiceDate(): \DateTime
    {
        return $this->invoiceDate;
    }

    public function setInvoiceDate(\DateTime $invoiceDate): void
    {
        $this->invoiceDate = $invoiceDate;
    }

    public function getCustomerId(): ?int
    {
        return $this->customerId;
    }

    public function setCustomerId(?int $customerId): void
    {
        $this->customerId = $customerId;
    }

    public function getCustomer(): ?Customer
    {
        return $this->customer;
    }

    public function setCustomer(?Customer $customer): void
    {
        $this->customer = $customer;
        if ($customer) {
            $this->customerId = $customer->getCustomerId();
        }
    }

    public function getGrandTotal(): float
    {
        return $this->grandTotal;
    }

    public function getItems(): array
    {
        return $this->items;
    }

    public function setItems(array $items): void
    {
        $this->items = [];
        foreach ($items as $item) {
            $this->addItem($item);
        }
    }

    public function addItem(InvoiceItem $item): void
    {
        $item->setInvoiceId($this->invoiceId);
        $this->items[] = $item;
        $this->calculateGrandTotal();
    }

    public function removeItem(InvoiceItem $item): void
    {
        $this->items = array_filter($this->items, function ($existingItem) use ($item) {
            return !$existingItem->equals($item);
        });
        $this->calculateGrandTotal();
    }

    public function getCreatedAt(): \DateTime
    {
        return $this->createdAt;
    }

    public function setCreatedAt(\DateTime $createdAt): void
    {
        $this->createdAt = $createdAt;
    }

    public function getUpdatedAt(): \DateTime
    {
        return $this->updatedAt;
    }

    public function setUpdatedAt(\DateTime $updatedAt): void
    {
        $this->updatedAt = $updatedAt;
    }

    /**
     * Calculate grand total from all items
     */
    public function calculateGrandTotal(): void
    {
        $this->grandTotal = 0.0;
        foreach ($this->items as $item) {
            $this->grandTotal += $item->getTotalPrice();
        }
        $this->grandTotal = round($this->grandTotal, 2);
    }

    /**
     * Validate invoice data
     * 
     * @return bool
     * @throws InvalidArgumentException
     */
    public function validate(): bool
    {
        if ($this->invoiceNumber <= 0) {
            throw new InvalidArgumentException('Invoice number must be greater than 0');
        }

        if (empty($this->items)) {
            throw new InvalidArgumentException('Invoice must have at least one item');
        }

        if (!$this->customer && !$this->customerId) {
            throw new InvalidArgumentException('Invoice must have a customer');
        }

        return true;
    }

    /**
     * Convert entity to array
     * 
     * @return array
     */
    public function toArray(): array
    {
        return [
            'invoice_id' => $this->invoiceId,
            'invoice_number' => $this->invoiceNumber,
            'invoice_date' => $this->invoiceDate->format('Y-m-d'),
            'customer_id' => $this->customerId,
            'customer' => $this->customer ? $this->customer->toArray() : null,
            'grand_total' => $this->grandTotal,
            'items' => array_map(fn($item) => $item->toArray(), $this->items),
            'created_at' => $this->createdAt->format('Y-m-d H:i:s'),
            'updated_at' => $this->updatedAt->format('Y-m-d H:i:s')
        ];
    }

    /**
     * Create entity from array
     * 
     * @param array $data
     * @return static
     */
    public static function fromArray(array $data): static
    {
        $invoiceDate = new \DateTime($data['invoice_date']);
        $createdAt = isset($data['created_at']) 
            ? new \DateTime($data['created_at']) 
            : null;
        $updatedAt = isset($data['updated_at']) 
            ? new \DateTime($data['updated_at']) 
            : null;

        $customer = null;
        if (isset($data['customer']) && is_array($data['customer'])) {
            $customer = Customer::fromArray($data['customer']);
        }

        $invoice = new static(
            $data['invoice_number'],
            $invoiceDate,
            $data['customer_id'] ?? null,
            $customer,
            $data['invoice_id'] ?? null,
            $createdAt,
            $updatedAt
        );

        if (isset($data['items']) && is_array($data['items'])) {
            $items = array_map(fn($itemData) => InvoiceItem::fromArray($itemData), $data['items']);
            $invoice->setItems($items);
        }

        return $invoice;
    }

    /**
     * Check if this invoice equals another invoice
     * 
     * @param Invoice $other
     * @return bool
     */
    public function equals(Invoice $other): bool
    {
        return $this->invoiceNumber === $other->invoiceNumber;
    }

    /**
     * String representation of invoice
     * 
     * @return string
     */
    public function __toString(): string
    {
        $customerName = $this->customer ? $this->customer->getCustomerName() : 'Unknown';
        return "Invoice #{$this->invoiceNumber} - {$customerName} (Total: {$this->grandTotal})";
    }
}
