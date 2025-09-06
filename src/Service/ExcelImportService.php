<?php

namespace Mohamedaladdin\InvoiceImporter\Service;

use Mohamedaladdin\InvoiceImporter\Entity\Customer;
use Mohamedaladdin\InvoiceImporter\Entity\Invoice;
use Mohamedaladdin\InvoiceImporter\Entity\InvoiceItem;
use Mohamedaladdin\InvoiceImporter\Exception\ImportException;
use Mohamedaladdin\InvoiceImporter\Repository\Interface\CustomerRepositoryInterface;
use Mohamedaladdin\InvoiceImporter\Repository\Interface\InvoiceRepositoryInterface;
use Mohamedaladdin\InvoiceImporter\Repository\Interface\InvoiceItemRepositoryInterface;
use Mohamedaladdin\InvoiceImporter\Service\Interface\ExcelImportServiceInterface;
use Mohamedaladdin\InvoiceImporter\Service\ImportResult;
use Mohamedaladdin\InvoiceImporter\Utility\DataValidator;
use Mohamedaladdin\InvoiceImporter\Utility\ExcelReader;

/**
 * Excel Import Service Implementation
 */
class ExcelImportService implements ExcelImportServiceInterface
{
    private array $importStatistics = [];

    public function __construct(
        private CustomerRepositoryInterface $customerRepository,
        private InvoiceRepositoryInterface $invoiceRepository,
        private InvoiceItemRepositoryInterface $itemRepository
    ) {
    }

    public function importFromFile(string $filePath): ImportResult
    {
        if (!ExcelReader::isValidExcelFile($filePath)) {
            throw ImportException::withFile("Invalid Excel file: {$filePath}", $filePath);
        }

        try {
            $excelReader = new ExcelReader($filePath);
            $data = $excelReader->getAllData();
            
            return $this->importFromData($data);
        } catch (\Exception $e) {
            throw ImportException::withFile(
                "Failed to read Excel file: " . $e->getMessage(),
                $filePath,
                $e
            );
        }
    }

    public function importFromData(array $data): ImportResult
    {
        $result = new ImportResult();
        $this->importStatistics = [
            'total_rows' => count($data),
            'processed_rows' => 0,
            'successful_imports' => 0,
            'failed_imports' => 0,
            'customers_created' => 0,
            'invoices_created' => 0
        ];

        try {
            // Check if data has header row and skip it
            $startRow = $this->hasHeaderRow($data) ? 1 : 0;
            
            // Group data by invoice number
            $groupedData = $this->groupDataByInvoice($data, $startRow);
            
            if (empty($groupedData)) {
                throw new ImportException("No valid invoice data found in the file");
            }

            // Process each invoice
            foreach ($groupedData as $invoiceNumber => $invoiceRows) {
                try {
                    $invoice = $this->processInvoiceRows($invoiceNumber, $invoiceRows);
                    $this->invoiceRepository->save($invoice);
                    $result->addImportedInvoice($invoice);
                    $this->importStatistics['invoices_created']++;
                    $this->importStatistics['successful_imports']++;
                } catch (\Exception $e) {
                    $result->addError("Invoice {$invoiceNumber} processing failed: " . $e->getMessage());
                    $this->importStatistics['failed_imports']++;
                }
                
                $this->importStatistics['processed_rows'] += count($invoiceRows);
            }
            
        } catch (\Exception $e) {
            throw new ImportException("Import process failed: " . $e->getMessage(), 0, $e);
        }

        return $result;
    }

    public function validateFile(string $filePath): bool
    {
        if (!ExcelReader::isValidExcelFile($filePath)) {
            return false;
        }

        try {
            $excelReader = new ExcelReader($filePath);
            $data = $excelReader->getAllData();
            
            if (empty($data)) {
                return false;
            }

            // Check if data has minimum required structure
            $startRow = $this->hasHeaderRow($data) ? 1 : 0;
            $groupedData = $this->groupDataByInvoice($data, $startRow);
            
            return !empty($groupedData);
        } catch (\Exception $e) {
            return false;
        }
    }

    public function getImportStatistics(): array
    {
        return $this->importStatistics;
    }

    /**
     * Check if data has header row
     * 
     * @param array $data
     * @return bool
     */
    private function hasHeaderRow(array $data): bool
    {
        if (empty($data)) {
            return false;
        }

        $firstRow = $data[0];
        $headerKeywords = ['invoice', 'customer', 'product', 'quantity', 'price', 'date'];
        
        foreach ($firstRow as $cell) {
            if (is_string($cell)) {
                $lowerCell = strtolower($cell);
                foreach ($headerKeywords as $keyword) {
                    if (strpos($lowerCell, $keyword) !== false) {
                        return true;
                    }
                }
            }
        }

        return false;
    }

