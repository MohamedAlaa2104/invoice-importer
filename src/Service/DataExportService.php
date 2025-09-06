<?php

namespace Mohamedaladdin\InvoiceImporter\Service;

use Mohamedaladdin\InvoiceImporter\Exception\ExportException;
use Mohamedaladdin\InvoiceImporter\Repository\Interface\InvoiceRepositoryInterface;
use Mohamedaladdin\InvoiceImporter\Repository\Interface\CustomerRepositoryInterface;
use Mohamedaladdin\InvoiceImporter\Service\Interface\DataExportServiceInterface;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;

/**
 * Data Export Service Implementation
 */
class DataExportService implements DataExportServiceInterface
{
    private array $supportedFormats = ['json', 'xml', 'excel'];

    public function __construct(
        private InvoiceRepositoryInterface $invoiceRepository,
        private CustomerRepositoryInterface $customerRepository
    ) {
    }

    public function exportAllInvoices(string $format = 'json', array $filters = []): string
    {
        try {
            $invoices = $this->invoiceRepository->findAll();
            
            // Apply filters if provided
            if (!empty($filters)) {
                $invoices = $this->applyFilters($invoices, $filters);
            }

            return $this->formatData($invoices, $format);
        } catch (\Exception $e) {
            throw ExportException::withFormat(
                "Failed to export all invoices: " . $e->getMessage(),
                $format,
                $e
            );
        }
    }

    public function exportInvoicesByCustomer(int $customerId, string $format = 'json'): string
    {
        try {
            $invoices = $this->invoiceRepository->findByCustomerId($customerId);
            return $this->formatData($invoices, $format);
        } catch (\Exception $e) {
            throw ExportException::withFormat(
                "Failed to export invoices by customer: " . $e->getMessage(),
                $format,
                $e
            );
        }
    }

    public function exportInvoicesByDateRange(\DateTime $startDate, \DateTime $endDate, string $format = 'json'): string
    {
        try {
            $invoices = $this->invoiceRepository->findByDateRange($startDate, $endDate);
            return $this->formatData($invoices, $format);
        } catch (\Exception $e) {
            throw ExportException::withFormat(
                "Failed to export invoices by date range: " . $e->getMessage(),
                $format,
                $e
            );
        }
    }

    public function exportInvoice(int $invoiceId, string $format = 'json'): string
    {
        try {
            $invoice = $this->invoiceRepository->findById($invoiceId);
            if (!$invoice) {
                throw new ExportException("Invoice not found: {$invoiceId}");
            }

            return $this->formatData([$invoice], $format);
        } catch (\Exception $e) {
            throw ExportException::withFormat(
                "Failed to export invoice: " . $e->getMessage(),
                $format,
                $e
            );
        }
    }

    public function exportCustomers(string $format = 'json'): string
    {
        try {
            $customers = $this->customerRepository->findAll();
            return $this->formatData($customers, $format);
        } catch (\Exception $e) {
            throw ExportException::withFormat(
                "Failed to export customers: " . $e->getMessage(),
                $format,
                $e
            );
        }
    }

    public function getSupportedFormats(): array
    {
        return $this->supportedFormats;
    }

    public function isValidFormat(string $format): bool
    {
        return in_array(strtolower($format), $this->supportedFormats);
    }

    /**
     * Format data according to specified format
     * 
     * @param array $data
     * @param string $format
     * @return string
     * @throws ExportException
     */
    private function formatData(array $data, string $format): string
    {
        if (!$this->isValidFormat($format)) {
            throw ExportException::withFormat("Unsupported export format: {$format}", $format);
        }

        switch (strtolower($format)) {
            case 'json':
                return $this->formatAsJson($data);
            case 'xml':
                return $this->formatAsXml($data);
            case 'excel':
                return $this->formatAsExcel($data);
            default:
                throw ExportException::withFormat("Unsupported export format: {$format}", $format);
        }
    }

