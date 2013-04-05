<?php
namespace TYPO3\CMS\Backend\Tree\Pagetree;

/***************************************************************
 *  Copyright notice
 *
 *  (c) 2010-2013 TYPO3 Tree Team <http://forge.typo3.org/projects/typo3v4-extjstrees>
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
 * Page Tree and Context Menu Commands
 *
 * @author Stefan Galinski <stefan.galinski@gmail.com>
 */
class Commands {

	/**
	 * @var boolean|null
	 */
	static protected $useNavTitle = NULL;

	/**
	 * @var boolean|null
	 */
	static protected $addIdAsPrefix = NULL;

	/**
	 * @var boolean|null
	 */
	static protected $addDomainName = NULL;

	/**
	 * @var array|null
	 */
	static protected $backgroundColors = NULL;

	/**
	 * @var integer|null
	 */
	static protected $titleLength = NULL;

	/**
	 * Visibly the page
	 *
	 * @param \TYPO3\CMS\Backend\Tree\Pagetree\PagetreeNode $node
	 * @return void
	 */
	static public function visiblyNode(\TYPO3\CMS\Backend\Tree\Pagetree\PagetreeNode $node) {
		$data['pages'][$node->getWorkspaceId()]['hidden'] = 0;
		self::processTceCmdAndDataMap(array(), $data);
	}

	/**
	 * Hide the page
	 *
	 * @param \TYPO3\CMS\Backend\Tree\Pagetree\PagetreeNode $node
	 * @return void
	 */
	static public function disableNode(\TYPO3\CMS\Backend\Tree\Pagetree\PagetreeNode $node) {
		$data['pages'][$node->getWorkspaceId()]['hidden'] = 1;
		self::processTceCmdAndDataMap(array(), $data);
	}

	/**
	 * Delete the page
	 *
	 * @param \TYPO3\CMS\Backend\Tree\Pagetree\PagetreeNode $node
	 * @return void
	 */
	static public function deleteNode(\TYPO3\CMS\Backend\Tree\Pagetree\PagetreeNode $node) {
		$cmd['pages'][$node->getId()]['delete'] = 1;
		self::processTceCmdAndDataMap($cmd);
	}

	/**
	 * Restore the page
	 *
	 * @param \TYPO3\CMS\Backend\Tree\Pagetree\PagetreeNode $node
	 * @param integer $targetId
	 * @return void
	 */
	static public function restoreNode(\TYPO3\CMS\Backend\Tree\Pagetree\PagetreeNode $node, $targetId) {
		$cmd['pages'][$node->getId()]['undelete'] = 1;
		self::processTceCmdAndDataMap($cmd);
		if ($node->getId() !== $targetId) {
			self::moveNode($node, $targetId);
		}
	}

	/**
	 * Updates the node label
	 *
	 * @param \TYPO3\CMS\Backend\Tree\Pagetree\PagetreeNode $node
	 * @param string $updatedLabel
	 * @return void
	 */
	static public function updateNodeLabel(\TYPO3\CMS\Backend\Tree\Pagetree\PagetreeNode $node, $updatedLabel) {
		$data['pages'][$node->getWorkspaceId()][$node->getTextSourceField()] = $updatedLabel;
		self::processTceCmdAndDataMap(array(), $data);
	}

	/**
	 * Copies a page and returns the id of the new page
	 *
	 * Node: Use a negative target id to specify a sibling target else the parent is used
	 *
	 * @param \TYPO3\CMS\Backend\Tree\Pagetree\PagetreeNode $sourceNode
	 * @param integer $targetId
	 * @return integer
	 */
	static public function copyNode(\TYPO3\CMS\Backend\Tree\Pagetree\PagetreeNode $sourceNode, $targetId) {
		$cmd['pages'][$sourceNode->getId()]['copy'] = $targetId;
		$returnValue = self::processTceCmdAndDataMap($cmd);
		return $returnValue['pages'][$sourceNode->getId()];
	}

	/**
	 * Moves a page
	 *
	 * Node: Use a negative target id to specify a sibling target else the parent is used
	 *
	 * @param \TYPO3\CMS\Backend\Tree\Pagetree\PagetreeNode $sourceNode
	 * @param integer $targetId
	 * @return void
	 */
	static public function moveNode(\TYPO3\CMS\Backend\Tree\Pagetree\PagetreeNode $sourceNode, $targetId) {
		$cmd['pages'][$sourceNode->getId()]['move'] = $targetId;
		self::processTceCmdAndDataMap($cmd);
	}

