<?php

namespace Mohamedaladdin\InvoiceImporter\Tests\Unit\Service;

use Mohamedaladdin\InvoiceImporter\Tests\TestCase;
use Mohamedaladdin\InvoiceImporter\Repository\CustomerRepository;
use Mohamedaladdin\InvoiceImporter\Repository\InvoiceRepository;
use Mohamedaladdin\InvoiceImporter\Repository\InvoiceItemRepository;
use Mohamedaladdin\InvoiceImporter\Service\DataExportService;
use Mohamedaladdin\InvoiceImporter\Entity\Customer;
use Mohamedaladdin\InvoiceImporter\Entity\Invoice;
use Mohamedaladdin\InvoiceImporter\Entity\InvoiceItem;

/**
 * Data Export Service Test
 */
class DataExportServiceTest extends TestCase
{
    private DataExportService $exportService;
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
        
        $this->exportService = new DataExportService(
            $this->invoiceRepository,
            $this->customerRepository
        );
    }

    public function testExportAllInvoicesAsJson(): void
    {
        $this->createTestData();
        
        $result = $this->exportService->exportAllInvoices('json');
        
        $this->assertIsString($result);
        $data = json_decode($result, true);
        $this->assertIsArray($data);
        $this->assertTrue(array_key_exists('export_timestamp', $data));
        $this->assertTrue(array_key_exists('total_records', $data));
        $this->assertTrue(array_key_exists('data', $data));
        $this->assertEquals(1, $data['total_records']);
    }

    public function testExportAllInvoicesAsXml(): void
    {
        $this->createTestData();
        
        $result = $this->exportService->exportAllInvoices('xml');
        
        $this->assertIsString($result);
        $this->assertStringContainsString('<export', $result);
        $this->assertStringContainsString('</export>', $result);
    }

    public function testExportAllInvoicesAsExcel(): void
    {
        $this->createTestData();
        
        $result = $this->exportService->exportAllInvoices('excel');
        
        $this->assertIsString($result);
        // Excel files start with PK (ZIP signature)
        $this->assertStringStartsWith('PK', $result);
    }

    public function testExportCustomersAsJson(): void
    {
        $this->createTestData();
        
        $result = $this->exportService->exportCustomers('json');
        
        $this->assertIsString($result);
        $data = json_decode($result, true);
        $this->assertIsArray($data);
        $this->assertEquals(1, $data['total_records']);
    }

    public function testExportInvoicesByCustomer(): void
    {
        $this->createTestData();
        
        $customer = $this->customerRepository->findAll()[0];
        $result = $this->exportService->exportInvoicesByCustomer($customer->getCustomerId(), 'json');
        
        $this->assertIsString($result);
        $data = json_decode($result, true);
        $this->assertIsArray($data);
        $this->assertEquals(1, $data['total_records']);
    }

    public function testExportInvoicesByDateRange(): void
    {
        $this->createTestData();
        
        $startDate = new \DateTime('2023-01-01');
        $endDate = new \DateTime('2023-12-31');
        
        $result = $this->exportService->exportInvoicesByDateRange($startDate, $endDate, 'json');
        
        $this->assertIsString($result);
        $data = json_decode($result, true);
        $this->assertIsArray($data);
        $this->assertEquals(1, $data['total_records']);
    }

    public function testGetSupportedFormats(): void
    {
        $formats = $this->exportService->getSupportedFormats();
        
        $this->assertIsArray($formats);
        $this->assertContainsEquals('json', $formats);
        $this->assertContainsEquals('xml', $formats);
        $this->assertContainsEquals('excel', $formats);
    }

    public function testIsValidFormat(): void
    {
        $this->assertTrue($this->exportService->isValidFormat('json'));
        $this->assertTrue($this->exportService->isValidFormat('xml'));
        $this->assertTrue($this->exportService->isValidFormat('excel'));
        $this->assertFalse($this->exportService->isValidFormat('csv'));
        $this->assertFalse($this->exportService->isValidFormat('invalid'));
    }

    private function createTestData(): void
    {
        // Create a customer
        $customer = new Customer('Test Customer', 'Test Address');
        $savedCustomer = $this->customerRepository->save($customer);
        
        // Create an invoice
        $invoice = new Invoice(1, new \DateTime('2023-01-15'), $savedCustomer->getCustomerId(), $savedCustomer);
        
        // Add items to invoice
        $item1 = new InvoiceItem('Product A', 2, 25.50);
        $item2 = new InvoiceItem('Product B', 1, 15.00);
        
        $invoice->addItem($item1);
        $invoice->addItem($item2);
        
        // Save invoice
        $this->invoiceRepository->save($invoice);
    }
}
