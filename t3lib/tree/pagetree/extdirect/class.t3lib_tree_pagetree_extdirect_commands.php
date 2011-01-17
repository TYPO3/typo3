<?php
/***************************************************************
*  Copyright notice
*
*  (c) 2010 TYPO3 Tree Team <http://forge.typo3.org/projects/typo3v4-extjstrees>
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
 * Commands for the Page tree
 *
 * @author Stefan Galinski <stefan.galinski@gmail.com>
 * @package TYPO3
 * @subpackage t3lib
 */
class t3lib_tree_pagetree_extdirect_Commands {
	/**
	 * Visibly the page
	 *
	 * @param stdClass $nodeData
	 * @return array
	 */
	public function visiblyNode($nodeData) {
		/** @var $node t3lib_tree_pagetree_Node */
		$node = t3lib_div::makeInstance('t3lib_tree_pagetree_Node', (array) $nodeData);

		/** @var $dataProvider t3lib_tree_pagetree_DataProvider */
		$dataProvider = t3lib_div::makeInstance('t3lib_tree_pagetree_DataProvider');

		try {
			t3lib_tree_pagetree_Commands::visiblyNode($node);
			$newNode = t3lib_tree_pagetree_Commands::getNode($node->getId());
			$newNode->setLeaf($node->isLeafNode());
			$returnValue = $newNode->toArray();
		} catch (Exception $exception) {
			$returnValue = array(
				 'success' => FALSE,
				 'error' => $exception->getMessage(),
			 );
		}

		return $returnValue;
	}

	/**
	 * Hide the page
	 *
	 * @param stdClass $nodeData
	 * @return array
	 */
	public function disableNode($nodeData) {
		/** @var $node t3lib_tree_pagetree_Node */
		$node = t3lib_div::makeInstance('t3lib_tree_pagetree_Node', (array) $nodeData);

		/** @var $dataProvider t3lib_tree_pagetree_DataProvider */
		$dataProvider = t3lib_div::makeInstance('t3lib_tree_pagetree_DataProvider');

		try {
			t3lib_tree_pagetree_Commands::disableNode($node);
			$newNode = t3lib_tree_pagetree_Commands::getNode($node->getId());
			$newNode->setLeaf($node->isLeafNode());
			$returnValue = $newNode->toArray();
		} catch (Exception $exception) {
			$returnValue = array(
				 'success' => FALSE,
				 'message' => $exception->getMessage(),
			 );
		}

		return $returnValue;
	}

	/**
	 * Delete the page
	 *
	 * @param stdClass $nodeData
	 * @return array
	 */
	public function deleteNode($nodeData) {
		/** @var $node t3lib_tree_pagetree_Node */
		$node = t3lib_div::makeInstance('t3lib_tree_pagetree_Node', (array) $nodeData);

		try {
			t3lib_tree_pagetree_Commands::deleteNode($node);

			$returnValue = array();
			if ($GLOBALS['BE_USER']->workspace) {
				$record = t3lib_tree_pagetree_Commands::getNodeRecord($node->getId());
				if ($record['_ORIG_uid']) {
					$newNode = t3lib_tree_pagetree_Commands::getNewNode($record);
					$returnValue = $newNode->toArray();
				}
			}
		} catch (Exception $exception) {
			$returnValue = array(
				 'success' => FALSE,
				 'message' => $exception->getMessage(),
			 );
		}

		return $returnValue;
	}

	/**
	 * Gets title and message for JS deletion confirmation window
	 *
	 * @param  $uid The uid of the record to be deleted
	 * @return array The title and the message for the confirmation window
	 */
	public function getConfirmContentDeletionMessage($uid) {
		return array(
			'title' => $GLOBALS['LANG']->sL('LLL:EXT:cms/layout/locallang.xml:deleteItem'),
			'message' => $GLOBALS['LANG']->sL('LLL:EXT:cms/layout/locallang.xml:deleteWarning')
		);
	}

	/**
	 * Restore the page
	 *
	 * @param stdClass $nodeData
	 * @param int $destination
	 * @return array
	 */
	public function restoreNode($nodeData, $destination) {
		/** @var $node t3lib_tree_pagetree_Node */
		$node = t3lib_div::makeInstance('t3lib_tree_pagetree_Node', (array) $nodeData);

		try {
			t3lib_tree_pagetree_Commands::restoreNode($node, $destination);
			$newNode = t3lib_tree_pagetree_Commands::getNode($node->getId());
			$returnValue = $newNode->toArray();
		} catch (Exception $exception) {
			$returnValue = array(
				 'success' => FALSE,
				 'message' => $exception->getMessage(),
			 );
		}

		return $returnValue;
	}

