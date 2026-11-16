<?php

declare(strict_types=1);

namespace App\Middleware;

use App\Services\AuthService;
use FlowForge\Core\HttpException;
use FlowForge\Core\Request;
use FlowForge\Core\Response;
use FlowForge\Middleware\MiddlewareInterface;

/**
 * Route middleware "role:<name>[,<name>]": restricts a route to one or more roles,
 * e.g. ->middleware('role:reviewer'). Must run after "auth".
 */
final class RequireRole implements MiddlewareInterface
{
    /** @var string[] */
    private array $roles;

    public function __construct(string ...$roles)
    {
        $this->roles = $roles;
    }

    public function handle(Request $request, callable $next): Response
    {
        $role = (new AuthService())->role();

        if ($role === null || !in_array($role, $this->roles, true)) {
            throw new HttpException(403, sprintf(
                'Access denied: this page is restricted to %s accounts.',
                implode(' / ', $this->roles)
            ));
        }

        return $next($request);
    }
}
