<?php

declare(strict_types=1);

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

namespace TYPO3\CMS\Core\Tree\TableConfiguration;

use Psr\EventDispatcher\EventDispatcherInterface;
use TYPO3\CMS\Backend\Tree\SortedTreeNodeCollection;
use TYPO3\CMS\Backend\Tree\TreeNode;
use TYPO3\CMS\Backend\Tree\TreeNodeCollection;
use TYPO3\CMS\Backend\Utility\BackendUtility;
use TYPO3\CMS\Core\Database\Connection;
use TYPO3\CMS\Core\Database\ConnectionPool;
use TYPO3\CMS\Core\Database\Query\Expression\ExpressionBuilder;
use TYPO3\CMS\Core\Database\RelationHandler;
use TYPO3\CMS\Core\Imaging\Icon;
use TYPO3\CMS\Core\Imaging\IconFactory;
use TYPO3\CMS\Core\Localization\LanguageService;
use TYPO3\CMS\Core\Tree\Event\ModifyTreeDataEvent;
use TYPO3\CMS\Core\Utility\GeneralUtility;

/**
 * TCA tree data provider
 */
class DatabaseTreeDataProvider extends AbstractTableConfigurationTreeDataProvider
{
    public const MODE_CHILDREN = 1;
    public const MODE_PARENT = 2;

    protected string $tableName = '';

    /**
     * @var string
     */
    protected $treeId = '';

    protected string $labelField = '';

    protected string $tableWhere = '';

    /**
     * @var self::MODE_*
     */
    protected int $lookupMode = self::MODE_CHILDREN;

    protected string $lookupField = '';

    /**
     * @var int[]
     */
    protected array $startingPoints = [0];

    protected array $idCache = [];

    /**
     * Stores TCA-Configuration of the LookUpField in tableName
     *
     * @var array<string, mixed>
     */
    protected array $columnConfiguration;

    /**
     * node sort values (the orderings from foreign_Table_where evaluation)
     *
     * @var array<string, mixed>
     */
    protected array $nodeSortValues = [];

    public function __construct(protected EventDispatcherInterface $eventDispatcher) {}

    /**
     * Sets the label field
     */
    public function setLabelField(string $labelField): void
    {
        $this->labelField = $labelField;
    }

    /**
     * Gets the label field
     */
    public function getLabelField(): string
    {
        return $this->labelField;
    }

    /**
     * Sets the table name
     */
    public function setTableName(string $tableName): void
    {
        $this->tableName = $tableName;
    }

    /**
     * Gets the table name
     */
    public function getTableName(): string
    {
        return $this->tableName;
    }

    /**
     * Sets the lookup field
     */
    public function setLookupField(string $lookupField): void
    {
        $this->lookupField = $lookupField;
    }

    /**
     * Gets the lookup field
     */
    public function getLookupField(): string
    {
        return $this->lookupField;
    }

    /**
     * Sets the lookup mode
     *
     * @param self::MODE_* $lookupMode
     */
    public function setLookupMode(int $lookupMode): void
    {
        $this->lookupMode = $lookupMode;
    }

    /**
     * Gets the lookup mode
     *
     * @return self::MODE_*
     */
    public function getLookupMode(): int
    {
        return $this->lookupMode;
    }

    /**
     * Gets the nodes
     */
    public function getNodes(TreeNode $node): void {}

    /**
     * Gets the root node
     */
    public function getRoot(): DatabaseTreeNode
    {
        return $this->buildRepresentationForNode($this->treeData);
    }

    /**
     * Sets the root uids
     *
     * @param int[] $startingPoints
     */
    public function setStartingPoints(array $startingPoints): void
    {
        $this->startingPoints = $startingPoints;
    }

    /**
     * Gets the root uids
     *
     * @return int[]
     */
    public function getStartingPoints(): array
    {
        return $this->startingPoints;
    }

    /**
     * Sets the tableWhere clause
     */
    public function setTableWhere(string $tableWhere): void
    {
        $this->tableWhere = $tableWhere;
    }

    /**
     * Gets the tableWhere clause
     */
    public function getTableWhere(): string
    {
        return $this->tableWhere;
    }

