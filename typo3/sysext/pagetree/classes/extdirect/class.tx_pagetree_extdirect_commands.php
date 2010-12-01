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
 * @subpackage tx_pagetree
 */
class tx_pagetree_ExtDirect_Commands {
	/**
	 * Visibly the page
	 *
	 * @param stdClass $nodeData
	 * @return array
	 */
	public function visiblyNode($nodeData) {
		/** @var $node tx_pagetree_Node */
		$node = t3lib_div::makeInstance('tx_pagetree_Node', (array) $nodeData);

		try {
			tx_pagetree_Commands::visiblyNode($node);
			$newNode = tx_pagetree_Commands::getNode($node->getId());
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
		/** @var $node tx_pagetree_Node */
		$node = t3lib_div::makeInstance('tx_pagetree_Node', (array) $nodeData);

		try {
			tx_pagetree_Commands::disableNode($node);
			$newNode = tx_pagetree_Commands::getNode($node->getId());
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
		/** @var $node tx_pagetree_Node */
		$node = t3lib_div::makeInstance('tx_pagetree_Node', (array) $nodeData);

		try {
			tx_pagetree_Commands::deleteNode($node);
			$returnValue = array();
		} catch (Exception $exception) {
			$returnValue = array(
				 'success' => FALSE,
				 'message' => $exception->getMessage(),
			 );
		}

		return $returnValue;

	}

	/**
	 * Restore the page
	 *
	 * @param stdClass $nodeData
	 * @return array
	 */
	public function restoreNode($nodeData) {
		/** @var $node tx_pagetree_Node */
		$node = t3lib_div::makeInstance('tx_pagetree_Node', (array) $nodeData);

		try {
			tx_pagetree_Commands::restoreNode($node);
			$newNode = tx_pagetree_Commands::getNode($node->getId());
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
		/** @var $node tx_pagetree_Node */
		$node = t3lib_div::makeInstance('tx_pagetree_Node', (array) $nodeData);

		try {
			tx_pagetree_Commands::updateNodeLabel($node, $updatedLabel);
			$returnValue = array();
		} catch (Exception $exception) {
			$returnValue = array(
				 'success' => FALSE,
				 'message' => $exception->getMessage(),
			 );
		}

		return $returnValue;
	}

	/**
	 * Moves the source node directly as the first child of the destination node
	 *
	 * @param stdClass $nodeData
	 * @param int $destination
	 * @return array
	 */
	public function moveNodeToFirstChildOfDestination($nodeData, $destination) {
		/** @var $node tx_pagetree_Node */
		$node = t3lib_div::makeInstance('tx_pagetree_Node', (array) $nodeData);

		try {
			tx_pagetree_Commands::moveNode($node, $destination);
			$returnValue = array();
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
		/** @var $node tx_pagetree_Node */
		$node = t3lib_div::makeInstance('tx_pagetree_Node', (array) $nodeData);

		try {
			tx_pagetree_Commands::moveNode($node, -$destination);
			$returnValue = array();
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
		/** @var $node tx_pagetree_Node */
		$node = t3lib_div::makeInstance('tx_pagetree_Node', (array) $nodeData);

		try {
			$newPageId = tx_pagetree_Commands::copyNode($node, $destination);
			$returnValue = tx_pagetree_Commands::getNode($newPageId)->toArray();
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
		/** @var $node tx_pagetree_Node */
		$node = t3lib_div::makeInstance('tx_pagetree_Node', (array) $nodeData);

		try {
			$newPageId = tx_pagetree_Commands::copyNode($node, -$destination);
			$returnValue = tx_pagetree_Commands::getNode($newPageId)->toArray();
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
		/** @var $parentNode tx_pagetree_Node */
		$parentNode = t3lib_div::makeInstance('tx_pagetree_Node', (array) $parentNodeData);

		try {
			$newPageId = tx_pagetree_Commands::createNode($parentNode, $parentNode->getId(), $pageType);
			$returnValue = tx_pagetree_Commands::getNode($newPageId)->toArray();
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
		/** @var $parentNode tx_pagetree_Node */
		$parentNode = t3lib_div::makeInstance('tx_pagetree_Node', (array) $parentNodeData);

		try {
			$newPageId = tx_pagetree_Commands::createNode($parentNode, -$destination, $pageType);
			$returnValue = tx_pagetree_Commands::getNode($newPageId)->toArray();
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
	 * @param string $workspacePreview
	 * @return string
	 */
	public static function getViewLink($nodeData, $workspacePreview) {
		// @TODO use the hook to get the needed information's


//		$viewScriptPreviewEnabled  = '/' . TYPO3_mainDir . 'mod/user/ws/wsol_preview.php?id=';
//		$viewScriptPreviewDisabled = '/index.php?id=';
//
//			// check alternate Domains
//		$rootLine = t3lib_BEfunc::BEgetRootLine($id);
//		if ($rootLine) {
//			$parts = parse_url(t3lib_div::getIndpEnv('TYPO3_SITE_URL'));
//			if (t3lib_BEfunc::getDomainStartPage($parts['host'], $parts['path'])) {
//				$preUrl_temp = t3lib_BEfunc::firstDomainRecord($rootLine);
//			}
//		}
//		$preUrl = ($preUrl_temp ? (t3lib_div::getIndpEnv('TYPO3_SSL') ?
//			'https://' : 'http://') . $preUrl_temp : '' . '..');
//
//			// Look if a fixed preview language should be added:
//		$viewLanguageOrder = $GLOBALS['BE_USER']->getTSConfigVal('options.view.languageOrder');
//		if (strlen($viewLanguageOrder))	{
//			$suffix = '';
//
//				// Find allowed languages (if none, all are allowed!)
//			if (!$GLOBALS['BE_USER']->user['admin'] &&
//				strlen($GLOBALS['BE_USER']->groupData['allowed_languages'])) {
//				$allowed_languages = array_flip(explode(',', $GLOBALS['BE_USER']->groupData['allowed_languages']));
//			}
//
//				// Traverse the view order, match first occurrence
//			$lOrder = t3lib_div::intExplode(',',$viewLanguageOrder);
//			foreach($lOrder as $langUid)	{
//				if (is_array($allowed_languages) && count($allowed_languages)) {
//					if (isset($allowed_languages[$langUid])) {	// Choose if set.
//						$suffix = '&L='.$langUid;
//						break;
//					}
//				} else {	// All allowed since no lang. are listed.
//					$suffix = '&L='.$langUid;
//					break;
//				}
//			}
//
//				// Add it:
//			$addGetVars.= $suffix;
//		}
//
//		$urlPreviewEnabled  = $preUrl . $viewScriptPreviewEnabled . $id . $addGetVars;
//		$urlPreviewDisabled = $preUrl . $viewScriptPreviewDisabled . $id . $addGetVars;
//
//		if ($workspacePreview) {
//			return $urlPreviewEnabled;
//		} else {
//			return $urlPreviewDisabled;
//		}

//		$javascriptLink = t3lib_BEfunc::viewOnClick($id);
//		debug($javascriptLink);

		return 'http://linux-schmie.de/wp-content/uploads/2010/07/Baustelle.png';
	}
}

if (defined('TYPO3_MODE') && $TYPO3_CONF_VARS[TYPO3_MODE]['XCLASS']['typo3/sysext/pagetree/classes/extdirect/class.tx_pagetree_extdirect_commands.php']) {
	include_once($TYPO3_CONF_VARS[TYPO3_MODE]['XCLASS']['typo3/sysext/pagetree/classes/extdirect/class.tx_pagetree_extdirect_commands.php']);
}

?>