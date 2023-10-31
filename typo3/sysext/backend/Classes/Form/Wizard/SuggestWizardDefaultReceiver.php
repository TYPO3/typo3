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

namespace TYPO3\CMS\Backend\Form\Wizard;

use TYPO3\CMS\Backend\Tree\Repository\PageTreeRepository;
use TYPO3\CMS\Backend\Utility\BackendUtility;
use TYPO3\CMS\Core\Authentication\BackendUserAuthentication;
use TYPO3\CMS\Core\Database\ConnectionPool;
use TYPO3\CMS\Core\Database\Query\QueryBuilder;
use TYPO3\CMS\Core\Database\Query\QueryHelper;
use TYPO3\CMS\Core\Database\Query\Restriction\DeletedRestriction;
use TYPO3\CMS\Core\Database\Query\Restriction\WorkspaceRestriction;
use TYPO3\CMS\Core\Imaging\Icon;
use TYPO3\CMS\Core\Imaging\IconFactory;
use TYPO3\CMS\Core\Localization\LanguageService;
use TYPO3\CMS\Core\Type\Bitmask\Permission;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Core\Utility\MathUtility;

/**
 * Default implementation of a handler class for an ajax record selector.
 *
 * Normally other implementations should be inherited from this one.
 * queryTable() should not be overwritten under normal circumstances.
 */
class SuggestWizardDefaultReceiver
{
    /**
     * The name of the table to query
     *
     * @var string
     */
    protected $table = '';

    /**
     * The name of the foreign table to query (records from this table will be used for displaying instead of the ones
     * from $table)
     *
     * @var string
     */
    protected $mmForeignTable = '';

    /**
     * Configuration for this selector from TSconfig
     *
     * @var array
     */
    protected $config = [];

    /**
     * The list of pages that are allowed to perform the search for records on
     *
     * @var array Array of PIDs
     */
    protected $allowedPages = [];

    /**
     * The maximum number of items to select.
     *
     * @var int
     */
    protected $maxItems = 10;

    /**
     * @var array
     */
    protected $params = [];

    /**
     * @var IconFactory
     */
    protected $iconFactory;

    /**
     * @var QueryBuilder
     */
    protected $queryBuilder;

    /**
     * The constructor of this class
     *
     * @param string $table The table to query
     * @param array $config The configuration (TCA overlaid with TSconfig) to use for this selector
     */
    public function __construct($table, $config)
    {
        $this->iconFactory = GeneralUtility::makeInstance(IconFactory::class);
        $this->queryBuilder = $this->getQueryBuilderForTable($table);
        $this->queryBuilder->getRestrictions()
            ->removeAll()
            ->add(GeneralUtility::makeInstance(DeletedRestriction::class))
            // if table is versionized, only get the records from the Live Workspace
            // the overlay itself of WS-records is done below
            ->add(GeneralUtility::makeInstance(WorkspaceRestriction::class, 0));
        $this->table = $table;
        $this->config = $config;
        // get a list of all the pages that should be looked on
        if (isset($config['pidList'])) {
            $pageIds = GeneralUtility::intExplode(',', (string)$config['pidList'], true);
            $depth = (int)($config['pidDepth'] ?? 0);
            $availablePageIds = $this->getAvailablePageIds($pageIds, $depth);
            $this->allowedPages = array_unique(array_merge($this->allowedPages, ...$availablePageIds));
        }
        if (isset($config['maxItemsInResultList'])) {
            $this->maxItems = $config['maxItemsInResultList'];
        }
        $backendUser = $this->getBackendUser();
        $backendUser->initializeWebmountsForElementBrowser();
        if ($this->table === 'pages') {
            $this->queryBuilder->andWhere(
                QueryHelper::stripLogicalOperatorPrefix($backendUser->getPagePermsClause(Permission::PAGE_SHOW)),
                $this->queryBuilder->expr()->eq('sys_language_uid', 0)
            );
        }
        if (isset($config['addWhere'])) {
            $this->queryBuilder->andWhere(
                QueryHelper::stripLogicalOperatorPrefix($config['addWhere'])
            );
        }
    }

