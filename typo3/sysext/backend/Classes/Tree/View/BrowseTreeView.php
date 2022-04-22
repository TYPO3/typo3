<?php

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

namespace TYPO3\CMS\Backend\Tree\View;

use TYPO3\CMS\Backend\Utility\BackendUtility;
use TYPO3\CMS\Core\Exception\SiteNotFoundException;
use TYPO3\CMS\Core\Site\SiteFinder;
use TYPO3\CMS\Core\Type\Bitmask\Permission;
use TYPO3\CMS\Core\Utility\GeneralUtility;

/**
 * Generate a page-tree, browsable.
 */
class BrowseTreeView extends AbstractTreeView
{
    /**
     * Initialize, setting what is necessary for browsing pages.
     * Using the current user.
     *
     * @param string $clause Additional clause for selecting pages.
     * @param string $orderByFields record ORDER BY field
     */
    public function init($clause = '', $orderByFields = '')
    {
        $backendUser = $this->getBackendUser();
        // This will hide records from display - it has nothing to do with user rights!!
        $clauseExcludePidList = '';
        $pidList = $backendUser->getTSConfig()['options.']['hideRecords.']['pages'] ?? '';
        if (!empty($pidList)) {
            if ($pidList = implode(',', GeneralUtility::intExplode(',', $pidList))) {
                $clauseExcludePidList = ' AND pages.uid NOT IN (' . $pidList . ')';
            }
        }
        // This is very important for making trees of pages: Filtering out deleted pages, pages with no access to and sorting them correctly:
        parent::init(' AND deleted=0 AND sys_language_uid=0 AND ' . $backendUser->getPagePermsClause(Permission::PAGE_SHOW) . ' ' . $clause . $clauseExcludePidList, 'sorting');
    }

    /**
     * Creates title attribute content for pages.
     * Uses API function in \TYPO3\CMS\Backend\Utility\BackendUtility which will retrieve lots of useful information for pages.
     *
     * @param array $row The table row.
     * @return string
     */
    public function getTitleAttrib($row)
    {
        return BackendUtility::titleAttribForPages($row, '1=1 ' . $this->clause, false);
    }

    /**
     * Returns the title for the input record. If blank, a "no title" label (localized) will be returned.
     * Do NOT htmlspecialchar the string from this function - has already been done.
     *
     * @param array $row The input row array (where the key "title" is used for the title)
     * @param int $titleLen Title length (30)
     * @return string The title.
     */
    public function getTitleStr($row, $titleLen = 30)
    {
        $title = parent::getTitleStr($row, $titleLen);
        if (!empty($row['is_siteroot'])
            && ($this->getBackendUser()->getTSConfig()['options.']['pageTree.']['showDomainNameWithTitle'] ?? false)
        ) {
            $pageId = (int)$row['uid'];
            $siteFinder = GeneralUtility::makeInstance(SiteFinder::class);
            try {
                $site = $siteFinder->getSiteByRootPageId($pageId);
                $title .= ' [' . (string)$site->getBase() . ']';
            } catch (SiteNotFoundException $e) {
                // No site found
            }
        }
        return $title;
    }
}
