<?php
/***************************************************************
 *  Copyright notice
 *
 *  (c) 2009 Sebastian Kurfuerst
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
 * Abstract tree dataprovider
 *
 * @author	Sebastian Kurfuerst <sebastian@typo3.org>
 * @package	TYPO3
 */
abstract class tx_pagetree_dataprovider_AbstractTree {

	protected $treeName;

	/**
	 * Fetches the Items for the next level of the tree
	 *
	 * @param mixed The ID of the tree node
	 * @param string The rootline of the element
	 * @return array The next level tree elements
	 */
	public function getNextTreeLevel($id, $rootline) {
		// Load UC
		$GLOBALS['BE_USER']->backendSetUC();
		$treeState =& $GLOBALS['BE_USER']->uc['treeState'][$this->treeName];

		$records = $this->getRecordsForNextTreeLevel($this->decodeTreeNodeId($id));
		$results = array();
		foreach ($records as $record) {
			$singleResult = $this->prepareRecordForResultList($record, $rootline);
			$rootlineOfSubElement = $rootline;
			// the rootline functionality is completely nonsense and should be replaced completely
			if ($singleResult['id']) {
				$rootlineOfSubElement = $rootline . '/' . $singleResult['id'];
			}
			if (isset($treeState[$rootlineOfSubElement])) {
				$singleResult['expanded'] = true;
				$singleResult['children'] = $this->getNextTreeLevel($singleResult['id'], $rootlineOfSubElement);
			}

			$results[] = $singleResult;
		}
		return $results;
	}

	/**
	 * Prepares the records for the result list & fetches the actions
	 *
	 * @param array $record The record to be prepared
	 * @param string $rootline The rootline of the record
	 * @return array The prepared result
	 */
	protected function prepareRecordForResultList($record, $rootline = '') {
		$currentResult = $this->getTreeNodePropertiesForRecord($record, $rootline);
		$currentResult['actions'] = $this->getActionsForRecord($record);
		return $currentResult;
	}

	/**
	 * Saves the current expanded state of the tree to the user settings
	 *
	 * @param string $rootline The current rootline
	 */
	public function registerExpandedNode($rootline) {
		$GLOBALS['BE_USER']->backendSetUC();
		$treeState =& $GLOBALS['BE_USER']->uc['treeState'][$this->treeName];
		$treeState[$rootline] = TRUE;
		$GLOBALS['BE_USER']->writeUC();
	}

	/**
	 * Saves the current collapsed state of the tree to the user settings
	 *
	 * @param string $rootline The current rootline
	 */
	public function registerCollapsedNode($rootline) {
		$GLOBALS['BE_USER']->backendSetUC();
		$treeState =& $GLOBALS['BE_USER']->uc['treeState'][$this->treeName];
		if (isset($treeState[$rootline])) {
			unset($treeState[$rootline]);
		}
		$GLOBALS['BE_USER']->writeUC();
	}


	/**
	 * Encode node id and its rootline into an global unique identifier even if the same
	 * node id is used a couple of times (webmounts, filemount, ...)
	 *
	 * @param string $id node id
	 * @param string $rootline rootline / path include base-node like webmount-id/filemount-name
	 */
	protected function encodeTreeNodeId($id, $rootline) {
		if ($rootline == '/root') {
			return $this->idEncode($id);
		} else {
			list(, , $mountpoint) = explode('/', $rootline);
			return 'mp' . $this->idEncode($mountpoint) . '-' . $this->idEncode($id);
		}
	}

	/**
	 * Decodes the id of the tree node
	 *
	 * @param string $id The ID string
	 * @return int
	 */
	protected function decodeTreeNodeId($id) {
		if (strpos($id, 'mp') === 0) {
			// everything _INSIDE_ a mountpage
			// mp is in there, extract the ID!
			list(, $id) = explode('-', $id);
			return $this->idDecode($id);
		} else {
			// /root, and /root/XXX (mountpage)
			return $id;
		}
	}

	/**
	 * Encodes an id, this one just does an intval, if you need
	 * something more implement your own method
	 * @return the encoded ID
	 */
	protected function idEncode($id) {
		return intval($id);
	}

	/**
	 * Decodes an id, this one just does an intval, if you need
	 * something more implement your own method
	 * @return the decoded ID
	 */
	protected function idDecode($id) {
		return intval($id);
	}

	/**
	 * Get Label
	 *
	 * @param string $label label name
	 * @return string the localized label string
	 */
	protected function getLabel($label) {
		return $GLOBALS['LANG']->sL($label, TRUE);
	}

	/**
	 * Get Icon for Context Menu
	 *
	 * @param string $icon Icon name
	 * @return string Returns part of the img tag like ' src="[backPath][src]" [wHattribs]'
	 */
	protected function getIcon($icon) {

		return $icon;
	}

	/**
	 * Fetches a filtered tree
	 *
	 * @param string $searchString The string to search for
	 *
	 */
	abstract public function getFilteredTree($searchString);

	/**
	 * Fetches the records for the next tree level (subpages or subdirectories f.e.)
	 *
	 * @return array a multidimensional array of records to be displayed in the tree on the next level, including the metainformation for the record
	 */
	abstract protected function getRecordsForNextTreeLevel($id);

	/**
	 * gets a record, and MUST return an array with the following properties:
	 * - id (UNIQUE id)
	 * - leaf (boolean) - FALSE, if it has child records
	 * - text - the title of the node in the tree
	 * additionally, you can specify any of the Ext.tree.TreeNode properties, except "attributes".
	 */
	abstract protected function getTreeNodePropertiesForRecord($record, $rootline);


	/**
	 * Get an array of actions which can be performed with this single record. Used for the context menu.
	 */
	abstract protected function getActionsForRecord($record);

	/**
	 * Generates the context menu items which can be toogled on/off by the record actions.
	 * You need to define them inside a hash array with the fields "text" and "callback".
	 * @see getActionsForRecord
	 */
	abstract public function getContextMenuConfiguration();
}

if (defined('TYPO3_MODE') && $TYPO3_CONF_VARS[TYPO3_MODE]['XCLASS']['typo3/sysext/pagetree/extdirect/dataprovider/class.tx_pagetree_dataprovider_abstracttree.php']) {
	include_once($TYPO3_CONF_VARS[TYPO3_MODE]['XCLASS']['typo3/sysext/pagetree/extdirect/dataprovider/class.tx_pagetree_dataprovider_abstracttree.php']);
}

?>