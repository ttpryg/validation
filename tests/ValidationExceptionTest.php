<?php

declare(strict_types=1);

namespace Ttpryg\Validation\Tests;

use PHPUnit\Framework\TestCase;
use Ttpryg\Validation\Exception\ExceptionInterface;
use Ttpryg\Validation\Exception\ValidationException;
use Ttpryg\Validation\Exception\ValidationExceptionInterface;

final class ValidationExceptionTest extends TestCase
{
    private ValidationException $exception;

    protected function setUp(): void
    {
        parent::setUp();

        $this->exception = new ValidationException([
            'email' => ['This value should not be blank.', 'This value is not a valid email address.'],
            'name' => ['This value is too short.'],
        ]);
    }

    public function test_errors_are_grouped_per_field(): void
    {
        $this->assertSame([
            'email' => ['This value should not be blank.', 'This value is not a valid email address.'],
            'name' => ['This value is too short.'],
        ], $this->exception->getErrors());
    }

    public function test_status_code_is_unprocessable_entity(): void
    {
        $this->assertSame(422, $this->exception->getStatusCode());
        $this->assertSame(422, $this->exception->getCode());
    }

    public function test_default_message_is_validation_failed(): void
    {
        $this->assertSame('Validation failed', $this->exception->getMessage());
        $this->assertSame('Custom message', (new ValidationException([], 'Custom message'))->getMessage());
    }

    public function test_it_implements_the_package_contracts(): void
    {
        $this->assertInstanceOf(ValidationExceptionInterface::class, $this->exception);
        $this->assertInstanceOf(ExceptionInterface::class, $this->exception);
        $this->assertInstanceOf(\RuntimeException::class, $this->exception);
    }
}
