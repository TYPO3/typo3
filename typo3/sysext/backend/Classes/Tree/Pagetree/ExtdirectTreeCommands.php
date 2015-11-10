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

use TYPO3\CMS\Backend\Utility\BackendUtility;
use TYPO3\CMS\Core\Utility\GeneralUtility;

/**
 * Commands for the Page tree
 */
class ExtdirectTreeCommands
{
    /**
     * Visibly the page
     *
     * @param stdClass $nodeData
     * @return array
     */
    public function visiblyNode($nodeData)
    {
        /** @var $node \TYPO3\CMS\Backend\Tree\Pagetree\PagetreeNode */
        $node = GeneralUtility::makeInstance(\TYPO3\CMS\Backend\Tree\Pagetree\PagetreeNode::class, (array)$nodeData);
        try {
            Commands::visiblyNode($node);
            $newNode = Commands::getNode($node->getId());
            $newNode->setLeaf($node->isLeafNode());
            $returnValue = $newNode->toArray();
        } catch (\Exception $exception) {
            $returnValue = array(
                'success' => false,
                'error' => $exception->getMessage()
            );
        }
        return $returnValue;
    }

    /**
     * Hide the page
     *
     * @param stdClass $nodeData
     * @return array
     */
    public function disableNode($nodeData)
    {
        /** @var $node \TYPO3\CMS\Backend\Tree\Pagetree\PagetreeNode */
        $node = GeneralUtility::makeInstance(\TYPO3\CMS\Backend\Tree\Pagetree\PagetreeNode::class, (array)$nodeData);
        try {
            Commands::disableNode($node);
            $newNode = Commands::getNode($node->getId());
            $newNode->setLeaf($node->isLeafNode());
            $returnValue = $newNode->toArray();
        } catch (\Exception $exception) {
            $returnValue = array(
                'success' => false,
                'message' => $exception->getMessage()
            );
        }
        return $returnValue;
    }

    /**
     * Delete the page
     *
     * @param stdClass $nodeData
     * @return array
     */
    public function deleteNode($nodeData)
    {
        /** @var $node \TYPO3\CMS\Backend\Tree\Pagetree\PagetreeNode */
        $node = GeneralUtility::makeInstance(\TYPO3\CMS\Backend\Tree\Pagetree\PagetreeNode::class, (array)$nodeData);
        try {
            Commands::deleteNode($node);
            $returnValue = array();
            if ($GLOBALS['BE_USER']->workspace) {
                $record = Commands::getNodeRecord($node->getId());
                if ($record['_ORIG_uid']) {
                    $newNode = Commands::getNewNode($record);
                    $returnValue = $newNode->toArray();
                }
            }
        } catch (\Exception $exception) {
            $returnValue = array(
                'success' => false,
                'message' => $exception->getMessage()
            );
        }
        return $returnValue;
    }

    /**
     * Restore the page
     *
     * @param stdClass $nodeData
     * @param int $destination
     * @return array
     */
    public function restoreNode($nodeData, $destination)
    {
        /** @var $node \TYPO3\CMS\Backend\Tree\Pagetree\PagetreeNode */
        $node = GeneralUtility::makeInstance(\TYPO3\CMS\Backend\Tree\Pagetree\PagetreeNode::class, (array)$nodeData);
        try {
            Commands::restoreNode($node, $destination);
            $newNode = Commands::getNode($node->getId());
            $returnValue = $newNode->toArray();
        } catch (\Exception $exception) {
            $returnValue = array(
                'success' => false,
                'message' => $exception->getMessage()
            );
        }
        return $returnValue;
    }

    /**
     * Updates the given field with a new text value, may be used to inline update
     * the title field in the new page tree
     *
     * @param stdClass $nodeData
     * @param string $updatedLabel
     * @return array
     */
    public function updateLabel($nodeData, $updatedLabel)
    {
        if ($updatedLabel === '') {
            return array();
        }
        /** @var $node \TYPO3\CMS\Backend\Tree\Pagetree\PagetreeNode */
        $node = GeneralUtility::makeInstance(\TYPO3\CMS\Backend\Tree\Pagetree\PagetreeNode::class, (array)$nodeData);
        try {
            Commands::updateNodeLabel($node, $updatedLabel);
            $shortendedText = GeneralUtility::fixed_lgd_cs($updatedLabel, (int)$GLOBALS['BE_USER']->uc['titleLen']);
            $returnValue = array(
                'editableText' => $updatedLabel,
                'updatedText' => htmlspecialchars($shortendedText)
            );
        } catch (\Exception $exception) {
            $returnValue = array(
                'success' => false,
                'message' => $exception->getMessage()
            );
        }
        return $returnValue;
    }

