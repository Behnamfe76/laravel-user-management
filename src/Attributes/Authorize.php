<?php

namespace Fereydooni\LaravelUserManagement\Attributes;

use Attribute;

#[Attribute(Attribute::TARGET_METHOD)]
class Authorize
{
    public function __construct(
        public ?string $permission = null,
        public ?string $role = null,
        public ?string $userType = 'user'
    ) {
        if ($this->permission === null && $this->role === null) {
            throw new \InvalidArgumentException('Either permission or role must be specified');
        }
    }
} 