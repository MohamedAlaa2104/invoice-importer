<?php

namespace Mohamedaladdin\InvoiceImporter\Service;

/**
 * Import Result Class
 * Contains results of import operation
 */
class ImportResult
{
    private int $successCount = 0;
    private int $errorCount = 0;
    private array $errors = [];
    private array $importedInvoices = [];

    public function getSuccessCount(): int
    {
        return $this->successCount;
    }

    public function setSuccessCount(int $count): void
    {
        $this->successCount = $count;
    }

    public function getErrorCount(): int
    {
        return $this->errorCount;
    }

    public function setErrorCount(int $count): void
    {
        $this->errorCount = $count;
    }

    public function getErrors(): array
    {
        return $this->errors;
    }

    public function addError(string $error): void
    {
        $this->errors[] = $error;
        $this->errorCount++;
    }

    public function getImportedInvoices(): array
    {
        return $this->importedInvoices;
    }

    public function addImportedInvoice($invoice): void
    {
        $this->importedInvoices[] = $invoice;
        $this->successCount++;
    }

    public function isSuccess(): bool
    {
        return $this->errorCount === 0;
    }

    public function getTotalProcessed(): int
    {
        return $this->successCount + $this->errorCount;
    }
}