    /**
     * Sets a temporary mount point
     *
     * @param stdClass $nodeData
     * @return array
     */
    public static function setTemporaryMountPoint($nodeData)
    {
        /** @var $node \TYPO3\CMS\Backend\Tree\Pagetree\PagetreeNode */
        $node = GeneralUtility::makeInstance(\TYPO3\CMS\Backend\Tree\Pagetree\PagetreeNode::class, (array)$nodeData);
        $GLOBALS['BE_USER']->uc['pageTree_temporaryMountPoint'] = $node->getId();
        $GLOBALS['BE_USER']->writeUC($GLOBALS['BE_USER']->uc);
        return Commands::getMountPointPath();
    }

    /**
     * Moves the source node directly as the first child of the destination node
     *
     * @param stdClass $nodeData
     * @param int $destination
     * @return array
     */
    public function moveNodeToFirstChildOfDestination($nodeData, $destination)
    {
        /** @var $node \TYPO3\CMS\Backend\Tree\Pagetree\PagetreeNode */
        $node = GeneralUtility::makeInstance(\TYPO3\CMS\Backend\Tree\Pagetree\PagetreeNode::class, (array)$nodeData);
        try {
            Commands::moveNode($node, $destination);
            $newNode = Commands::getNode($node->getId(), false);
            $newNode->setLeaf($node->isLeafNode());
            $returnValue = $newNode->toArray();
        } catch (\Exception $exception) {
            $returnValue = array(
                'success' => false,
                'message' => $exception->getMessage()
            );
        }
        return $returnValue;
    }

    /**
     * Moves the source node directly after the destination node
     *
     * @param stdClass $nodeData
     * @param int $destination
     * @return void
     */
    public function moveNodeAfterDestination($nodeData, $destination)
    {
        /** @var $node \TYPO3\CMS\Backend\Tree\Pagetree\PagetreeNode */
        $node = GeneralUtility::makeInstance(\TYPO3\CMS\Backend\Tree\Pagetree\PagetreeNode::class, (array)$nodeData);
        try {
            Commands::moveNode($node, -$destination);
            $newNode = Commands::getNode($node->getId(), false);
            $newNode->setLeaf($node->isLeafNode());
            $returnValue = $newNode->toArray();
        } catch (\Exception $exception) {
            $returnValue = array(
                'success' => false,
                'message' => $exception->getMessage()
            );
        }
        return $returnValue;
    }

    /**
     * Copies the source node directly as the first child of the destination node and
     * returns the created node.
     *
     * @param stdClass $nodeData
     * @param int $destination
     * @return array
     */
    public function copyNodeToFirstChildOfDestination($nodeData, $destination)
    {
        /** @var $node \TYPO3\CMS\Backend\Tree\Pagetree\PagetreeNode */
        $node = GeneralUtility::makeInstance(\TYPO3\CMS\Backend\Tree\Pagetree\PagetreeNode::class, (array)$nodeData);
        /** @var $dataProvider \TYPO3\CMS\Backend\Tree\Pagetree\DataProvider */
        $dataProvider = GeneralUtility::makeInstance(\TYPO3\CMS\Backend\Tree\Pagetree\DataProvider::class);
        try {
            $newPageId = Commands::copyNode($node, $destination);
            $newNode = Commands::getNode($newPageId);
            $newNode->setLeaf($node->isLeafNode());
            $returnValue = $newNode->toArray();
        } catch (\Exception $exception) {
            $returnValue = array(
                'success' => false,
                'message' => $exception->getMessage()
            );
        }
        return $returnValue;
    }