	/**
	 * Creates a page of the given doktype and returns the id of the created page
	 *
	 * @param \TYPO3\CMS\Backend\Tree\Pagetree\PagetreeNode $parentNode
	 * @param integer $targetId
	 * @param integer $pageType
	 * @return integer
	 */
	static public function createNode(\TYPO3\CMS\Backend\Tree\Pagetree\PagetreeNode $parentNode, $targetId, $pageType) {
		$placeholder = 'NEW12345';
		$pid = intval($parentNode->getWorkspaceId());
		$targetId = intval($targetId);

		// Use page TsConfig as default page initialization
		$pageTs = \TYPO3\CMS\Backend\Utility\BackendUtility::getPagesTSconfig($pid);
		if (array_key_exists('TCAdefaults.', $pageTs) && array_key_exists('pages.', $pageTs['TCAdefaults.'])) {
			$data['pages'][$placeholder] = $pageTs['TCAdefaults.']['pages.'];
		} else {
			$data['pages'][$placeholder] = array();
		}

		$data['pages'][$placeholder]['pid'] = $pid;
		$data['pages'][$placeholder]['doktype'] = $pageType;
		$data['pages'][$placeholder]['title'] = $GLOBALS['LANG']->sL('LLL:EXT:lang/locallang_core.xlf:tree.defaultPageTitle', TRUE);
		$newPageId = self::processTceCmdAndDataMap(array(), $data);
		$node = self::getNode($newPageId[$placeholder]);
		if ($pid !== $targetId) {
			self::moveNode($node, $targetId);
		}

		return $newPageId[$placeholder];
	}

	/**
	 * Process TCEMAIN commands and data maps
	 *
	 * Command Map:
	 * Used for moving, recover, remove and some more operations.
	 *
	 * Data Map:
	 * Used for creating and updating records,
	 *
	 * This API contains all necessary access checks.
	 *
	 * @param array $cmd
	 * @param array $data
	 * @return array
	 * @throws \RuntimeException if an error happened while the TCE processing
	 */
	static protected function processTceCmdAndDataMap(array $cmd, array $data = array()) {
		/** @var $tce \TYPO3\CMS\Core\DataHandling\DataHandler */
		$tce = \TYPO3\CMS\Core\Utility\GeneralUtility::makeInstance('TYPO3\\CMS\\Core\\DataHandling\\DataHandler');
		$tce->stripslashes_values = 0;
		$tce->start($data, $cmd);
		$tce->copyTree = \TYPO3\CMS\Core\Utility\MathUtility::forceIntegerInRange($GLOBALS['BE_USER']->uc['copyLevels'], 0, 100);
		if (count($cmd)) {
			$tce->process_cmdmap();
			$returnValues = $tce->copyMappingArray_merged;
		} elseif (count($data)) {
			$tce->process_datamap();
			$returnValues = $tce->substNEWwithIDs;
		} else {
			$returnValues = array();
		}
		// check errors
		if (count($tce->errorLog)) {
			throw new \RuntimeException(implode(chr(10), $tce->errorLog), 1333754629);
		}
		return $returnValues;
	}

	/**
	 * Returns a node from the given node id
	 *
	 * @param integer $nodeId
	 * @param boolean $unsetMovePointers
	 * @return \TYPO3\CMS\Backend\Tree\Pagetree\PagetreeNode
	 */
	static public function getNode($nodeId, $unsetMovePointers = TRUE) {
		$record = self::getNodeRecord($nodeId, $unsetMovePointers);
		return self::getNewNode($record);
	}

	/**
	 * Returns the mount point path for a temporary mount or the given id
	 *
	 * @param integer $uid
	 * @return string
	 */
	static public function getMountPointPath($uid = -1) {
		if ($uid === -1) {
			$uid = intval($GLOBALS['BE_USER']->uc['pageTree_temporaryMountPoint']);
		}
		if ($uid <= 0) {
			return '';
		}
		if (self::$useNavTitle === NULL) {
			self::$useNavTitle = $GLOBALS['BE_USER']->getTSConfigVal('options.pageTree.showNavTitle');
		}
		$rootline = array_reverse(\TYPO3\CMS\Backend\Utility\BackendUtility::BEgetRootLine($uid));
		array_shift($rootline);
		$path = array();
		foreach ($rootline as $rootlineElement) {
			$record = self::getNodeRecord($rootlineElement['uid']);
			$text = $record['title'];
			if (self::$useNavTitle && trim($record['nav_title']) !== '') {
				$text = $record['nav_title'];
			}
			$path[] = htmlspecialchars($text);
		}
		return '/' . implode('/', $path);
	}

	/**
	 * Returns a node record from a given id
	 *
	 * @param integer $nodeId
	 * @param boolean $unsetMovePointers
	 * @return array
	 */
	static public function getNodeRecord($nodeId, $unsetMovePointers = TRUE) {
		$record = \TYPO3\CMS\Backend\Utility\BackendUtility::getRecordWSOL('pages', $nodeId, '*', '', TRUE, $unsetMovePointers);
		return $record;
	}

	/**
	 * Returns the first configured domain name for a page
	 *
	 * @param integer $uid
	 * @return string
	 */
	static public function getDomainName($uid) {
		$whereClause = 'pid=' . intval($uid) . \TYPO3\CMS\Backend\Utility\BackendUtility::deleteClause('sys_domain') . \TYPO3\CMS\Backend\Utility\BackendUtility::BEenableFields('sys_domain');
		$domain = $GLOBALS['TYPO3_DB']->exec_SELECTgetSingleRow('domainName', 'sys_domain', $whereClause, '', 'sorting');
		return is_array($domain) ? htmlspecialchars($domain['domainName']) : '';
	}

