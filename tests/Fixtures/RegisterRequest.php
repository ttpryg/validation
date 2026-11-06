<?php

declare(strict_types=1);

namespace Ttpryg\Validation\Tests\Fixtures;

use Symfony\Component\Validator\Constraints as Assert;

/**
 * DTO validated through Symfony attribute mapping (no array rule stripping).
 */
final class RegisterRequest
{
    public function __construct(
        #[Assert\NotBlank]
        #[Assert\Length(min: 3)]
        public string $name,

        #[Assert\NotBlank]
        #[Assert\Email]
        public string $email,

        #[Assert\NotBlank]
        #[Assert\Length(min: 8)]
        public string $password,

        #[Assert\Positive]
        public int $age = 18
    ) {}
}
