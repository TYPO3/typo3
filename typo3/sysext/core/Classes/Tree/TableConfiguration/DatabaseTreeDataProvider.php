<?php
namespace TYPO3\CMS\Core\Tree\TableConfiguration;

/*
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
use TYPO3\CMS\Core\Database\ConnectionPool;
use TYPO3\CMS\Core\Database\Query\Expression\ExpressionBuilder;
use TYPO3\CMS\Core\Imaging\Icon;
use TYPO3\CMS\Core\Imaging\IconFactory;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Extbase\Object\ObjectManager;
use TYPO3\CMS\Extbase\SignalSlot\Dispatcher;

/**
 * TCA tree data provider
 */
class DatabaseTreeDataProvider extends AbstractTableConfigurationTreeDataProvider
{
    const SIGNAL_PostProcessTreeData = 'PostProcessTreeData';
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
     * @var int
     */
    protected $lookupMode = self::MODE_CHILDREN;

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
    protected $idCache = [];

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
    protected $nodeSortValues = [];

    /**
     * @var array TCEforms compiled TSConfig array
     */
    protected $generatedTSConfig = [];

    /**
     * @var Dispatcher
     */
    protected $signalSlotDispatcher;

    /**
     * Sets the label field
     *
     * @param string $labelField
     */
    public function setLabelField($labelField)
    {
        $this->labelField = $labelField;
    }

    /**
     * Gets the label field
     *
     * @return string
     */
    public function getLabelField()
    {
        return $this->labelField;
    }

    /**
     * Sets the table name
     *
     * @param string $tableName
     */
    public function setTableName($tableName)
    {
        $this->tableName = $tableName;
    }

    /**
     * Gets the table name
     *
     * @return string
     */
    public function getTableName()
    {
        return $this->tableName;
    }

    /**
     * Sets the lookup field
     *
     * @param string $lookupField
     */
    public function setLookupField($lookupField)
    {
        $this->lookupField = $lookupField;
    }

    /**
     * Gets the lookup field
     *
     * @return string
     */
    public function getLookupField()
    {
        return $this->lookupField;
    }

    /**
     * Sets the lookup mode
     *
     * @param int $lookupMode
     */
    public function setLookupMode($lookupMode)
    {
        $this->lookupMode = $lookupMode;
    }

    /**
     * Gets the lookup mode
     *
     * @return int
     */
    public function getLookupMode()
    {
        return $this->lookupMode;
    }

    /**
     * Gets the nodes
     *
     * @param \TYPO3\CMS\Backend\Tree\TreeNode $node
     * @return \TYPO3\CMS\Backend\Tree\TreeNodeCollection
     */
    public function getNodes(\TYPO3\CMS\Backend\Tree\TreeNode $node)
    {
    }

    /**
     * Gets the root node
     *
     * @return \TYPO3\CMS\Core\Tree\TableConfiguration\DatabaseTreeNode
     */
    public function getRoot()
    {
        return $this->buildRepresentationForNode($this->treeData);
    }

    /**
     * Sets the root uid
     *
     * @param int $rootUid
     */
    public function setRootUid($rootUid)
    {
        $this->rootUid = $rootUid;
    }

    /**
     * Gets the root uid
     *
     * @return int
     */
    public function getRootUid()
    {
        return $this->rootUid;
    }

    /**
     * Sets the tableWhere clause
     *
     * @param string $tableWhere
     */
    public function setTableWhere($tableWhere)
    {
        $this->tableWhere = $tableWhere;
    }

    /**
     * Gets the tableWhere clause
     *
     * @return string
     */
    public function getTableWhere()
    {
        return $this->tableWhere;
    }