    /**
     * Copies the source node directly after the destination node and returns the
     * created node.
     *
     * @param stdClass $nodeData
     * @param int $destination
     * @return array
     */
    public function copyNodeAfterDestination($nodeData, $destination)
    {
        /** @var $node \TYPO3\CMS\Backend\Tree\Pagetree\PagetreeNode */
        $node = GeneralUtility::makeInstance(\TYPO3\CMS\Backend\Tree\Pagetree\PagetreeNode::class, (array)$nodeData);
        /** @var $dataProvider \TYPO3\CMS\Backend\Tree\Pagetree\DataProvider */
        $dataProvider = GeneralUtility::makeInstance(\TYPO3\CMS\Backend\Tree\Pagetree\DataProvider::class);
        try {
            $newPageId = Commands::copyNode($node, -$destination);
            $newNode = Commands::getNode($newPageId);
            $newNode->setLeaf($node->isLeafNode());
            $returnValue = $newNode->toArray();
        } catch (\Exception $exception) {
            $returnValue = array(
                'success' => false,
                'message' => $exception->getMessage()
            );
        }
        return $returnValue;
    }

    /**
     * Inserts a new node as the first child node of the destination node and returns the created node.
     *
     * @param stdClass $parentNodeData
     * @param int $pageType
     * @return array
     */
    public function insertNodeToFirstChildOfDestination($parentNodeData, $pageType)
    {
        /** @var $parentNode \TYPO3\CMS\Backend\Tree\Pagetree\PagetreeNode */
        $parentNode = GeneralUtility::makeInstance(\TYPO3\CMS\Backend\Tree\Pagetree\PagetreeNode::class, (array)$parentNodeData);
        try {
            $newPageId = Commands::createNode($parentNode, $parentNode->getId(), $pageType);
            $returnValue = Commands::getNode($newPageId)->toArray();
        } catch (\Exception $exception) {
            $returnValue = array(
                'success' => false,
                'message' => $exception->getMessage()
            );
        }
        return $returnValue;
    }

    /**
     * Inserts a new node directly after the destination node and returns the created node.
     *
     * @param stdClass $parentNodeData
     * @param int $destination
     * @param int $pageType
     * @return array
     */
    public function insertNodeAfterDestination($parentNodeData, $destination, $pageType)
    {
        /** @var $parentNode \TYPO3\CMS\Backend\Tree\Pagetree\PagetreeNode */
        $parentNode = GeneralUtility::makeInstance(\TYPO3\CMS\Backend\Tree\Pagetree\PagetreeNode::class, (array)$parentNodeData);
        try {
            $newPageId = Commands::createNode($parentNode, -$destination, $pageType);
            $returnValue = Commands::getNode($newPageId)->toArray();
        } catch (\Exception $exception) {
            $returnValue = array(
                'success' => false,
                'message' => $exception->getMessage()
            );
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
        /** @var $node \TYPO3\CMS\Backend\Tree\Pagetree\PagetreeNode */
        $node = GeneralUtility::makeInstance(\TYPO3\CMS\Backend\Tree\Pagetree\PagetreeNode::class, (array)$nodeData);
        $javascriptLink = BackendUtility::viewOnClick($node->getId());
        $extractedLink = '';
        if (preg_match('/window\\.open\\(\'([^\']+)\'/i', $javascriptLink, $match)) {
            // Clean JSON-serialized ampersands ('&')
            // @see GeneralUtility::quoteJSvalue()
            $extractedLink = json_decode('"' . trim($match[1], '"') . '"', JSON_HEX_AMP);
        };
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
        $mountPoints = array_map('intval', $GLOBALS['BE_USER']->returnWebmounts());
        if (empty($mountPoints)) {
            $mountPoints = array(0);
        }
        $mountPoints[] = (int)$GLOBALS['BE_USER']->uc['pageTree_temporaryMountPoint'];
        $mountPoints = array_unique($mountPoints);
        /** @var $userSettingsController \TYPO3\CMS\Backend\Controller\UserSettingsController */
        $userSettingsController = GeneralUtility::makeInstance(\TYPO3\CMS\Backend\Controller\UserSettingsController::class);
        $state = $userSettingsController->process('get', 'BackendComponents.States.' . $stateId);
        if (empty($state)) {
            $state = new \StdClass();
            $state->stateHash = new \StdClass();
        }
        $state->stateHash = (object)$state->stateHash;
        $rootline = BackendUtility::BEgetRootLine($nodeId, '', $GLOBALS['BE_USER']->workspace != 0);
        $rootlineIds = array();
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
}
