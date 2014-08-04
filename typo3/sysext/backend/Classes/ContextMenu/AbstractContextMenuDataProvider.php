<?php
namespace TYPO3\CMS\Backend\ContextMenu;

/**
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
use TYPO3\CMS\Backend\Utility\BackendUtility;

/**
 * Abstract Context Menu Data Provider
 *
 * @author Stefan Galinski <stefan.galinski@gmail.com>
 */
abstract class AbstractContextMenuDataProvider {

	/**
	 * List of actions that are generally disabled
	 *
	 * @var array
	 */
	protected $disableItems = array();

	/**
	 * Context Menu Type (e.g. table.pages, table.tt_content)
	 *
	 * @var string
	 */
	protected $contextMenuType = '';

	/**
	 * Returns the context menu type
	 *
	 * @return string
	 */
	public function getContextMenuType() {
		return $this->contextMenuType;
	}

	/**
	 * Sets the context menu type
	 *
	 * @param string $contextMenuType
	 * @return void
	 */
	public function setContextMenuType($contextMenuType) {
		$this->contextMenuType = $contextMenuType;
	}

	/**
	 * Returns the actions of the node
	 *
	 * @param \TYPO3\CMS\Backend\Tree\TreeNode $node
	 * @return \TYPO3\CMS\Backend\ContextMenu\ContextMenuActionCollection
	 */
	abstract public function getActionsForNode(\TYPO3\CMS\Backend\Tree\TreeNode $node);

	/**
	 * Returns the configuration of the specified context menu type
	 *
	 * @return array
	 */
	protected function getConfiguration() {
		$contextMenuActions = $GLOBALS['BE_USER']->getTSConfig('options.contextMenu.' . $this->contextMenuType . '.items');
		return $contextMenuActions['properties'];
	}

	/**
	 * Evaluates a given display condition and returns TRUE if the condition matches
	 *
	 * Examples:
	 * getContextInfo|inCutMode:1 || isInCopyMode:1
	 * isLeafNode:1
	 * isLeafNode:1 && isInCutMode:1
	 *
	 * @param \TYPO3\CMS\Backend\Tree\TreeNode $node
	 * @param string $displayCondition
	 * @return boolean
	 */
	protected function evaluateDisplayCondition(\TYPO3\CMS\Backend\Tree\TreeNode $node, $displayCondition) {
		if ($displayCondition === '') {
			return TRUE;
		}
		// Parse condition string
		$conditions = array();
		preg_match_all('/(.+?)(>=|<=|!=|=|>|<)(.+?)(\\|\\||&&|$)/is', $displayCondition, $conditions);
		$lastResult = FALSE;
		$chainType = '';
		$amountOfConditions = count($conditions[0]);
		for ($i = 0; $i < $amountOfConditions; ++$i) {
			// Check method for existence
			$method = trim($conditions[1][$i]);
			list($method, $index) = explode('|', $method);
			if (!method_exists($node, $method)) {
				continue;
			}
			// Fetch compare value
			$returnValue = call_user_func(array($node, $method));
			if (is_array($returnValue)) {
				$returnValue = $returnValue[$index];
			}
			// Compare fetched and expected values
			$operator = trim($conditions[2][$i]);
			$expected = trim($conditions[3][$i]);
			if ($operator === '=') {
				$returnValue = $returnValue == $expected;
			} elseif ($operator === '>') {
				$returnValue = $returnValue > $expected;
			} elseif ($operator === '<') {
				$returnValue = $returnValue < $expected;
			} elseif ($operator === '>=') {
				$returnValue = $returnValue >= $expected;
			} elseif ($operator === '<=') {
				$returnValue = $returnValue <= $expected;
			} elseif ($operator === '!=') {
				$returnValue = $returnValue != $expected;
			} else {
				$returnValue = FALSE;
				$lastResult = FALSE;
			}
			// Chain last result and the current if requested
			if ($chainType === '||') {
				$lastResult = $lastResult || $returnValue;
			} elseif ($chainType === '&&') {
				$lastResult = $lastResult && $returnValue;
			} else {
				$lastResult = $returnValue;
			}
			// Save chain type for the next condition
			$chainType = trim($conditions[4][$i]);
		}
		return $lastResult;
	}

	/**
	 * Returns the next context menu level
	 *
	 * @param array $actions
	 * @param \TYPO3\CMS\Backend\Tree\TreeNode $node
	 * @param integer $level
	 * @return \TYPO3\CMS\Backend\ContextMenu\ContextMenuActionCollection
	 */
	protected function getNextContextMenuLevel(array $actions, \TYPO3\CMS\Backend\Tree\TreeNode $node, $level = 0) {
		/** @var $actionCollection \TYPO3\CMS\Backend\ContextMenu\ContextMenuActionCollection */
		$actionCollection = \TYPO3\CMS\Core\Utility\GeneralUtility::makeInstance('TYPO3\\CMS\\Backend\\ContextMenu\\ContextMenuActionCollection');
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
			/** @var $action \TYPO3\CMS\Backend\ContextMenu\ContextMenuAction */
			$action = \TYPO3\CMS\Core\Utility\GeneralUtility::makeInstance('TYPO3\\CMS\\Backend\\ContextMenu\\ContextMenuAction');
			$action->setId($index);
			if ($type === 'DIVIDER') {
				$action->setType('divider');
			} else {
				if (in_array($actionConfiguration['name'], $this->disableItems) || isset($actionConfiguration['displayCondition']) && trim($actionConfiguration['displayCondition']) !== '' && !$this->evaluateDisplayCondition($node, $actionConfiguration['displayCondition'])) {
					unset($action);
					continue;
				}
				$label = $GLOBALS['LANG']->sL($actionConfiguration['label'], TRUE);
				if ($type === 'SUBMENU') {
					$action->setType('submenu');
					$action->setChildActions($this->getNextContextMenuLevel($actionConfiguration, $node, $level + 1));
				} else {
					$action->setType('action');
					$action->setCallbackAction($actionConfiguration['callbackAction']);
					if (is_array($actionConfiguration['customAttributes.'])) {
						if (!empty($actionConfiguration['customAttributes.']['contentUrl'])) {
							$actionConfiguration['customAttributes.']['contentUrl'] = $this->replaceModuleTokenInContentUrl($actionConfiguration['customAttributes.']['contentUrl']);
						}
						$action->setCustomAttributes($actionConfiguration['customAttributes.']);
					}
				}
				$action->setLabel($label);
				if (isset($actionConfiguration['icon']) && trim($actionConfiguration['icon']) !== '') {
					$action->setIcon($actionConfiguration['icon']);
				} elseif (isset($actionConfiguration['spriteIcon'])) {
					$action->setClass(\TYPO3\CMS\Backend\Utility\IconUtility::getSpriteIconClasses($actionConfiguration['spriteIcon']));
				}
			}
			$actionCollection->offsetSet($level . (int)$index, $action);
			$actionCollection->ksort();
		}
		return $actionCollection;
	}

	/**
	 * Add the CSRF token to the module URL if a "M" parameter is found
	 *
	 * @param string $contentUrl
	 * @return string
	 */
	protected function replaceModuleTokenInContentUrl($contentUrl) {
		$parsedUrl = parse_url($contentUrl);
		parse_str($parsedUrl['query'], $urlParameters);
		if (isset($urlParameters['M'])) {
			$moduleName = $urlParameters['M'];
			unset($urlParameters['M']);
			$contentUrl = BackendUtility::getModuleUrl($moduleName, $urlParameters);
		}
		return $contentUrl;
	}
}
