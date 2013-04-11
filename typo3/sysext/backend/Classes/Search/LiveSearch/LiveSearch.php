<?php
namespace TYPO3\CMS\Backend\Search\LiveSearch;

/***************************************************************
 *  Copyright notice
 *
 *  (c) 2009-2013 Michael Klapper <michael.klapper@aoemedia.de>
 *  (c) 2010-2013 Jeff Segars <jeff@webempoweredchurch.org>
 *  All rights reserved
 *
 *  This script is part of the TYPO3 project. The TYPO3 project is
 *  free software; you can redistribute it and/or modify
 *  it under the terms of the GNU General Public License as published by
 *  the Free Software Foundation; either version 2 of the License, or
 *  (at your option) any later version.
 *
 *  The GNU General Public License can be found at
 *  http://www.gnu.org/copyleft/gpl.html.
 *  A copy is found in the textfile GPL.txt and important notices to the license
 *  from the author is found in LICENSE.txt distributed with these scripts.
 *
 *
 *  This script is distributed in the hope that it will be useful,
 *  but WITHOUT ANY WARRANTY; without even the implied warranty of
 *  MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 *  GNU General Public License for more details.
 *
 *  This copyright notice MUST APPEAR in all copies of the script!
 ***************************************************************/
/**
 * Class for handling backend live search.
 *
 * @author Michael Klapper <michael.klapper@aoemedia.de>
 * @author Jeff Segars <jeff@webempoweredchurch.org>
 */
class LiveSearch {

	/**
	 * @var string
	 */
	const PAGE_JUMP_TABLE = 'pages';
	/**
	 * @var integer
	 */
	const RECURSIVE_PAGE_LEVEL = 99;
	/**
	 * @var integer
	 */
	const GROUP_TITLE_MAX_LENGTH = 15;
	/**
	 * @var integer
	 */
	const RECORD_TITLE_MAX_LENGTH = 28;
	/**
	 * @var string
	 */
	private $queryString = '';

	/**
	 * @var integer
	 */
	private $startCount = 0;

	/**
	 * @var integer
	 */
	private $limitCount = 5;

	/**
	 * @var string
	 */
	protected $userPermissions = '';

	/**
	 * @var \TYPO3\CMS\Backend\Search\LiveSearch\QueryParser
	 */
	protected $queryParser = NULL;

	/**
	 * Initialize access settings.
	 *
	 * @return void
	 */
	public function __construct() {
		$this->userPermissions = $GLOBALS['BE_USER']->getPagePermsClause(1);
		$this->queryParser = \TYPO3\CMS\Core\Utility\GeneralUtility::makeInstance('TYPO3\\CMS\\Backend\\Search\\LiveSearch\\QueryParser');
	}

	/**
	 * Find records from database based on the given $searchQuery.
	 *
	 * @param string $searchQuery
	 * @return string Edit link to an page record if exists. Otherwise an empty string will returned
	 */
	public function findPage($searchQuery) {
		$link = '';
		$pageId = $this->queryParser->getId($searchQuery);
		$pageRecord = $this->findPageById($pageId);
		if (!empty($pageRecord)) {
			$link = $this->getEditLink(self::PAGE_JUMP_TABLE, $this->findPageById($pageId));
		}
		return $link;
	}

	/**
	 * Find records from database based on the given $searchQuery.
	 *
	 * @param string $searchQuery
	 * @return array Result list of database search.
	 */
	public function find($searchQuery) {
		$recordArray = array();
		$pageList = array();
		$mounts = $GLOBALS['BE_USER']->returnWebmounts();
		foreach ($mounts as $pageId) {
			$pageList[] = $this->getAvailablePageIds($pageId, self::RECURSIVE_PAGE_LEVEL);
		}
		$pageIdList = implode(',', array_unique(explode(',', implode(',', $pageList))));
		unset($pageList);
		$limit = $this->startCount . ',' . $this->limitCount;
		if ($this->queryParser->isValidCommand($searchQuery)) {
			$this->setQueryString($this->queryParser->getSearchQueryValue($searchQuery));
			$tableName = $this->queryParser->getTableNameFromCommand($searchQuery);
			if ($tableName) {
				$recordArray[] = $this->findByTable($tableName, $pageIdList, $limit);
			}
		} else {
			$this->setQueryString($searchQuery);
			$recordArray = $this->findByGlobalTableList($pageIdList);
		}
		return $recordArray;
	}

	/**
	 * Retrieve the page record from given $id.
	 *
	 * @param integer $id
	 * @return array
	 */
	protected function findPageById($id) {
		$pageRecord = array();
		$row = \TYPO3\CMS\Backend\Utility\BackendUtility::getRecord(self::PAGE_JUMP_TABLE, $id);
		if (is_array($row)) {
			$pageRecord = $row;
		}
		return $pageRecord;
	}

