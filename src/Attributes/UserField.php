<?php

declare(strict_types=1);

namespace Fereydooni\LaravelUserManagement\Attributes;

use Attribute;

#[Attribute(Attribute::TARGET_CLASS | Attribute::IS_REPEATABLE)]
class UserField
{
    /**
     * Create a new UserField attribute instance.
     *
     * @param string $name The name of the user field
     * @param string $type The data type of the field (string, integer, boolean, etc.)
     * @param bool $required Whether the field is required
     * @param bool $unique Whether the field value must be unique
     */
    public function __construct(
        public readonly string $name,
        public readonly string $type = 'string',
        public readonly bool $required = false,
        public readonly bool $unique = false,
    ) {
    }
} 