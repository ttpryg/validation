<?php

declare(strict_types=1);

namespace Ttpryg\Validation\Exception;

use RuntimeException;

final class ValidationException extends RuntimeException implements ExceptionInterface, ValidationExceptionInterface
{
    /**
     * @param  array<string, list<string>>  $errors  Validation errors grouped per field.
     */
    public function __construct(private readonly array $errors, string $message = 'Validation failed')
    {
        parent::__construct($message, 422);
    }

    /**
     * @return array<string, list<string>>
     */
    public function getErrors(): array
    {
        return $this->errors;
    }

    public function getStatusCode(): int
    {
        return 422;
    }
}
