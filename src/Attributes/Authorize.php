<?php

namespace Fereydooni\LaravelUserManagement\Attributes;

use Attribute;

#[Attribute(Attribute::TARGET_METHOD)]
class Authorize
{
    public function __construct(
        public ?string $permission = null,
        public ?string $role = null,
        public ?string $userType = 'user',
        public string $routeType = 'all'
    ) {
        if ($this->permission === null && $this->role === null) {
            throw new \InvalidArgumentException('Either permission or role must be specified');
        }

        if (!in_array($this->routeType, ['all', 'api', 'web', 'block'])) {
            throw new \InvalidArgumentException('Route type must be one of: all, api, web, block');
        }
    }
} 