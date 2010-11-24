<?php
/***************************************************************
 *  Copyright notice
 *
 *  (c) 2010 Steffen Ritter <info@steffen-ritter.net>
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
 * TCA tree data provider
 *
 * @author Steffen Ritter <info@steffen-ritter.net>
 * @package TYPO3
 * @subpackage t3lib_tree
 */

class t3lib_tree_Tca_DatabaseTreeDataProvider extends t3lib_tree_Tca_AbstractTcaTreeDataProvider {

	const MODE_CHILDREN = 1;
	const MODE_PARENT = 2;

	/**
	 * @var string
	 */
	protected $tableName = '';

	/**
	 * @var string
	 */
	protected $treeId = '';

	/**
	 * @var string
	 */
	protected $labelField = '';

	/**
	 * @var string
	 *
	 */
	protected $tableWhere = '';

	/**
	 * @var int
	 */
	protected $lookupMode = t3lib_tree_tca_DatabaseTreeDataProvider::MODE_CHILDREN;

	/**
	 * @var string
	 */
	protected $lookupField = '';

	/**
	 * @var int
	 */
	protected $rootUid = 0;

	/**
	 * @var array
	 */
	protected $idCache = array();

	/**
	 * Sets the label field
	 *
	 * @param string $labelField
	 * @return void
	 */
	public function setLabelField($labelField) {
		$this->labelField = $labelField;
	}

	/**
	 * Gets the label field
	 *
	 * @return string
	 */
	public function getLabelField() {
		return $this->labelField;
	}

	/**
	 * Sets the table name
	 *
	 * @param string $tableName
	 * @return void
	 */
	public function setTableName($tableName) {
		$this->tableName = $tableName;
	}

	/**
	 * Gets the table name
	 *
	 * @return string
	 */
	public function getTableName() {
		return $this->tableName;
	}

	/**
	 * Sets the lookup field
	 *
	 * @param string $lookupField
	 * @return void
	 */
	public function setLookupField($lookupField) {
		$this->lookupField = $lookupField;
	}

	/**
	 * Gets the lookup field
	 *
	 * @return string
	 */
	public function getLookupField() {
		return $this->lookupField;
	}

	/**
	 * Sets the lookup mode
	 *
	 * @param int $lookupMode
	 * @return void
	 */
	public function setLookupMode($lookupMode) {
		$this->lookupMode = $lookupMode;
	}

	/**
	 * Gets the lookup mode
	 *
	 * @return int
	 */
	public function getLookupMode() {
		return $this->lookupMode;
	}


	/**
	 * Gets the nodes
	 *
	 * @param t3lib_tree_Node $node
	 * @return t3lib_tree_NodeCollection
	 */
	public function getNodes(t3lib_tree_Node $node) {

	}

	/**
	 * Gets the root node
	 *
	 * @return t3lib_tree_tca_DatabaseNode
	 */
	public function getRoot() {
		return $this->buildRepresentationForNode($this->treeData);
	}

	/**
	 * Sets the root uid
	 *
	 * @param  $rootUid
	 * @return void
	 */
	public function setRootUid($rootUid) {
		$this->rootUid = $rootUid;
	}

	/**
	 * Gets the root uid
	 *
	 * @return int
	 */
	public function getRootUid() {
		return $this->rootUid;
	}

	/**
	 * Sets the tableWhere clause
	 *
	 * @param string $tableWhere
	 * @return void
	 */
	public function setTableWhere(string $tableWhere) {
		$this->tableWhere = $tableWhere;
	}

	/**
	 * Gets the tableWhere clause
	 *
	 * @return string
	 */
	public function getTableWhere() {
		return $this->tableWhere;
	}

