<?php

namespace Mohamedaladdin\InvoiceImporter\Repository;

use Mohamedaladdin\InvoiceImporter\Database\Connection\DatabaseConnectionInterface;
use Mohamedaladdin\InvoiceImporter\Entity\Customer;
use Mohamedaladdin\InvoiceImporter\Exception\DatabaseException;
use Mohamedaladdin\InvoiceImporter\Repository\Interface\CustomerRepositoryInterface;

/**
 * Customer Repository Implementation
 */
class CustomerRepository implements CustomerRepositoryInterface
{
    private DatabaseConnectionInterface $connection;

    public function __construct(DatabaseConnectionInterface $connection)
    {
        $this->connection = $connection;
    }

    public function save(Customer $customer): Customer
    {
        try {
            if ($customer->getCustomerId() === null) {
                // Insert new customer
                $sql = "INSERT INTO customers (customer_name, customer_address, created_at, updated_at) 
                        VALUES (?, ?, ?, ?)";
                $params = [
                    $customer->getCustomerName(),
                    $customer->getCustomerAddress(),
                    $customer->getCreatedAt()->format('Y-m-d H:i:s'),
                    $customer->getUpdatedAt()->format('Y-m-d H:i:s')
                ];

                $this->connection->execute($sql, $params);
                $customer->setCustomerId($this->connection->getLastInsertId());
            } else {
                // Update existing customer
                $sql = "UPDATE customers 
                        SET customer_name = ?, customer_address = ?, updated_at = ? 
                        WHERE customer_id = ?";
                $params = [
                    $customer->getCustomerName(),
                    $customer->getCustomerAddress(),
                    $customer->getUpdatedAt()->format('Y-m-d H:i:s'),
                    $customer->getCustomerId()
                ];

                $this->connection->execute($sql, $params);
            }

            return $customer;
        } catch (\Exception $e) {
            throw DatabaseException::withSql(
                "Failed to save customer: " . $e->getMessage(),
                $sql ?? '',
                $params ?? [],
                $e
            );
        }
    }

    public function findById(int $customerId): ?Customer
    {
        try {
            $sql = "SELECT * FROM customers WHERE customer_id = ?";
            $row = $this->connection->fetchOne($sql, [$customerId]);

            return $row ? Customer::fromArray($row) : null;
        } catch (\Exception $e) {
            throw DatabaseException::withSql(
                "Failed to find customer by ID: " . $e->getMessage(),
                $sql,
                [$customerId],
                $e
            );
        }
    }

    public function findByNameAndAddress(string $name, string $address): ?Customer
    {
        try {
            $sql = "SELECT * FROM customers WHERE customer_name = ? AND customer_address = ?";
            $row = $this->connection->fetchOne($sql, [$name, $address]);

            return $row ? Customer::fromArray($row) : null;
        } catch (\Exception $e) {
            throw DatabaseException::withSql(
                "Failed to find customer by name and address: " . $e->getMessage(),
                $sql,
                [$name, $address],
                $e
            );
        }
    }

    public function findAll(): array
    {
        try {
            $sql = "SELECT * FROM customers ORDER BY customer_name";
            $rows = $this->connection->fetchAll($sql);

            return array_map(fn($row) => Customer::fromArray($row), $rows);
        } catch (\Exception $e) {
            throw DatabaseException::withSql(
                "Failed to find all customers: " . $e->getMessage(),
                $sql,
                [],
                $e
            );
        }
    }

    public function findByName(string $name): array
    {
        try {
            $sql = "SELECT * FROM customers WHERE customer_name LIKE ? ORDER BY customer_name";
            $rows = $this->connection->fetchAll($sql, ["%{$name}%"]);

            return array_map(fn($row) => Customer::fromArray($row), $rows);
        } catch (\Exception $e) {
            throw DatabaseException::withSql(
                "Failed to find customers by name: " . $e->getMessage(),
                $sql,
                ["%{$name}%"],
                $e
            );
        }
    }

    public function delete(int $customerId): bool
    {
        try {
            $sql = "DELETE FROM customers WHERE customer_id = ?";
            return $this->connection->execute($sql, [$customerId]);
        } catch (\Exception $e) {
            throw DatabaseException::withSql(
                "Failed to delete customer: " . $e->getMessage(),
                $sql,
                [$customerId],
                $e
            );
        }
    }

    public function exists(int $customerId): bool
    {
        try {
            $sql = "SELECT COUNT(*) as count FROM customers WHERE customer_id = ?";
            $result = $this->connection->fetchOne($sql, [$customerId]);
            return ($result['count'] ?? 0) > 0;
        } catch (\Exception $e) {
            throw DatabaseException::withSql(
                "Failed to check customer existence: " . $e->getMessage(),
                $sql,
                [$customerId],
                $e
            );
        }
    }

    public function count(): int
    {
        try {
            $sql = "SELECT COUNT(*) as count FROM customers";
            $result = $this->connection->fetchOne($sql);
            return (int) ($result['count'] ?? 0);
        } catch (\Exception $e) {
            throw DatabaseException::withSql(
                "Failed to count customers: " . $e->getMessage(),
                $sql,
                [],
                $e
            );
        }
    }

    public function getOrCreate(string $name, string $address): Customer
    {
        // Try to find existing customer
        $existingCustomer = $this->findByNameAndAddress($name, $address);
        if ($existingCustomer) {
            return $existingCustomer;
        }

        // Create new customer
        $customer = new Customer($name, $address);
        return $this->save($customer);
    }
}
