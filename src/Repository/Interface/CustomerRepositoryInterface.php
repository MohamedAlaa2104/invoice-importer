<?php

namespace Mohamedaladdin\InvoiceImporter\Repository\Interface;

use Mohamedaladdin\InvoiceImporter\Entity\Customer;

/**
 * Customer Repository Interface
 * Defines contract for customer data operations
 */
interface CustomerRepositoryInterface
{
    /**
     * Save customer to database
     * 
     * @param Customer $customer
     * @return Customer
     */
    public function save(Customer $customer): Customer;

    /**
     * Find customer by ID
     * 
     * @param int $customerId
     * @return Customer|null
     */
    public function findById(int $customerId): ?Customer;

    /**
     * Find customer by name and address
     * 
     * @param string $name
     * @param string $address
     * @return Customer|null
     */
    public function findByNameAndAddress(string $name, string $address): ?Customer;

    /**
     * Find all customers
     * 
     * @return array
     */
    public function findAll(): array;

    /**
     * Find customers by name (partial match)
     * 
     * @param string $name
     * @return array
     */
    public function findByName(string $name): array;

    /**
     * Delete customer by ID
     * 
     * @param int $customerId
     * @return bool
     */
    public function delete(int $customerId): bool;

    /**
     * Check if customer exists
     * 
     * @param int $customerId
     * @return bool
     */
    public function exists(int $customerId): bool;

    /**
     * Count total customers
     * 
     * @return int
     */
    public function count(): int;

    /**
     * Get or create customer (useful for imports)
     * 
     * @param string $name
     * @param string $address
     * @return Customer
     */
    public function getOrCreate(string $name, string $address): Customer;
}
