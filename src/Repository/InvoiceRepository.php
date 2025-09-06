<?php

namespace Mohamedaladdin\InvoiceImporter\Repository;

use Mohamedaladdin\InvoiceImporter\Database\Connection\DatabaseConnectionInterface;
use Mohamedaladdin\InvoiceImporter\Entity\Invoice;
use Mohamedaladdin\InvoiceImporter\Entity\Customer;
use Mohamedaladdin\InvoiceImporter\Entity\InvoiceItem;
use Mohamedaladdin\InvoiceImporter\Exception\DatabaseException;
use Mohamedaladdin\InvoiceImporter\Repository\Interface\InvoiceRepositoryInterface;

/**
 * Invoice Repository Implementation
 */
class InvoiceRepository implements InvoiceRepositoryInterface
{
    private DatabaseConnectionInterface $connection;
    private CustomerRepository $customerRepository;
    private InvoiceItemRepository $itemRepository;

    public function __construct(
        DatabaseConnectionInterface $connection,
        CustomerRepository $customerRepository,
        InvoiceItemRepository $itemRepository
    ) {
        $this->connection = $connection;
        $this->customerRepository = $customerRepository;
        $this->itemRepository = $itemRepository;
    }

    public function save(Invoice $invoice): Invoice
    {
        try {
            if ($invoice->getInvoiceId() === null) {
                // Insert new invoice
                $sql = "INSERT INTO invoices (invoice_number, invoice_date, customer_id, grand_total, created_at, updated_at) 
                        VALUES (?, ?, ?, ?, ?, ?)";
                $params = [
                    $invoice->getInvoiceNumber(),
                    $invoice->getInvoiceDate()->format('Y-m-d'),
                    $invoice->getCustomerId(),
                    $invoice->getGrandTotal(),
                    $invoice->getCreatedAt()->format('Y-m-d H:i:s'),
                    $invoice->getUpdatedAt()->format('Y-m-d H:i:s')
                ];

                $this->connection->execute($sql, $params);
                $invoice->setInvoiceId($this->connection->getLastInsertId());

                // Save invoice items
                foreach ($invoice->getItems() as $item) {
                    $item->setInvoiceId($invoice->getInvoiceId());
                    $this->itemRepository->save($item);
                }
            } else {
                // Update existing invoice
                $sql = "UPDATE invoices 
                        SET invoice_number = ?, invoice_date = ?, customer_id = ?, grand_total = ?, updated_at = ? 
                        WHERE invoice_id = ?";
                $params = [
                    $invoice->getInvoiceNumber(),
                    $invoice->getInvoiceDate()->format('Y-m-d'),
                    $invoice->getCustomerId(),
                    $invoice->getGrandTotal(),
                    $invoice->getUpdatedAt()->format('Y-m-d H:i:s'),
                    $invoice->getInvoiceId()
                ];

                $this->connection->execute($sql, $params);

                // Update invoice items (delete existing and insert new)
                $this->itemRepository->deleteByInvoiceId($invoice->getInvoiceId());
                foreach ($invoice->getItems() as $item) {
                    $item->setInvoiceId($invoice->getInvoiceId());
                    $this->itemRepository->save($item);
                }
            }

            return $invoice;
        } catch (\Exception $e) {
            throw DatabaseException::withSql(
                "Failed to save invoice: " . $e->getMessage(),
                $sql ?? '',
                $params ?? [],
                $e
            );
        }
    }

    public function findById(int $invoiceId): ?Invoice
    {
        try {
            $sql = "SELECT * FROM invoices WHERE invoice_id = ?";
            $row = $this->connection->fetchOne($sql, [$invoiceId]);

            if (!$row) {
                return null;
            }

            $invoice = Invoice::fromArray($row);

            // Load customer
            if ($row['customer_id']) {
                $customer = $this->customerRepository->findById($row['customer_id']);
                $invoice->setCustomer($customer);
            }

            // Load items
            $items = $this->itemRepository->findByInvoiceId($invoiceId);
            $invoice->setItems($items);

            return $invoice;
        } catch (\Exception $e) {
            throw DatabaseException::withSql(
                "Failed to find invoice by ID: " . $e->getMessage(),
                $sql,
                [$invoiceId],
                $e
            );
        }
    }

    public function findByInvoiceNumber(int $invoiceNumber): ?Invoice
    {
        try {
            $sql = "SELECT * FROM invoices WHERE invoice_number = ?";
            $row = $this->connection->fetchOne($sql, [$invoiceNumber]);

            if (!$row) {
                return null;
            }

            return $this->findById($row['invoice_id']);
        } catch (\Exception $e) {
            throw DatabaseException::withSql(
                "Failed to find invoice by number: " . $e->getMessage(),
                $sql,
                [$invoiceNumber],
                $e
            );
        }
    }

