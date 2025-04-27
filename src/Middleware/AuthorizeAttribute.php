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

        $reflection = new \ReflectionMethod($controller, $method);
        $attributes = $reflection->getAttributes(Authorize::class);

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
            
            if (!$request->user()->hasPermissionThroughAttribute(
                $instance->permission,
                $instance->role,
                $instance->userType
            )) {
                abort(403, 'Unauthorized action.');
            }
        }

        return $next($request);
    }
} 