    /**
     * Format data as JSON
     * 
     * @param array $data
     * @return string
     */
    private function formatAsJson(array $data): string
    {
        $exportData = [
            'export_timestamp' => date('Y-m-d H:i:s'),
            'total_records' => count($data),
            'data' => array_map(fn($item) => $item->toArray(), $data)
        ];

        return json_encode($exportData, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
    }

    /**
     * Format data as XML
     * 
     * @param array $data
     * @return string
     */
    private function formatAsXml(array $data): string
    {
        $xml = new \SimpleXMLElement('<export></export>');
        $xml->addAttribute('timestamp', date('Y-m-d H:i:s'));
        $xml->addAttribute('total_records', count($data));

        foreach ($data as $item) {
            $this->arrayToXml($item->toArray(), $xml);
        }

        return $xml->asXML();
    }

    /**
     * Convert array to XML recursively
     * 
     * @param array $data
     * @param \SimpleXMLElement $xml
     */
    private function arrayToXml(array $data, \SimpleXMLElement $xml): void
    {
        foreach ($data as $key => $value) {
            if (is_array($value)) {
                if (is_numeric($key)) {
                    $key = 'item';
                }
                $subnode = $xml->addChild($key);
                $this->arrayToXml($value, $subnode);
            } else {
                if (is_numeric($key)) {
                    $key = 'item';
                }
                $xml->addChild($key, htmlspecialchars($value));
            }
        }
    }

    /**
     * Apply filters to data
     * 
     * @param array $data
     * @param array $filters
     * @return array
     */
    private function applyFilters(array $data, array $filters): array
    {
        $filtered = $data;

        // Filter by customer ID
        if (isset($filters['customer_id'])) {
            $filtered = array_filter($filtered, function ($invoice) use ($filters) {
                return $invoice->getCustomerId() == $filters['customer_id'];
            });
        }

        // Filter by date range
        if (isset($filters['start_date']) && isset($filters['end_date'])) {
            $startDate = $filters['start_date'] instanceof \DateTime 
                ? $filters['start_date'] 
                : new \DateTime($filters['start_date']);
            $endDate = $filters['end_date'] instanceof \DateTime 
                ? $filters['end_date'] 
                : new \DateTime($filters['end_date']);

            $filtered = array_filter($filtered, function ($invoice) use ($startDate, $endDate) {
                $invoiceDate = $invoice->getInvoiceDate();
                return $invoiceDate >= $startDate && $invoiceDate <= $endDate;
            });
        }

        // Filter by minimum amount
        if (isset($filters['min_amount'])) {
            $filtered = array_filter($filtered, function ($invoice) use ($filters) {
                return $invoice->getGrandTotal() >= $filters['min_amount'];
            });
        }

        // Filter by maximum amount
        if (isset($filters['max_amount'])) {
            $filtered = array_filter($filtered, function ($invoice) use ($filters) {
                return $invoice->getGrandTotal() <= $filters['max_amount'];
            });
        }

        return array_values($filtered);
    }

    /**
     * Format data as Excel file
     * 
     * @param array $data
     * @return string
     * @throws ExportException
     */
    private function formatAsExcel(array $data): string
    {
        try {
            $spreadsheet = new Spreadsheet();
            $worksheet = $spreadsheet->getActiveSheet();
            $worksheet->setTitle('Invoices');

            // Set headers
            $headers = [
                'A1' => 'invoice',
                'B1' => 'Invoice Date',
                'C1' => 'Customer Name',
                'D1' => 'Customer Address',
                'E1' => 'Product Name',
                'F1' => 'Quantity',
                'G1' => 'Price',
                'H1' => 'Total',
                'I1' => 'Grand Total'
            ];

            foreach ($headers as $cell => $value) {
                $worksheet->setCellValue($cell, $value);
            }

            // Add data rows
            $row = 2;
            foreach ($data as $invoice) {
                if (method_exists($invoice, 'getItems')) {
                    // This is an invoice object
                    $items = $invoice->getItems();
                    $grandTotal = $invoice->getGrandTotal();
                    
                    foreach ($items as $item) {
                        $worksheet->setCellValue('A' . $row, $invoice->getInvoiceNumber());
                        $worksheet->setCellValue('B' . $row, $invoice->getInvoiceDate()->format('Y-m-d'));
                        $worksheet->setCellValue('C' . $row, $invoice->getCustomer()->getCustomerName());
                        $worksheet->setCellValue('D' . $row, $invoice->getCustomer()->getCustomerAddress());
                        $worksheet->setCellValue('E' . $row, $item->getProductName());
                        $worksheet->setCellValue('F' . $row, $item->getQuantity());
                        $worksheet->setCellValue('G' . $row, $item->getUnitPrice());
                        $worksheet->setCellValue('H' . $row, $item->getTotalPrice());
                        $worksheet->setCellValue('I' . $row, $grandTotal);
                        $row++;
                    }
                } else {
                    // This is a customer object or other data
                    $worksheet->setCellValue('A' . $row, $invoice->getCustomerName() ?? '');
                    $worksheet->setCellValue('B' . $row, $invoice->getCustomerAddress() ?? '');
                    $row++;
                }
            }

            // Create temporary file
            $tempFile = tempnam(sys_get_temp_dir(), 'export_') . '.xlsx';
            $writer = new Xlsx($spreadsheet);
            $writer->save($tempFile);

            // Read file content
            $content = file_get_contents($tempFile);
            unlink($tempFile);

            return $content;

        } catch (\Exception $e) {
            throw ExportException::withFormat("Failed to create Excel export: " . $e->getMessage(), 'excel', $e);
        }
    }
}
