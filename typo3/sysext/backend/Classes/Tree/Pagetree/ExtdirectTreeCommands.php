<?php
namespace TYPO3\CMS\Backend\Tree\Pagetree;

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

use TYPO3\CMS\Backend\Controller\UserSettingsController;
use TYPO3\CMS\Backend\Utility\BackendUtility;
use TYPO3\CMS\Core\Authentication\BackendUserAuthentication;
use TYPO3\CMS\Core\Utility\GeneralUtility;

/**
 * Commands for the Page tree
 */
class ExtdirectTreeCommands
{
    /**
     * Visibly the page
     *
     * @param \stdClass $nodeData
     * @return array
     */
    public function visiblyNode($nodeData)
    {
        /** @var $node PagetreeNode */
        $node = GeneralUtility::makeInstance(PagetreeNode::class, (array)$nodeData);
        try {
            Commands::visiblyNode($node);
            $newNode = Commands::getNode($node->getId());
            $newNode->setLeaf($node->isLeafNode());
            $returnValue = $newNode->toArray();
        } catch (\Exception $exception) {
            $returnValue = [
                'success' => false,
                'error' => $exception->getMessage()
            ];
        }
        return $returnValue;
    }

    /**
     * Hide the page
     *
     * @param \stdClass $nodeData
     * @return array
     */
    public function disableNode($nodeData)
    {
        /** @var $node PagetreeNode */
        $node = GeneralUtility::makeInstance(PagetreeNode::class, (array)$nodeData);
        try {
            Commands::disableNode($node);
            $newNode = Commands::getNode($node->getId());
            $newNode->setLeaf($node->isLeafNode());
            $returnValue = $newNode->toArray();
        } catch (\Exception $exception) {
            $returnValue = [
                'success' => false,
                'message' => $exception->getMessage()
            ];
        }
        return $returnValue;
    }

    /**
     * Delete the page
     *
     * @param \stdClass $nodeData
     * @return array
     */
    public function deleteNode($nodeData)
    {
        /** @var $node PagetreeNode */
        $node = GeneralUtility::makeInstance(PagetreeNode::class, (array)$nodeData);
        try {
            Commands::deleteNode($node);
            $returnValue = [];
            if (static::getBackendUser()->workspace) {
                $record = Commands::getNodeRecord($node->getId());
                if ($record['_ORIG_uid']) {
                    $newNode = Commands::getNewNode($record);
                    $returnValue = $newNode->toArray();
                }
            }
        } catch (\Exception $exception) {
            $returnValue = [
                'success' => false,
                'message' => $exception->getMessage()
            ];
        }
        return $returnValue;
    }

    /**
     * Restore the page
     *
     * @param \stdClass $nodeData
     * @param int $destination
     * @return array
     */
    public function restoreNode($nodeData, $destination)
    {
        /** @var $node PagetreeNode */
        $node = GeneralUtility::makeInstance(PagetreeNode::class, (array)$nodeData);
        try {
            Commands::restoreNode($node, $destination);
            $newNode = Commands::getNode($node->getId());
            $returnValue = $newNode->toArray();
        } catch (\Exception $exception) {
            $returnValue = [
                'success' => false,
                'message' => $exception->getMessage()
            ];
        }
        return $returnValue;
    }

    /**
     * Updates the given field with a new text value, may be used to inline update
     * the title field in the new page tree
     *
     * @param \stdClass $nodeData
     * @param string $updatedLabel
     * @return array
     */
    public function updateLabel($nodeData, $updatedLabel)
    {
        if ($updatedLabel === '') {
            return [];
        }
        /** @var $node PagetreeNode */
        $node = GeneralUtility::makeInstance(PagetreeNode::class, (array)$nodeData);
        try {
            Commands::updateNodeLabel($node, $updatedLabel);
            $shortendedText = GeneralUtility::fixed_lgd_cs($updatedLabel, (int)static::getBackendUser()->uc['titleLen']);
            $returnValue = [
                'editableText' => $updatedLabel,
                'updatedText' => htmlspecialchars($shortendedText)
            ];
        } catch (\Exception $exception) {
            $returnValue = [
                'success' => false,
                'message' => $exception->getMessage()
            ];
        }
        return $returnValue;
    }

    /**
     * Sets a temporary mount point
     *
     * @param \stdClass $nodeData
     * @return array
     */
    public static function setTemporaryMountPoint($nodeData)
    {
        /** @var $node PagetreeNode */
        $node = GeneralUtility::makeInstance(PagetreeNode::class, (array)$nodeData);
        static::getBackendUser()->uc['pageTree_temporaryMountPoint'] = $node->getId();
        static::getBackendUser()->writeUC(static::getBackendUser()->uc);
        return Commands::getMountPointPath();
    }