    /**
     * Builds a complete node including childs
     *
     * @param \TYPO3\CMS\Backend\Tree\TreeNode $basicNode
     * @param \TYPO3\CMS\Core\Tree\TableConfiguration\DatabaseTreeNode|null $parent
     * @param int $level
     * @return \TYPO3\CMS\Core\Tree\TableConfiguration\DatabaseTreeNode Node object
     */
    protected function buildRepresentationForNode(\TYPO3\CMS\Backend\Tree\TreeNode $basicNode, DatabaseTreeNode $parent = null, $level = 0)
    {
        /** @var $node \TYPO3\CMS\Core\Tree\TableConfiguration\DatabaseTreeNode */
        $node = GeneralUtility::makeInstance(\TYPO3\CMS\Core\Tree\TableConfiguration\DatabaseTreeNode::class);
        $row = [];
        if ($basicNode->getId() == 0) {
            $node->setSelected(false);
            $node->setExpanded(true);
            $node->setLabel($GLOBALS['LANG']->sL($GLOBALS['TCA'][$this->tableName]['ctrl']['title']));
        } else {
            $row = BackendUtility::getRecordWSOL($this->tableName, $basicNode->getId(), '*', '', false);
            $node->setLabel(BackendUtility::getRecordTitle($this->tableName, $row) ?: $basicNode->getId());
            $node->setSelected(GeneralUtility::inList($this->getSelectedList(), $basicNode->getId()));
            $node->setExpanded($this->isExpanded($basicNode));
        }
        $node->setId($basicNode->getId());
        $node->setSelectable(!GeneralUtility::inList($this->getNonSelectableLevelList(), $level) && !in_array($basicNode->getId(), $this->getItemUnselectableList()));
        $node->setSortValue($this->nodeSortValues[$basicNode->getId()]);
        $iconFactory = GeneralUtility::makeInstance(IconFactory::class);
        $node->setIcon($iconFactory->getIconForRecord($this->tableName, $row, Icon::SIZE_SMALL));
        $node->setParentNode($parent);
        if ($basicNode->hasChildNodes()) {
            $node->setHasChildren(true);
            /** @var $childNodes \TYPO3\CMS\Backend\Tree\SortedTreeNodeCollection */
            $childNodes = GeneralUtility::makeInstance(\TYPO3\CMS\Backend\Tree\SortedTreeNodeCollection::class);
            $tempNodes = [];
            foreach ($basicNode->getChildNodes() as $child) {
                $tempNodes[] = $this->buildRepresentationForNode($child, $node, $level + 1);
            }
            $childNodes->exchangeArray($tempNodes);
            $childNodes->asort();
            $node->setChildNodes($childNodes);
        }
        return $node;
    }

    /**
     * Init the tree data
     */
    public function initializeTreeData()
    {
        parent::initializeTreeData();
        $this->nodeSortValues = array_flip($this->itemWhiteList);
        $this->columnConfiguration = $GLOBALS['TCA'][$this->getTableName()]['columns'][$this->getLookupField()]['config'];
        if (isset($this->columnConfiguration['foreign_table']) && $this->columnConfiguration['foreign_table'] != $this->getTableName()) {
            throw new \InvalidArgumentException('TCA Tree configuration is invalid: tree for different node-Tables is not implemented yet', 1290944650);
        }
        $this->treeData = GeneralUtility::makeInstance(\TYPO3\CMS\Backend\Tree\TreeNode::class);
        $this->loadTreeData();
        $this->emitPostProcessTreeDataSignal();
    }

    /**
     * Loads the tree data (all possible children)
     */
    protected function loadTreeData()
    {
        $this->treeData->setId($this->getRootUid());
        $this->treeData->setParentNode(null);
        if ($this->levelMaximum >= 1) {
            $childNodes = $this->getChildrenOf($this->treeData, 1);
            if ($childNodes !== null) {
                $this->treeData->setChildNodes($childNodes);
            }
        }
    }

