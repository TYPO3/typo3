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

namespace TYPO3\CMS\Backend\Middleware;

use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\MiddlewareInterface;
use Psr\Http\Server\RequestHandlerInterface;
use TYPO3\CMS\Backend\Module\ModuleInterface;
use TYPO3\CMS\Backend\Module\ModuleProvider;
use TYPO3\CMS\Backend\Routing\Route;
use TYPO3\CMS\Backend\Routing\RouteRedirect;
use TYPO3\CMS\Backend\Routing\UriBuilder;
use TYPO3\CMS\Backend\Utility\BackendUtility;
use TYPO3\CMS\Core\Http\RedirectResponse;
use TYPO3\CMS\Core\Type\Bitmask\Permission;
use TYPO3\CMS\Core\Utility\MathUtility;

/**
 * Validates module access and extends the PSR-7 Request with the
 * resolved module object for the use in further components.
 *
 * @internal
 */
class BackendModuleValidator implements MiddlewareInterface
{
    public function __construct(
        protected readonly UriBuilder $uriBuilder,
        protected readonly ModuleProvider $moduleProvider
    ) {
    }

    /**
     * In case the current route targets a TYPO3 backend module and the user
     * has necessary access permissions, add the module to the request.
     *
     * @param ServerRequestInterface $request
     * @param RequestHandlerInterface $handler
     * @return ResponseInterface
     */
    public function process(ServerRequestInterface $request, RequestHandlerInterface $handler): ResponseInterface
    {
        /** @var Route $route */
        $route = $request->getAttribute('route');

        if ($route->hasOption('module')
            && ($module = $route->getOption('module')) instanceof ModuleInterface
        ) {
            $this->validateModuleAccess($request, $module);

            // This module request (which is usually opened inside the list_frame)
            // has been issued from a toplevel browser window (e.g. a link was opened in a new tab).
            // Redirect to open the module as frame inside the TYPO3 backend layout.
            // HEADS UP: This header will only be available in secure connections (https:// or .localhost TLD)
            if ($request->getHeaderLine('Sec-Fetch-Dest') === 'document') {
                return new RedirectResponse(
                    $this->uriBuilder->buildUriWithRedirect(
                        'main',
                        [],
                        RouteRedirect::createFromRoute($route, $request->getQueryParams())
                    )
                );
            }

            // Add the validated module to the current request
            $request = $request->withAttribute('module', $module);
        }

        return $handler->handle($request);
    }

    /**
     * Checks whether the current user is allowed to access the requested module. Does
     * also evaluate page access permissions, in case an "id" is given in the request.
     *
     * @throws \RuntimeException
     */
    protected function validateModuleAccess(ServerRequestInterface $request, ModuleInterface $module): void
    {
        $backendUserAuthentication = $GLOBALS['BE_USER'];
        if (!$this->moduleProvider->accessGranted($module->getIdentifier(), $backendUserAuthentication)) {
            throw new \RuntimeException('You don\'t have access to this module.', 1642450334);
        }

        $id = $request->getQueryParams()['id'] ?? $request->getParsedBody()['id'] ?? 0;
        if (MathUtility::canBeInterpretedAsInteger($id) && $id > 0) {
            $id = (int)$id;
            $permClause = $backendUserAuthentication->getPagePermsClause(Permission::PAGE_SHOW);
            // Check page access
            if (!is_array(BackendUtility::readPageAccess($id, $permClause))) {
                // Check if page has been deleted
                $deleteField = $GLOBALS['TCA']['pages']['ctrl']['delete'];
                $pageInfo = BackendUtility::getRecord('pages', $id, $deleteField, $permClause ? ' AND ' . $permClause : '', false);
                if (!($pageInfo[$deleteField] ?? false)) {
                    throw new \RuntimeException('You don\'t have access to this page', 1289917924);
                }
            }
        }
    }
}
