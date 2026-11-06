<?php

declare(strict_types=1);

namespace Ttpryg\Validation\Tests;

use PHPUnit\Framework\TestCase;
use Symfony\Component\Validator\Constraints as Assert;
use Ttpryg\Validation\Exception\ValidationException;
use Ttpryg\Validation\Tests\Fixtures\RegisterRequest;
use Ttpryg\Validation\Validator;

final class ValidatorTest extends TestCase
{
    private Validator $validator;

    protected function setUp(): void
    {
        parent::setUp();
        $this->validator = new Validator;
    }

    public function test_valid_data_passes_with_string_rules(): void
    {
        $input = [
            'name' => 'John Doe',
            'email' => 'john@example.com',
            'password' => 'secret123',
        ];

        $rules = [
            'name' => 'required|min:3|max:255',
            'email' => 'required|email',
            'password' => 'required|min:8',
        ];

        $this->assertSame($input, $this->validator->validate($input, $rules));
    }

    public function test_invalid_data_throws_validation_exception_grouped_per_field(): void
    {
        $input = [
            'name' => 'Jo',
            'email' => 'not-an-email',
            'password' => '123',
        ];

        $rules = [
            'name' => 'required|min:3',
            'email' => 'required|email',
            'password' => 'required|email|min:8',
        ];

        try {
            $this->validator->validate($input, $rules);
            $this->fail('Expected a ValidationException to be thrown.');
        } catch (ValidationException $exception) {
            $errors = $exception->getErrors();

            $this->assertArrayHasKey('name', $errors);
            $this->assertArrayHasKey('email', $errors);
            $this->assertArrayHasKey('password', $errors);
            $this->assertIsArray($errors['email']);
            $this->assertContains('This value is not a valid email address.', $errors['email']);
            $this->assertSame('Validation failed', $exception->getMessage());
        }
    }

    public function test_field_rules_accept_constraint_instances(): void
    {
        $input = ['age' => 25];

        $rules = [
            'age' => [new Assert\NotBlank, new Assert\Type('integer'), new Assert\GreaterThanOrEqual(18)],
        ];

        $this->assertSame($input, $this->validator->validate($input, $rules));
    }

    public function test_field_rules_accept_a_single_constraint_instance(): void
    {
        $rules = ['email' => new Assert\Email];

        $this->assertSame(
            ['email' => 'john@example.com'],
            $this->validator->validate(['email' => 'john@example.com'], $rules)
        );

        $this->expectException(ValidationException::class);
        $this->validator->validate(['email' => 'nope'], $rules);
    }

    public function test_rules_accept_a_single_collection_constraint(): void
    {
        $constraint = new Assert\Collection([
            'fields' => [
                'email' => new Assert\Optional([new Assert\Email]),
            ],
            'allowExtraFields' => true,
        ]);

        $input = ['email' => 'john@example.com', 'role' => 'admin'];

        // A raw Constraint is used verbatim: no rule-driven key stripping happens.
        $this->assertSame($input, $this->validator->validate($input, $constraint));
    }

    public function test_collection_constraint_violations_are_reported(): void
    {
        $constraint = new Assert\Collection([
            'fields' => [
                'email' => new Assert\Optional([new Assert\Email]),
            ],
        ]);

        $this->expectException(ValidationException::class);
        $this->validator->validate(['email' => 'nope'], $constraint);
    }

    public function test_object_is_validated_through_attribute_mapping(): void
    {
        $request = new RegisterRequest(
            name: 'John Doe',
            email: 'john@example.com',
            password: 'secret123',
            age: 30
        );

        $this->assertSame($request, $this->validator->validate($request));
    }

