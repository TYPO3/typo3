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
use TYPO3\CMS\Backend\Dto\Tree\Status\StatusInformation;
use TYPO3\CMS\Core\Attribute\AsEventListener;
use TYPO3\CMS\Core\Authentication\BackendUserAuthentication;
use TYPO3\CMS\Core\Database\Connection;
use TYPO3\CMS\Core\Database\ConnectionPool;
use TYPO3\CMS\Core\Localization\LanguageService;
use TYPO3\CMS\Core\Schema\TcaSchemaFactory;
use TYPO3\CMS\Core\Type\ContextualFeedbackSeverity;
use TYPO3\CMS\Core\Versioning\VersionState;

/**
 * Page tree controller listener to add StatusInformation badges for pages having workspace changes.
 *
 * @internal
 */
final readonly class PageTreeItemsHighlighter
{
    public function __construct(
        private ConnectionPool $connectionPool,
        private TcaSchemaFactory $tcaSchemaFactory,
    ) {}

    #[AsEventListener('typo3-workspaces/page-tree-items-highlighter')]
    public function __invoke(AfterPageTreeItemsPreparedEvent $event): void
    {
        $items = $event->getItems();
        $workspaceUid = $this->getBackendUser()->workspace;
        if ($workspaceUid <= 0 || $items === []) {
            return;
        }
        $pageIdsWithVersionedRecords = $this->getPageUidsWithVersionedRecords($workspaceUid);
        foreach ($items as &$item) {
            $page = $item['_page'] ?? [];
            if (!is_array($page) || !isset($page['uid']) || (int)$page['uid'] <= 0) {
                continue;
            }
            if ((int)($page['t3ver_wsid'] ?? 0) === $workspaceUid) {
                // The page is an overlay in this workspace. Add info about this.
                $label = $this->getLanguageService()->sL('LLL:EXT:workspaces/Resources/Private/Language/locallang.xlf:status.has_changes');
                if (VersionState::tryFrom($page['t3ver_state'] ?? 0) === VersionState::NEW_PLACEHOLDER) {
                    $label = $this->getLanguageService()->sL('LLL:EXT:workspaces/Resources/Private/Language/locallang.xlf:status.is_new');
                }
                $item['statusInformation'][] = new StatusInformation(
                    label: $label,
                    severity: ContextualFeedbackSeverity::WARNING,
                );
            } elseif (isset($pageIdsWithVersionedRecords[$page['uid']])) {
                // The page contains workspace records in current BE user selected workspace. Add info about this.
                $item['statusInformation'][] = new StatusInformation(
                    label: $this->getLanguageService()->sL('LLL:EXT:workspaces/Resources/Private/Language/locallang.xlf:status.contains_changes'),
                    severity: ContextualFeedbackSeverity::WARNING,
                );
            }
        }
        unset($item);
        $event->setItems($items);
    }

    /**
     * Iterate all workspace aware TCA tables to create a list of page uids
     * that have versioned records on it.
     */
    private function getPageUidsWithVersionedRecords(int $workspaceUid): array
    {
        $pageUidsWithVersionedRecords = [];
        foreach ($this->tcaSchemaFactory->all() as $tableName => $schema) {
            if ($tableName === 'pages' || !$schema->isWorkspaceAware()) {
                continue;
            }
            $queryBuilder = $this->connectionPool->getQueryBuilderForTable($tableName);
            // Not adding DeletedRestriction since workspace records are not soft-delete aware.
            $queryBuilder->getRestrictions()->removeAll();
            // Fetch distinct pids of all versioned records within given workspace of given table
            $result = $queryBuilder
                ->select('pid')
                ->from($tableName)
                ->where($queryBuilder->expr()->eq('t3ver_wsid', $queryBuilder->createNamedParameter($workspaceUid, Connection::PARAM_INT)))
                ->groupBy('pid')
                ->executeQuery();
            while ($row = $result->fetchAssociative()) {
                $pageUidsWithVersionedRecords[(int)$row['pid']] = true;
            }
        }
        return $pageUidsWithVersionedRecords;
    }

    protected function getLanguageService(): LanguageService
    {
        return $GLOBALS['LANG'];
    }

    protected function getBackendUser(): BackendUserAuthentication
    {
        return $GLOBALS['BE_USER'];
    }
}
