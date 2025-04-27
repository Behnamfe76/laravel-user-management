<?php

namespace Fereydooni\LaravelUserManagement\Middleware;

use Closure;
use Illuminate\Http\Request;
use Fereydooni\LaravelUserManagement\Attributes\Authorize;
use Symfony\Component\HttpFoundation\Response;

class AuthorizeAttribute
{
    public function handle(Request $request, Closure $next): Response
    {
        if (!config('user-management.attribute_based_authorization')) {
            return $next($request);
        }

        $route = $request->route();
        $controller = $route->getController();
        $method = $route->getActionMethod();

        // Check class-level attributes
        $classReflection = new \ReflectionClass($controller);
        $classAttributes = $classReflection->getAttributes(Authorize::class);
        
        // Check method-level attributes
        $methodReflection = new \ReflectionMethod($controller, $method);
        $methodAttributes = $methodReflection->getAttributes(Authorize::class);

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
    }
} 