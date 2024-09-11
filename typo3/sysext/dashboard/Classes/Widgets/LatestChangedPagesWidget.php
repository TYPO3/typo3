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

namespace TYPO3\CMS\Dashboard\Widgets;

use Psr\Http\Message\ServerRequestInterface;
use TYPO3\CMS\Backend\Routing\PreviewUriBuilder;
use TYPO3\CMS\Backend\Utility\BackendUtility;
use TYPO3\CMS\Backend\View\BackendViewFactory;
use TYPO3\CMS\Core\Authentication\BackendUserAuthentication;
use TYPO3\CMS\Core\Context\Context;
use TYPO3\CMS\Core\Database\Connection;
use TYPO3\CMS\Core\Database\ConnectionPool;
use TYPO3\CMS\Core\Database\Query\QueryBuilder;
use TYPO3\CMS\Core\Exception\SiteNotFoundException;
use TYPO3\CMS\Core\Site\SiteFinder;
use TYPO3\CMS\Core\Type\Bitmask\Permission;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Core\Utility\RootlineUtility;

/**
 * This widget will show a list of pages where latest changes in pages and tt_content
 * where made. The sys_history is used to get the latest changes.
 *
 * The list contains:
 * - datetime of change
 * - user (avatar, icon, name and realName)
 * - page title and rootline
 * - controls (show history, view webpage, edit page content, edit page properties)
 *
 * The following options are available during registration:
 * - limit          int     number of pages to show in list
 * - historyLimit   int     number of sys_history records to be fetched in order
 *                          to find limit number of pages. Increase this value
 *                          if number of pages in list is not achieved.
 */
class LatestChangedPagesWidget implements WidgetInterface, RequestAwareWidgetInterface
{
    /**
     * @var array{limit: int, historyLimit: int}
     */
    private readonly array $options;
    private ServerRequestInterface $request;

    public function __construct(
        private readonly BackendViewFactory $backendViewFactory,
        private readonly ConnectionPool $connectionPool,
        private readonly WidgetConfigurationInterface $configuration,
        private readonly SiteFinder $siteFinder,
        array $options = [],
    ) {
        $this->options = array_merge([
            'limit' => 10,
            'historyLimit' => 1000,
        ], $options);
    }

    public function renderWidgetContent(): string
    {
        $sysHistoryEntries = $this->getSysHistoryEntries($this->options['historyLimit']);
        $latestPages = $this->getLatestPagesFromSysHistory($sysHistoryEntries, $this->options['limit']);
        $latestPages = $this->enrichPageInformation($latestPages);

        $view = $this->backendViewFactory->create($this->request);
        $view->assignMultiple([
            'latestPages' => $latestPages,
            'configuration' => $this->configuration,
            'dateFormat' => $GLOBALS['TYPO3_CONF_VARS']['SYS']['ddmmyy'] . ' ' . $GLOBALS['TYPO3_CONF_VARS']['SYS']['hhmm'],
        ]);

        return $view->render('Widget/LatestChangedPagesWidget');
    }

    private function getSysHistoryEntries(int $limit): array
    {
        $queryBuilder = $this->getQueryBuilderSysHistory();
        $workspaceId = GeneralUtility::makeInstance(Context::class)->getPropertyFromAspect('workspace', 'id');
        return $queryBuilder
            ->select('tablename', 'recuid', 'tstamp', 'userid')
            ->from('sys_history')
            ->where(
                $queryBuilder->expr()->in(
                    'tablename',
                    [
                        $queryBuilder->createNamedParameter('pages'),
                        $queryBuilder->createNamedParameter('tt_content'),
                    ]
                ),
                $queryBuilder->expr()->eq('workspace', $workspaceId),
            )
            ->addOrderBy('tstamp', 'desc')
            ->setMaxResults($limit)
            ->executeQuery()
            ->fetchAllAssociative();
    }

