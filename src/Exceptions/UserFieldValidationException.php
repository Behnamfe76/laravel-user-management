<?php

declare(strict_types=1);

namespace Fereydooni\LaravelUserManagement\Exceptions;

use Exception;

class UserFieldValidationException extends Exception
{
    protected array $validationErrors;

    public function __construct(array $validationErrors, string $message = "User field validation failed", int $code = 0, ?Exception $previous = null)
    {
        $this->validationErrors = $validationErrors;
        parent::__construct($message, $code, $previous);
    }

    /**
     * Get the validation errors.
     *
     * @return array
     */
    public function getValidationErrors(): array
    {
        return $this->validationErrors;
    }
}