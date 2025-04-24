<?php

declare(strict_types=1);

namespace Fereydooni\LaravelUserManagement\Exceptions;

use Exception;

class PermissionNotFoundException extends Exception
{
    /**
     * Create a new PermissionNotFoundException instance.
     *
     * @param string|null $permissionName
     * @param int $code
     * @param \Throwable|null $previous
     */
    public function __construct(?string $permissionName = null, int $code = 0, \Throwable $previous = null)
    {
        $message = $permissionName 
            ? "Permission '{$permissionName}' not found." 
            : "Permission not found.";
        
        parent::__construct($message, $code, $previous);
    }
}