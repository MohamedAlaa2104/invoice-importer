<?php

namespace Mohamedaladdin\InvoiceImporter\Exception;

/**
 * Database Exception
 * Thrown when database operations fail
 */
class DatabaseException extends \Exception
{
    private ?string $sql = null;
    private array $context = [];

    public function __construct(
        string $message = "",
        int $code = 0,
        ?\Throwable $previous = null,
        ?string $sql = null,
        array $context = []
    ) {
        parent::__construct($message, $code, $previous);
        $this->sql = $sql;
        $this->context = $context;
    }

    /**
     * Get the SQL query that caused the exception
     * 
     * @return string|null
     */
    public function getSql(): ?string
    {
        return $this->sql;
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
     * Create exception with SQL context
     * 
     * @param string $message
     * @param string $sql
     * @param array $params
     * @param \Throwable|null $previous
     * @return static
     */
    public static function withSql(
        string $message,
        string $sql,
        array $params = [],
        ?\Throwable $previous = null
    ): static {
        return new static(
            $message,
            0,
            $previous,
            $sql,
            ['params' => $params]
        );
    }
}
