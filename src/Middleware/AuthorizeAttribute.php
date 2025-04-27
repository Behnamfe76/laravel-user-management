<?php

namespace Fereydooni\LaravelUserManagement\Middleware;

use Closure;
use Illuminate\Http\Request;
use Fereydooni\LaravelUserManagement\Attributes\Authorize;
use Symfony\Component\HttpFoundation\Response;
use ReflectionException;

class AuthorizeAttribute
{
    public function handle(Request $request, Closure $next): Response
    {
        if (!config('user-management.attribute_based_authorization')) {
            return $next($request);
        }

        try {
            $route = $request->route();
            
            // Skip if no route is found
            if (!$route) {
                return $next($request);
            }

            $controller = $route->getController();
            $method = $route->getActionMethod();

            // Skip if controller or method is not available
            if (!$controller || !$method) {
                return $next($request);
            }

            // Check class-level attributes
            try {
                $classReflection = new \ReflectionClass($controller);
                $classAttributes = $classReflection->getAttributes(Authorize::class);
            } catch (ReflectionException $e) {
                // Log the error but continue with method check
                \Illuminate\Support\Facades\Log::error('Failed to reflect controller class: ' . $e->getMessage());
                $classAttributes = [];
                abort(500, 'Internal Server Error');
            }
            
            // Check method-level attributes
            try {
                $methodReflection = new \ReflectionMethod($controller, $method);
                $methodAttributes = $methodReflection->getAttributes(Authorize::class);
            } catch (ReflectionException $e) {
                // Log the error but continue with class attributes
                \Illuminate\Support\Facades\Log::error('Failed to reflect method: ' . $e->getMessage());
                $methodAttributes = [];
                abort(500, 'Internal Server Error');
            }

            // Combine all attributes (class-level first, then method-level)
            $attributes = array_merge($classAttributes, $methodAttributes);

            // If no attributes are found, allow access
            if (empty($attributes)) {
                return $next($request);
            }

            foreach ($attributes as $attribute) {
                $instance = $attribute->newInstance();
                
                // Check if route is blocked
                if ($instance->routeType === 'block') {
                    abort(403, 'This route is currently blocked.');
                }
                
                // Check route type
                if ($instance->routeType !== 'all') {
                    $isApi = $request->expectsJson() || str_starts_with($request->path(), 'api/');
                    $currentRouteType = $isApi ? 'api' : 'web';
                    
                    if ($instance->routeType !== $currentRouteType) {
                        abort(403, 'This route is not accessible from ' . $currentRouteType . ' context.');
                    }
                }
                
                // Skip permission check if permission is 'all'
                if ($instance->permission !== 'all' && !$request->user()->hasPermissionThroughAttribute(
                    $instance->permission,
                    null,
                    $instance->userType
                )) {
                    abort(403, 'Unauthorized action.');
                }

                // Skip role check if role is 'all'
                if ($instance->role !== 'all' && !$request->user()->hasPermissionThroughAttribute(
                    null,
                    $instance->role,
                    $instance->userType
                )) {
                    abort(403, 'Unauthorized action.');
                }
            }

            return $next($request);
        } catch (\Exception $e) {
            // Log the error and allow the request to continue
            \Illuminate\Support\Facades\Log::error('Authorization middleware error: ' . $e->getMessage());
            $statusCode = $e instanceof \Illuminate\Auth\AuthenticationException ? 401 : 
                         ($e instanceof \Illuminate\Auth\Access\AuthorizationException ? 403 : 500);
            abort($statusCode, $e->getMessage());
        }
    }
} 