	/**
	 * Builds a complete node including childs
	 *
	 * @param t3lib_tree_Node $basicNode
	 * @param null|t3lib_tree_tca_DatabaseNode $parent
	 * @param int $level
	 * @return A|object
	 */
	protected function buildRepresentationForNode(t3lib_tree_Node $basicNode, t3lib_tree_tca_DatabaseNode $parent = NULL, $level = 0) {
		$node = t3lib_div::makeInstance('t3lib_tree_tca_DatabaseNode');
		$row = array();
		if ($basicNode->getId() == 0) {
			$node->setSelected(FALSE);
			$node->setExpanded(TRUE);
			$node->setLabel($GLOBALS['LANG']->sL($GLOBALS['TCA'][$this->tableName]['ctrl']['title']));
		} else {
			$row = t3lib_BEfunc::getRecordWSOL($this->tableName, $basicNode->getId(), '*');
			if ($this->getLabelField() !== '') {
				$node->setLabel($row[$this->getLabelField()]);
			} else {
				$node->setLabel($basicNode->getId());
			}
			$node->setSelected(t3lib_div::inList($this->getSelectedList(), $basicNode->getId()));
			$node->setExpanded($this->isExpanded($basicNode));
		}
		$node->setSelectable(!t3lib_div::inList($this->getNonSelectableLevelList(), $level));
		$node->setIcon(t3lib_iconWorks::mapRecordTypeToSpriteIconClass($this->tableName, $row));
		$node->setId($basicNode->getId());
		$node->setParentNode($parent);
		if ($basicNode->hasChildNodes()) {
			$node->setHasChildren(TRUE);

			$childNodes = t3lib_div::makeInstance('t3lib_tree_NodeCollection');
			foreach ($basicNode->getChildNodes() as $child) {
				$childNodes->append($this->buildRepresentationForNode($child, $node, $level + 1));
			}
			$node->setChildNodes($childNodes);
		}

		return $node;
	}

	/**
	 * Init the tree data
	 *
	 * @return void
	 */
	public function initializeTreeData() {
		parent::initializeTreeData();

		$this->treeData = t3lib_div::makeInstance('t3lib_tree_Node');
		$this->treeData->setId($this->getRootUid());
		$this->treeData->setParentNode(NULL);
		$childNodes = $this->getChildrenOf($this->treeData, 0);
		if ($childNodes !== NULL) {
			$this->treeData->setChildNodes($childNodes);
		}
	}

	/**
	 * Gets node children
	 *
	 * @param t3lib_tree_Node $node
	 * @param  $level
	 * @return A|null|object
	 */
	protected function getChildrenOf(t3lib_tree_Node $node, $level) {
		$nodeData = NULL;
		if ($node->getId() !== 0) {
			$nodeData = current($GLOBALS['TYPO3_DB']->exec_SELECTgetRows(
									'*',
									$this->tableName,
									'uid=' . $node->getId()
								));
		}
		if ($nodeData == NULL) {
			$nodeData = array(
				'uid' => 0,
				$this->getLookupField() => '',
			);
		}
		$storage = NULL;
		$children = $this->getRelatedRecords($nodeData);
		if (count($children)) {
			$storage = t3lib_div::makeInstance('t3lib_tree_NodeCollection');
			foreach ($children as $child) {
				$node = t3lib_div::makeInstance('t3lib_tree_Node');
				;
				$node->setId($child);
				if ($level <= $this->levelMaximum) {
					$children = $this->getChildrenOf($node, $level + 1);
					if ($children !== NULL) {
						$node->setChildNodes($children);
					}
				}
				$storage->append($node);
			}
		}

		return $storage;
	}

	/**
	 * Gets related records depending on TCA configuration
	 *
	 * @param  $row
	 * @return array
	 */
	protected function getRelatedRecords(array $row) {
		if ($this->getLookupMode() == t3lib_tree_tca_DatabaseTreeDataProvider::MODE_PARENT) {
			$children = $this->getChildrenUidsFromParentRelation($row);
		} else {
			$children = $this->getChildrenUidsFromChildrenRelation($row);
		}

		$allowedArray = array();
		foreach ($children as $child) {
			if (!in_array($child, $this->idCache)) {
				$allowedArray[] = $child;
			}
		}

		$this->idCache = array_merge($this->idCache, $allowedArray);

		return $allowedArray;
	}

