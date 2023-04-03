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

namespace TYPO3\CMS\Seo\Widgets\Provider;

use Doctrine\DBAL\Result;
use TYPO3\CMS\Backend\Utility\BackendUtility;
use TYPO3\CMS\Core\Authentication\BackendUserAuthentication;
use TYPO3\CMS\Core\Database\ConnectionPool;
use TYPO3\CMS\Core\Database\Query\Restriction\WorkspaceRestriction;
use TYPO3\CMS\Core\Exception\SiteNotFoundException;
use TYPO3\CMS\Core\Site\SiteFinder;
use TYPO3\CMS\Core\Type\Bitmask\Permission;

/**
 * @internal
 */
final class PagesWithoutDescriptionDataProvider
{
    public function __construct(
        private readonly SiteFinder $siteFinder,
        private readonly ConnectionPool $connectionPool,
        private readonly array $excludedDoktypes,
        private readonly int $limit
    ) {
    }

    public function getPages(): array
    {
        $backendUser = $this->getBackendUser();
        $items = [];
        if (!$backendUser->check('tables_modify', 'pages')) {
            // Early return in case user is not allowed to modify pages at all
            return $items;
        }
        $rowCount = 0;
        $pagesResult = $this->getPotentialPages();
        while ($row = $pagesResult->fetchAssociative()) {
            if (!$backendUser->doesUserHaveAccess($row, Permission::PAGE_EDIT)) {
                continue;
            }
            BackendUtility::workspaceOL('pages', $row, $backendUser->workspace);
            $pageId = $row['l10n_parent'] ?: $row['uid'];
            try {
                $site = $this->siteFinder->getSiteByPageId($pageId);
                // make sure the language of the row actually exists in the site
                $site->getLanguageById($row['sys_language_uid']);
            } catch (SiteNotFoundException | \InvalidArgumentException) {
                continue;
            }
            $router = $site->getRouter();
            $row['frontendUrl'] = (string)$router->generateUri($pageId, ['_language' => $row['sys_language_uid']]);
            $items[] = $row;
            $rowCount++;
            if ($rowCount >= $this->limit) {
                return $items;
            }
        }
        return $items;
    }

    /**
     * Fetches potential candidates for the list from the database.
     * Doktypes that do not require a meta description (such as directories and links) are ignored.
     * Pages with noindex or a canonical are also ignored for this reason.
     * Language versions are considered individually.
     * Workspace versions are considered for the workspace the user is in.
     */
    private function getPotentialPages(): Result
    {
        $queryBuilder = $this->connectionPool->getQueryBuilderForTable('pages');
        $queryBuilder->getRestrictions()->add(new WorkspaceRestriction($this->getBackendUser()->workspace));
        return $queryBuilder
            ->select('uid', 'pid', 'title', 'slug', 'sys_language_uid', 'l10n_parent', 'perms_userid', 'perms_groupid', 'perms_user', 'perms_group', 'perms_everybody')
            ->from('pages')
            ->where(
                $queryBuilder->expr()->notIn('doktype', $this->excludedDoktypes),
                $queryBuilder->expr()->and(
                    $queryBuilder->expr()->eq('no_index', $queryBuilder->createNamedParameter(0)),
                    $queryBuilder->expr()->eq('canonical_link', $queryBuilder->createNamedParameter('')),
                ),
                $queryBuilder->expr()->or(
                    $queryBuilder->expr()->eq('description', $queryBuilder->createNamedParameter('')),
                    $queryBuilder->expr()->isNull('description')
                ),
            )
            ->orderBy('tstamp', 'DESC')
            ->executeQuery();
    }

    private function getBackendUser(): BackendUserAuthentication
    {
        return $GLOBALS['BE_USER'];
    }
}
