<?php

namespace Mohamedaladdin\InvoiceImporter\Entity;

use InvalidArgumentException;

/**
 * Customer Entity
 * Represents a customer in the invoice system
 */
class Customer
{
    private ?int $customerId;
    private string $customerName;
    private string $customerAddress;
    private \DateTime $createdAt;
    private \DateTime $updatedAt;

    public function __construct(
        string $customerName,
        string $customerAddress,
        ?int $customerId = null,
        ?\DateTime $createdAt = null,
        ?\DateTime $updatedAt = null
    ) {
        $this->setCustomerName($customerName);
        $this->setCustomerAddress($customerAddress);
        $this->customerId = $customerId;
        $this->createdAt = $createdAt ?? new \DateTime();
        $this->updatedAt = $updatedAt ?? new \DateTime();
    }

    public function getCustomerId(): ?int
    {
        return $this->customerId;
    }

    public function setCustomerId(?int $customerId): void
    {
        $this->customerId = $customerId;
    }

    public function getCustomerName(): string
    {
        return $this->customerName;
    }

    public function setCustomerName(string $customerName): void
    {
        $customerName = trim($customerName);
        if (empty($customerName)) {
            throw new InvalidArgumentException('Customer name cannot be empty');
        }
        if (strlen($customerName) > 255) {
            throw new InvalidArgumentException('Customer name cannot exceed 255 characters');
        }
        $this->customerName = $customerName;
    }

    public function getCustomerAddress(): string
    {
        return $this->customerAddress;
    }

    public function setCustomerAddress(string $customerAddress): void
    {
        $customerAddress = trim($customerAddress);
        if (empty($customerAddress)) {
            throw new InvalidArgumentException('Customer address cannot be empty');
        }
        $this->customerAddress = $customerAddress;
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
     * Convert entity to array
     * 
     * @return array
     */
    public function toArray(): array
    {
        return [
            'customer_id' => $this->customerId,
            'customer_name' => $this->customerName,
            'customer_address' => $this->customerAddress,
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
            $data['customer_name'],
            $data['customer_address'],
            $data['customer_id'] ?? null,
            $createdAt,
            $updatedAt
        );
    }

    /**
     * Check if this customer equals another customer
     * 
     * @param Customer $other
     * @return bool
     */
    public function equals(Customer $other): bool
    {
        return $this->customerName === $other->customerName 
            && $this->customerAddress === $other->customerAddress;
    }

    /**
     * String representation of customer
     * 
     * @return string
     */
    public function __toString(): string
    {
        return "Customer: {$this->customerName} ({$this->customerAddress})";
    }
}
