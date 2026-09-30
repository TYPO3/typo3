<?php

/*
 * This file is part of the TYPO3 CMS project.
 *
 * It is free software; you can redistribute it and/or modify it under
 * the terms of the GNU General Public License, either version 2
 * of the License, or any later version.
 *
 * For the full copyright and license information, please read the
 * LICENSE.txt file that was distributed with this source code.
 *
 * The TYPO3 project - inspiring people to share!
 */

namespace TYPO3\CMS\Backend\Routing;

use Symfony\Component\Routing\Route as SymfonyRoute;

/**
 * Declares who may reach a backend route, via the route option "access":
 *
 * - "anonymous": reachable without backend user and without request token
 * - "authenticated" (default): requires a backend user and a valid request token
 * - "authenticated-without-token": requires a backend user, but no request token
 *
 * Routes of backend modules use the option "access" for the identifier of a module
 * access gate (e.g. "admin" or "user") and always require an authenticated backend user.
 */
enum RouteAccess: string
{
    case Anonymous = 'anonymous';
    case Authenticated = 'authenticated';
    case AuthenticatedWithoutToken = 'authenticated-without-token';

    /**
     * @internal only used by the Router to detect and migrate the legacy value
     * @deprecated since v15, will be removed in v16. The legacy access "public" only omitted
     *             the request token, and is mapped to "authenticated-without-token".
     */
    public const string LEGACY_PUBLIC = 'public';

    /**
     * Any other value fails closed and is treated as "authenticated", as the option is
     * also used with the identifiers of module access gates, such as "admin".
     */
    public static function fromRoute(Route|SymfonyRoute $route): self
    {
        if ($route->getOption('module') !== null) {
            return self::Authenticated;
        }
        $access = $route->getOption('access');
        if ($access === self::LEGACY_PUBLIC) {
            return self::AuthenticatedWithoutToken;
        }
        return (is_string($access) ? self::tryFrom($access) : null) ?? self::Authenticated;
    }

    public function requiresAuthentication(): bool
    {
        return $this !== self::Anonymous;
    }

    public function requiresRequestToken(): bool
    {
        return $this === self::Authenticated;
    }
}
