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

namespace TYPO3Tests\PlaywrightHelper\Middleware;

use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\MiddlewareInterface;
use Psr\Http\Server\RequestHandlerInterface;
use TYPO3\CMS\Core\Http\JsonResponse;
use TYPO3\CMS\Install\Service\EnableFileService;

/**
 * Middleware to handle Playwright helper requests early in the backend stack.
 * Endpoints live under /typo3/playwright-helper/ and run before
 * typo3/cms-backend/locked-backend, so they work without an authenticated
 * backend session.
 *
 * !!! SECURITY WARNING !!!
 *
 * This middleware exposes UNAUTHENTICATED HTTP endpoints that can toggle the
 * install tool enable file. It exists ONLY for the playwright e2e test setup
 * and MUST NEVER be loaded in any installation that is reachable from outside
 * a CI/test sandbox. Doing so would allow anyone to enable the install tool
 * remotely and is a critical security hole.
 *
 * The package shipping this middleware (typo3tests/playwright-helper) lives
 * under Build/tests/packages/ and is composer-required only by
 * Build/Scripts/setupAcceptanceComposer.sh. It must not be added as a
 * dependency anywhere else, and it must not be loaded as an extension in any
 * non-test TYPO3 installation.
 *
 * @internal Testing only.
 */
final readonly class PlaywrightHelperMiddleware implements MiddlewareInterface
{
    private const PATH_PREFIX = '/typo3/playwright-helper/';

    public function process(ServerRequestInterface $request, RequestHandlerInterface $handler): ResponseInterface
    {
        $path = $request->getUri()->getPath();
        if (!str_starts_with($path, self::PATH_PREFIX)) {
            return $handler->handle($request);
        }
        switch (substr($path, strlen(self::PATH_PREFIX))) {
            case 'install-tool/enable':
                EnableFileService::createInstallToolEnableFile();
                break;
            case 'install-tool/disable':
                EnableFileService::removeInstallToolEnableFile();
                break;
            case 'install-tool/status':
                break;
            default:
                return $handler->handle($request);
        }
        return new JsonResponse([
            'enabled' => EnableFileService::installToolEnableFileExists(),
        ]);
    }
}