	/**
	 * Find records from all registered TCA table & column values.
	 *
	 * @param string $pageIdList Comma separated list of page IDs
	 * @return array Records found in the database matching the searchQuery
	 */
	protected function findByGlobalTableList($pageIdList) {
		$limit = $this->limitCount;
		$getRecordArray = array();
		foreach ($GLOBALS['TCA'] as $tableName => $value) {
			$recordArray = $this->findByTable($tableName, $pageIdList, '0,' . $limit);
			$recordCount = count($recordArray);
			if ($recordCount) {
				$limit = $limit - $recordCount;
				$getRecordArray[] = $recordArray;
				if ($limit <= 0) {
					break;
				}
			}
		}
		return $getRecordArray;
	}

	/**
	 * Find records by given table name.
	 *
	 * @param string $tableName Database table name
	 * @param string $pageIdList Comma separated list of page IDs
	 * @param string $limit MySql Limit notation
	 * @return array Records found in the database matching the searchQuery
	 * @see getRecordArray()
	 * @see makeOrderByTable()
	 * @see makeQuerySearchByTable()
	 * @see extractSearchableFieldsFromTable()
	 */
	protected function findByTable($tableName, $pageIdList, $limit) {
		$fieldsToSearchWithin = $this->extractSearchableFieldsFromTable($tableName);
		$getRecordArray = array();
		if (count($fieldsToSearchWithin) > 0) {
			$pageBasedPermission = $tableName == 'pages' && $this->userPermissions ? $this->userPermissions : '1=1 ';
			$where = 'pid IN (' . $pageIdList . ') AND ' . $pageBasedPermission . $this->makeQuerySearchByTable($tableName, $fieldsToSearchWithin);
			$orderBy = $this->makeOrderByTable($tableName);
			$getRecordArray = $this->getRecordArray($tableName, $where, $this->makeOrderByTable($tableName), $limit);
		}
		return $getRecordArray;
	}

	/**
	 * Process the Database operation to get the search result.
	 *
	 * @param string $tableName Database table name
	 * @param string $where
	 * @param string $orderBy
	 * @param string $limit MySql Limit notation
	 * @return array
	 * @see \TYPO3\CMS\Backend\Utility\IconUtility::getSpriteIconForRecord()
	 * @see getTitleFromCurrentRow()
	 * @see getEditLink()
	 */
	protected function getRecordArray($tableName, $where, $orderBy, $limit) {
		$collect = array();
		$isFirst = TRUE;
		$queryParts = array(
			'SELECT' => '*',
			'FROM' => $tableName,
			'WHERE' => $where,
			'ORDERBY' => $orderBy,
			'LIMIT' => $limit
		);
		$result = $GLOBALS['TYPO3_DB']->exec_SELECT_queryArray($queryParts);
		$dbCount = $GLOBALS['TYPO3_DB']->sql_num_rows($result);
		while ($row = $GLOBALS['TYPO3_DB']->sql_fetch_assoc($result)) {
			$collect[] = array(
				'id' => $tableName . ':' . $row['uid'],
				'pageId' => $tableName === 'pages' ? $row['uid'] : $row['pid'],
				'recordTitle' => $isFirst ? $this->getRecordTitlePrep($this->getTitleOfCurrentRecordType($tableName), self::GROUP_TITLE_MAX_LENGTH) : '',
				'iconHTML' => \TYPO3\CMS\Backend\Utility\IconUtility::getSpriteIconForRecord($tableName, $row),
				'title' => $this->getRecordTitlePrep($this->getTitleFromCurrentRow($tableName, $row), self::RECORD_TITLE_MAX_LENGTH),
				'editLink' => $this->getEditLink($tableName, $row)
			);
			$isFirst = FALSE;
		}
		$GLOBALS['TYPO3_DB']->sql_free_result($result);
		return $collect;
	}

	/**
	 * Build a backend edit link based on given record.
	 *
	 * @param string $tableName Record table name
	 * @param array $row Current record row from database.
	 * @return string Link to open an edit window for record.
	 * @see \TYPO3\CMS\Backend\Utility\BackendUtility::readPageAccess()
	 */
	protected function getEditLink($tableName, $row) {
		$pageInfo = \TYPO3\CMS\Backend\Utility\BackendUtility::readPageAccess($row['pid'], $this->userPermissions);
		$calcPerms = $GLOBALS['BE_USER']->calcPerms($pageInfo);
		$editLink = '';
		if ($tableName == 'pages') {
			$localCalcPerms = $GLOBALS['BE_USER']->calcPerms(\TYPO3\CMS\Backend\Utility\BackendUtility::getRecord('pages', $row['uid']));
			$permsEdit = $localCalcPerms & 2;
		} else {
			$permsEdit = $calcPerms & 16;
		}
		// "Edit" link: ( Only if permissions to edit the page-record of the content of the parent page ($this->id)
		// @todo Is there an existing function to generate this link?
		if ($permsEdit) {
			$editLink = 'alt_doc.php?' . '&edit[' . $tableName . '][' . $row['uid'] . ']=edit';
		}
		return $editLink;
	}

