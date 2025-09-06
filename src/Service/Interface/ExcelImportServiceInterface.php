<?php

namespace Mohamedaladdin\InvoiceImporter\Service\Interface;

use Mohamedaladdin\InvoiceImporter\Service\ImportResult;

/**
 * Excel Import Service Interface
 * Defines contract for Excel import operations
 */
interface ExcelImportServiceInterface
{
    /**
     * Import invoices from Excel file
     * 
     * @param string $filePath Path to Excel file
     * @return ImportResult
     */
    public function importFromFile(string $filePath): ImportResult;

    /**
     * Import invoices from Excel data array
     * 
     * @param array $data Excel data array
     * @return ImportResult
     */
    public function importFromData(array $data): ImportResult;

    /**
     * Validate Excel file structure
     * 
     * @param string $filePath Path to Excel file
     * @return bool
     */
    public function validateFile(string $filePath): bool;

    /**
     * Get import statistics
     * 
     * @return array
     */
    public function getImportStatistics(): array;
}
