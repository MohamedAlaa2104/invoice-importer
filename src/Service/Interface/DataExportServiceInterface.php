<?php

namespace Mohamedaladdin\InvoiceImporter\Service\Interface;

/**
 * Data Export Service Interface
 * Defines contract for data export operations
 */
interface DataExportServiceInterface
{
    /**
     * Export all invoices
     * 
     * @param string $format Export format (json, xml)
     * @param array $filters Optional filters
     * @return string
     */
    public function exportAllInvoices(string $format = 'json', array $filters = []): string;

    /**
     * Export invoices by customer ID
     * 
     * @param int $customerId Customer ID
     * @param string $format Export format (json, xml)
     * @return string
     */
    public function exportInvoicesByCustomer(int $customerId, string $format = 'json'): string;

    /**
     * Export invoices by date range
     * 
     * @param \DateTime $startDate Start date
     * @param \DateTime $endDate End date
     * @param string $format Export format (json, xml)
     * @return string
     */
    public function exportInvoicesByDateRange(\DateTime $startDate, \DateTime $endDate, string $format = 'json'): string;

    /**
     * Export single invoice
     * 
     * @param int $invoiceId Invoice ID
     * @param string $format Export format (json, xml)
     * @return string
     */
    public function exportInvoice(int $invoiceId, string $format = 'json'): string;

    /**
     * Export customers
     * 
     * @param string $format Export format (json, xml)
     * @return string
     */
    public function exportCustomers(string $format = 'json'): string;

    /**
     * Get supported export formats
     * 
     * @return array
     */
    public function getSupportedFormats(): array;

    /**
     * Validate export format
     * 
     * @param string $format
     * @return bool
     */
    public function isValidFormat(string $format): bool;
}
