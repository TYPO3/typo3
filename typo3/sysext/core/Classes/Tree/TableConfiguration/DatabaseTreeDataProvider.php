<?php
namespace TYPO3\CMS\Core\Tree\TableConfiguration;

/***************************************************************
 *  Copyright notice
 *
 *  (c) 2010-2013 Steffen Ritter <info@steffen-ritter.net>
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
 */
class DatabaseTreeDataProvider extends \TYPO3\CMS\Core\Tree\TableConfiguration\AbstractTableConfigurationTreeDataProvider {

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
	 */
	protected $tableWhere = '';

	/**
	 * @var integer
	 */
	protected $lookupMode = self::MODE_CHILDREN;

	/**
	 * @var string
	 */
	protected $lookupField = '';

	/**
	 * @var integer
	 */
	protected $rootUid = 0;

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
	 * @param integer $lookupMode
	 * @return void
	 */
	public function setLookupMode($lookupMode) {
		$this->lookupMode = $lookupMode;
	}

	/**
	 * Gets the lookup mode
	 *
	 * @return integer
	 */
	public function getLookupMode() {
		return $this->lookupMode;
	}

	/**
	 * Gets the nodes
	 *
	 * @param \TYPO3\CMS\Backend\Tree\TreeNode $node
	 * @return \TYPO3\CMS\Backend\Tree\TreeNodeCollection
	 */
	public function getNodes(\TYPO3\CMS\Backend\Tree\TreeNode $node) {

	}

	/**
	 * Gets the root node
	 *
	 * @return \TYPO3\CMS\Core\Tree\TableConfiguration\DatabaseTreeNode
	 */
	public function getRoot() {
		return $this->buildRepresentationForNode($this->treeData);
	}

	/**
	 * Sets the root uid
	 *
	 * @param integer $rootUid
	 * @return void
	 */
	public function setRootUid($rootUid) {
		$this->rootUid = $rootUid;
	}

