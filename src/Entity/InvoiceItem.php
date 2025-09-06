<?php

namespace Mohamedaladdin\InvoiceImporter\Entity;

use InvalidArgumentException;

/**
 * Invoice Item Entity
 * Represents an item within an invoice
 */
class InvoiceItem
{
    private ?int $itemId;
    private ?int $invoiceId;
    private string $productName;
    private int $quantity;
    private float $unitPrice;
    private float $totalPrice;
    private \DateTime $createdAt;
    private \DateTime $updatedAt;

    public function __construct(
        string $productName,
        int $quantity,
        float $unitPrice,
        ?int $itemId = null,
        ?int $invoiceId = null,
        ?\DateTime $createdAt = null,
        ?\DateTime $updatedAt = null
    ) {
        $this->itemId = $itemId;
        $this->invoiceId = $invoiceId;
        $this->productName = '';
        $this->quantity = 0;
        $this->unitPrice = 0.0;
        $this->totalPrice = 0.0;
        $this->createdAt = $createdAt ?? new \DateTime();
        $this->updatedAt = $updatedAt ?? new \DateTime();
        
        $this->setProductName($productName);
        $this->setQuantity($quantity);
        $this->setUnitPrice($unitPrice);
    }

    public function getItemId(): ?int
    {
        return $this->itemId;
    }

    public function setItemId(?int $itemId): void
    {
        $this->itemId = $itemId;
    }

    public function getInvoiceId(): ?int
    {
        return $this->invoiceId;
    }

    public function setInvoiceId(?int $invoiceId): void
    {
        $this->invoiceId = $invoiceId;
    }

    public function getProductName(): string
    {
        return $this->productName;
    }

    public function setProductName(string $productName): void
    {
        $productName = trim($productName);
        if (empty($productName)) {
            throw new InvalidArgumentException('Product name cannot be empty');
        }
        if (strlen($productName) > 255) {
            throw new InvalidArgumentException('Product name cannot exceed 255 characters');
        }
        $this->productName = $productName;
    }

    public function getQuantity(): int
    {
        return $this->quantity;
    }

    public function setQuantity(int $quantity): void
    {
        if ($quantity <= 0) {
            throw new InvalidArgumentException('Quantity must be greater than 0');
        }
        $this->quantity = $quantity;
        $this->calculateTotalPrice();
    }

    public function getUnitPrice(): float
    {
        return $this->unitPrice;
    }

    public function setUnitPrice(float $unitPrice): void
    {
        if ($unitPrice < 0) {
            throw new InvalidArgumentException('Unit price cannot be negative');
        }
        $this->unitPrice = $unitPrice;
        $this->calculateTotalPrice();
    }

    public function getTotalPrice(): float
    {
        return $this->totalPrice;
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
     * Calculate total price based on quantity and unit price
     */
    private function calculateTotalPrice(): void
    {
        $this->totalPrice = round($this->quantity * $this->unitPrice, 2);
    }

    /**
     * Convert entity to array
     * 
     * @return array
     */
    public function toArray(): array
    {
        return [
            'item_id' => $this->itemId,
            'invoice_id' => $this->invoiceId,
            'product_name' => $this->productName,
            'quantity' => $this->quantity,
            'unit_price' => $this->unitPrice,
            'total_price' => $this->totalPrice,
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
        $createdAt = isset($data['created_at']) 
            ? new \DateTime($data['created_at']) 
            : null;
        $updatedAt = isset($data['updated_at']) 
            ? new \DateTime($data['updated_at']) 
            : null;

        return new static(
            $data['product_name'],
            $data['quantity'],
            $data['unit_price'],
            $data['item_id'] ?? null,
            $data['invoice_id'] ?? null,
            $createdAt,
            $updatedAt
        );
    }

    /**
     * Check if this item equals another item
     * 
     * @param InvoiceItem $other
     * @return bool
     */
    public function equals(InvoiceItem $other): bool
    {
        return $this->productName === $other->productName 
            && $this->quantity === $other->quantity 
            && $this->unitPrice === $other->unitPrice;
    }

    /**
     * String representation of invoice item
     * 
     * @return string
     */
    public function __toString(): string
    {
        return "Item: {$this->productName} (Qty: {$this->quantity}, Price: {$this->unitPrice}, Total: {$this->totalPrice})";
    }
}
