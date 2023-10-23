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

namespace TYPO3\CMS\Workspaces\EventListener;

use TYPO3\CMS\Backend\Controller\Event\AfterPageTreeItemsPreparedEvent;
use TYPO3\CMS\Core\Authentication\BackendUserAuthentication;
use TYPO3\CMS\Core\Versioning\VersionState;
use TYPO3\CMS\Workspaces\Service\WorkspaceService;

/**
 * Listener to add classes to page tree items, which contain workspace versions, to highlight them
 */
final class PageTreeItemsHighlighter
{
    public function __construct(
        private readonly WorkspaceService $workspaceService
    ) {}

    public function __invoke(AfterPageTreeItemsPreparedEvent $event): void
    {
        $items = $event->getItems();
        $workspaceId = $this->getBackendUser()->workspace;

        if ($workspaceId <= 0 || $items === []) {
            return;
        }

        foreach ($items as &$item) {
            $page = $item['_page'] ?? [];
            if (!is_array($page) || $page === []) {
                continue;
            }

            if ((int)($page['t3ver_wsid'] ?? 0) === $workspaceId
                && (
                    (int)($page['t3ver_oid'] ?? 0) > 0
                    || (int)($page['t3ver_state'] ?? 0) === VersionState::NEW_PLACEHOLDER
                )
            ) {
                $item['class'] = 'ver-element ver-versions';
            } elseif (
                $this->workspaceService->hasPageRecordVersions(
                    $workspaceId,
                    (int)(($page['t3ver_oid'] ?? 0) ?: ($page['uid'] ?? 0))
                )
            ) {
                $item['class'] = 'ver-versions';
            }
        }
        unset($item);

        $event->setItems($items);
    }

    protected function getBackendUser(): BackendUserAuthentication
    {
        return $GLOBALS['BE_USER'];
    }
}
