<?php

declare(strict_types=1);

namespace Ttpryg\Validation\Exception;

/**
 * Contract for validation failures.
 *
 * Consumers such as HTTP frameworks or JSON response writers can detect and
 * render validation errors by checking `instanceof ValidationExceptionInterface`
 * without depending on a concrete exception class.
 */
interface ValidationExceptionInterface extends ExceptionInterface
{
    /**
     * Validation errors grouped per field: field name => list of messages.
     *
     * @return array<string, list<string>>
     */
    public function getErrors(): array;

    /**
     * Suggested HTTP status code for the response (422 by default).
     */
    public function getStatusCode(): int;
}