	/**
	 * Updates the given field with a new text value, may be used to inline update
	 * the title field in the new page tree
	 *
	 * @param stdClass $nodeData
	 * @param string $updatedLabel
	 * @return array
	 */
	public function updateLabel($nodeData, $updatedLabel) {
		if ($updatedLabel === '') {
			return array();
		}

		/** @var $node t3lib_tree_pagetree_Node */
		$node = t3lib_div::makeInstance('t3lib_tree_pagetree_Node', (array) $nodeData);

		try {
			t3lib_tree_pagetree_Commands::updateNodeLabel($node, $updatedLabel);

			$shortendedText = t3lib_div::fixed_lgd_cs($updatedLabel, intval($GLOBALS['BE_USER']->uc['titleLen']));
			$returnValue = array(
				'editableText' => $updatedLabel,
				'updatedText' => htmlspecialchars($shortendedText),
			);
		} catch (Exception $exception) {
			$returnValue = array(
				 'success' => FALSE,
				 'message' => $exception->getMessage(),
			 );
		}

		return $returnValue;
	}

	/**
	 * Sets a temporary mount point
	 *
	 * @param stdClass $nodeData
	 * @return array
	 */
	public static function setTemporaryMountPoint($nodeData) {
		/** @var $node t3lib_tree_pagetree_Node */
		$node = t3lib_div::makeInstance('t3lib_tree_pagetree_Node', (array) $nodeData);
		$GLOBALS['BE_USER']->uc['pageTree_temporaryMountPoint'] = $node->getId();
		$GLOBALS['BE_USER']->writeUC($GLOBALS['BE_USER']->uc);

		return t3lib_tree_pagetree_Commands::getMountPointPath();
	}

	/**
	 * Moves the source node directly as the first child of the destination node
	 *
	 * @param stdClass $nodeData
	 * @param int $destination
	 * @return array
	 */
	public function moveNodeToFirstChildOfDestination($nodeData, $destination) {
		/** @var $node t3lib_tree_pagetree_Node */
		$node = t3lib_div::makeInstance('t3lib_tree_pagetree_Node', (array) $nodeData);

		try {
			t3lib_tree_pagetree_Commands::moveNode($node, $destination);
			$newNode = t3lib_tree_pagetree_Commands::getNode($node->getId(), FALSE);
			$newNode->setLeaf($node->isLeafNode());
			$returnValue = $newNode->toArray();
		} catch (Exception $exception) {
			$returnValue = array(
				 'success' => FALSE,
				 'message' => $exception->getMessage(),
			 );
		}

		return $returnValue;
	}

	/**
	 * Moves the source node directly after the destination node
	 *
	 * @param stdClass $nodeData
	 * @param int $destination
	 * @return void
	 */
	public function moveNodeAfterDestination($nodeData, $destination) {
		/** @var $node t3lib_tree_pagetree_Node */
		$node = t3lib_div::makeInstance('t3lib_tree_pagetree_Node', (array) $nodeData);

		try {
			t3lib_tree_pagetree_Commands::moveNode($node, -$destination);
			$newNode = t3lib_tree_pagetree_Commands::getNode($node->getId(), FALSE);
			$newNode->setLeaf($node->isLeafNode());
			$returnValue = $newNode->toArray();
		} catch (Exception $exception) {
			$returnValue = array(
				 'success' => FALSE,
				 'message' => $exception->getMessage(),
			 );
		}

		return $returnValue;
	}

	/**
	 * Copies the source node directly as the first child of the destination node and
	 * returns the created node.
	 *
	 * @param stdClass $nodeData
	 * @param int $destination
	 * @return array
	 */
	public function copyNodeToFirstChildOfDestination($nodeData, $destination) {
		/** @var $node t3lib_tree_pagetree_Node */
		$node = t3lib_div::makeInstance('t3lib_tree_pagetree_Node', (array) $nodeData);

		/** @var $dataProvider t3lib_tree_pagetree_DataProvider */
		$dataProvider = t3lib_div::makeInstance('t3lib_tree_pagetree_DataProvider');

		try {
			$newPageId = t3lib_tree_pagetree_Commands::copyNode($node, $destination);
			$newNode = t3lib_tree_pagetree_Commands::getNode($newPageId);
			$newNode->setLeaf($node->isLeafNode());
			$returnValue = $newNode->toArray();
		} catch (Exception $exception) {
			$returnValue = array(
				 'success' => FALSE,
				 'message' => $exception->getMessage(),
			 );
		}

		return $returnValue;
	}