    /**
     * Queries a table for records and completely processes them
     *
     * Returns a two-dimensional array of almost finished records; the only need to be put into a <li>-structure
     *
     * If you subclass this class, you will most likely only want to overwrite the functions called from here, but not
     * this function itself
     *
     * @param array $params
     * @param int $recursionCounter The parent object
     * @return array Array of rows or FALSE if nothing found
     */
    public function queryTable(&$params, $recursionCounter = 0)
    {
        $maxQueryResults = 50;
        $rows = [];
        $this->params = &$params;
        $start = $recursionCounter * $maxQueryResults;
        $this->prepareSelectStatement();
        $this->prepareOrderByStatement();
        $result = $this->queryBuilder->select($this->table . '.*')
            ->from($this->table)
            ->setFirstResult($start)
            ->setMaxResults($maxQueryResults)
            ->executeQuery();
        $allRowsCount = $this->queryBuilder
            ->count($this->table . '.uid')
            ->resetQueryPart('orderBy')
            ->executeQuery()
            ->fetchOne();
        if ($allRowsCount) {
            while ($row = $result->fetchAssociative()) {
                // check if we already have collected the maximum number of records
                if (count($rows) > $this->maxItems) {
                    break;
                }
                $this->manipulateRecord($row);
                $this->makeWorkspaceOverlay($row);
                // check if the user has access to the record
                if (!$this->checkRecordAccess($row, $row['uid'])) {
                    continue;
                }
                $icon = $this->iconFactory->getIconForRecord($this->table, $row, Icon::SIZE_SMALL);
                $uid = ($row['t3ver_oid'] ?? 0) > 0 ? $row['t3ver_oid'] : $row['uid'];
                $path = $this->getRecordPath($row, $uid);
                $label = $this->getLabel($row);
                $entry = [
                    'table' => $this->mmForeignTable ?: $this->table,
                    'label' => strip_tags($label),
                    'path' => $path,
                    'uid' => $uid,
                    'icon' => [
                        'identifier' => $icon->getIdentifier(),
                        'overlay' => $icon->getOverlayIcon()?->getIdentifier(),
                    ],
                ];
                $rows[$this->table . '_' . $uid] = $this->renderRecord($row, $entry);
            }

            // if there are less records than we need, call this function again to get more records
            if (count($rows) < $this->maxItems && $allRowsCount >= $maxQueryResults && $recursionCounter < $this->maxItems) {
                $tmp = self::queryTable($params, ++$recursionCounter);
                $rows = array_merge($tmp, $rows);
            }
        }
        return $rows;
    }

    /**
     * Prepare the statement for selecting the records which will be returned to the selector. May also return some
     * other records (e.g. from a mm-table) which will be used later on to select the real records
     */
    protected function prepareSelectStatement()
    {
        $expressionBuilder = $this->queryBuilder->expr();
        $searchString = $this->params['value'];
        if ($searchString !== '') {
            $splitStrings = $this->splitSearchString($searchString);
            $constraints = [];
            foreach ($splitStrings as $splitString) {
                $constraints[] = $this->buildConstraintBlock($splitString);
            }
            foreach ($constraints as $constraint) {
                $this->queryBuilder->andWhere($expressionBuilder->and($constraint));
            }
        }
        if (!empty($this->allowedPages)) {
            $pidList = array_map('intval', $this->allowedPages);
            if (!empty($pidList)) {
                $this->queryBuilder->andWhere(
                    $expressionBuilder->in('pid', $pidList)
                );
            }
        }
        // add an additional search condition comment
        if (isset($this->config['searchCondition']) && $this->config['searchCondition'] !== '') {
            $this->queryBuilder->andWhere(QueryHelper::stripLogicalOperatorPrefix($this->config['searchCondition']));
        }
    }

    /**
     * Creates OR constraints for each split searchWord.
     *
     * @return string|\TYPO3\CMS\Core\Database\Query\Expression\CompositeExpression
     */
    protected function buildConstraintBlock(string $searchString)
    {
        $expressionBuilder = $this->queryBuilder->expr();
        $selectParts = $expressionBuilder->or();
        if (MathUtility::canBeInterpretedAsInteger($searchString) && (int)$searchString > 0) {
            $selectParts = $selectParts->with($expressionBuilder->eq('uid', (int)$searchString));
        }
        $searchWholePhrase = !isset($this->config['searchWholePhrase']) || $this->config['searchWholePhrase'];
        $likeCondition = ($searchWholePhrase ? '%' : '') . $this->queryBuilder->escapeLikeWildcards($searchString) . '%';
        // Search in all fields given by label or label_alt
        $selectFieldsList = ($GLOBALS['TCA'][$this->table]['ctrl']['label'] ?? '') . ',' . ($GLOBALS['TCA'][$this->table]['ctrl']['label_alt'] ?? '') . ',' . ($this->config['additionalSearchFields'] ?? '');
        $selectFields = GeneralUtility::trimExplode(',', $selectFieldsList, true);
        $selectFields = array_unique($selectFields);
        foreach ($selectFields as $field) {
            $selectParts = $selectParts->with($expressionBuilder->like($field, $this->queryBuilder->createPositionalParameter($likeCondition)));
        }

        return $selectParts;
    }

    /**
     * Splits the search string by space
     * This allows searching for 'elements basic' and will find results like "elements rte basic"
     * To search for whole phrases enclose by double-quotes: '"elements basic"', results in empty result
     */
    protected function splitSearchString(string $searchString): array
    {
        return str_getcsv($searchString, ' ');
    }

