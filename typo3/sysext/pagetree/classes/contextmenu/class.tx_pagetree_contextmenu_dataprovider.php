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
 * Context Menu Data Provider for the Page Tree
 *
 * @author Stefan Galinski <stefan.galinski@gmail.com>
 * @package TYPO3
 * @subpackage t3lib
 */
class tx_pagetree_ContextMenu_DataProvider extends t3lib_contextmenu_AbstractDataProvider {
	/**
	 * Old Context Menu Options (access mapping)
	 *
	 * @var array
	 */
	protected $contextMenuMapping = array(
		// @todo should be used (compatibility)
		'view' => 'canBeViewed',
		'edit' => 'canBeEdited',
		'new' => 'canCreateNewPages',
		'info' => 'canShowInfo',
		'copy' => 'canBeCopied',
		'cut' => 'canBeCut',
		'paste' => 'canBePasted',
		'move_wizard' => 'canBeMoved',
		'new_wizard' => 'dsfdsfdsf',
		'mount_as_treeroot' => 'canBeTemporaryMountPoint',
		'hide' => 'canBeDisabled',
		'delete' => 'canBeRemoved',
		'history' => 'canShowHistory',
	);

	/**
	 * Returns the actions for the node
	 *
	 * @param tx_pagetree_Node $node
	 * @return t3lib_contextmenu_ActionCollection
	 */
	public function getActionsForNode(t3lib_tree_Node $node) {
		$contextMenuActions = $this->getNextContextMenuLevel($this->getConfiguration(), $node);

		return $contextMenuActions;
	}

	/**
	 * Evaluates a given display condition and returns true if the condition matches
	 *
	 * Examples:
	 * getContextInfo|inCutMode:1 || isInCopyMode:1
	 * isLeafNode:1
	 * isLeafNode:1 && isInCutMode:1
	 *
	 * @param tx_pagetree_Node $node
	 * @param string $displayCondition
	 * @return boolean
	 */
	protected function evaluateDisplayCondition(tx_pagetree_Node $node, $displayCondition) {
		if ($displayCondition === '') {
			return TRUE;
		}

			// parse condition string
		$conditions = array();
		preg_match_all('/(.+?)(>=|<=|!=|=|>|<)(.+?)(\|\||&&|$)/is', $displayCondition, $conditions);

		$lastResult = FALSE;
		$chainType = '';
		$amountOfConditions = count($conditions[0]);
		for ($i = 0; $i < $amountOfConditions; ++$i) {
				// check method for existence
			$method = trim($conditions[1][$i]);
			list($method, $index) = explode('|', $method);
			if (!method_exists($node, $method)) {
				continue;
			}

				// fetch compare value
			$returnValue = call_user_func(array($node, $method));
			if (is_array($returnValue)) {
				$returnValue = $returnValue[$index];
			}

				// compare fetched and expected values
			$operator = trim($conditions[2][$i]);
			$expected = trim($conditions[3][$i]);
			if ($operator === '=') {
				$returnValue = ($returnValue == $expected);
			} elseif ($operator === '>') {
				$returnValue = ($returnValue > $expected);
			} elseif ($operator === '<') {
				$returnValue = ($returnValue < $expected);
			} elseif ($operator === '>=') {
				$returnValue = ($returnValue >= $expected);
			} elseif ($operator === '<=') {
				$returnValue = ($returnValue <= $expected);
			} elseif ($operator === '!=') {
				$returnValue = ($returnValue != $expected);
			} else {
				$returnValue = FALSE;
				$lastResult = FALSE;
			}

				// chain last result and the current if requested
			if ($chainType === '||') {
				$lastResult = ($lastResult || $returnValue);
			} elseif ($chainType === '&&') {
				$lastResult = ($lastResult && $returnValue);
			} else {
				$lastResult = $returnValue;
			}

				// save chain type for the next condition
			$chainType = trim($conditions[4][$i]);
		}

		return $lastResult;
	}

	/**
	 * Returns the next context menu level
	 *
	 * @param array $actions
	 * @param tx_pagetree_Node $node
	 * @param int $level
	 * @return t3lib_contextmenu_ActionCollection
	 */
	protected function getNextContextMenuLevel(array $actions, tx_pagetree_Node $node, $level = 0) {
		/** @var $actionCollection t3lib_contextmenu_ActionCollection */
		$actionCollection = t3lib_div::makeInstance('t3lib_contextmenu_ActionCollection');

		if ($level > 5) {
			return $actionCollection;
		}

		$type = '';
		foreach ($actions as $index => $actionConfiguration) {
			if (substr($index, -1) !== '.') {
				$type = $actionConfiguration;
				if ($type !== 'DIVIDER') {
					continue;
				}
			}

			if (!in_array($type, array('DIVIDER', 'SUBMENU', 'ITEM'))) {
				continue;
			}

			/** @var $action tx_pagetree_ContextMenu_Action */
			$action = t3lib_div::makeInstance('tx_pagetree_ContextMenu_Action');
			$action->setId($index);

			if ($type === 'DIVIDER') {
				$action->setType('divider');
			} else {
				if (isset($actionConfiguration['displayCondition'])
					&& trim($actionConfiguration['displayCondition']) !== ''
					&& !$this->evaluateDisplayCondition($node, $actionConfiguration['displayCondition'])
				) {
					unset($action);
					continue;
				}

				if ($type === 'SUBMENU') {
					$label = $GLOBALS['LANG']->sL($actionConfiguration['label'], TRUE);
					$action->setType('submenu');
					$action->setChildActions(
						$this->getNextContextMenuLevel($actionConfiguration, $node, ++$level)
					);
				} else {
					$label = $GLOBALS['LANG']->sL($actionConfiguration['label'], TRUE);
					$action->setType('action');
					$action->setCallbackAction($actionConfiguration['callbackAction']);
				}

				$action->setLabel($label);
				if (isset($actionConfiguration['icon']) && trim($actionConfiguration['icon']) !== '') {
					$action->setIcon($actionConfiguration['icon']);
				} elseif (isset($actionConfiguration['spriteIcon'])) {
					$action->setClass(
						t3lib_iconWorks::getSpriteIconClasses($actionConfiguration['spriteIcon'])
					);
				}
			}

			$actionCollection->append($action);
		}

		return $actionCollection;
	}
}

if (defined('TYPO3_MODE') && $TYPO3_CONF_VARS[TYPO3_MODE]['XCLASS']['typo3/sysext/pagetree/classes/class.tx_pagetree_contextmenu_dataprovider.php']) {
	include_once($TYPO3_CONF_VARS[TYPO3_MODE]['XCLASS']['typo3/sysext/pagetree/classes/class.tx_pagetree_contextmenu_dataprovider.php']);
}

?>