    private function getLatestPagesFromSysHistory(array $history, int $limit): array
    {
        $latestPages = [];
        foreach ($history as $historyEntry) {
            $pageId = $historyEntry['recuid'];
            if ($historyEntry['tablename'] === 'tt_content') {
                $pageId = $this->getPageOfContentElement($historyEntry['recuid']);
            }
            if (!$pageId || isset($latestPages[$pageId])) {
                continue;
            }

            $pageRecord = BackendUtility::readPageAccess($pageId, $this->getBackendUser()->getPagePermsClause(Permission::PAGE_SHOW));
            if ($pageRecord === false || $pageRecord === []) {
                // Backend user has no access to show page information. Dismiss this page.
                continue;
            }

            $latestPages[$pageId]['history'] = $historyEntry;
            $pageRecord['_uid'] = $pageRecord['sys_language_uid'] > 0 ? $pageRecord['l10n_parent'] : $pageRecord['uid'];
            $latestPages[$pageId]['pageRecord'] = $pageRecord;
            try {
                $latestPages[$pageId]['siteLanguage'] = $this->siteFinder->getSiteByPageId($pageRecord['_uid'])->getLanguageById($pageRecord['sys_language_uid']);
            } catch (SiteNotFoundException $exception) {
                $latestPages[$pageId]['siteLanguage'] = null;
            }
            // Override tstamp of pageRecord with tstamp from history record if newer
            $latestPages[$pageId]['pageRecord']['tstamp'] = max($historyEntry['tstamp'], $latestPages[$pageId]['pageRecord']['tstamp']);

            if (count($latestPages) >= $limit) {
                break;
            }
        }
        return $latestPages;
    }

    private function enrichPageInformation(array $latestPages): array
    {
        $userNames = BackendUtility::getUserNames('username,realName,uid');

        foreach ($latestPages as $pageId => &$page) {
            $page['rootline'] = $this->getRootLine($pageId);

            $uriPageId = $page['pageRecord']['sys_language_uid'] > 0 ? $page['pageRecord']['l10n_parent'] : $page['pageRecord']['uid'];
            $page['viewLink'] = (string)PreviewUriBuilder::create($uriPageId)
                ->withRootLine(BackendUtility::BEgetRootLine($uriPageId))
                ->withLanguage((int)$page['pageRecord']['sys_language_uid'])
                ->buildUri();
            $page['userName'] = $userNames[$page['history']['userid']]['username'] ?? '';
            $page['realName'] = $userNames[$page['history']['userid']]['realName'] ?? '';
        }

        return $latestPages;
    }

    private function getPageOfContentElement(int $uid): int
    {
        $queryBuilder = $this->getQueryBuilderForContentElements();
        $contentRecord = $queryBuilder
            ->select('pid', 'sys_language_uid')
            ->from('tt_content')
            ->where($queryBuilder->expr()->eq('uid', $queryBuilder->createNamedParameter($uid, Connection::PARAM_INT)))
            ->setMaxResults(1)
            ->executeQuery()
            ->fetchAssociative();

        $queryBuilder = $this->getQueryBuilderForPages();
        if ($contentRecord !== false && (int)$contentRecord['sys_language_uid'] > 0) {
            return $queryBuilder
                ->select('uid')
                ->from('pages')
                ->where(
                    $queryBuilder->expr()->eq('l10n_parent', $queryBuilder->createNamedParameter($contentRecord['pid'], Connection::PARAM_INT)),
                    $queryBuilder->expr()->eq('sys_language_uid', $queryBuilder->createNamedParameter($contentRecord['sys_language_uid'], Connection::PARAM_INT))
                )
                ->setMaxResults(1)
                ->executeQuery()
                ->fetchAssociative()['uid'];
        }

        return $queryBuilder
            ->select('uid')
            ->from('pages')
            ->where(
                $queryBuilder->expr()->eq('uid', $queryBuilder->createNamedParameter($contentRecord['pid'], Connection::PARAM_INT))
            )
            ->setMaxResults(1)
            ->executeQuery()
            ->fetchAssociative()['uid'];
    }

    private function getRootLine(int $pageId): string
    {
        $rootLine = GeneralUtility::makeInstance(RootlineUtility::class, $pageId)->get();
        return implode(' / ', array_slice(
            array_map(
                static fn(array $page): string => $page['title'],
                array_reverse($rootLine)
            ),
            0,
            -1
        ));
    }

    private function getQueryBuilderSysHistory(): QueryBuilder
    {
        $queryBuilder = $this->connectionPool->getConnectionForTable('sys_history')->createQueryBuilder();
        return $queryBuilder;
    }

    private function getQueryBuilderForContentElements(): QueryBuilder
    {
        $queryBuilder = $this->connectionPool->getConnectionForTable('tt_content')->createQueryBuilder();
        $queryBuilder->getRestrictions()->removeAll();
        return $queryBuilder;
    }

    private function getQueryBuilderForPages(): QueryBuilder
    {
        $queryBuilder = $this->connectionPool->getConnectionForTable('pages')->createQueryBuilder();
        $queryBuilder->getRestrictions()->removeAll();
        return $queryBuilder;
    }

    public function getOptions(): array
    {
        return $this->options;
    }

    public function setRequest(ServerRequestInterface $request): void
    {
        $this->request = $request;
    }

    private function getBackendUser(): BackendUserAuthentication
    {
        return $GLOBALS['BE_USER'];
    }
}