    /**
     * Get array of page ids from given page id and depth
     *
     * @param array $entryPointPageIds List of possible page IDs.
     * @param int $depth Depth to go down.
     * @return array of all page ids
     */
    protected function getAvailablePageIds(array $entryPointPageIds, int $depth = 0): array
    {
        if ($depth === 0) {
            return $entryPointPageIds;
        }
        $pageIds = [];
        $repository = GeneralUtility::makeInstance(PageTreeRepository::class);
        $pages = $repository->getFlattenedPages($entryPointPageIds, $depth);
        foreach ($pages as $page) {
            $pageIds[] = (int)$page['uid'];
        }
        return $pageIds;
    }

    /**
     * Prepares the clause by which the result elements are sorted. See description of ORDER BY in
     * SQL standard for reference.
     */
    protected function prepareOrderByStatement()
    {
        if (empty($this->config['orderBy'])) {
            $this->queryBuilder->addOrderBy($GLOBALS['TCA'][$this->table]['ctrl']['label']);
        } else {
            foreach (QueryHelper::parseOrderBy($this->config['orderBy']) as $orderPair) {
                [$fieldName, $order] = $orderPair;
                $this->queryBuilder->addOrderBy($fieldName, $order);
            }
        }
    }

    /**
     * Manipulate a record before using it to render the selector; may be used to replace a MM-relation etc.
     *
     * @param array $row
     */
    protected function manipulateRecord(&$row) {}

    /**
     * Selects whether the logged in Backend User is allowed to read a specific record
     *
     * @param array $row
     * @param int $uid
     * @return bool
     */
    protected function checkRecordAccess($row, $uid)
    {
        $backendUser = $this->getBackendUser();
        $retValue = true;
        $table = $this->mmForeignTable ?: $this->table;
        if ($table === 'pages') {
            if (!BackendUtility::readPageAccess($uid, $backendUser->getPagePermsClause(Permission::PAGE_SHOW))) {
                $retValue = false;
            }
        } elseif (isset($GLOBALS['TCA'][$table]['ctrl']['is_static']) && (bool)$GLOBALS['TCA'][$table]['ctrl']['is_static']) {
            $retValue = true;
        } else {
            if (!is_array(BackendUtility::readPageAccess($row['pid'], $backendUser->getPagePermsClause(Permission::PAGE_SHOW)))) {
                $retValue = false;
            }
        }
        return $retValue;
    }

    /**
     * Overlay the given record with its workspace-version, if any
     *
     * @param array $row The record to get the workspace version for
     */
    protected function makeWorkspaceOverlay(&$row)
    {
        // Check for workspace-versions
        if ($this->getBackendUser()->workspace !== 0 && BackendUtility::isTableWorkspaceEnabled($this->table)) {
            BackendUtility::workspaceOL($this->mmForeignTable ?: $this->table, $row);
        }
    }

    /**
     * Returns the path for a record. Is the whole path for all records except pages - for these the last part is cut
     * off, because it contains the pagetitle itself, which would be double information
     *
     * The path is returned uncut, cutting has to be done by calling function.
     *
     * @param array $row The row
     * @param int $uid UID of the record
     * @return string The record-path
     */
    protected function getRecordPath(&$row, $uid)
    {
        $titleLimit = max($this->config['maxPathTitleLength'] ?? 0, 0);
        if (($this->mmForeignTable ?: $this->table) === 'pages') {
            $path = BackendUtility::getRecordPath($uid, '', $titleLimit);
            // For pages we only want the first (n-1) parts of the path,
            // because the n-th part is the page itself
            $path = substr($path, 0, (int)strrpos($path, '/', -2)) . '/';
        } else {
            $path = BackendUtility::getRecordPath($row['pid'], '', $titleLimit);
        }
        return $path;
    }

    /**
     * Returns a label for a given record; usually only a wrapper for \TYPO3\CMS\Backend\Utility\BackendUtility::getRecordTitle
     *
     * @param array $row The record to get the label for
     * @return string The label
     */
    protected function getLabel($row)
    {
        return BackendUtility::getRecordTitle($this->mmForeignTable ?: $this->table, $row, true);
    }

    /**
     * Calls a user function for rendering the page.
     *
     * This user function should manipulate $entry
     *
     * @param array $row The row
     * @param array $entry The entry to render
     * @return array The rendered entry (will be put into a <li> later on
     */
    protected function renderRecord($row, $entry)
    {
        // Call renderlet if available (normal pages etc. usually don't have one)
        if (($this->config['renderFunc'] ?? '') != '') {
            $params = [
                'table' => $this->table,
                'uid' => $row['uid'],
                'row' => $row,
                'entry' => &$entry,
            ];
            GeneralUtility::callUserFunction($this->config['renderFunc'], $params, $this);
        }
        return $entry;
    }

    protected function getLanguageService(): LanguageService
    {
        return $GLOBALS['LANG'];
    }

    public function getBackendUser(): BackendUserAuthentication
    {
        return $GLOBALS['BE_USER'];
    }

    /**
     * @param string $table
     * @return QueryBuilder
     */
    protected function getQueryBuilderForTable($table)
    {
        return GeneralUtility::makeInstance(ConnectionPool::class)->getQueryBuilderForTable($table);
    }
}