	/**
	 * Gets the root uid
	 *
	 * @return integer
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
	public function setTableWhere($tableWhere) {
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
	 * @param \TYPO3\CMS\Backend\Tree\TreeNode $basicNode
	 * @param NULL|\TYPO3\CMS\Core\Tree\TableConfiguration\DatabaseTreeNode $parent
	 * @param integer $level
	 * @return \TYPO3\CMS\Core\Tree\TableConfiguration\DatabaseTreeNode Node object
	 */
	protected function buildRepresentationForNode(\TYPO3\CMS\Backend\Tree\TreeNode $basicNode, \TYPO3\CMS\Core\Tree\TableConfiguration\DatabaseTreeNode $parent = NULL, $level = 0) {
		/** @var $node \TYPO3\CMS\Core\Tree\TableConfiguration\DatabaseTreeNode */
		$node = \TYPO3\CMS\Core\Utility\GeneralUtility::makeInstance('TYPO3\\CMS\\Core\\Tree\\TableConfiguration\\DatabaseTreeNode');
		$row = array();
		if ($basicNode->getId() == 0) {
			$node->setSelected(FALSE);
			$node->setExpanded(TRUE);
			$node->setLabel($GLOBALS['LANG']->sL($GLOBALS['TCA'][$this->tableName]['ctrl']['title']));
		} else {
			$row = \TYPO3\CMS\Backend\Utility\BackendUtility::getRecordWSOL($this->tableName, $basicNode->getId(), '*', '', FALSE);
			if ($this->getLabelField() !== '') {
				$node->setLabel($row[$this->getLabelField()]);
			} else {
				$node->setLabel($basicNode->getId());
			}
			$node->setSelected(\TYPO3\CMS\Core\Utility\GeneralUtility::inList($this->getSelectedList(), $basicNode->getId()));
			$node->setExpanded($this->isExpanded($basicNode));
		}
		$node->setId($basicNode->getId());
		$node->setSelectable(!\TYPO3\CMS\Core\Utility\GeneralUtility::inList($this->getNonSelectableLevelList(), $level) && !in_array($basicNode->getId(), $this->getItemUnselectableList()));
		$node->setSortValue($this->nodeSortValues[$basicNode->getId()]);
		$node->setIcon(\TYPO3\CMS\Backend\Utility\IconUtility::mapRecordTypeToSpriteIconClass($this->tableName, $row));
		$node->setParentNode($parent);
		if ($basicNode->hasChildNodes()) {
			$node->setHasChildren(TRUE);
			/** @var $childNodes \TYPO3\CMS\Backend\Tree\SortedTreeNodeCollection */
			$childNodes = \TYPO3\CMS\Core\Utility\GeneralUtility::makeInstance('TYPO3\\CMS\\Backend\\Tree\\SortedTreeNodeCollection');
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
		$this->nodeSortValues = array_flip($this->itemWhiteList);
		$this->columnConfiguration = $GLOBALS['TCA'][$this->getTableName()]['columns'][$this->getLookupField()]['config'];
		if (isset($this->columnConfiguration['foreign_table']) && $this->columnConfiguration['foreign_table'] != $this->getTableName()) {
			throw new \InvalidArgumentException('TCA Tree configuration is invalid: tree for different node-Tables is not implemented yet', 1290944650);
		}
		$this->treeData = \TYPO3\CMS\Core\Utility\GeneralUtility::makeInstance('TYPO3\\CMS\\Backend\\Tree\\TreeNode');
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
	 * @param \TYPO3\CMS\Backend\Tree\TreeNode $node
	 * @param integer $level
	 * @return NULL|\TYPO3\CMS\Backend\Tree\TreeNodeCollection
	 */
	protected function getChildrenOf(\TYPO3\CMS\Backend\Tree\TreeNode $node, $level) {
		$nodeData = NULL;
		if ($node->getId() !== 0) {
			$nodeData = $GLOBALS['TYPO3_DB']->exec_SELECTgetSingleRow('*', $this->tableName, 'uid=' . $node->getId());
		}
		if ($nodeData == NULL) {
			$nodeData = array(
				'uid' => 0,
				$this->getLookupField() => ''
			);
		}
		$storage = NULL;
		$children = $this->getRelatedRecords($nodeData);
		if (count($children)) {
			/** @var $storage \TYPO3\CMS\Backend\Tree\TreeNodeCollection */
			$storage = \TYPO3\CMS\Core\Utility\GeneralUtility::makeInstance('TYPO3\\CMS\\Backend\\Tree\\TreeNodeCollection');
			foreach ($children as $child) {
				$node = \TYPO3\CMS\Core\Utility\GeneralUtility::makeInstance('TYPO3\\CMS\\Backend\\Tree\\TreeNode');
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
		if ($this->getLookupMode() == \TYPO3\CMS\Core\Tree\TableConfiguration\DatabaseTreeDataProvider::MODE_PARENT) {
			$children = $this->getChildrenUidsFromParentRelation($row);
		} else {
			$children = $this->getChildrenUidsFromChildrenRelation($row);
		}
		$allowedArray = array();
		foreach ($children as $child) {
			if (!in_array($child, $this->idCache) && in_array($child, $this->itemWhiteList)) {
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
	 * @return array
	 */
	protected function getChildrenUidsFromParentRelation(array $row) {
		$uid = $row['uid'];
		switch ((string) $this->columnConfiguration['type']) {
		case 'inline':

		case 'select':
			if ($this->columnConfiguration['MM']) {
				/** @var $dbGroup \TYPO3\CMS\Core\Database\RelationHandler */
				$dbGroup = \TYPO3\CMS\Core\Utility\GeneralUtility::makeInstance('TYPO3\\CMS\\Core\\Database\\RelationHandler');
				// Dummy field for setting "look from other site"
				$this->columnConfiguration['MM_oppositeField'] = 'children';
				$dbGroup->start($row[$this->getLookupField()], $this->getTableName(), $this->columnConfiguration['MM'], $uid, $this->getTableName(), $this->columnConfiguration);
				$relatedUids = $dbGroup->tableArray[$this->getTableName()];
			} elseif ($this->columnConfiguration['foreign_field']) {
				$relatedUids = $this->listFieldQuery($this->columnConfiguration['foreign_field'], $uid);
			} else {
				$relatedUids = $this->listFieldQuery($this->getLookupField(), $uid);
			}
			break;
		default:
			$relatedUids = $this->listFieldQuery($this->getLookupField(), $uid);
		}
		return $relatedUids;
	}

	/**
	 * Gets related children records depending on TCA configuration
	 *
	 * @param array $row
	 * @return array
	 */
	protected function getChildrenUidsFromChildrenRelation(array $row) {
		$relatedUids = array();
		$uid = $row['uid'];
		$value = $row[$this->getLookupField()];
		switch ((string) $this->columnConfiguration['type']) {
		case 'inline':

		case 'select':
			if ($this->columnConfiguration['MM']) {
				/** @var $dbGroup \TYPO3\CMS\Core\Database\RelationHandler */
				$dbGroup = \TYPO3\CMS\Core\Utility\GeneralUtility::makeInstance('TYPO3\\CMS\\Core\\Database\\RelationHandler');
				$dbGroup->start($value, $this->getTableName(), $this->columnConfiguration['MM'], $uid, $this->getTableName(), $this->columnConfiguration);
				$relatedUids = $dbGroup->tableArray[$this->getTableName()];
			} elseif ($this->columnConfiguration['foreign_field']) {
				$records = $GLOBALS['TYPO3_DB']->exec_SELECTgetRows('uid', $this->getTableName(), $this->columnConfiguration['foreign_field'] . '=' . intval($uid));
				foreach ($records as $record) {
					$relatedUids[] = $record['uid'];
				}
			} else {
				$relatedUids = \TYPO3\CMS\Core\Utility\GeneralUtility::intExplode(',', $value, TRUE);
			}
			break;
		default:
			$relatedUids = \TYPO3\CMS\Core\Utility\GeneralUtility::intExplode(',', $value, TRUE);
		}
		return $relatedUids;
	}

	/**
	 * Queries the table for an field which might contain a list.
	 *
	 * @param string $fieldName the name of the field to be queried
	 * @param integer $queryId the uid to search for
	 * @return integer[] all uids found
	 */
	protected function listFieldQuery($fieldName, $queryId) {
		$records = $GLOBALS['TYPO3_DB']->exec_SELECTgetRows('uid', $this->getTableName(), $GLOBALS['TYPO3_DB']->listQuery($fieldName, intval($queryId), $this->getTableName()) . (intval($queryId) == 0 ? ' OR ' . $fieldName . ' = \'\'' : ''));
		$uidArray = array();
		foreach ($records as $record) {
			$uidArray[] = $record['uid'];
		}
		return $uidArray;
	}

}


?>