    /**
     * Moves the source node directly as the first child of the destination node
     *
     * @param \stdClass $nodeData
     * @param int $destination
     * @return array
     */
    public function moveNodeToFirstChildOfDestination($nodeData, $destination)
    {
        /** @var $node PagetreeNode */
        $node = GeneralUtility::makeInstance(PagetreeNode::class, (array)$nodeData);
        try {
            Commands::moveNode($node, $destination);
            $newNode = Commands::getNode($node->getId(), false);
            $newNode->setLeaf($node->isLeafNode());
            $returnValue = $newNode->toArray();
        } catch (\Exception $exception) {
            $returnValue = [
                'success' => false,
                'message' => $exception->getMessage()
            ];
        }
        return $returnValue;
    }

    /**
     * Moves the source node directly after the destination node
     *
     * @param \stdClass $nodeData
     * @param int $destination
     * @return array
     */
    public function moveNodeAfterDestination($nodeData, $destination)
    {
        /** @var $node PagetreeNode */
        $node = GeneralUtility::makeInstance(PagetreeNode::class, (array)$nodeData);
        try {
            Commands::moveNode($node, -$destination);
            $newNode = Commands::getNode($node->getId(), false);
            $newNode->setLeaf($node->isLeafNode());
            $returnValue = $newNode->toArray();
        } catch (\Exception $exception) {
            $returnValue = [
                'success' => false,
                'message' => $exception->getMessage()
            ];
        }
        return $returnValue;
    }

    /**
     * Copies the source node directly as the first child of the destination node and
     * returns the created node.
     *
     * @param \stdClass $nodeData
     * @param int $destination
     * @return array
     */
    public function copyNodeToFirstChildOfDestination($nodeData, $destination)
    {
        /** @var $node PagetreeNode */
        $node = GeneralUtility::makeInstance(PagetreeNode::class, (array)$nodeData);
        try {
            $newPageId = Commands::copyNode($node, $destination);
            $newNode = Commands::getNode($newPageId);
            $newNode->setLeaf($node->isLeafNode());
            $returnValue = $newNode->toArray();
        } catch (\Exception $exception) {
            $returnValue = [
                'success' => false,
                'message' => $exception->getMessage()
            ];
        }
        return $returnValue;
    }

    /**
     * Copies the source node directly after the destination node and returns the
     * created node.
     *
     * @param \stdClass $nodeData
     * @param int $destination
     * @return array
     */
    public function copyNodeAfterDestination($nodeData, $destination)
    {
        /** @var $node PagetreeNode */
        $node = GeneralUtility::makeInstance(PagetreeNode::class, (array)$nodeData);
        try {
            $newPageId = Commands::copyNode($node, -$destination);
            $newNode = Commands::getNode($newPageId);
            $newNode->setLeaf($node->isLeafNode());
            $returnValue = $newNode->toArray();
        } catch (\Exception $exception) {
            $returnValue = [
                'success' => false,
                'message' => $exception->getMessage()
            ];
        }
        return $returnValue;
    }

    /**
     * Inserts a new node as the first child node of the destination node and returns the created node.
     *
     * @param \stdClass $parentNodeData
     * @param int $pageType
     * @return array
     */
    public function insertNodeToFirstChildOfDestination($parentNodeData, $pageType)
    {
        /** @var $parentNode PagetreeNode */
        $parentNode = GeneralUtility::makeInstance(PagetreeNode::class, (array)$parentNodeData);
        try {
            $newPageId = Commands::createNode($parentNode, $parentNode->getId(), $pageType);
            $returnValue = Commands::getNode($newPageId)->toArray();
        } catch (\Exception $exception) {
            $returnValue = [
                'success' => false,
                'message' => $exception->getMessage()
            ];
        }
        return $returnValue;
    }

    /**
     * Inserts a new node directly after the destination node and returns the created node.
     *
     * @param \stdClass $parentNodeData
     * @param int $destination
     * @param int $pageType
     * @return array
     */
    public function insertNodeAfterDestination($parentNodeData, $destination, $pageType)
    {
        /** @var $parentNode PagetreeNode */
        $parentNode = GeneralUtility::makeInstance(PagetreeNode::class, (array)$parentNodeData);
        try {
            $newPageId = Commands::createNode($parentNode, -$destination, $pageType);
            $returnValue = Commands::getNode($newPageId)->toArray();
        } catch (\Exception $exception) {
            $returnValue = [
                'success' => false,
                'message' => $exception->getMessage()
            ];
        }
        return $returnValue;
    }

