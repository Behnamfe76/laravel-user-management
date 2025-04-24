<?php

declare(strict_types=1);

namespace Fereydooni\LaravelUserManagement\Attributes;

use Attribute;

#[Attribute(Attribute::TARGET_CLASS | Attribute::IS_REPEATABLE)]
class UserRole
{
    /**
     * Create a new UserRole attribute instance.
     *
     * @param string $name The name of the role
     * @param array<string> $permissions Array of permission names
     */
    public function __construct(
        public readonly string $name,
        public readonly array $permissions = [],
    ) {
    }
} 