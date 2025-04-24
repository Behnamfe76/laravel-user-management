<?php

declare(strict_types=1);

namespace Fereydooni\LaravelUserManagement\Exceptions;

use Exception;

class RoleNotFoundException extends Exception
{
    /**
     * RoleNotFoundException constructor.
     *
     * @param string $roleName The name of the role that was not found.
     * @param int $code The exception code (optional).
     * @param Exception|null $previous The previous exception used for exception chaining (optional).
     */
    public function __construct(string $roleName, int $code = 0, Exception $previous = null)
    {
        $message = "The role '{$roleName}' was not found.";
        parent::__construct($message, $code, $previous);
    }
}