	/**
	 * Copies the source node directly after the destination node and returns the
	 * created node.
	 *
	 * @param stdClass $nodeData
	 * @param int $destination
	 * @return array
	 */
	public function copyNodeAfterDestination($nodeData, $destination) {
		/** @var $node t3lib_tree_pagetree_Node */
		$node = t3lib_div::makeInstance('t3lib_tree_pagetree_Node', (array) $nodeData);

		/** @var $dataProvider t3lib_tree_pagetree_DataProvider */
		$dataProvider = t3lib_div::makeInstance('t3lib_tree_pagetree_DataProvider');

		try {
			$newPageId = t3lib_tree_pagetree_Commands::copyNode($node, -$destination);
			$newNode = t3lib_tree_pagetree_Commands::getNode($newPageId);
			$newNode->setLeaf($node->isLeafNode());
			$returnValue = $newNode->toArray();
		} catch (Exception $exception) {
			$returnValue = array(
				 'success' => FALSE,
				 'message' => $exception->getMessage(),
			 );
		}

		return $returnValue;
	}

	/**
	 * Inserts a new node as the first child node of the destination node and returns the created node.
	 *
	 * @param stdClass $parentNodeData
	 * @param int $pageType
	 * @return array
	 */
	public function insertNodeToFirstChildOfDestination($parentNodeData, $pageType) {
		/** @var $parentNode t3lib_tree_pagetree_Node */
		$parentNode = t3lib_div::makeInstance('t3lib_tree_pagetree_Node', (array) $parentNodeData);

		try {
			$newPageId = t3lib_tree_pagetree_Commands::createNode($parentNode, $parentNode->getId(), $pageType);
			$returnValue = t3lib_tree_pagetree_Commands::getNode($newPageId)->toArray();
		} catch (Exception $exception) {
			$returnValue = array(
				 'success' => FALSE,
				 'message' => $exception->getMessage(),
			 );
		}

		return $returnValue;
	}

	/**
	 * Inserts a new node directly after the destination node and returns the created node.
	 *
	 * @param stdClass $parentNodeData
	 * @param int $destination
	 * @param int $pageType
	 * @return array
	 */
	public function insertNodeAfterDestination($parentNodeData, $destination, $pageType) {
		/** @var $parentNode t3lib_tree_pagetree_Node */
		$parentNode = t3lib_div::makeInstance('t3lib_tree_pagetree_Node', (array) $parentNodeData);

		try {
			$newPageId = t3lib_tree_pagetree_Commands::createNode($parentNode, -$destination, $pageType);
			$returnValue = t3lib_tree_pagetree_Commands::getNode($newPageId)->toArray();
		} catch (Exception $exception) {
			$returnValue = array(
				 'success' => FALSE,
				 'message' => $exception->getMessage(),
			 );
		}

		return $returnValue;
	}

	/**
	 * Returns the view link of a given node
	 *
	 * @param stdClass $nodeData
	 * @return string
	 */
	public static function getViewLink($nodeData) {
		/** @var $node t3lib_tree_pagetree_Node */
		$node = t3lib_div::makeInstance('t3lib_tree_pagetree_Node', (array) $nodeData);

		$javascriptLink = t3lib_BEfunc::viewOnClick($node->getId());
		preg_match('/window\.open\(\'([^\']+)\'/i', $javascriptLink, $match);

		return $match[1];
	}
}

if (defined('TYPO3_MODE') && isset($GLOBALS['TYPO3_CONF_VARS'][TYPO3_MODE]['XCLASS']['t3lib/tree/pagetree/extdirect/class.t3lib_tree_pagetree_extdirect_commands.php'])) {
	include_once($GLOBALS['TYPO3_CONF_VARS'][TYPO3_MODE]['XCLASS']['t3lib/tree/pagetree/extdirect/class.t3lib_tree_pagetree_extdirect_commands.php']);
}

?>