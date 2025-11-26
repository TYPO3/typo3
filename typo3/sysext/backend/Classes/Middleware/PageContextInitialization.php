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
use Psr\Log\LoggerAwareInterface;
use Psr\Log\LoggerAwareTrait;
use TYPO3\CMS\Backend\Context\PageContextFactory;
use TYPO3\CMS\Backend\Module\ModuleInterface;
use TYPO3\CMS\Backend\Routing\Route;
use TYPO3\CMS\Backend\Utility\BackendUtility;
use TYPO3\CMS\Core\Authentication\BackendUserAuthentication;

/**
 * Initializes PageContext for backend modules that work with pages.
 *
 * Creates a PageContext with resolved language information and stores it
 * in the request attribute 'pageContext' for use by controllers.
 *
 * This middleware only runs for modules that use the page tree navigation
 * component ('@typo3/backend/tree/page-tree-element'). This includes modules
 * like web_layout, records, and others under the 'content' parent, as well
 * as standalone modules that explicitly set the navigation component.
 *
 * The middleware:
 * - Checks if the module or any of its parents use the page tree component
 * - Extracts the page ID from request (query or body parameter 'id')
 * - Defaults to page ID 0 (NullSite/root level) if not found
 * - Creates PageContext with language resolution
 *
 * If PageContext creation fails (e.g. no site found, no page access),
 * the middleware logs a warning but continues without breaking the request,
 * allowing controllers to handle the missing context or create it manually.
 *
 * @internal
 */
class PageContextInitialization implements MiddlewareInterface, LoggerAwareInterface
{
    use LoggerAwareTrait;

    public function __construct(
        protected readonly PageContextFactory $pageContextFactory,
    ) {}

    public function process(ServerRequestInterface $request, RequestHandlerInterface $handler): ResponseInterface
    {
        if (!$this->requiresPageContext($request)) {
            return $handler->handle($request);
        }

        $backendUser = $request->getAttribute('backend.user', $GLOBALS['BE_USER']);

        // Only process if user is authenticated in the backend
        if (!($backendUser instanceof BackendUserAuthentication) || !$backendUser->user) {
            return $handler->handle($request);
        }

        $pageId = $this->determinePageId($request);
        try {
            $request = $request->withAttribute(
                'pageContext',
                $this->pageContextFactory->createFromRequest($request, $pageId, $backendUser)
            );
        } catch (\Exception $e) {
            // If PageContext creation fails, log the error and continue without it.
            // Controllers can fall back to manual creation if needed.
            $this->logger->warning(
                'Failed to create PageContext in middleware for page {page}: {error}',
                [
                    'page' => $pageId,
                    'error' => $e->getMessage(),
                    'exception' => $e,
                ]
            );
        }

        return $handler->handle($request);
    }

    private function determinePageId(ServerRequestInterface $request): int
    {
        $id = $request->getParsedBody()['id'] ?? $request->getQueryParams()['id'] ?? null;
        if ($id !== null) {
            return (int)$id;
        }

        $editStatement = $request->getParsedBody()['edit'] ?? $request->getQueryParams()['edit'] ?? null;
        if (is_array($editStatement)) {
            $table = key($editStatement);
            $uidAndAction = current($editStatement);
            $uid = (int)key($uidAndAction);
            $action = current($uidAndAction);
            if ($action === 'edit') {
                return $this->getPageIdByRecord($table, $uid);
            }
            if ($action === 'new') {
                return $this->getPageIdByRecord($table, $uid, true);
            }
        }

        $commandStatement = $request->getParsedBody()['cmd'] ?? $request->getQueryParams()['cmd'] ?? null;
        if (is_array($commandStatement)) {
            $table = key($commandStatement);
            $uidActionAndTarget = current($commandStatement);
            $uid = (int)key($uidActionAndTarget);
            $actionAndTarget = current($uidActionAndTarget);
            $action = key($actionAndTarget);
            $target = current($actionAndTarget);
            if ($action === 'delete') {
                return $this->getPageIdByRecord($table, $uid);
            }
            if ($action === 'copy' || $action === 'move') {
                return $this->getPageIdByRecord($table, (int)($target['target'] ?? $target), true);
            }
        }

        return 0;
    }

    private function getPageIdByRecord(string $table, int $id, bool $ignoreTable = false): int
    {
        $pageId = 0;
        if ($table && $id) {
            if (($ignoreTable || $table === 'pages') && $id >= 0) {
                $pageId = $id;
            } else {
                $record = BackendUtility::getRecordWSOL($table, abs($id), '*', '', false);
                $pageId = (int)($record['pid'] ?? 0);
            }
        }
        return $pageId;
    }

    /**
     * Check if the current request requires PageContext initialization.
     *
     * PageContext is required when:
     * 1. Module uses the page tree navigation component
     * 2. Route explicitly has 'requestPageContext' option set to true
     */
    private function requiresPageContext(ServerRequestInterface $request): bool
    {
        return ((($module = $request->getAttribute('module')) instanceof ModuleInterface) && $module->getNavigationComponent() === '@typo3/backend/tree/page-tree-element')
            || ((($route = $request->getAttribute('route')) instanceof Route) && $route->getOption('requestPageContext'));
    }
}