    public function findByCustomerId(int $customerId): array
    {
        try {
            $sql = "SELECT * FROM invoices WHERE customer_id = ? ORDER BY invoice_date DESC";
            $rows = $this->connection->fetchAll($sql, [$customerId]);

            $invoices = [];
            foreach ($rows as $row) {
                $invoice = Invoice::fromArray($row);
                
                // Load customer
                $customer = $this->customerRepository->findById($customerId);
                $invoice->setCustomer($customer);

                // Load items
                $items = $this->itemRepository->findByInvoiceId($row['invoice_id']);
                $invoice->setItems($items);

                $invoices[] = $invoice;
            }

            return $invoices;
        } catch (\Exception $e) {
            throw DatabaseException::withSql(
                "Failed to find invoices by customer ID: " . $e->getMessage(),
                $sql,
                [$customerId],
                $e
            );
        }
    }

    public function findAll(): array
    {
        try {
            $sql = "SELECT * FROM invoices ORDER BY invoice_date DESC";
            $rows = $this->connection->fetchAll($sql);

            $invoices = [];
            foreach ($rows as $row) {
                $invoice = Invoice::fromArray($row);
                
                // Load customer
                if ($row['customer_id']) {
                    $customer = $this->customerRepository->findById($row['customer_id']);
                    $invoice->setCustomer($customer);
                }

                // Load items
                $items = $this->itemRepository->findByInvoiceId($row['invoice_id']);
                $invoice->setItems($items);

                $invoices[] = $invoice;
            }

            return $invoices;
        } catch (\Exception $e) {
            throw DatabaseException::withSql(
                "Failed to find all invoices: " . $e->getMessage(),
                $sql,
                [],
                $e
            );
        }
    }

    public function findByDateRange(\DateTime $startDate, \DateTime $endDate): array
    {
        try {
            $sql = "SELECT * FROM invoices WHERE invoice_date BETWEEN ? AND ? ORDER BY invoice_date DESC";
            $params = [
                $startDate->format('Y-m-d'),
                $endDate->format('Y-m-d')
            ];
            $rows = $this->connection->fetchAll($sql, $params);

            $invoices = [];
            foreach ($rows as $row) {
                $invoice = Invoice::fromArray($row);
                
                // Load customer
                if ($row['customer_id']) {
                    $customer = $this->customerRepository->findById($row['customer_id']);
                    $invoice->setCustomer($customer);
                }

                // Load items
                $items = $this->itemRepository->findByInvoiceId($row['invoice_id']);
                $invoice->setItems($items);

                $invoices[] = $invoice;
            }

            return $invoices;
        } catch (\Exception $e) {
            throw DatabaseException::withSql(
                "Failed to find invoices by date range: " . $e->getMessage(),
                $sql,
                $params,
                $e
            );
        }
    }

    public function delete(int $invoiceId): bool
    {
        try {
            // Delete invoice items first (foreign key constraint)
            $this->itemRepository->deleteByInvoiceId($invoiceId);

            // Delete invoice
            $sql = "DELETE FROM invoices WHERE invoice_id = ?";
            return $this->connection->execute($sql, [$invoiceId]);
        } catch (\Exception $e) {
            throw DatabaseException::withSql(
                "Failed to delete invoice: " . $e->getMessage(),
                $sql,
                [$invoiceId],
                $e
            );
        }
    }

    public function exists(int $invoiceId): bool
    {
        try {
            $sql = "SELECT COUNT(*) as count FROM invoices WHERE invoice_id = ?";
            $result = $this->connection->fetchOne($sql, [$invoiceId]);
            return ($result['count'] ?? 0) > 0;
        } catch (\Exception $e) {
            throw DatabaseException::withSql(
                "Failed to check invoice existence: " . $e->getMessage(),
                $sql,
                [$invoiceId],
                $e
            );
        }
    }

    public function invoiceNumberExists(int $invoiceNumber): bool
    {
        try {
            $sql = "SELECT COUNT(*) as count FROM invoices WHERE invoice_number = ?";
            $result = $this->connection->fetchOne($sql, [$invoiceNumber]);
            return ($result['count'] ?? 0) > 0;
        } catch (\Exception $e) {
            throw DatabaseException::withSql(
                "Failed to check invoice number existence: " . $e->getMessage(),
                $sql,
                [$invoiceNumber],
                $e
            );
        }
    }

    public function count(): int
    {
        try {
            $sql = "SELECT COUNT(*) as count FROM invoices";
            $result = $this->connection->fetchOne($sql);
            return (int) ($result['count'] ?? 0);
        } catch (\Exception $e) {
            throw DatabaseException::withSql(
                "Failed to count invoices: " . $e->getMessage(),
                $sql,
                [],
                $e
            );
        }
    }

    public function getTotalRevenue(): float
    {
        try {
            $sql = "SELECT SUM(grand_total) as total FROM invoices";
            $result = $this->connection->fetchOne($sql);
            return (float) ($result['total'] ?? 0);
        } catch (\Exception $e) {
            throw DatabaseException::withSql(
                "Failed to get total revenue: " . $e->getMessage(),
                $sql,
                [],
                $e
            );
        }
    }
}