	/**
	 * Retrieve the record name
	 *
	 * @param string $tableName Record table name
	 * @return string
	 */
	protected function getTitleOfCurrentRecordType($tableName) {
		return $GLOBALS['LANG']->sL($GLOBALS['TCA'][$tableName]['ctrl']['title']);
	}

	/**
	 * Crops a title string to a limited length and if it really was cropped,
	 * wrap it in a <span title="...">|</span>,
	 * which offers a tooltip with the original title when moving mouse over it.
	 *
	 * @param string $title The title string to be cropped
	 * @param integer $titleLength Crop title after this length - if not set, BE_USER->uc['titleLen'] is used
	 * @return string The processed title string, wrapped in <span title="...">|</span> if cropped
	 */
	public function getRecordTitlePrep($title, $titleLength = 0) {
		// If $titleLength is not a valid positive integer, use BE_USER->uc['titleLen']:
		if (!$titleLength || !\TYPO3\CMS\Core\Utility\MathUtility::canBeInterpretedAsInteger($titleLength) || $titleLength < 0) {
			$titleLength = $GLOBALS['BE_USER']->uc['titleLen'];
		}
		return htmlspecialchars(\TYPO3\CMS\Core\Utility\GeneralUtility::fixed_lgd_cs($title, $titleLength));
	}

	/**
	 * Retrieve the column name which contains the title value
	 *
	 * @param string $tableName Record table name
	 * @param array $row Current record row from database.
	 * @return string
	 * @todo Use the backend function to get the calculated label instead.
	 */
	protected function getTitleFromCurrentRow($tableName, $row) {
		$titleColumnName = $GLOBALS['TCA'][$tableName]['ctrl']['label'];
		return $row[$titleColumnName];
	}

	/**
	 * Build the MySql where clause by table.
	 *
	 * @param string $tableName Record table name
	 * @param array $fieldsToSearchWithin User right based visible fields where we can search within.
	 * @return string
	 */
	protected function makeQuerySearchByTable($tableName, array $fieldsToSearchWithin) {
		$queryPart = '';
		$whereParts = array();
		// If the search string is a simple integer, assemble an equality comparison
		if (\TYPO3\CMS\Core\Utility\MathUtility::canBeInterpretedAsInteger($this->queryString)) {
			foreach ($fieldsToSearchWithin as $fieldName) {
				if ($fieldName == 'uid' || $fieldName == 'pid' || isset($GLOBALS['TCA'][$tableName]['columns'][$fieldName])) {
					$fieldConfig = &$GLOBALS['TCA'][$tableName]['columns'][$fieldName]['config'];
					// Assemble the search condition only if the field is an integer, or is uid or pid
					if ($fieldName == 'uid' || $fieldName == 'pid' || $fieldConfig['type'] == 'input' && $fieldConfig['eval'] && \TYPO3\CMS\Core\Utility\GeneralUtility::inList($fieldConfig['eval'], 'int')) {
						$whereParts[] = $fieldName . '=' . $this->queryString;
					} elseif (
						$fieldConfig['type'] == 'text' ||
						$fieldConfig['type'] == 'flex' ||
						($fieldConfig['type'] == 'input' && (!$fieldConfig['eval'] ||
						!preg_match('/date|time|int/', $fieldConfig['eval'])))) {
							// Otherwise and if the field makes sense to be searched, assemble a like condition
							$whereParts[] = $fieldName . ' LIKE \'%' . $this->queryString . '%\'';
					}
				}
			}
		} else {
			$like = '\'%' . $GLOBALS['TYPO3_DB']->escapeStrForLike($GLOBALS['TYPO3_DB']->quoteStr($this->queryString, $tableName), $tableName) . '%\'';
			foreach ($fieldsToSearchWithin as $fieldName) {
				if (isset($GLOBALS['TCA'][$tableName]['columns'][$fieldName])) {
					$fieldConfig = &$GLOBALS['TCA'][$tableName]['columns'][$fieldName]['config'];
					// Check whether search should be case-sensitive or not
					$format = 'LCASE(%s) LIKE LCASE(%s)';
					if (is_array($fieldConfig['search'])) {
						if (in_array('case', $fieldConfig['search'])) {
							$format = '%s LIKE %s';
						}
						// Apply additional condition, if any
						if ($fieldConfig['search']['andWhere']) {
							$format = '((' . $fieldConfig['search']['andWhere'] . ') AND (' . $format . '))';
						}
					}
					// Assemble the search condition only if the field makes sense to be searched
					if ($fieldConfig['type'] == 'text' || $fieldConfig['type'] == 'flex' || $fieldConfig['type'] == 'input' && (!$fieldConfig['eval'] || !preg_match('/date|time|int/', $fieldConfig['eval']))) {
						$whereParts[] = sprintf($format, $fieldName, $like);
					}
				}
			}
		}
		// If at least one condition was defined, create the search query
		if (count($whereParts) > 0) {
			$queryPart = ' AND (' . implode(' OR ', $whereParts) . ')';
			// And the relevant conditions for deleted and versioned records
			$queryPart .= \TYPO3\CMS\Backend\Utility\BackendUtility::deleteClause($tableName);
			$queryPart .= \TYPO3\CMS\Backend\Utility\BackendUtility::versioningPlaceholderClause($tableName);
		} else {
			$queryPart = ' AND 0 = 1';
		}
		return $queryPart;
	}

