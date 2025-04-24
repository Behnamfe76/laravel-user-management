<?php

declare(strict_types=1);

namespace Fereydooni\LaravelUserManagement\Console\Commands;

use Fereydooni\LaravelUserManagement\Attributes\UserField;
use Fereydooni\LaravelUserManagement\Attributes\UserRole;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Str;
use ReflectionClass;
use Symfony\Component\Finder\Finder;

class ScanAttributesCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'user-management:scan-attributes 
                            {--model= : The model to scan for attributes (default is from config)}
                            {--path= : The path to scan for models}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Scan models for UserField and UserRole attributes';

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        $this->info('Scanning for user attributes...');

        if ($model = $this->option('model')) {
            // Scan a specific model
            $this->scanModel($model);
        } elseif ($path = $this->option('path')) {
            // Scan models in a specific path
            $this->scanPath($path);
        } else {
            // Scan the default user model from config
            $defaultModel = config('user-management.user_model');
            $this->scanModel($defaultModel);
        }

        $this->info('Scan completed successfully!');
        return Command::SUCCESS;
    }

    /**
     * Scan a specific model for attributes.
     *
     * @param string $modelClass
     * @return void
     */
    protected function scanModel(string $modelClass): void
    {
        $this->info("Scanning model: {$modelClass}");

        try {
            $reflectionClass = new ReflectionClass($modelClass);
            
            // Process UserField attributes
            $userFields = $this->extractUserFields($reflectionClass);
            
            // Process UserRole attributes
            $userRoles = $this->extractUserRoles($reflectionClass);

            if (empty($userFields) && empty($userRoles)) {
                $this->warn("No UserField or UserRole attributes found in {$modelClass}.");
                return;
            }

            // Display fields
            if (!empty($userFields)) {
                $this->info("Found " . count($userFields) . " user fields:");
                foreach ($userFields as $name => $field) {
                    $this->line(" - {$name} (type: {$field['type']}, required: " . 
                        ($field['required'] ? 'yes' : 'no') . ", unique: " . 
                        ($field['unique'] ? 'yes' : 'no') . ")");
                }
            }

            // Display roles
            if (!empty($userRoles)) {
                $this->info("Found " . count($userRoles) . " user roles:");
                foreach ($userRoles as $name => $role) {
                    $permissions = implode(', ', $role['permissions']);
                    $this->line(" - {$name} (permissions: {$permissions})");
                }
            }
        } catch (\ReflectionException $e) {
            $this->error("Error scanning model {$modelClass}: " . $e->getMessage());
        }
    }

    /**
     * Scan all models in a directory for attributes.
     *
     * @param string $path
     * @return void
     */
    protected function scanPath(string $path): void
    {
        $this->info("Scanning models in path: {$path}");

        if (!File::isDirectory($path)) {
            $this->error("Directory not found: {$path}");
            return;
        }

        $finder = new Finder();
        $finder->files()->in($path)->name('*.php');

        if (!$finder->hasResults()) {
            $this->warn("No PHP files found in {$path}.");
            return;
        }

        $foundAttributes = false;

        foreach ($finder as $file) {
            $className = $this->getClassNameFromFile($file->getRealPath());
            
            if ($className && class_exists($className)) {
                try {
                    $reflection = new ReflectionClass($className);
                    
                    // Only process classes that have the attributes we're looking for
                    $fieldAttributes = $reflection->getAttributes(UserField::class);
                    $roleAttributes = $reflection->getAttributes(UserRole::class);
                    
                    if (!empty($fieldAttributes) || !empty($roleAttributes)) {
                        $this->scanModel($className);
                        $foundAttributes = true;
                    }
                } catch (\Throwable $e) {
                    $this->warn("Skipping {$className}: " . $e->getMessage());
                }
            }
        }

        if (!$foundAttributes) {
            $this->warn("No models with UserField or UserRole attributes found in {$path}.");
        }
    }

    /**
     * Extract user fields from a class.
     *
     * @param ReflectionClass $reflectionClass
     * @return array<string,array>
     */
    protected function extractUserFields(ReflectionClass $reflectionClass): array
    {
        $fields = [];
        $attributes = $reflectionClass->getAttributes(UserField::class);

        foreach ($attributes as $attribute) {
            $field = $attribute->newInstance();
            $fields[$field->name] = [
                'type' => $field->type,
                'required' => $field->required,
                'unique' => $field->unique,
            ];
        }

        return $fields;
    }

    /**
     * Extract user roles from a class.
     *
     * @param ReflectionClass $reflectionClass
     * @return array<string,array>
     */
    protected function extractUserRoles(ReflectionClass $reflectionClass): array
    {
        $roles = [];
        $attributes = $reflectionClass->getAttributes(UserRole::class);

        foreach ($attributes as $attribute) {
            $role = $attribute->newInstance();
            $roles[$role->name] = [
                'permissions' => $role->permissions,
            ];
        }

        return $roles;
    }

    /**
     * Get the class name from a file path.
     *
     * @param string $path
     * @return string|null
     */
    protected function getClassNameFromFile(string $path): ?string
    {
        $contents = file_get_contents($path);
        if (!$contents) {
            return null;
        }

        // Extract namespace
        $namespace = null;
        if (preg_match('/namespace\s+([^;]+);/', $contents, $matches)) {
            $namespace = $matches[1];
        }

        // Extract class name
        if (preg_match('/class\s+([a-zA-Z0-9_]+)/', $contents, $matches)) {
            $className = $matches[1];
            return $namespace ? $namespace . '\\' . $className : $className;
        }

        return null;
    }
} 