	/**
	 * Creates a node with the given record information
	 *
	 * @param array $record
	 * @param integer $mountPoint
	 * @return \TYPO3\CMS\Backend\Tree\Pagetree\PagetreeNode
	 */
	static public function getNewNode($record, $mountPoint = 0) {
		if (self::$titleLength === NULL) {
			self::$useNavTitle = $GLOBALS['BE_USER']->getTSConfigVal('options.pageTree.showNavTitle');
			self::$addIdAsPrefix = $GLOBALS['BE_USER']->getTSConfigVal('options.pageTree.showPageIdWithTitle');
			self::$addDomainName = $GLOBALS['BE_USER']->getTSConfigVal('options.pageTree.showDomainNameWithTitle');
			self::$backgroundColors = $GLOBALS['BE_USER']->getTSConfigProp('options.pageTree.backgroundColor');
			self::$titleLength = intval($GLOBALS['BE_USER']->uc['titleLen']);
		}
		/** @var $subNode \TYPO3\CMS\Backend\Tree\Pagetree\PagetreeNode */
		$subNode = \TYPO3\CMS\Core\Utility\GeneralUtility::makeInstance('TYPO3\\CMS\\Backend\\Tree\\Pagetree\\PagetreeNode');
		$subNode->setRecord($record);
		$subNode->setCls($record['_CSSCLASS']);
		$subNode->setType('pages');
		$subNode->setId($record['uid']);
		$subNode->setMountPoint($mountPoint);
		$subNode->setWorkspaceId($record['_ORIG_uid'] ? $record['_ORIG_uid'] : $record['uid']);
		$subNode->setBackgroundColor(self::$backgroundColors[$record['uid']]);
		$field = 'title';
		$text = $record['title'];
		if (self::$useNavTitle && trim($record['nav_title']) !== '') {
			$field = 'nav_title';
			$text = $record['nav_title'];
		}
		if (trim($text) === '') {
			$visibleText = '[' . $GLOBALS['LANG']->sL('LLL:EXT:lang/locallang_core.xlf:labels.no_title', TRUE) . ']';
		} else {
			$visibleText = $text;
		}
		$visibleText = \TYPO3\CMS\Core\Utility\GeneralUtility::fixed_lgd_cs($visibleText, self::$titleLength);
		$suffix = '';
		if (self::$addDomainName) {
			$domain = self::getDomainName($record['uid']);
			$suffix = $domain !== '' ? ' [' . $domain . ']' : '';
		}
		$qtip = str_replace(' - ', '<br />', htmlspecialchars(\TYPO3\CMS\Backend\Utility\BackendUtility::titleAttribForPages($record, '', FALSE)));
		$prefix = '';
		$lockInfo = \TYPO3\CMS\Backend\Utility\BackendUtility::isRecordLocked('pages', $record['uid']);
		if (is_array($lockInfo)) {
			$qtip .= '<br />' . htmlspecialchars($lockInfo['msg']);
			$prefix .= \TYPO3\CMS\Backend\Utility\IconUtility::getSpriteIcon('status-warning-in-use', array(
				'class' => 'typo3-pagetree-status'
			));
		}
		// Call stats information hook
		$stat = '';
		if (is_array($GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS']['GLOBAL']['recStatInfoHooks'])) {
			$_params = array('pages', $record['uid']);
			$fakeThis = NULL;
			foreach ($GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS']['GLOBAL']['recStatInfoHooks'] as $_funcRef) {
				$stat .= \TYPO3\CMS\Core\Utility\GeneralUtility::callUserFunction($_funcRef, $_params, $fakeThis);
			}
		}
		$prefix .= htmlspecialchars(self::$addIdAsPrefix ? '[' . $record['uid'] . '] ' : '');
		$subNode->setEditableText($text);
		$subNode->setText(htmlspecialchars($visibleText), $field, $prefix, htmlspecialchars($suffix) . $stat);
		$subNode->setQTip($qtip);
		if ($record['uid'] !== 0) {
			$spriteIconCode = \TYPO3\CMS\Backend\Utility\IconUtility::getSpriteIconForRecord('pages', $record);
		} else {
			$spriteIconCode = \TYPO3\CMS\Backend\Utility\IconUtility::getSpriteIcon('apps-pagetree-root');
		}
		$subNode->setSpriteIconCode($spriteIconCode);
		if (!$subNode->canCreateNewPages() || intval($record['t3ver_state']) === 2) {
			$subNode->setIsDropTarget(FALSE);
		}
		if (!$subNode->canBeEdited() || !$subNode->canBeRemoved() || intval($record['t3ver_state']) === 2) {
			$subNode->setDraggable(FALSE);
		}
		return $subNode;
	}

}


?>