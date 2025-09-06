<?php

namespace Mohamedaladdin\InvoiceImporter\Tests\Unit\Service;

use Mohamedaladdin\InvoiceImporter\Tests\TestCase;
use Mohamedaladdin\InvoiceImporter\Repository\CustomerRepository;
use Mohamedaladdin\InvoiceImporter\Repository\InvoiceRepository;
use Mohamedaladdin\InvoiceImporter\Repository\InvoiceItemRepository;
use Mohamedaladdin\InvoiceImporter\Service\ExcelImportService;
use Mohamedaladdin\InvoiceImporter\Exception\ImportException;

/**
 * Excel Import Service Test
 */
class ExcelImportServiceTest extends TestCase
{
    private ExcelImportService $importService;
    private CustomerRepository $customerRepository;
    private InvoiceRepository $invoiceRepository;
    private InvoiceItemRepository $itemRepository;

    protected function setUp(): void
    {
        parent::setUp();
        
        $this->customerRepository = new CustomerRepository($this->connection);
        $this->itemRepository = new InvoiceItemRepository($this->connection);
        $this->invoiceRepository = new InvoiceRepository(
            $this->connection,
            $this->customerRepository,
            $this->itemRepository
        );
        
        $this->importService = new ExcelImportService(
            $this->customerRepository,
            $this->invoiceRepository,
            $this->itemRepository
        );
    }

    public function testImportFromValidData(): void
    {
        $data = $this->getTestInvoiceData();
        
        $result = $this->importService->importFromData($data);
        
        $this->assertTrue($result->isSuccess());
        $this->assertEquals(2, $result->getSuccessCount());
        $this->assertEquals(0, $result->getErrorCount());
        $this->assertCount(2, $result->getImportedInvoices());
    }

    public function testImportWithInvalidData(): void
    {
        $invalidData = [
            // Header row
            ['invoice', 'Invoice Date', 'Customer Name', 'Customer Address', 'Product Name', 'Quantity', 'Price', 'Total', 'Grand Total'],
            // Invalid row - empty customer name
            [1, '2023年1月15日', '', '123 Main St', 'Product A', 2, 25.50, 51.00, 51.00]
        ];
        
        $result = $this->importService->importFromData($invalidData);
        
        $this->assertFalse($result->isSuccess());
        $this->assertEquals(0, $result->getSuccessCount());
        $this->assertGreaterThan(0, $result->getErrorCount());
    }

    public function testImportCreatesCustomers(): void
    {
        $data = $this->getTestInvoiceData();
        
        $this->importService->importFromData($data);
        
        $customers = $this->customerRepository->findAll();
        $this->assertCount(2, $customers);
        
        $customerNames = array_map(fn($c) => $c->getCustomerName(), $customers);
        $this->assertContainsEquals('John Doe', $customerNames);
        $this->assertContainsEquals('Jane Smith', $customerNames);
    }

    public function testImportCreatesInvoices(): void
    {
        $data = $this->getTestInvoiceData();
        
        $this->importService->importFromData($data);
        
        $invoices = $this->invoiceRepository->findAll();
        $this->assertCount(2, $invoices);
        
        $invoiceNumbers = array_map(fn($i) => $i->getInvoiceNumber(), $invoices);
        $this->assertContainsEquals(1, $invoiceNumbers);
        $this->assertContainsEquals(2, $invoiceNumbers);
    }

    public function testImportCreatesInvoiceItems(): void
    {
        $data = $this->getTestInvoiceData();
        
        $this->importService->importFromData($data);
        
        $items = $this->itemRepository->findAll();
        $this->assertCount(3, $items); // 2 items for invoice 1, 1 item for invoice 2
        
        $productNames = array_map(fn($i) => $i->getProductName(), $items);
        $this->assertContainsEquals('Product A', $productNames);
        $this->assertContainsEquals('Product B', $productNames);
        $this->assertContainsEquals('Product C', $productNames);
    }

    public function testImportCalculatesGrandTotal(): void
    {
        $data = $this->getTestInvoiceData();
        
        $this->importService->importFromData($data);
        
        $invoices = $this->invoiceRepository->findAll();
        
        // Invoice 1: (2 * 25.50) + (1 * 15.00) = 66.00
        $invoice1Filtered = array_filter($invoices, fn($i) => $i->getInvoiceNumber() === 1);
        $invoice1 = reset($invoice1Filtered);
        $this->assertEquals(66.00, $invoice1->getGrandTotal());
        
        // Invoice 2: (3 * 10.00) = 30.00
        $invoice2Filtered = array_filter($invoices, fn($i) => $i->getInvoiceNumber() === 2);
        $invoice2 = reset($invoice2Filtered);
        $this->assertEquals(30.00, $invoice2->getGrandTotal());
    }

    public function testGetImportStatistics(): void
    {
        $data = $this->getTestInvoiceData();
        
        $this->importService->importFromData($data);
        
        $stats = $this->importService->getImportStatistics();
        
        $this->assertTrue(array_key_exists('total_rows', $stats));
        $this->assertTrue(array_key_exists('processed_rows', $stats));
        $this->assertTrue(array_key_exists('successful_imports', $stats));
        $this->assertTrue(array_key_exists('failed_imports', $stats));
        $this->assertTrue(array_key_exists('customers_created', $stats));
        $this->assertTrue(array_key_exists('invoices_created', $stats));
        
        $this->assertEquals(2, $stats['successful_imports']);
        $this->assertEquals(2, $stats['customers_created']);
        $this->assertEquals(2, $stats['invoices_created']);
    }
}
