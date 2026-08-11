<?php

declare(strict_types=1);

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

namespace TYPO3\CMS\Install\Middleware;

use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\MiddlewareInterface;
use Psr\Http\Server\RequestHandlerInterface;
use TYPO3\CMS\Core\Http\RedirectResponse;

/**
 * Ensures that the install tool entry point is actually called from the site base path,
 * e.g. from /subdir/?__typo3_install in an instance that is located in %DOCROOT%/subdir/.
 *
 * Example redirects that this middleware creates:
 *  * `/example/?__typo3_install` => `/?__typo3_install` (site-path=/)
 *  * `/subdir/example/?__typo3_install` => `/subdir/?__typo3_install` (site-path=/subdir/)
 *
 * Besides providing a canonical entry point URL, this is a prerequisite for
 * \TYPO3\CMS\Install\Http\Security\ReferrerEnforcer, which identifies same-origin referrers
 * by comparing them against the site path.
 *
 * @internal This class is only meant to be used within EXT:install and is not part of the TYPO3 Core API.
 */
final readonly class PathGuard implements MiddlewareInterface
{
    /**
     * Redirects to the site base path in case the install tool was addressed via a different path
     */
    public function process(ServerRequestInterface $request, RequestHandlerInterface $handler): ResponseInterface
    {
        $normalizedParams = $request->getAttribute('normalizedParams');
        if ($request->getUri()->getPath() !== $normalizedParams->getSitePath()) {
            return new RedirectResponse($request->getUri()->withPath($normalizedParams->getSitePath()));
        }
        return $handler->handle($request);
    }
}