    /**
     * Gets node children
     *
     * @param \TYPO3\CMS\Backend\Tree\TreeNode $node
     * @param int $level
     * @return \TYPO3\CMS\Backend\Tree\TreeNodeCollection|null
     */
    protected function getChildrenOf(\TYPO3\CMS\Backend\Tree\TreeNode $node, $level)
    {
        $nodeData = null;
        if ($node->getId() !== 0) {
            $queryBuilder = GeneralUtility::makeInstance(ConnectionPool::class)
                ->getQueryBuilderForTable($this->getTableName());
            $queryBuilder->getRestrictions()->removeAll();
            $nodeData = $queryBuilder->select('*')
                ->from($this->getTableName())
                ->where(
                    $queryBuilder->expr()->eq(
                        'uid',
                        $queryBuilder->createNamedParameter($node->getId(), \PDO::PARAM_INT)
                    )
                )
                ->setMaxResults(1)
                ->execute()
                ->fetch();
        }
        if (empty($nodeData)) {
            $nodeData = [
                'uid' => 0,
                $this->getLookupField() => ''
            ];
        }
        $storage = null;
        $children = $this->getRelatedRecords($nodeData);
        if (!empty($children)) {
            /** @var $storage \TYPO3\CMS\Backend\Tree\TreeNodeCollection */
            $storage = GeneralUtility::makeInstance(\TYPO3\CMS\Backend\Tree\TreeNodeCollection::class);
            foreach ($children as $child) {
                $node = GeneralUtility::makeInstance(\TYPO3\CMS\Backend\Tree\TreeNode::class);
                $node->setId($child);
                if ($level < $this->levelMaximum) {
                    $children = $this->getChildrenOf($node, $level + 1);
                    if ($children !== null) {
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
    protected function getRelatedRecords(array $row)
    {
        if ($this->getLookupMode() == self::MODE_PARENT) {
            $children = $this->getChildrenUidsFromParentRelation($row);
        } else {
            $children = $this->getChildrenUidsFromChildrenRelation($row);
        }
        $allowedArray = [];
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
    protected function getChildrenUidsFromParentRelation(array $row)
    {
        $uid = $row['uid'];
        switch ((string)$this->columnConfiguration['type']) {
            case 'inline':

            case 'select':
                if ($this->columnConfiguration['MM']) {
                    /** @var $dbGroup \TYPO3\CMS\Core\Database\RelationHandler */
                    $dbGroup = GeneralUtility::makeInstance(\TYPO3\CMS\Core\Database\RelationHandler::class);
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
    protected function getChildrenUidsFromChildrenRelation(array $row)
    {
        $relatedUids = [];
        $uid = $row['uid'];
        $value = $row[$this->getLookupField()];
        switch ((string)$this->columnConfiguration['type']) {
            case 'inline':
                // Intentional fall-through
            case 'select':
                if ($this->columnConfiguration['MM']) {
                    $dbGroup = GeneralUtility::makeInstance(\TYPO3\CMS\Core\Database\RelationHandler::class);
                    $dbGroup->start(
                        $value,
                        $this->getTableName(),
                        $this->columnConfiguration['MM'],
                        $uid,
                        $this->getTableName(),
                        $this->columnConfiguration
                    );
                    $relatedUids = $dbGroup->tableArray[$this->getTableName()];
                } elseif ($this->columnConfiguration['foreign_field']) {
                    $queryBuilder = GeneralUtility::makeInstance(ConnectionPool::class)
                        ->getQueryBuilderForTable($this->getTableName());
                    $queryBuilder->getRestrictions()->removeAll();
                    $records = $queryBuilder->select('uid')
                        ->from($this->getTableName())
                        ->where(
                            $queryBuilder->expr()->eq(
                                $this->columnConfiguration['foreign_field'],
                                $queryBuilder->createNamedParameter($uid, \PDO::PARAM_INT)
                            )
                        )
                        ->execute()
                        ->fetchAll();

                    if (!empty($records)) {
                        $relatedUids = array_column($records, 'uid');
                    }
                } else {
                    $relatedUids = GeneralUtility::intExplode(',', $value, true);
                }
                break;
            default:
                $relatedUids = GeneralUtility::intExplode(',', $value, true);
        }
        return $relatedUids;
    }

    /**
     * Queries the table for an field which might contain a list.
     *
     * @param string $fieldName the name of the field to be queried
     * @param int $queryId the uid to search for
     * @return int[] all uids found
     */
    protected function listFieldQuery($fieldName, $queryId)
    {
        $queryId = (int)$queryId;
        $queryBuilder = GeneralUtility::makeInstance(ConnectionPool::class)
            ->getQueryBuilderForTable($this->getTableName());
        $queryBuilder->getRestrictions()->removeAll();

        $queryBuilder->select('uid')
            ->from($this->getTableName())
            ->where($queryBuilder->expr()->inSet($fieldName, $queryBuilder->quote($queryId)));

        if ($queryId === 0) {
            $queryBuilder->orWhere(
                $queryBuilder->expr()->comparison(
                    'CAST(' . $queryBuilder->quoteIdentifier($fieldName) . ' AS CHAR)',
                    ExpressionBuilder::EQ,
                    $queryBuilder->quote('')
                )
            );
        }

        $records = $queryBuilder->execute()->fetchAll();
        $uidArray = is_array($records) ? array_column($records, 'uid') : [];

        return $uidArray;
    }

    /**
     * Emits the post processing tree data signal.
     */
    protected function emitPostProcessTreeDataSignal()
    {
        $this->getSignalSlotDispatcher()->dispatch(
            self::class,
            self::SIGNAL_PostProcessTreeData,
            [$this, $this->treeData]
        );
    }

    /**
     * Get the SignalSlot dispatcher
     *
     * @return Dispatcher
     */
    protected function getSignalSlotDispatcher()
    {
        if (!isset($this->signalSlotDispatcher)) {
            $this->signalSlotDispatcher = $this->getObjectManager()->get(Dispatcher::class);
        }
        return $this->signalSlotDispatcher;
    }

    /**
     * Get the ObjectManager
     *
     * @return ObjectManager
     */
    protected function getObjectManager()
    {
        return GeneralUtility::makeInstance(ObjectManager::class);
    }
}
