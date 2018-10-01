<?php
namespace TYPO3\CMS\Backend\Security;

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

use TYPO3\CMS\Backend\Tree\TreeNode;
use TYPO3\CMS\Backend\Tree\TreeNodeCollection;
use TYPO3\CMS\Core\Authentication\BackendUserAuthentication;
use TYPO3\CMS\Core\Database\ConnectionPool;
use TYPO3\CMS\Core\Tree\TableConfiguration\DatabaseTreeDataProvider;
use TYPO3\CMS\Core\Utility\GeneralUtility;

/**
 * We do not have AOP in TYPO3 for now, thus the aspect which
 * deals with tree data security is a slot which reacts on a signal
 * on data data object initialization.
 *
 * The aspect define category mount points according to BE User permissions.
 *
 * @internal This class is TYPO3-internal hook and is not considered part of the Public TYPO3 API.
 */
class CategoryPermissionsAspect
{
    /**
     * @var string
     */
    protected $categoryTableName = 'sys_category';

    /**
     * @var BackendUserAuthentication
     */
    protected $backendUserAuthentication;

    /**
     * @param BackendUserAuthentication|null $backendUserAuthentication
     */
    public function __construct($backendUserAuthentication = null)
    {
        $this->backendUserAuthentication = $backendUserAuthentication ?: $GLOBALS['BE_USER'];
    }

    /**
     * The slot for the signal in DatabaseTreeDataProvider, which only affects the TYPO3 Backend
     *
     * @param DatabaseTreeDataProvider $dataProvider
     * @param TreeNode $treeData
     */
    public function addUserPermissionsToCategoryTreeData(DatabaseTreeDataProvider $dataProvider, $treeData)
    {
        // Only evaluate this in the backend
        if (!(TYPO3_REQUESTTYPE & TYPO3_REQUESTTYPE_BE)) {
            return;
        }

        if (!$this->backendUserAuthentication->isAdmin() && $dataProvider->getTableName() === $this->categoryTableName) {

            // Get User permissions related to category
            $categoryMountPoints = $this->backendUserAuthentication->getCategoryMountPoints();

            // Backup child nodes to be processed.
            $treeNodeCollection = $treeData->getChildNodes();

            if (!empty($categoryMountPoints) && !empty($treeNodeCollection)) {

                // Check the rootline against categoryMountPoints when tree was filtered
                if ($dataProvider->getRootUid() !== null) {
                    if (in_array($dataProvider->getRootUid(), $categoryMountPoints)) {
                        return;
                    }
                    $uidsInRootline = $this->findUidsInRootline($dataProvider->getRootUid());
                    if (!empty(array_intersect($categoryMountPoints, $uidsInRootline))) {
                        // One of the parents was found in categoryMountPoints so all children are secure
                        return;
                    }
                }

                // First, remove all child nodes which must be analyzed to be considered as "secure".
                // The nodes were backed up in variable $treeNodeCollection beforehand.
                $treeData->removeChildNodes();

                // Create an empty tree node collection to receive the secured nodes.
                /** @var TreeNodeCollection $securedTreeNodeCollection */
                $securedTreeNodeCollection = \TYPO3\CMS\Core\Utility\GeneralUtility::makeInstance(\TYPO3\CMS\Backend\Tree\TreeNodeCollection::class);

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

    /**
     * Recursively look up for a category mount point within a tree.
     *
     * @param int $categoryMountPoint
     * @param TreeNodeCollection $treeNodeCollection
     * @return TreeNode|null
     */
    protected function lookUpCategoryMountPointInTreeNodes($categoryMountPoint, TreeNodeCollection $treeNodeCollection)
    {
        $result = null;

        // If any User permission, recursively traverse the tree and set tree part as mount point
        foreach ($treeNodeCollection as $treeNode) {

            /** @var \TYPO3\CMS\Backend\Tree\TreeNode $treeNode */
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
    protected function findUidsInRootline($uid)
    {
        /** @var \TYPO3\CMS\Core\Database\Query\QueryBuilder $queryBuilder */
        $queryBuilder = GeneralUtility::makeInstance(ConnectionPool::class)->getQueryBuilderForTable($this->categoryTableName);
        $row = $queryBuilder
            ->select('parent')
            ->from($this->categoryTableName)
            ->where(
                $queryBuilder->expr()->eq('uid', $queryBuilder->createNamedParameter($uid, \PDO::PARAM_INT))
            )
            ->execute()
            ->fetch();

        $parentUids = [];
        if ($row['parent'] > 0) {
            $parentUids = $this->findUidsInRootline($row['parent']);
            $parentUids[] = $row['parent'];
        }
        return $parentUids;
    }
}
