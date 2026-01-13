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

namespace TYPO3\CMS\Redirects\Security;

use Symfony\Component\DependencyInjection\Attribute\Autowire;
use TYPO3\CMS\Backend\Utility\BackendUtility;
use TYPO3\CMS\Core\Authentication\BackendUserAuthentication;
use TYPO3\CMS\Core\Cache\Frontend\FrontendInterface;
use TYPO3\CMS\Core\LinkHandling\Exception\UnknownLinkHandlerException;
use TYPO3\CMS\Core\LinkHandling\Exception\UnknownUrnException;
use TYPO3\CMS\Core\LinkHandling\LinkService;
use TYPO3\CMS\Core\Resource\FileInterface;
use TYPO3\CMS\Core\Type\Bitmask\Permission;
use TYPO3\CMS\Redirects\Data\SourceHostProvider;

/**
 * Security guard to validate access to sys_redirect records for the current backend user.
 *
 * @internal
 */
final class RedirectPermissionGuard
{
    /**
     * @var list<non-empty-string>|null
     */
    private ?array $allowedHosts = null;

    public function __construct(
        private readonly LinkService $linkService,
        private readonly SourceHostProvider $sourceHostProvider,
        #[Autowire(service: 'cache.runtime')]
        private readonly FrontendInterface $cache,
    ) {}

    public function isAllowedRedirect(array $redirect): bool
    {
        if ($this->getBackendUser()->isAdmin()) {
            return true;
        }

        return $this->isAllowedSourceHost($redirect['source_host'] ?? '')
            && $this->isAllowedTarget($redirect['target'] ?? '');
    }

    public function getAllowedHosts(): array
    {
        $this->allowedHosts ??= $this->sourceHostProvider->getHosts(true);

        return $this->allowedHosts;
    }

    private function isAllowedSourceHost(string $host): bool
    {
        return in_array($host, $this->getAllowedHosts(), true);
    }

    private function isAllowedTarget(string $target): bool
    {
        $cacheIdentifier = 'RedirectPermissionGuard-isAllowedTarget-' . md5($target);

        if ($this->cache->has($cacheIdentifier)) {
            return $this->cache->get($cacheIdentifier);
        }

        $result = true;

        if (str_starts_with($target, 't3://')) {
            try {
                $resolvedLink = $this->linkService->resolveByStringRepresentation($target);

                if ((int)($resolvedLink['pageuid'] ?? 0) > 0) {
                    $result = $this->canAccessPage((int)$resolvedLink['pageuid']);
                } elseif (($resolvedLink['file'] ?? null) instanceof FileInterface) {
                    $result = $this->canAccessFile($resolvedLink['file']);
                }
            } catch (UnknownUrnException|UnknownLinkHandlerException) {
            }
        }

        $this->cache->set($cacheIdentifier, $result);

        return $result;
    }

    private function canAccessPage(int $pageUid): bool
    {
        $page = BackendUtility::getRecord('pages', $pageUid, '*', '', false);

        // If the page does no longer exist, we allow access to the redirect
        if ($page === null) {
            return true;
        }

        return $this->getBackendUser()->doesUserHaveAccess($page, Permission::PAGE_SHOW);
    }

    private function canAccessFile(FileInterface $file): bool
    {
        return $file->getStorage()->checkFileActionPermission('read', $file);
    }

    private function getBackendUser(): BackendUserAuthentication
    {
        return $GLOBALS['BE_USER'];
    }
}
