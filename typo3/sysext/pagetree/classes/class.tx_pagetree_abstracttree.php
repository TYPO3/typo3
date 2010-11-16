<?php
/***************************************************************
*  Copyright notice
*
*  (c) 2009 Susanne Moog <s.moog@neusta.de>
*  		Bodo Eichstaedt <bodo.eichstaedt@wmdb.de>
*
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
 * Abstract class for page trees
 *
 * $Id: $
 *
 * @author	Susanne Moog
 * @author  Bodo Eichstaedt
 * @package TYPO3
 */
abstract class tx_pagetree_AbstractTree {
	protected $table = 'pages';
	protected $fields = 'uid';

	/**
	 * Fetches the subpages to a given id. Uses the class variables $table and $pages
	 * to determine what to fetch from where. Calls getFilterClause($id) to add a where
	 * clause to the query.
	 *
	 * @param int $id The parent ID of the subpages to fetch
	 * @return array The subpages as array
	 */
	public function getSubPages($id) {
		$where = 'pid= ' . $GLOBALS['TYPO3_DB']->fullQuoteStr($id, $this->table) .
			$this->getFilterClause($id);

		$resultResourceSubpages = $GLOBALS['TYPO3_DB']->exec_SELECTquery(
			$this->fields,
			$this->table,
			$where,
			'',
			'sorting'
		);

		if ($GLOBALS['TYPO3_DB']->sql_num_rows($resultResourceSubpages)) {
			while ($row = $GLOBALS['TYPO3_DB']->sql_fetch_assoc($resultResourceSubpages)) {
				$row = t3lib_befunc::getRecordWSOL(
					'pages',
					$row['uid'], 
					'*',
					'',
					TRUE, 
					$GLOBALS['BE_USER']->uc['currentPageTreeLanguage']
				);
				$this->addMetaInformationToPage($row['uid'], $row);
				$subpages[$row['uid']] = $row;
			}
		}

		return $subpages;
	}


	/**
	 * Gets information about the root page. Because the root page isn't fetched from DB
	 * we add infos here statically, like the sitename, the subpages and the id.
	 *
	 * @return array Information about the root page (structure like addMetaInformationToPage)
	 */
	public function addRootPageInformation() {
		$rootPageInfo = array(
			'uid' => 0,
			'title' => !empty($GLOBALS['TYPO3_CONF_VARS']['SYS']['sitename']) ?
								$GLOBALS['TYPO3_CONF_VARS']['SYS']['sitename'] : 'TYPO3',
			'_subpages' => 1,
			'_actions' => false);
		return $rootPageInfo;
	}

	/**
	 * Fetches a where clause to narrow the resulting list of tree elements
	 * Mostly this should contain access/permission checks
	 *
	 * @param int $id The page Id to select
	 */
	abstract protected function getFilterClause($id);

	/**
	 * Creates a new page
	 *
	 * @param int $parentId The ID of the future parent
	 * @param int $doktype The doctype of the new page
	 */
	abstract public function create($parentId, $targetId, $doktype);
	/**
	 * Moves a page
	 *
	 * @param int $sourceId The page to move
	 * @param int $targetId The page to move the page into
	 */
	abstract public function move($sourceId, $targetId);

	/**
	 * Copies a page
	 *
	 * @param int $sourceId The page to copy
	 * @param int $targetId The page to copy the page into
	 */
	abstract public function copy($sourceId, $targetId);

	/**
	 * Shows a page
	 *
	 * @param array $row The result row of the page to show
	 */
	abstract public function show($row);

	/**
	 * Hides a page
	 *
	 * @param array $row The result row of the page to hide
	 */
	abstract public function disable($id);

	/**
	 * Deletes a page
	 *
	 * @param array $row The result row of the page to delete
	 */
	abstract public function remove($id);

	/**
	 * Restores (undeletes) a page
	 *
	 * @param array $row The result row of the page to restore
	 */
	abstract public function restore($id);
	
	/**
	 * Check if a page can be edited
	 *
	 * @param array $row The result row of the page to be edited
	 */
	abstract public function canBeEdited($row);

		
	/**
	 * Check if new pages can be created
	 *
	 * @param array $row The result row of the page to be created
	 */
	abstract public function canCreateNewPages($row);

		
	/**
	 * Check if a page can be removed
	 *
	 * @param array $row The result row of the page to be removed
	 */
	abstract public function canBeRemoved($row);
	
		
	/**
	 * Check if a page can be viewed
	 *
	 * @param array $row The result row of the page to be viewed
	 */
	abstract public function canBeViewed($row);
	
		
	/**
	 * Check if a page can be moved
	 *
	 * @param array $row The result row of the page to be moved
	 */
	abstract public function canBeMoved($row);
		
	/**
	 * Check if a page can have subpages
	 *
	 * @param array $row The result row of the page to have subpages
	 */
	abstract public function canHaveSubpages($row);
	
		
	/**
	 * Check if a page can be copied
	 *
	 * @param array $row The result row of the page to be copied
	 */
	abstract public function canBeCopied($row);
	
		
	/**
	 * Check if a page can be disabled
	 *
	 * @param array $row The result row of the page to be disabled
	 */
	abstract public function canBeDisabled($row);
	
		
	/**
	 * Check if a page can show info
	 * 
	 */
	abstract public function canShowInfo();
	
		
	/**
	 * Check if a page can be cut
	 *
	 */
	abstract public function canBeCut();
	
		
	/**
	 * Check if a page can be pasted
	 *
	 */
	abstract public function canBePasted();

	/**
	 * Check if a page can show history
	 *
	 */
	abstract public function canShowHistory();

}


if (defined('TYPO3_MODE') && $TYPO3_CONF_VARS[TYPO3_MODE]['XCLASS']['typo3/sysext/pagetree/classes/class.tx_pagetree_abstracttree.php'])	{
	include_once($TYPO3_CONF_VARS[TYPO3_MODE]['XCLASS']['typo3/sysext/pagetree/classes/class.tx_pagetree_abstracttree.php']);
}

?>