	/**
	 * Gets related records depending on TCA configuration
	 *
	 * @param  $row
	 * @return array
	 */
	protected function getChildrenUidsFromParentRelation(array $row) {
		$relatedUids = array();
		$uid = $row['uid'];

		$columnConfiguration = $GLOBALS['TCA'][$this->getTableName()]['columns'][$this->getLookupField()]['config'];
		switch ((string) $columnConfiguration['type']) {
			case 'inline':
			case 'select':
				if ($columnConfiguration['MM']) {
					$dbGroup = t3lib_div::makeInstance('t3lib_loadDBGroup');
					// dummy field for setting "look from other site"
					$columnConfiguration['MM_oppositeField'] = 'children';

					$dbGroup->start(
						$row[$this->getLookupField()],
						$columnConfiguration['foreign_table'],
						$columnConfiguration['MM'],
						$uid, $this->getTableName(),
						$columnConfiguration
					);

					$relatedUids = $dbGroup->tableArray[$columnConfiguration['foreign_table']];
				} elseif ($columnConfiguration['foreign_table'] && $GLOBALS['TCA'][$columnConfiguration['foreign_table']] && $columnConfiguration['foreign_field']) {
					$records = $GLOBALS['TYPO3_DB']->exec_SELECTgetRows(
						'uid',
						$columnConfiguration['foreign_table'],
						" (CONCAT(','," . $columnConfiguration['foreign_field'] . ",',') LIKE '%," . intval($uid) . ",%' "
						. (intval($uid) == 0 ? (" OR " . $columnConfiguration['foreign_field'] . " = ''") : '')
						. ") " . t3lib_BEfunc::deleteClause($columnConfiguration['foreign_table'])
					);
					foreach ($records as $record) {
						$relatedUids[] = $record['uid'];
					}
				} else {
					$records = $GLOBALS['TYPO3_DB']->exec_SELECTgetRows(
						'uid',
						$columnConfiguration['foreign_table'],
						" (CONCAT(','," . $this->getLookupField() . ",',') LIKE '%," . intval($uid) . ",%' "
						. (intval($uid) == 0 ? (" OR " . $this->getLookupField() . " = ''") : '')
						. ") " . t3lib_BEfunc::deleteClause($columnConfiguration['foreign_table'])
					);
					foreach ($records as $record) {
						$relatedUids[] = $record['uid'];
					}
				}
			break;
			case 'group':
				$records = $GLOBALS['TYPO3_DB']->exec_SELECTgetRows(
					'uid',
					$columnConfiguration['foreign_table'],
					" (CONCAT(','," . $this->getLookupField() . ",',') LIKE '%," . intval($uid) . ",%' "
					. (intval($uid) == 0 ? (" OR " . $this->getLookupField() . " = ''") : '')
					. ") " . t3lib_BEfunc::deleteClause($columnConfiguration['foreign_table'])
				);
				foreach ($records as $record) {
					$relatedUids[] = $record['uid'];
				}
			break;
		}

		return $relatedUids;
	}

	/**
	 * Gets related children records depending on TCA configuration
	 *
	 * @param  $row
	 * @return array
	 */
	protected function getChildrenUidsFromChildrenRelation(array $row) {
		$relatedUids = array();
		$uid = $row['uid'];
		$value = $row[$this->getLookupField()];

		$columnConfiguration = $GLOBALS['TCA'][$this->getTableName()]['columns'][$this->getLookupField()]['config'];
		switch ((string) $columnConfiguration['type']) {
			case 'inline':
			case 'select':
				if ($columnConfiguration['MM']) {
					$dbGroup = t3lib_div::makeInstance('t3lib_loadDBGroup');
					$dbGroup->start(
						$value,
						$columnConfiguration['foreign_table'],
						$columnConfiguration['MM'],
						$uid,
						$this->getTableName(),
						$columnConfiguration
					);

					$relatedUids = $dbGroup->tableArray[$columnConfiguration['foreign_table']];
				} elseif ($columnConfiguration['foreign_table'] && $GLOBALS['TCA'][$columnConfiguration['foreign_table']] && $columnConfiguration['foreign_field']) {
					$records = $GLOBALS['TYPO3_DB']->exec_SELECTgetRows(
						'uid',
						$columnConfiguration['foreign_table'],
						$columnConfiguration['foreign_field'] . '=' . intval($uid) . ' ' . t3lib_BEfunc::deleteClause($columnConfiguration['foreign_table'])
					);
					foreach ($records as $record) {
						$relatedUids[] = $record['uid'];
					}
				} else {
					$relatedUids = t3lib_div::intExplode(',', $value, TRUE);
				}
			break;
			case 'group':
				$relatedUids = t3lib_div::intExplode(',', $value, TRUE);
			break;
		}

		return $relatedUids;
	}

}

?>