    /**
     * Returns the view link of a given node
     *
     * @param \stdClass $nodeData
     * @return string
     */
    public static function getViewLink($nodeData)
    {
        /** @var $node PagetreeNode */
        $node = GeneralUtility::makeInstance(PagetreeNode::class, (array)$nodeData);
        $javascriptLink = BackendUtility::viewOnClick($node->getId());
        $extractedLink = '';
        if (preg_match('/window\\.open\\(\'([^\']+)\'/i', $javascriptLink, $match)) {
            // Clean JSON-serialized ampersands ('&')
            // @see GeneralUtility::quoteJSvalue()
            $extractedLink = json_decode('"' . trim($match[1], '"') . '"', JSON_HEX_AMP);
        }
        return $extractedLink;
    }

    /**
     * Adds the rootline of a given node to the tree expansion state and adds the node
     * itself as the current selected page. This leads to the expansion and selection of
     * the node in the tree after a refresh.
     *
     * @static
     * @param string $stateId
     * @param int $nodeId
     * @return array
     */
    public static function addRootlineOfNodeToStateHash($stateId, $nodeId)
    {
        $mountPoints = array_map('intval', static::getBackendUser()->returnWebmounts());
        if (empty($mountPoints)) {
            $mountPoints = [0];
        }
        if (!empty(static::getBackendUser()->uc['pageTree_temporaryMountPoint'])) {
            $mountPoints[] = (int)static::getBackendUser()->uc['pageTree_temporaryMountPoint'];
        }
        $mountPoints = array_unique($mountPoints);
        /** @var $userSettingsController UserSettingsController */
        $userSettingsController = GeneralUtility::makeInstance(UserSettingsController::class);
        $state = $userSettingsController->process('get', 'BackendComponents.States.' . $stateId);
        if (empty($state)) {
            $state = new \stdClass();
            $state->stateHash = new \stdClass();
        }
        $state->stateHash = (object)$state->stateHash;
        $rootline = BackendUtility::BEgetRootLine($nodeId, '', (int)static::getBackendUser()->workspace !== 0);
        $rootlineIds = [];
        foreach ($rootline as $pageData) {
            $rootlineIds[] = (int)$pageData['uid'];
        }
        foreach ($mountPoints as $mountPoint) {
            if (!in_array($mountPoint, $rootlineIds, true)) {
                continue;
            }
            $isFirstNode = true;
            foreach ($rootline as $pageData) {
                $node = Commands::getNewNode($pageData, $mountPoint);
                if ($isFirstNode) {
                    $isFirstNode = false;
                    $state->stateHash->lastSelectedNode = $node->calculateNodeId();
                } else {
                    $state->stateHash->{$node->calculateNodeId('')} = 1;
                }
            }
        }
        $userSettingsController->process('set', 'BackendComponents.States.' . $stateId, $state);
        return (array)$state->stateHash;
    }

    /**
     * Gets the path steps for a given page.
     * This methods considers multiple mount points,
     * thus the returned array is multidimensional, e.g.
     *
     * array(
     *   array('p0', 'p1', 'p13', 'p44'),
     *   array('p0', 'p13-1', 'p44-1'),
     * )
     *
     * @param int $pageId
     * @return array
     */
    public static function getNodePaths($pageId)
    {
        $pagePaths = [];
        $mountPoints = array_map('intval', static::getBackendUser()->returnWebmounts());
        if (empty($mountPoints)) {
            $mountPoints = [0];
        }
        $mountPoints[] = (int)static::getBackendUser()->uc['pageTree_temporaryMountPoint'];
        $mountPoints = array_unique($mountPoints);
        $rootLine = BackendUtility::BEgetRootLine($pageId, '', (int)static::getBackendUser()->workspace !== 0);
        $rootLineIds = [];
        foreach ($rootLine as $rootLineLevel) {
            $rootLineIds[] = (int)$rootLineLevel['uid'];
        }
        foreach ($mountPoints as $mountPoint) {
            $pagePath = [];
            if (!in_array($mountPoint, $rootLineIds, true)) {
                continue;
            }
            foreach ($rootLine as $rootLineLevel) {
                $node = Commands::getNewNode($rootLineLevel, $mountPoint);
                array_unshift($pagePath, $node->calculateNodeId());
                // Break if mount-point has been reached
                if ($mountPoint === (int)$rootLineLevel['uid']) {
                    break;
                }
            }
            // Attach valid partial root-lines
            if (!empty($pagePath)) {
                if ($mountPoint !== 0) {
                    array_unshift($pagePath, Commands::getNewNode(['uid' => 0])->calculateNodeId());
                }
                $pagePaths[] = $pagePath;
            }
        }
        return $pagePaths;
    }

    /**
     * @return BackendUserAuthentication
     */
    protected static function getBackendUser()
    {
        return $GLOBALS['BE_USER'];
    }
}
