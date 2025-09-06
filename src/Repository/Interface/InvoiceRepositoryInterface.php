<?php

namespace Mohamedaladdin\InvoiceImporter\Repository\Interface;

use Mohamedaladdin\InvoiceImporter\Entity\Invoice;

/**
 * Invoice Repository Interface
 * Defines contract for invoice data operations
 */
interface InvoiceRepositoryInterface
{
    /**
     * Save invoice to database
     * 
     * @param Invoice $invoice
     * @return Invoice
     */
    public function save(Invoice $invoice): Invoice;

    /**
     * Find invoice by ID
     * 
     * @param int $invoiceId
     * @return Invoice|null
     */
    public function findById(int $invoiceId): ?Invoice;

    /**
     * Find invoice by invoice number
     * 
     * @param int $invoiceNumber
     * @return Invoice|null
     */
    public function findByInvoiceNumber(int $invoiceNumber): ?Invoice;

    /**
     * Find invoices by customer ID
     * 
     * @param int $customerId
     * @return array
     */
    public function findByCustomerId(int $customerId): array;

    /**
     * Find all invoices
     * 
     * @return array
     */
    public function findAll(): array;

    /**
     * Find invoices by date range
     * 
     * @param \DateTime $startDate
     * @param \DateTime $endDate
     * @return array
     */
    public function findByDateRange(\DateTime $startDate, \DateTime $endDate): array;

    /**
     * Delete invoice by ID
     * 
     * @param int $invoiceId
     * @return bool
     */
    public function delete(int $invoiceId): bool;

    /**
     * Check if invoice exists
     * 
     * @param int $invoiceId
     * @return bool
     */
    public function exists(int $invoiceId): bool;

    /**
     * Check if invoice number exists
     * 
     * @param int $invoiceNumber
     * @return bool
     */
    public function invoiceNumberExists(int $invoiceNumber): bool;

    /**
     * Count total invoices
     * 
     * @return int
     */
    public function count(): int;

    /**
     * Get total revenue
     * 
     * @return float
     */
    public function getTotalRevenue(): float;
}