    /**
     * Group data by invoice number
     * 
     * @param array $data
     * @param int $startRow
     * @return array
     */
    private function groupDataByInvoice(array $data, int $startRow): array
    {
        $grouped = [];
        
        for ($i = $startRow; $i < count($data); $i++) {
            $row = $data[$i];
            
            // Skip empty rows
            if (empty(array_filter($row))) {
                continue;
            }

            // Extract invoice number (first column)
            $invoiceNumber = $row[0] ?? null;
            if (!$invoiceNumber || !is_numeric($invoiceNumber)) {
                continue;
            }

            $invoiceNumber = (int) $invoiceNumber;
            
            if (!isset($grouped[$invoiceNumber])) {
                $grouped[$invoiceNumber] = [];
            }
            
            $grouped[$invoiceNumber][] = $row;
        }

        return $grouped;
    }

    /**
     * Process invoice rows and create entities
     * 
     * @param int $invoiceNumber
     * @param array $invoiceRows
     * @return Invoice
     * @throws ImportException
     */
    private function processInvoiceRows(int $invoiceNumber, array $invoiceRows): Invoice
    {
        if (empty($invoiceRows)) {
            throw new ImportException("No invoice rows provided for invoice {$invoiceNumber}");
        }

        // Get the first row to extract invoice-level information
        $firstRow = $invoiceRows[0];
        
        // Extract invoice information from the first row
        // Column mapping: 0=invoice, 1=date, 2=customer_name, 3=customer_address, 4=product, 5=qty, 6=price, 7=total, 8=grand_total
        $invoiceDate = $firstRow[1] ?? null;
        $customerName = $firstRow[2] ?? '';
        $customerAddress = $firstRow[3] ?? '';

        if (empty($customerName) || empty($customerAddress)) {
            throw new ImportException("Customer information is required for invoice {$invoiceNumber}");
        }

        // Get or create customer
        $existingCustomer = $this->customerRepository->findByNameAndAddress($customerName, $customerAddress);
        if (!$existingCustomer) {
            $this->importStatistics['customers_created']++;
        }
        $customer = $this->customerRepository->getOrCreate($customerName, $customerAddress);

        // Parse invoice date
        $parsedDate = $this->parseInvoiceDate($invoiceDate);

        // Create invoice
        $invoice = new Invoice($invoiceNumber, $parsedDate, $customer->getCustomerId(), $customer);

        // Process each row as an invoice item
        foreach ($invoiceRows as $row) {
            try {
                $item = $this->createInvoiceItemFromRow($row);
                $invoice->addItem($item);
            } catch (\Exception $e) {
                throw new ImportException("Failed to process item for invoice {$invoiceNumber}: " . $e->getMessage());
            }
        }

        // Validate invoice
        $invoice->validate();

        return $invoice;
    }

    /**
     * Create invoice item from row data
     * 
     * @param array $row
     * @return InvoiceItem
     * @throws ImportException
     */
    private function createInvoiceItemFromRow(array $row): InvoiceItem
    {
        // Column mapping: 0=invoice, 1=date, 2=customer_name, 3=customer_address, 4=product, 5=qty, 6=price, 7=total, 8=grand_total
        $productName = $row[4] ?? '';
        $quantity = $row[5] ?? 0;
        $unitPrice = $row[6] ?? 0;

        if (empty($productName)) {
            throw new ImportException("Product name cannot be empty");
        }

        // Convert quantity and price to numeric values
        $quantity = is_numeric($quantity) ? (float) $quantity : 0;
        $unitPrice = is_numeric($unitPrice) ? (float) $unitPrice : 0;

        return new InvoiceItem($productName, $quantity, $unitPrice);
    }

    /**
     * Parse invoice date from various formats
     * 
     * @param mixed $dateValue
     * @return \DateTime
     * @throws ImportException
     */
    private function parseInvoiceDate(mixed $dateValue): \DateTime
    {
        if (empty($dateValue)) {
            throw new ImportException("Invoice date cannot be empty");
        }

        try {
            if ($dateValue instanceof \DateTime) {
                return $dateValue;
            }

            if (is_numeric($dateValue)) {
                // Excel serial date
                $timestamp = \PhpOffice\PhpSpreadsheet\Shared\Date::excelToTimestamp($dateValue);
                return new \DateTime('@' . $timestamp);
            }

            if (is_string($dateValue)) {
                // Handle Chinese date format like "1212122020年1月1日"
                if (preg_match('/(\d{4})年(\d{1,2})月(\d{1,2})日/', $dateValue, $matches)) {
                    $year = $matches[1];
                    $month = str_pad($matches[2], 2, '0', STR_PAD_LEFT);
                    $day = str_pad($matches[3], 2, '0', STR_PAD_LEFT);
                    $dateString = "{$year}-{$month}-{$day}";
                    return new \DateTime($dateString);
                }
                
                // Try standard date parsing
                return new \DateTime($dateValue);
            }

            throw new ImportException("Invalid date format: {$dateValue}");
        } catch (\Exception $e) {
            throw new ImportException("Failed to parse invoice date: " . $e->getMessage());
        }
    }
}
