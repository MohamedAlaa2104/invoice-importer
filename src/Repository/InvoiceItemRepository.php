<?php

namespace Mohamedaladdin\InvoiceImporter\Repository;

use Mohamedaladdin\InvoiceImporter\Database\Connection\DatabaseConnectionInterface;
use Mohamedaladdin\InvoiceImporter\Entity\InvoiceItem;
use Mohamedaladdin\InvoiceImporter\Exception\DatabaseException;
use Mohamedaladdin\InvoiceImporter\Repository\Interface\InvoiceItemRepositoryInterface;

/**
 * Invoice Item Repository Implementation
 */
class InvoiceItemRepository implements InvoiceItemRepositoryInterface
{
    private DatabaseConnectionInterface $connection;

    public function __construct(DatabaseConnectionInterface $connection)
    {
        $this->connection = $connection;
    }

    public function save(InvoiceItem $item): InvoiceItem
    {
        try {
            if ($item->getItemId() === null) {
                // Insert new item
                $sql = "INSERT INTO invoice_items (invoice_id, product_name, quantity, unit_price, total_price, created_at, updated_at) 
                        VALUES (?, ?, ?, ?, ?, ?, ?)";
                $params = [
                    $item->getInvoiceId(),
                    $item->getProductName(),
                    $item->getQuantity(),
                    $item->getUnitPrice(),
                    $item->getTotalPrice(),
                    $item->getCreatedAt()->format('Y-m-d H:i:s'),
                    $item->getUpdatedAt()->format('Y-m-d H:i:s')
                ];

                $this->connection->execute($sql, $params);
                $item->setItemId($this->connection->getLastInsertId());
            } else {
                // Update existing item
                $sql = "UPDATE invoice_items 
                        SET invoice_id = ?, product_name = ?, quantity = ?, unit_price = ?, total_price = ?, updated_at = ? 
                        WHERE item_id = ?";
                $params = [
                    $item->getInvoiceId(),
                    $item->getProductName(),
                    $item->getQuantity(),
                    $item->getUnitPrice(),
                    $item->getTotalPrice(),
                    $item->getUpdatedAt()->format('Y-m-d H:i:s'),
                    $item->getItemId()
                ];

                $this->connection->execute($sql, $params);
            }

            return $item;
        } catch (\Exception $e) {
            throw DatabaseException::withSql(
                "Failed to save invoice item: " . $e->getMessage(),
                $sql ?? '',
                $params ?? [],
                $e
            );
        }
    }

    public function findById(int $itemId): ?InvoiceItem
    {
        try {
            $sql = "SELECT * FROM invoice_items WHERE item_id = ?";
            $row = $this->connection->fetchOne($sql, [$itemId]);

            return $row ? InvoiceItem::fromArray($row) : null;
        } catch (\Exception $e) {
            throw DatabaseException::withSql(
                "Failed to find invoice item by ID: " . $e->getMessage(),
                $sql,
                [$itemId],
                $e
            );
        }
    }

    public function findByInvoiceId(int $invoiceId): array
    {
        try {
            $sql = "SELECT * FROM invoice_items WHERE invoice_id = ? ORDER BY item_id";
            $rows = $this->connection->fetchAll($sql, [$invoiceId]);

            return array_map(fn($row) => InvoiceItem::fromArray($row), $rows);
        } catch (\Exception $e) {
            throw DatabaseException::withSql(
                "Failed to find invoice items by invoice ID: " . $e->getMessage(),
                $sql,
                [$invoiceId],
                $e
            );
        }
    }

    public function findAll(): array
    {
        try {
            $sql = "SELECT * FROM invoice_items ORDER BY invoice_id, item_id";
            $rows = $this->connection->fetchAll($sql);

            return array_map(fn($row) => InvoiceItem::fromArray($row), $rows);
        } catch (\Exception $e) {
            throw DatabaseException::withSql(
                "Failed to find all invoice items: " . $e->getMessage(),
                $sql,
                [],
                $e
            );
        }
    }

    public function findByProductName(string $productName): array
    {
        try {
            $sql = "SELECT * FROM invoice_items WHERE product_name LIKE ? ORDER BY invoice_id, item_id";
            $rows = $this->connection->fetchAll($sql, ["%{$productName}%"]);

            return array_map(fn($row) => InvoiceItem::fromArray($row), $rows);
        } catch (\Exception $e) {
            throw DatabaseException::withSql(
                "Failed to find invoice items by product name: " . $e->getMessage(),
                $sql,
                ["%{$productName}%"],
                $e
            );
        }
    }

    public function delete(int $itemId): bool
    {
        try {
            $sql = "DELETE FROM invoice_items WHERE item_id = ?";
            return $this->connection->execute($sql, [$itemId]);
        } catch (\Exception $e) {
            throw DatabaseException::withSql(
                "Failed to delete invoice item: " . $e->getMessage(),
                $sql,
                [$itemId],
                $e
            );
        }
    }

    public function deleteByInvoiceId(int $invoiceId): bool
    {
        try {
            $sql = "DELETE FROM invoice_items WHERE invoice_id = ?";
            return $this->connection->execute($sql, [$invoiceId]);
        } catch (\Exception $e) {
            throw DatabaseException::withSql(
                "Failed to delete invoice items by invoice ID: " . $e->getMessage(),
                $sql,
                [$invoiceId],
                $e
            );
        }
    }

    public function exists(int $itemId): bool
    {
        try {
            $sql = "SELECT COUNT(*) as count FROM invoice_items WHERE item_id = ?";
            $result = $this->connection->fetchOne($sql, [$itemId]);
            return ($result['count'] ?? 0) > 0;
        } catch (\Exception $e) {
            throw DatabaseException::withSql(
                "Failed to check invoice item existence: " . $e->getMessage(),
                $sql,
                [$itemId],
                $e
            );
        }
    }

    public function count(): int
    {
        try {
            $sql = "SELECT COUNT(*) as count FROM invoice_items";
            $result = $this->connection->fetchOne($sql);
            return (int) ($result['count'] ?? 0);
        } catch (\Exception $e) {
            throw DatabaseException::withSql(
                "Failed to count invoice items: " . $e->getMessage(),
                $sql,
                [],
                $e
            );
        }
    }

    public function countByInvoiceId(int $invoiceId): int
    {
        try {
            $sql = "SELECT COUNT(*) as count FROM invoice_items WHERE invoice_id = ?";
            $result = $this->connection->fetchOne($sql, [$invoiceId]);
            return (int) ($result['count'] ?? 0);
        } catch (\Exception $e) {
            throw DatabaseException::withSql(
                "Failed to count invoice items by invoice ID: " . $e->getMessage(),
                $sql,
                [$invoiceId],
                $e
            );
        }
    }
}
