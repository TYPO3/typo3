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

namespace TYPO3\CMS\Backend\Controller;

use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use TYPO3\CMS\Backend\Attribute\AsController;
use TYPO3\CMS\Backend\Utility\BackendUtility;
use TYPO3\CMS\Core\Authentication\BackendUserAuthentication;
use TYPO3\CMS\Core\Cache\Flush\CacheFlushDemand;
use TYPO3\CMS\Core\Cache\Flush\CacheFlusher;
use TYPO3\CMS\Core\Cache\Flush\CacheFlushingContext;
use TYPO3\CMS\Core\Cache\Flush\CacheFlushLogger;
use TYPO3\CMS\Core\Http\JsonResponse;
use TYPO3\CMS\Core\Localization\LanguageService;
use TYPO3\CMS\Core\Type\Bitmask\Permission;

/**
 * Handles clearing caches from the clear cache toolbar item and the records module.
 *
 * @internal This class is a specific Backend controller implementation and is not part of the TYPO3's Core API.
 */
#[AsController]
readonly class ClearCacheController
{
    public function __construct(
        private CacheFlusher $cacheFlusher,
        private CacheFlushLogger $cacheFlushLogger,
    ) {}

    public function flushCacheGroupPagesAction(ServerRequestInterface $request): ResponseInterface
    {
        $this->flush(CacheFlushDemand::forCommand('pages'));

        $languageService = $this->getLanguageService();
        return new JsonResponse([
            'success' => true,
            'title' => $languageService->sL('core.cache:notification.group.pages.success.title'),
            'message' => $languageService->sL('core.cache:notification.group.pages.success.message'),
        ]);
    }

    public function flushCacheGroupAllAction(ServerRequestInterface $request): ResponseInterface
    {
        $this->flush(CacheFlushDemand::forCommand('all'));

        $languageService = $this->getLanguageService();
        return new JsonResponse([
            'success' => true,
            'title' => $languageService->sL('core.cache:notification.group.all.success.title'),
            'message' => $languageService->sL('core.cache:notification.group.all.success.message'),
        ]);
    }

    public function flushCachePageAction(ServerRequestInterface $request): ResponseInterface
    {
        $parsedBody = $request->getParsedBody();
        $pageUid = (int)($parsedBody['id'] ?? 0);
        $languageService = $this->getLanguageService();
        $permissionClause = $this->getBackendUser()->getPagePermsClause(Permission::PAGE_SHOW);
        $pageRow = BackendUtility::readPageAccess($pageUid, $permissionClause);
        if ($pageUid !== 0 && $this->getBackendUser()->doesUserHaveAccess($pageRow, Permission::PAGE_SHOW)) {
            $this->flush(CacheFlushDemand::forCommand($pageUid));
            return new JsonResponse([
                'success' => true,
                'title' => $languageService->sL('core.cache:notification.page.success.title'),
                'message' => sprintf($languageService->sL('core.cache:notification.page.success.message'), BackendUtility::getRecordTitle('pages', $pageRow)),
            ]);
        }
        return new JsonResponse([
            'success' => false,
            'title' => $languageService->sL('core.cache:notification.page.error.title'),
            'message' => $languageService->sL('core.cache:notification.page.error.message'),
        ]);
    }

    private function flush(CacheFlushDemand $demand): void
    {
        $backendUser = $this->getBackendUser();
        $this->cacheFlushLogger->logCommands($backendUser, $demand->commands);
        $this->cacheFlusher->flush($demand, CacheFlushingContext::fromBackendUser($backendUser));
    }

    protected function getBackendUser(): BackendUserAuthentication
    {
        return $GLOBALS['BE_USER'];
    }

    protected function getLanguageService(): LanguageService
    {
        return $GLOBALS['LANG'];
    }
}
