<?php

namespace Mohamedaladdin\InvoiceImporter\Exception;

/**
 * Export Exception
 * Thrown when data export operations fail
 */
class ExportException extends \Exception
{
    private ?string $format = null;
    private array $context = [];

    public function __construct(
        string $message = "",
        int $code = 0,
        ?\Throwable $previous = null,
        ?string $format = null,
        array $context = []
    ) {
        parent::__construct($message, $code, $previous);
        $this->format = $format;
        $this->context = $context;
    }

    /**
     * Get the export format that caused the exception
     * 
     * @return string|null
     */
    public function getFormat(): ?string
    {
        return $this->format;
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
     * Create exception with format context
     * 
     * @param string $message
     * @param string $format
     * @param \Throwable|null $previous
     * @return static
     */
    public static function withFormat(
        string $message,
        string $format,
        ?\Throwable $previous = null
    ): static {
        return new static(
            $message,
            0,
            $previous,
            $format
        );
    }
}
