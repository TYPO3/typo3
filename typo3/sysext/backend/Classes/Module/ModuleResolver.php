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

namespace TYPO3\CMS\Backend\Module;

use Psr\Http\Message\ServerRequestInterface;
use TYPO3\CMS\Core\Authentication\BackendUserAuthentication;

/**
 * Resolves the current module from a request.
 *
 * This service provides a centralized way to determine which backend module
 * is currently active based on various request attributes and parameters.
 *
 * @internal This class is not part of TYPO3's public API.
 */
final readonly class ModuleResolver
{
    public function __construct(
        private ModuleProvider $moduleProvider,
    ) {}

    /**
     * Resolves the current module from a request.
     *
     * Checks multiple sources in order:
     * 1. Routing attribute (from route configuration)
     * 2. Request attribute 'module'
     * 3. Query parameter 'module'
     *
     * @param ServerRequestInterface|null $request The current request
     * @return ModuleInterface|null The resolved module or null if not found
     */
    public function resolveModule(?ServerRequestInterface $request): ?ModuleInterface
    {
        if ($request === null) {
            return null;
        }

        // Check routing attribute first
        $routeResult = $request->getAttribute('routing');
        $currentModule = $routeResult?->getRoute()?->getOption('module');
        if ($currentModule !== null) {
            return $currentModule;
        }

        // Check request attribute
        $currentModule = $request->getAttribute('module');
        if ($currentModule !== null) {
            return $currentModule;
        }

        // Check query parameter
        if (isset($request->getQueryParams()['module'])) {
            $module = (string)$request->getQueryParams()['module'];
            return $this->moduleProvider->getModule($module, $this->getBackendUser());
        }

        return null;
    }

    private function getBackendUser(): BackendUserAuthentication
    {
        return $GLOBALS['BE_USER'];
    }
}
