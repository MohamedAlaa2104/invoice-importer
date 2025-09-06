<?php

namespace Mohamedaladdin\InvoiceImporter\Repository\Interface;

use Mohamedaladdin\InvoiceImporter\Entity\InvoiceItem;

/**
 * Invoice Item Repository Interface
 * Defines contract for invoice item data operations
 */
interface InvoiceItemRepositoryInterface
{
    /**
     * Save invoice item to database
     * 
     * @param InvoiceItem $item
     * @return InvoiceItem
     */
    public function save(InvoiceItem $item): InvoiceItem;

    /**
     * Find invoice item by ID
     * 
     * @param int $itemId
     * @return InvoiceItem|null
     */
    public function findById(int $itemId): ?InvoiceItem;

    /**
     * Find all items for an invoice
     * 
     * @param int $invoiceId
     * @return array
     */
    public function findByInvoiceId(int $invoiceId): array;

    /**
     * Find all invoice items
     * 
     * @return array
     */
    public function findAll(): array;

    /**
     * Find items by product name
     * 
     * @param string $productName
     * @return array
     */
    public function findByProductName(string $productName): array;

    /**
     * Delete invoice item by ID
     * 
     * @param int $itemId
     * @return bool
     */
    public function delete(int $itemId): bool;

    /**
     * Delete all items for an invoice
     * 
     * @param int $invoiceId
     * @return bool
     */
    public function deleteByInvoiceId(int $invoiceId): bool;

    /**
     * Check if invoice item exists
     * 
     * @param int $itemId
     * @return bool
     */
    public function exists(int $itemId): bool;

    /**
     * Count total invoice items
     * 
     * @return int
     */
    public function count(): int;

    /**
     * Count items for an invoice
     * 
     * @param int $invoiceId
     * @return int
     */
    public function countByInvoiceId(int $invoiceId): int;
}
