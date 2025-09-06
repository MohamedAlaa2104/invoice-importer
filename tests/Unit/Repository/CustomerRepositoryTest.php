<?php

namespace Mohamedaladdin\InvoiceImporter\Tests\Unit\Repository;

use Mohamedaladdin\InvoiceImporter\Tests\TestCase;
use Mohamedaladdin\InvoiceImporter\Repository\CustomerRepository;
use Mohamedaladdin\InvoiceImporter\Entity\Customer;

/**
 * Customer Repository Test
 */
class CustomerRepositoryTest extends TestCase
{
    private CustomerRepository $repository;

    protected function setUp(): void
    {
        parent::setUp();
        $this->repository = new CustomerRepository($this->connection);
    }

    public function testSaveNewCustomer(): void
    {
        $customer = new Customer('John Doe', '123 Main St, City, State');
        
        $savedCustomer = $this->repository->save($customer);
        
        $this->assertNotNull($savedCustomer->getCustomerId());
        $this->assertEquals('John Doe', $savedCustomer->getCustomerName());
        $this->assertEquals('123 Main St, City, State', $savedCustomer->getCustomerAddress());
    }

    public function testFindById(): void
    {
        $customer = new Customer('Jane Smith', '456 Oak Ave, City, State');
        $savedCustomer = $this->repository->save($customer);
        
        $foundCustomer = $this->repository->findById($savedCustomer->getCustomerId());
        
        $this->assertNotNull($foundCustomer);
        $this->assertEquals($savedCustomer->getCustomerId(), $foundCustomer->getCustomerId());
        $this->assertEquals('Jane Smith', $foundCustomer->getCustomerName());
    }

    public function testFindByNameAndAddress(): void
    {
        $customer = new Customer('Bob Johnson', '789 Pine St, City, State');
        $this->repository->save($customer);
        
        $foundCustomer = $this->repository->findByNameAndAddress('Bob Johnson', '789 Pine St, City, State');
        
        $this->assertNotNull($foundCustomer);
        $this->assertEquals('Bob Johnson', $foundCustomer->getCustomerName());
        $this->assertEquals('789 Pine St, City, State', $foundCustomer->getCustomerAddress());
    }

    public function testFindAll(): void
    {
        $customer1 = new Customer('Customer 1', 'Address 1');
        $customer2 = new Customer('Customer 2', 'Address 2');
        
        $this->repository->save($customer1);
        $this->repository->save($customer2);
        
        $customers = $this->repository->findAll();
        
        $this->assertCount(2, $customers);
    }

    public function testFindByName(): void
    {
        $customer1 = new Customer('John Smith', 'Address 1');
        $customer2 = new Customer('Jane Smith', 'Address 2');
        $customer3 = new Customer('Bob Johnson', 'Address 3');
        
        $this->repository->save($customer1);
        $this->repository->save($customer2);
        $this->repository->save($customer3);
        
        $smithCustomers = $this->repository->findByName('Smith');
        
        $this->assertCount(2, $smithCustomers);
        $names = array_map(fn($c) => $c->getCustomerName(), $smithCustomers);
        $this->assertContainsEquals('John Smith', $names);
        $this->assertContainsEquals('Jane Smith', $names);
    }

    public function testDelete(): void
    {
        $customer = new Customer('To Delete', 'Address');
        $savedCustomer = $this->repository->save($customer);
        
        $result = $this->repository->delete($savedCustomer->getCustomerId());
        
        $this->assertTrue($result);
        
        $deletedCustomer = $this->repository->findById($savedCustomer->getCustomerId());
        $this->assertNull($deletedCustomer);
    }

    public function testExists(): void
    {
        $customer = new Customer('Test Customer', 'Test Address');
        $savedCustomer = $this->repository->save($customer);
        
        $this->assertTrue($this->repository->exists($savedCustomer->getCustomerId()));
        $this->assertFalse($this->repository->exists(999));
    }

    public function testCount(): void
    {
        $this->assertEquals(0, $this->repository->count());
        
        $customer1 = new Customer('Customer 1', 'Address 1');
        $customer2 = new Customer('Customer 2', 'Address 2');
        
        $this->repository->save($customer1);
        $this->assertEquals(1, $this->repository->count());
        
        $this->repository->save($customer2);
        $this->assertEquals(2, $this->repository->count());
    }

    public function testGetOrCreate(): void
    {
        // First call should create
        $customer1 = $this->repository->getOrCreate('John Doe', '123 Main St');
        $this->assertNotNull($customer1->getCustomerId());
        
        // Second call should return existing
        $customer2 = $this->repository->getOrCreate('John Doe', '123 Main St');
        $this->assertEquals($customer1->getCustomerId(), $customer2->getCustomerId());
        
        // Should only have one customer in database
        $this->assertEquals(1, $this->repository->count());
    }

    public function testUpdateCustomer(): void
    {
        $customer = new Customer('Original Name', 'Original Address');
        $savedCustomer = $this->repository->save($customer);
        
        // Update customer
        $savedCustomer->setCustomerName('Updated Name');
        $savedCustomer->setCustomerAddress('Updated Address');
        $savedCustomer->setUpdatedAt(new \DateTime());
        
        $updatedCustomer = $this->repository->save($savedCustomer);
        
        $this->assertEquals($savedCustomer->getCustomerId(), $updatedCustomer->getCustomerId());
        $this->assertEquals('Updated Name', $updatedCustomer->getCustomerName());
        $this->assertEquals('Updated Address', $updatedCustomer->getCustomerAddress());
    }
}