    /**
     * Builds a complete node including children
     */
    protected function buildRepresentationForNode(TreeNode $basicNode, ?DatabaseTreeNode $parent = null, $level = 0): DatabaseTreeNode
    {
        $node = GeneralUtility::makeInstance(DatabaseTreeNode::class);
        $row = [];
        if ($basicNode->getId() == 0) {
            $node->setSelected(false);
            $node->setExpanded(true);
            $node->setLabel($this->getLanguageService()?->sL($GLOBALS['TCA'][$this->tableName]['ctrl']['title']));
        } else {
            if ($basicNode->getAdditionalData() === []) {
                $row = BackendUtility::getRecordWSOL($this->tableName, (int)$basicNode->getId(), '*', '', false) ?? [];
            } else {
                // @todo: This is part of the category tree performance hack
                $row = $basicNode->getAdditionalData();
            }
            $node->setLabel(BackendUtility::getRecordTitle($this->tableName, $row) ?: $basicNode->getId());
            $node->setSelected(GeneralUtility::inList($this->getSelectedList(), $basicNode->getId()));
            $node->setExpanded($this->isExpanded($basicNode));
        }
        $node->setId($basicNode->getId());
        $node->setSelectable(!GeneralUtility::inList($this->getNonSelectableLevelList(), (string)$level) && !in_array($basicNode->getId(), $this->getItemUnselectableList()));
        $node->setSortValue($this->nodeSortValues[$basicNode->getId()] ?? '');
        $iconFactory = GeneralUtility::makeInstance(IconFactory::class);
        $node->setIcon($iconFactory->getIconForRecord($this->tableName, $row, Icon::SIZE_SMALL));
        $node->setParentNode($parent);
        if ($basicNode->hasChildNodes()) {
            $node->setHasChildren(true);
            $childNodes = GeneralUtility::makeInstance(SortedTreeNodeCollection::class);
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
    public function initializeTreeData(): void
    {
        parent::initializeTreeData();
        $this->nodeSortValues = array_flip($this->itemWhiteList);
        $this->columnConfiguration = $GLOBALS['TCA'][$this->getTableName()]['columns'][$this->lookupField]['config'] ?? [];
        if (isset($this->columnConfiguration['foreign_table']) && $this->columnConfiguration['foreign_table'] !== $this->getTableName()) {
            throw new \InvalidArgumentException('TCA Tree configuration is invalid: tree for different node-Tables is not implemented yet', 1290944650);
        }
        $this->treeData = GeneralUtility::makeInstance(TreeNode::class);
        $this->loadTreeData();
        $event = $this->eventDispatcher->dispatch(new ModifyTreeDataEvent($this->treeData, $this));
        $this->treeData = $event->getTreeData();
    }

    /**
     * Loads the tree data (all possible children)
     */
    protected function loadTreeData(): void
    {
        if ($this->getStartingPoints()) {
            $startingPoints = $this->getStartingPoints();
        } else {
            $startingPoints = [0];
        }

        if (count($startingPoints) === 1) {
            // Only one starting point is available, grab it and set it as root node
            $startingPoint = current($startingPoints);
            $this->treeData->setId((string)$startingPoint);
            $this->treeData->setParentNode(null);

            if ($this->levelMaximum >= 1) {
                $childNodes = $this->getChildrenOf($this->treeData, 1);
                if ($childNodes !== null) {
                    $this->treeData->setChildNodes($childNodes);
                }
            }
        } else {
            // The current tree implementation disallows multiple elements on root level, thus we have to work around
            // this with a separate TreeNodeCollection that gets attached to the root node with uid 0. This has the
            // nasty side effect we cannot avoid the root node being rendered.

            $treeNodeCollection = GeneralUtility::makeInstance(TreeNodeCollection::class);
            foreach ($startingPoints as $startingPoint) {
                $treeData = GeneralUtility::makeInstance(TreeNode::class);
                $treeData->setId((string)$startingPoint);

                if ($this->levelMaximum >= 1) {
                    $childNodes = $this->getChildrenOf($treeData, 1);
                    if ($childNodes !== null) {
                        $treeData->setChildNodes($childNodes);
                    }
                }
                $treeNodeCollection->append($treeData);
            }
            $this->treeData->setId('0');
            $this->treeData->setChildNodes($treeNodeCollection);
        }
    }

    /**
     * Gets node children
     */
    protected function getChildrenOf(TreeNode $node, int $level): ?TreeNodeCollection
    {
        $nodeData = null;
        if ($node->getId() !== 0 && $node->getId() !== '0') {
            if (is_array($this->availableItems[(int)$node->getId()] ?? false)) {
                // @todo: This is part of the category tree performance hack
                $nodeData = $this->availableItems[(int)$node->getId()];
            } else {
                $nodeData = BackendUtility::getRecord($this->tableName, $node->getId(), '*', '', false);
            }
        }
        if (empty($nodeData)) {
            $nodeData = [
                'uid' => 0,
                $this->lookupField => '',
            ];
        }
        $storage = null;
        $children = $this->getRelatedRecords($nodeData);
        if (!empty($children)) {
            $storage = GeneralUtility::makeInstance(TreeNodeCollection::class);
            foreach ($children as $child) {
                $node = GeneralUtility::makeInstance(TreeNode::class, $this->availableItems[(int)$child] ?? []);
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
     */
    protected function getRelatedRecords(array $row): array
    {
        if ($this->getLookupMode() === self::MODE_PARENT) {
            $children = $this->getChildrenUidsFromParentRelation($row);
        } else {
            $children = $this->getChildrenUidsFromChildrenRelation($row);
        }
        $allowedArray = [];
        foreach ($children as $child) {
            if (!in_array($child, $this->idCache, true) && in_array($child, $this->itemWhiteList, true)) {
                $allowedArray[] = $child;
            }
        }
        $this->idCache = array_merge($this->idCache, $allowedArray);
        return $allowedArray;
    }

    /**
     * Gets related records depending on TCA configuration
     */
    protected function getChildrenUidsFromParentRelation(array $row): array
    {
        $uid = (int)$row['uid'];
        if (in_array($this->columnConfiguration['type'] ?? '', ['select', 'category', 'inline', 'file'], true)) {
            if ($this->columnConfiguration['MM'] ?? null) {
                $dbGroup = GeneralUtility::makeInstance(RelationHandler::class);
                // Dummy field for setting "look from other site"
                $this->columnConfiguration['MM_oppositeField'] = 'children';
                $dbGroup->start($row[$this->lookupField], $this->getTableName(), $this->columnConfiguration['MM'], $uid, $this->getTableName(), $this->columnConfiguration);
                $relatedUids = $dbGroup->tableArray[$this->getTableName()];
            } elseif ($this->columnConfiguration['foreign_field'] ?? null) {
                $relatedUids = $this->listFieldQuery($this->columnConfiguration['foreign_field'], $uid);
            } else {
                // Check available items
                if ($this->availableItems !== [] && $this->columnConfiguration['type'] === 'category') {
                    // @todo: This is part of the category tree performance hack
                    $relatedUids = [];
                    foreach ($this->availableItems as $item) {
                        if ($item[$this->lookupField] === $uid) {
                            $relatedUids[$item['uid']] = $item['sorting'];
                        }
                    }
                    if ($relatedUids !== []) {
                        // Ensure sorting is kept
                        asort($relatedUids);
                        $relatedUids = array_keys($relatedUids);
                    }
                } else {
                    $relatedUids = $this->listFieldQuery($this->lookupField, $uid);
                }
            }
        } else {
            $relatedUids = $this->listFieldQuery($this->lookupField, $uid);
        }

        return $relatedUids;
    }

    /**
     * Gets related children records depending on TCA configuration
     */
    protected function getChildrenUidsFromChildrenRelation(array $row): array
    {
        $relatedUids = [];
        $uid = (int)$row['uid'];
        $value = (string)$row[$this->lookupField];
        switch ((string)$this->columnConfiguration['type']) {
            case 'inline':
            case 'file':
                // Intentional fall-through
            case 'select':
            case 'category':
                if ($this->columnConfiguration['MM'] ?? false) {
                    $dbGroup = GeneralUtility::makeInstance(RelationHandler::class);
                    $dbGroup->start(
                        $value,
                        $this->getTableName(),
                        $this->columnConfiguration['MM'],
                        $uid,
                        $this->getTableName(),
                        $this->columnConfiguration
                    );
                    $relatedUids = $dbGroup->tableArray[$this->getTableName()];
                } elseif ($this->columnConfiguration['foreign_field'] ?? false) {
                    $queryBuilder = GeneralUtility::makeInstance(ConnectionPool::class)
                        ->getQueryBuilderForTable($this->getTableName());
                    $queryBuilder->getRestrictions()->removeAll();
                    $records = $queryBuilder->select('uid')
                        ->from($this->getTableName())
                        ->where(
                            $queryBuilder->expr()->eq(
                                $this->columnConfiguration['foreign_field'],
                                $queryBuilder->createNamedParameter($uid, Connection::PARAM_INT)
                            )
                        )
                        ->executeQuery()
                        ->fetchAllAssociative();

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
     * Queries the table for a field which might contain a list.
     *
     * @param string $fieldName the name of the field to be queried
     * @param int $queryId the uid to search for
     * @return int[] all uids found
     */
    protected function listFieldQuery(string $fieldName, int $queryId): array
    {
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

        $records = $queryBuilder->executeQuery()->fetchAllAssociative();
        return array_column($records, 'uid');
    }

    protected function getLanguageService(): ?LanguageService
    {
        return $GLOBALS['LANG'] ?? null;
    }
}
