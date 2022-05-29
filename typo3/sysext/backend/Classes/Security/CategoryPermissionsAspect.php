<?php

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

namespace TYPO3\CMS\Backend\Security;

use Psr\Http\Message\ServerRequestInterface;
use TYPO3\CMS\Backend\Tree\TreeNode;
use TYPO3\CMS\Backend\Tree\TreeNodeCollection;
use TYPO3\CMS\Core\Database\ConnectionPool;
use TYPO3\CMS\Core\Http\ApplicationType;
use TYPO3\CMS\Core\Tree\Event\ModifyTreeDataEvent;
use TYPO3\CMS\Core\Utility\GeneralUtility;

/**
 * This event listener deals with tree data security which reacts on a PSR-14 event
 * on data object initialization.
 *
 * The aspect defines category mount points according to BE User permissions.
 *
 * @internal This class is TYPO3-internal hook and is not considered part of the Public TYPO3 API.
 */
final class CategoryPermissionsAspect
{
    /**
     * @var string
     */
    private $categoryTableName = 'sys_category';

    /**
     * The listener for the event in DatabaseTreeDataProvider, which only affects the TYPO3 Backend
     *
     * @param ModifyTreeDataEvent $event
     */
    public function addUserPermissionsToCategoryTreeData(ModifyTreeDataEvent $event): void
    {
        // Only evaluate this in the backend
        if (!($GLOBALS['TYPO3_REQUEST'] ?? null) instanceof ServerRequestInterface
            || !ApplicationType::fromRequest($GLOBALS['TYPO3_REQUEST'])->isBackend()
        ) {
            return;
        }

        $dataProvider = $event->getProvider();
        $treeData = $event->getTreeData();

        if (!$GLOBALS['BE_USER']->isAdmin() && $dataProvider->getTableName() === $this->categoryTableName) {

            // Get User permissions related to category
            $categoryMountPoints = $GLOBALS['BE_USER']->getCategoryMountPoints();

            // Backup child nodes to be processed.
            $treeNodeCollection = $treeData->getChildNodes();

            if (!empty($categoryMountPoints) && !empty($treeNodeCollection)) {
                // @deprecated Remove merging of $dataProvider->getRootUid() in v12
                $startingPoints = array_unique(array_merge($dataProvider->getStartingPoints(), [$dataProvider->getRootUid()]));
                $shallRepopulateTree = false;

                // Check the rootline against categoryMountPoints when tree was filtered
                foreach ($startingPoints as $startingPoint) {
                    if (!in_array($startingPoint, $categoryMountPoints)) {
                        $shallRepopulateTree = true;
                        break;
                    }
                    $uidsInRootline = $this->findUidsInRootline($startingPoint);
                    if (empty(array_intersect($categoryMountPoints, $uidsInRootline))) {
                        $shallRepopulateTree = true;
                        break;
                    }
                }

                if ($shallRepopulateTree) {
                    // First, remove all child nodes which must be analyzed to be considered as "secure".
                    // The nodes were backed up in variable $treeNodeCollection beforehand.
                    $treeData->removeChildNodes();

                    // Create an empty tree node collection to receive the secured nodes.
                    $securedTreeNodeCollection = GeneralUtility::makeInstance(TreeNodeCollection::class);

                    foreach ($categoryMountPoints as $categoryMountPoint) {
                        $treeNode = $this->lookUpCategoryMountPointInTreeNodes((int)$categoryMountPoint, $treeNodeCollection);
                        if ($treeNode !== null) {
                            $securedTreeNodeCollection->append($treeNode);
                        }
                    }

                    // Reset child nodes.
                    $treeData->setChildNodes($securedTreeNodeCollection);
                }
            }
        }
    }

    /**
     * Recursively look up for a category mount point within a tree.
     *
     * @param int $categoryMountPoint
     * @param TreeNodeCollection $treeNodeCollection
     * @return TreeNode|null
     */
    private function lookUpCategoryMountPointInTreeNodes(int $categoryMountPoint, TreeNodeCollection $treeNodeCollection)
    {
        $result = null;

        // If any User permission, recursively traverse the tree and set tree part as mount point
        foreach ($treeNodeCollection as $treeNode) {

            /** @var TreeNode $treeNode */
            if ((int)$treeNode->getId() === $categoryMountPoint) {
                $result = $treeNode;
                break;
            }

            if ($treeNode->hasChildNodes()) {

                /** @var TreeNode $node */
                $node = $this->lookUpCategoryMountPointInTreeNodes($categoryMountPoint, $treeNode->getChildNodes());
                if ($node !== null) {
                    $result = $node;
                    break;
                }
            }
        }
        return $result;
    }

    /**
     * Find parent uids in rootline
     *
     * @param int $uid
     * @return array
     */
    private function findUidsInRootline(int $uid)
    {
        $queryBuilder = GeneralUtility::makeInstance(ConnectionPool::class)
            ->getQueryBuilderForTable($this->categoryTableName);
        $row = $queryBuilder
            ->select('parent')
            ->from($this->categoryTableName)
            ->where(
                $queryBuilder->expr()->eq('uid', $queryBuilder->createNamedParameter($uid, \PDO::PARAM_INT))
            )
            ->executeQuery()
            ->fetchAssociative();

        $parentUids = [];
        if ($row['parent'] > 0) {
            $parentUids = $this->findUidsInRootline($row['parent']);
            $parentUids[] = $row['parent'];
        }
        return $parentUids;
    }
}
