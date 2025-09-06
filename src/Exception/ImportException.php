<?php

namespace Mohamedaladdin\InvoiceImporter\Exception;

/**
 * Import Exception
 * Thrown when data import operations fail
 */
class ImportException extends \Exception
{
    private ?string $filePath = null;
    private ?int $rowNumber = null;
    private array $rowData = [];
    private array $context = [];

    public function __construct(
        string $message = "",
        int $code = 0,
        ?\Throwable $previous = null,
        ?string $filePath = null,
        ?int $rowNumber = null,
        array $rowData = [],
        array $context = []
    ) {
        parent::__construct($message, $code, $previous);
        $this->filePath = $filePath;
        $this->rowNumber = $rowNumber;
        $this->rowData = $rowData;
        $this->context = $context;
    }

    /**
     * Get the file path that caused the exception
     * 
     * @return string|null
     */
    public function getFilePath(): ?string
    {
        return $this->filePath;
    }

    /**
     * Get the row number that caused the exception
     * 
     * @return int|null
     */
    public function getRowNumber(): ?int
    {
        return $this->rowNumber;
    }

    /**
     * Get the row data that caused the exception
     * 
     * @return array
     */
    public function getRowData(): array
    {
        return $this->rowData;
    }

    /**
     * Get additional context information
     * 
     * @return array
     */
    public function getContext(): array
    {
        return $this->context;
    }

    /**
     * Create exception with file context
     * 
     * @param string $message
     * @param string $filePath
     * @param \Throwable|null $previous
     * @return static
     */
    public static function withFile(
        string $message,
        string $filePath,
        ?\Throwable $previous = null
    ): static {
        return new static(
            $message,
            0,
            $previous,
            $filePath
        );
    }

    /**
     * Create exception with row context
     * 
     * @param string $message
     * @param int $rowNumber
     * @param array $rowData
     * @param \Throwable|null $previous
     * @return static
     */
    public static function withRow(
        string $message,
        int $rowNumber,
        array $rowData = [],
        ?\Throwable $previous = null
    ): static {
        return new static(
            $message,
            0,
            $previous,
            null,
            $rowNumber,
            $rowData
        );
    }
}