	/**
	 * Build the MySql ORDER BY statement.
	 *
	 * @param string $tableName Record table name
	 * @return string
	 */
	protected function makeOrderByTable($tableName) {
		$orderBy = '';
		if (is_array($GLOBALS['TCA'][$tableName]['ctrl']) && array_key_exists('sortby', $GLOBALS['TCA'][$tableName]['ctrl'])) {
			$sortBy = trim($GLOBALS['TCA'][$tableName]['ctrl']['sortby']);
			if (!empty($sortBy)) {
				$orderBy = 'ORDER BY ' . $sortBy;
			}
		} else {
			$orderBy = $GLOBALS['TCA'][$tableName]['ctrl']['default_sortby'];
		}
		return $GLOBALS['TYPO3_DB']->stripOrderBy($orderBy);
	}

	/**
	 * Get all fields from given table where we can search for.
	 *
	 * @param string $tableName Name of the table for which to get the searchable fields
	 * @return array
	 */
	protected function extractSearchableFieldsFromTable($tableName) {
		// Get the list of fields to search in from the TCA, if any
		if (isset($GLOBALS['TCA'][$tableName]['ctrl']['searchFields'])) {
			$fieldListArray = \TYPO3\CMS\Core\Utility\GeneralUtility::trimExplode(',', $GLOBALS['TCA'][$tableName]['ctrl']['searchFields'], TRUE);
		} else {
			$fieldListArray = array();
		}
		// Add special fields
		if ($GLOBALS['BE_USER']->isAdmin()) {
			$fieldListArray[] = 'uid';
			$fieldListArray[] = 'pid';
		}
		return $fieldListArray;
	}

	/**
	 * Safely retrieve the queryString.
	 *
	 * @param string $tableName
	 * @return string
	 */
	public function getQueryString($tableName = '') {
		return $GLOBALS['TYPO3_DB']->quoteStr($this->queryString, $tableName);
	}

	/**
	 * Setter for limit value.
	 *
	 * @param integer $limitCount
	 * @return void
	 */
	public function setLimitCount($limitCount) {
		$limit = \TYPO3\CMS\Core\Utility\MathUtility::convertToPositiveInteger($limitCount);
		if ($limit > 0) {
			$this->limitCount = $limit;
		}
	}

	/**
	 * Setter for start count value.
	 *
	 * @param integer $startCount
	 * @return void
	 */
	public function setStartCount($startCount) {
		$this->startCount = \TYPO3\CMS\Core\Utility\MathUtility::convertToPositiveInteger($startCount);
	}

	/**
	 * Setter for the search query string.
	 *
	 * @param string $queryString
	 * @return void
	 * @see \TYPO3\CMS\Core\Utility\GeneralUtility::removeXSS()
	 */
	public function setQueryString($queryString) {
		$this->queryString = \TYPO3\CMS\Core\Utility\GeneralUtility::removeXSS($queryString);
	}

	/**
	 * Creates an instance of \TYPO3\CMS\Backend\Tree\View\PageTreeView which will select a
	 * page tree to $depth and return the object. In that object we will find the ids of the tree.
	 *
	 * @param integer $id Page id.
	 * @param integer $depth Depth to go down.
	 * @return string Comma separated list of uids
	 */
	protected function getAvailablePageIds($id, $depth) {
		$idList = '';
		$tree = \TYPO3\CMS\Core\Utility\GeneralUtility::makeInstance('TYPO3\\CMS\\Backend\\Tree\\View\\PageTreeView');
		$tree->init('AND ' . $this->userPermissions);
		$tree->makeHTML = 0;
		$tree->fieldArray = array('uid', 'php_tree_stop');
		if ($depth) {
			$tree->getTree($id, $depth, '');
		}
		$tree->ids[] = $id;
		$idList = implode(',', $tree->ids);
		return $idList;
	}

}


?>