    public function test_object_attribute_violations_are_reported(): void
    {
        $request = new RegisterRequest(
            name: 'Jo',
            email: 'not-an-email',
            password: '123',
            age: 30
        );

        try {
            $this->validator->validate($request);
            $this->fail('Expected a ValidationException to be thrown.');
        } catch (ValidationException $exception) {
            $errors = $exception->getErrors();

            $this->assertArrayHasKey('email', $errors);
            $this->assertArrayHasKey('password', $errors);
            $this->assertArrayHasKey('name', $errors);
            // Objects are returned as-is on success: keys are never stripped.
            $this->assertArrayNotHasKey('age', $errors);
        }
    }

    public function test_extra_keys_are_stripped_from_the_returned_array(): void
    {
        $input = [
            'email' => 'john@example.com',
            'password' => 'secret123',
            'is_admin' => true,
        ];

        $rules = [
            'email' => 'required|email',
            'password' => 'required|min:8',
        ];

        $validated = $this->validator->validate($input, $rules);

        $this->assertIsArray($validated);
        $this->assertSame(['email' => 'john@example.com', 'password' => 'secret123'], $validated);
        $this->assertArrayNotHasKey('is_admin', $validated);
    }

    public function test_required_rule_rejects_a_missing_field(): void
    {
        $rules = ['name' => 'required|min:3'];

        try {
            $this->validator->validate([], $rules);
            $this->fail('Expected a ValidationException to be thrown.');
        } catch (ValidationException $exception) {
            $this->assertSame(['name' => ['This field is missing.']], $exception->getErrors());
        }
    }

    public function test_optional_rule_allows_a_missing_field_but_rejects_a_blank_value(): void
    {
        $rules = ['nickname' => 'min:3'];

        $this->assertSame([], $this->validator->validate([], $rules));

        $this->expectException(ValidationException::class);
        $this->validator->validate(['nickname' => ''], $rules);
    }

    public function test_not_blank_behaves_like_required(): void
    {
        try {
            $this->validator->validate([], ['token' => 'not_blank']);
            $this->fail('Expected a ValidationException to be thrown.');
        } catch (ValidationException $exception) {
            $this->assertArrayHasKey('token', $exception->getErrors());
        }
    }

    public function test_validated_is_an_alias_of_validate(): void
    {
        $input = ['email' => 'john@example.com'];
        $rules = ['email' => 'required|email'];

        $this->assertSame($this->validator->validate($input, $rules), $this->validator->validated($input, $rules));
        $this->assertSame($input, $this->validator->validated($input, $rules));
    }

    public function test_validated_throws_for_invalid_data(): void
    {
        $this->expectException(ValidationException::class);
        $this->validator->validated(['email' => 'nope'], ['email' => 'required|email']);
    }

    public function test_every_supported_string_rule_is_recognised(): void
    {
        $rules = [
            'a' => 'email',
            'b' => 'min:2|max:4|length:4',
            'c' => 'string',
            'd' => 'int|integer|type:int|type:integer',
            'e' => 'bool|boolean|type:bool|type:boolean',
            'f' => 'array|type:array',
            'g' => 'numeric',
            'h' => 'url',
            'i' => 'uuid',
            'j' => 'json',
            'k' => 'regex:/^[A-Z]+$/',
            'l' => 'in:foo,bar',
            'm' => 'type:string',
        ];

        $input = [
            'a' => 'john@example.com',
            'b' => 'abcd',
            'c' => 'text',
            'd' => 5,
            'e' => true,
            'f' => [1, 2],
            'g' => '12.5',
            'h' => 'https://example.com',
            'i' => '11111111-1111-4111-8111-111111111111',
            'j' => '{"x":1}',
            'k' => 'ABC',
            'l' => 'foo',
            'm' => 'text',
        ];

        $this->assertSame($input, $this->validator->validate($input, $rules));
    }

    public function test_unknown_and_non_string_rules_are_ignored(): void
    {
        $rules = ['name' => 'required|some_unknown_rule|123'];

        $this->assertSame(['name' => 'John'], $this->validator->validate(['name' => 'John'], $rules));
    }
}
