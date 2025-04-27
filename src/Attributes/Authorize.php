<?php

namespace Fereydooni\LaravelUserManagement\Attributes;

use Attribute;

#[Attribute(Attribute::TARGET_METHOD | Attribute::TARGET_CLASS)]
class Authorize
{
    public function __construct(
        public ?string $permission = 'all',
        public ?string $role = 'all',
        public ?string $userType = 'user',
        public string $routeType = 'all'
    ) {
        if (!in_array($this->routeType, ['all', 'api', 'web', 'block'])) {
            throw new \InvalidArgumentException('Route type must be one of: all, api, web, block');
        }
    }
} 