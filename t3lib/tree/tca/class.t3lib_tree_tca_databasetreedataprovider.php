<?php
/***************************************************************
 *  Copyright notice
 *
 *  (c) 2010-2011 Steffen Ritter <info@steffen-ritter.net>
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
	 * @var t3lib_tree_Node
	 */
	protected $rootNode;

	/**
	 * @var int $rootLevel
	 */
	protected $rootLevel = 0;
	
	/**
	 * @var array
	 */
	protected $idCache = array();


	/**
	 * Stores TCA-Configuration of the LookUpField in tableName
	 *
	 * @var array
	 */
	protected $columnConfiguration;

	/**
	 * node sort values (the orderings from foreign_Table_where evaluation)
	 *
	 * @var array
	 */
	protected $nodeSortValues = array();

	/**
	 * @var array TCEforms compiled TSConfig array
	 */
	protected $generatedTSConfig = array();

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
	 *
	 * @return t3lib_tree_Node
	 */
	public function getRoot() {
		if ($this->rootNode == NULL) {
			$this->rootNode = $this->treeData->find($this->getRootUid());
			$this->rootLevel = $this->rootNode->getLevel();
		}
		return $this->rootNode;
	}

	/**
	 * Gets the root node
	 *
	 * @param int $levelMaximum
	 * @return t3lib_tree_tca_DatabaseNode
	 */
	public function renderComplete($levelMaximum = 999) {
		return $this->buildRepresentationForNode($this->getRoot(), $levelMaximum, NULL, $this->getRoot()->getLevel());
	}


	/**
	 * @param $nodeId
	 * @param int $levelMaximum
	 * @return t3lib_tree_tca_DatabaseNode
	 */
	public function renderNode($nodeId, $levelMaximum = 999) {
		$startingPoint = $this->getRoot()->find($nodeId);
		return $this->buildRepresentationForNode($startingPoint, $levelMaximum, NULL, $startingPoint->getLevel());
	}

	/**
	 * Sets the root uid
	 *
	 * @param  $rootUid
	 * @return void
	 */
	public function setRootUid($rootUid) {
		$this->rootUid = $rootUid;
		$this->rootNode = NULL;
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
	 * @param int $levelMaximum
	 * @param t3lib_tree_tca_DatabaseNode $parent (optional)
	 * @param int $level
	 * @return t3lib_tree_tca_DatabaseNode
	 */
	protected function buildRepresentationForNode(t3lib_tree_Node $basicNode, $levelMaximum = 999, t3lib_tree_tca_DatabaseNode $parent = NULL, $level = 0) {
		/** @var $node t3lib_tree_tca_DatabaseNode */
		$node = t3lib_div::makeInstance('t3lib_tree_tca_DatabaseNode');
		$node->setExpandable($basicNode->hasChildNodes());

		$row = array();
		if ($basicNode->getId() == 0) {
			$node->setChecked(NULL);
			$node->setExpanded(TRUE);
			$node->setText($GLOBALS['LANG']->sL($GLOBALS['TCA'][$this->tableName]['ctrl']['title']));
			$node->setLeaf(FALSE);
		} else {
			$row = t3lib_BEfunc::getRecordWSOL($this->tableName, $basicNode->getId(), '*', '', FALSE);
			$node->setRecord($row);
			$node->setSourceTable($this->tableName);
			if ($this->getLabelField() !== '') {
				$node->setText($row[$this->getLabelField()]);
				$node->setTextSourceField($this->getLabelField());
			} else {
				$node->setText($basicNode->getId());
			}

			$node->setChecked(t3lib_div::inList($this->getSelectedList(), $basicNode->getId()));
			$node->setExpanded($this->isExpanded($basicNode) && $basicNode->hasChildNodes());
		}

		$node->setDepth($basicNode->getLevel() - $this->rootLevel);
		$node->setId($basicNode->getId());

		if (t3lib_div::inList($this->getNonSelectableLevelList(), $level) || t3lib_div::inList($this->getNonSelectableLevelList(), '*') || in_array($basicNode->getId(), $this->getItemUnselectableList())) {
			$node->setChecked(NULL);
		}

		if (count($this->nodeSortValues)) {
			$node->setSortValue($this->nodeSortValues[$basicNode->getId()]);
		} elseif (isset($GLOBALS['TCA'][$this->getTableName()]['ctrl']['sortby'])) {
			$node->setSortValue($row[$GLOBALS['TCA'][$this->getTableName()]['ctrl']['sortby']]);
		}

		$node->setIconCls(t3lib_iconWorks::mapRecordTypeToSpriteIconClass($this->tableName, $row));
		$node->setParentNode($parent);
		if ($basicNode->hasChildNodes()) {
			$node->setLeaf(FALSE);

			/**
			 * @var t3lib_tree_SortedNodeCollection $childNodes
			 */
			$childNodes = t3lib_div::makeInstance('t3lib_tree_SortedNodeCollection');
			if ($level - 1 - $this->rootLevel < $levelMaximum) {
				foreach ($basicNode->getChildNodes() as $child) {
					$childNodes->append($this->buildRepresentationForNode($child, $levelMaximum, $node, $level + 1));
				}
				$node->setChildNodes($childNodes);
			}

		} else {
			$node->setLeaf(TRUE);
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
		$this->nodeSortValues = array_flip($this->itemWhiteList);

		$this->columnConfiguration = $GLOBALS['TCA'][$this->getTableName()]['columns'][$this->getLookupField()]['config'];
		if (isset($this->columnConfiguration['foreign_table']) && $this->columnConfiguration['foreign_table'] != $this->getTableName()) {
			throw new InvalidArgumentException(
				'TCA Tree configuration is invalid: tree for different node-Tables is not implemented yet',
				1290944650
			);
		}

		/** @var $treeData t3lib_tree_Node */
		$this->treeData = t3lib_div::makeInstance('t3lib_tree_Node');
		$this->treeData->setId(0);
		$this->treeData->setParentNode(NULL);
		$childNodes = $this->getChildrenOf($this->treeData, 0);
		if ($childNodes !== NULL) {
			$this->treeData->setChildNodes($childNodes);
		}
	}

	/**
	 * Gets node children
	 *
	 * @param t3lib_tree_Node $parentNode
	 * @param  $level
	 * @return t3lib_tree_NodeCollection
	 */
	protected function getChildrenOf(t3lib_tree_Node $parentNode, $level) {
		$nodeData = NULL;
		if ($parentNode->getId() !== 0) {
			$nodeData = $GLOBALS['TYPO3_DB']->exec_SELECTgetSingleRow(
				'*',
				$this->tableName,
				'uid=' . $parentNode->getId()
			);
		}
		if ($nodeData == NULL) {
			$nodeData = array(
				'uid' => 0,
				$this->getLookupField() => '',
			);
		}

		/** @var $storage t3lib_tree_NodeCollection */
		$storage = t3lib_div::makeInstance('t3lib_tree_NodeCollection');

		$children = $this->getRelatedRecords($nodeData);
		if (count($children)) {
			foreach ($children as $child) {
				/** @var $node t3lib_tree_Node */
				$node = t3lib_div::makeInstance('t3lib_tree_Node');
				$node->setParentNode($parentNode);

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
	 * @param array $row
	 * @return array
	 */
	protected function getRelatedRecords(array $row) {
		/** @var t3lib_TcaRelationService $relationService */
		$relationService = t3lib_div::makeInstance('t3lib_TcaRelationService', $this->tableName, NULL, $this->tableName, $this->lookupField);
		if ($this->getLookupMode() == t3lib_tree_tca_DatabaseTreeDataProvider::MODE_PARENT) {
			$children = $relationService->getRecordUidsWithRelationToCurrentRecord($row);
		} else {
			$children = $relationService->getRecordUidsWithRelationFromCurrentRecord($row);
		}
		
		$allowedArray = array();
		foreach ($children as $child) {
			if (!in_array($child, $this->idCache) && (count($this->itemWhiteList) == 0 || in_array($child, $this->itemWhiteList))) {
				$allowedArray[] = $child;
			}
		}

		$this->idCache = array_merge($this->idCache, $allowedArray);

		return $allowedArray;
	}

	/**
	 * Gets related records depending on TCA configuration
	 *
	 * @param array $row
	 * @deprecated since 4.7, will be removed as of 4.9
	 * @return array
	 */
	protected function getChildrenUidsFromParentRelation(array $row) {
		t3lib_div::logDeprecatedFunction();
		/** @var t3lib_TcaRelationService $relationService */
		$relationService = t3lib_div::makeInstance('t3lib_TcaRelationService', $this->tableName, $this->lookupField, $this->tableName);
		return $relationService->getRecordUidsWithRelationToCurrentRecord($row);
	}

	/**
	 * Gets related children records depending on TCA configuration
	 *
	 * @param array $row
	 * @deprecated
	 * @return array
	 */
	protected function getChildrenUidsFromChildrenRelation(array $row) {
		t3lib_div::logDeprecatedFunction();
		/** @var t3lib_TcaRelationService $relationService */
		$relationService = t3lib_div::makeInstance('t3lib_TcaRelationService', $this->tableName, $this->lookupField, $this->tableName);
		return $relationService->getRecordUidsWithRelationFromCurrentRecord($row);
	}

	/**
	 * Queries the table for an field which might contain a list.
	 *
	 * @param string $fieldName the name of the field to be queried
	 * @param int $queryId the uid to search for
	 *
	 * @return int[] all uids found
	 * @deprecated Deprecated as of TYPO3 4.7, will be removed with v. 4.9
	 */
	protected function listFieldQuery($fieldName, $queryId) {
		t3lib_div::logDeprecatedFunction();
		$records = $GLOBALS['TYPO3_DB']->exec_SELECTgetRows(
			'uid',
			$this->getTableName(),
			$GLOBALS['TYPO3_DB']->listQuery($fieldName, intval($queryId), $this->getTableName())
				. (intval($queryId) == 0 ? (' OR ' . $fieldName . ' = \'\'') : '')
		);
		$uidArray = array();
		foreach ($records as $record) {
			$uidArray[] = $record['uid'];
		}
		return $uidArray;
	}
}

?>