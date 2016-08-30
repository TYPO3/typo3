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
use TYPO3\CMS\Core\Imaging\Icon;
use TYPO3\CMS\Core\Imaging\IconFactory;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Core\Utility\MathUtility;
use TYPO3\CMS\Core\Versioning\VersionState;

/**
 * Page Tree and Context Menu Commands
 */
class Commands
{
    /**
     * @var bool|null
     */
    protected static $useNavTitle = null;

    /**
     * @var bool|null
     */
    protected static $addIdAsPrefix = null;

    /**
     * @var bool|null
     */
    protected static $addDomainName = null;

    /**
     * @var array|null
     */
    protected static $backgroundColors = null;

    /**
     * @var int|null
     */
    protected static $titleLength = null;

    /**
     * Visibly the page
     *
     * @param \TYPO3\CMS\Backend\Tree\Pagetree\PagetreeNode $node
     * @return void
     */
    public static function visiblyNode(\TYPO3\CMS\Backend\Tree\Pagetree\PagetreeNode $node)
    {
        $data['pages'][$node->getWorkspaceId()]['hidden'] = 0;
        self::processTceCmdAndDataMap([], $data);
    }

    /**
     * Hide the page
     *
     * @param \TYPO3\CMS\Backend\Tree\Pagetree\PagetreeNode $node
     * @return void
     */
    public static function disableNode(\TYPO3\CMS\Backend\Tree\Pagetree\PagetreeNode $node)
    {
        $data['pages'][$node->getWorkspaceId()]['hidden'] = 1;
        self::processTceCmdAndDataMap([], $data);
    }

    /**
     * Delete the page
     *
     * @param \TYPO3\CMS\Backend\Tree\Pagetree\PagetreeNode $node
     * @return void
     */
    public static function deleteNode(\TYPO3\CMS\Backend\Tree\Pagetree\PagetreeNode $node)
    {
        $cmd['pages'][$node->getId()]['delete'] = 1;
        self::processTceCmdAndDataMap($cmd);
    }

    /**
     * Restore the page
     *
     * @param \TYPO3\CMS\Backend\Tree\Pagetree\PagetreeNode $node
     * @param int $targetId
     * @return void
     */
    public static function restoreNode(\TYPO3\CMS\Backend\Tree\Pagetree\PagetreeNode $node, $targetId)
    {
        $cmd['pages'][$node->getId()]['undelete'] = 1;
        self::processTceCmdAndDataMap($cmd);
        if ($node->getId() !== $targetId) {
            self::moveNode($node, $targetId);
        }
    }

    /**
     * Updates the node label
     *
     * @param \TYPO3\CMS\Backend\Tree\Pagetree\PagetreeNode $node
     * @param string $updatedLabel
     * @return void
     */
    public static function updateNodeLabel(\TYPO3\CMS\Backend\Tree\Pagetree\PagetreeNode $node, $updatedLabel)
    {
        if ($GLOBALS['BE_USER']->checkLanguageAccess(0)) {
            $data['pages'][$node->getWorkspaceId()][$node->getTextSourceField()] = $updatedLabel;
            self::processTceCmdAndDataMap([], $data);
        } else {
            throw new \RuntimeException(implode(LF, ['Editing title of page id \'' . $node->getWorkspaceId() . '\' failed. Editing default language is not allowed.']), 1365513336);
        }
    }

    /**
     * Copies a page and returns the id of the new page
     *
     * Node: Use a negative target id to specify a sibling target else the parent is used
     *
     * @param \TYPO3\CMS\Backend\Tree\Pagetree\PagetreeNode $sourceNode
     * @param int $targetId
     * @return int
     */
    public static function copyNode(\TYPO3\CMS\Backend\Tree\Pagetree\PagetreeNode $sourceNode, $targetId)
    {
        $cmd['pages'][$sourceNode->getId()]['copy'] = $targetId;
        $returnValue = self::processTceCmdAndDataMap($cmd);
        return $returnValue['pages'][$sourceNode->getId()];
    }

    /**
     * Moves a page
     *
     * Node: Use a negative target id to specify a sibling target else the parent is used
     *
     * @param \TYPO3\CMS\Backend\Tree\Pagetree\PagetreeNode $sourceNode
     * @param int $targetId
     * @return void
     */
    public static function moveNode(\TYPO3\CMS\Backend\Tree\Pagetree\PagetreeNode $sourceNode, $targetId)
    {
        $cmd['pages'][$sourceNode->getId()]['move'] = $targetId;
        self::processTceCmdAndDataMap($cmd);
    }

    /**
     * Creates a page of the given doktype and returns the id of the created page
     *
     * @param \TYPO3\CMS\Backend\Tree\Pagetree\PagetreeNode $parentNode
     * @param int $targetId
     * @param int $pageType
     * @return int
     */
    public static function createNode(\TYPO3\CMS\Backend\Tree\Pagetree\PagetreeNode $parentNode, $targetId, $pageType)
    {
        $placeholder = 'NEW12345';
        $pid = (int)$parentNode->getWorkspaceId();
        $targetId = (int)$targetId;

        // Use page TsConfig as default page initialization
        $pageTs = BackendUtility::getPagesTSconfig($pid);
        if (array_key_exists('TCAdefaults.', $pageTs) && array_key_exists('pages.', $pageTs['TCAdefaults.'])) {
            $data['pages'][$placeholder] = $pageTs['TCAdefaults.']['pages.'];
        } else {
            $data['pages'][$placeholder] = [];
        }

        $data['pages'][$placeholder]['pid'] = $pid;
        $data['pages'][$placeholder]['doktype'] = $pageType;
        $data['pages'][$placeholder]['title'] = $GLOBALS['LANG']->sL('LLL:EXT:lang/locallang_core.xlf:tree.defaultPageTitle', true);
        $newPageId = self::processTceCmdAndDataMap([], $data);
        $node = self::getNode($newPageId[$placeholder]);
        if ($pid !== $targetId) {
            self::moveNode($node, $targetId);
        }

        return $newPageId[$placeholder];
    }

    /**
     * Process TCEMAIN commands and data maps
     *
     * Command Map:
     * Used for moving, recover, remove and some more operations.
     *
     * Data Map:
     * Used for creating and updating records,
     *
     * This API contains all necessary access checks.
     *
     * @param array $cmd
     * @param array $data
     * @return array
     * @throws \RuntimeException if an error happened while the TCE processing
     */
    protected static function processTceCmdAndDataMap(array $cmd, array $data = [])
    {
        /** @var $tce \TYPO3\CMS\Core\DataHandling\DataHandler */
        $tce = GeneralUtility::makeInstance(\TYPO3\CMS\Core\DataHandling\DataHandler::class);
        $tce->stripslashes_values = 0;
        $tce->start($data, $cmd);
        $tce->copyTree = MathUtility::forceIntegerInRange($GLOBALS['BE_USER']->uc['copyLevels'], 0, 100);
        if (!empty($cmd)) {
            $tce->process_cmdmap();
            $returnValues = $tce->copyMappingArray_merged;
        } elseif (!empty($data)) {
            $tce->process_datamap();
            $returnValues = $tce->substNEWwithIDs;
        } else {
            $returnValues = [];
        }
        // check errors
        if (!empty($tce->errorLog)) {
            throw new \RuntimeException(implode(LF, $tce->errorLog), 1333754629);
        }
        return $returnValues;
    }

    /**
     * Returns a node from the given node id
     *
     * @param int $nodeId
     * @param bool $unsetMovePointers
     * @return \TYPO3\CMS\Backend\Tree\Pagetree\PagetreeNode
     */
    public static function getNode($nodeId, $unsetMovePointers = true)
    {
        $record = self::getNodeRecord($nodeId, $unsetMovePointers);
        return self::getNewNode($record);
    }

    /**
     * Returns the mount point path for a temporary mount or the given id
     *
     * @param int $uid
     * @return string
     */
    public static function getMountPointPath($uid = -1)
    {
        if ($uid === -1) {
            $uid = (int)$GLOBALS['BE_USER']->uc['pageTree_temporaryMountPoint'];
        }
        if ($uid <= 0) {
            return '';
        }
        if (self::$useNavTitle === null) {
            self::$useNavTitle = $GLOBALS['BE_USER']->getTSConfigVal('options.pageTree.showNavTitle');
        }
        $rootline = array_reverse(BackendUtility::BEgetRootLine($uid));
        array_shift($rootline);
        $path = [];
        foreach ($rootline as $rootlineElement) {
            $record = self::getNodeRecord($rootlineElement['uid']);
            $text = $record['title'];
            if (self::$useNavTitle && trim($record['nav_title']) !== '') {
                $text = $record['nav_title'];
            }
            $path[] = htmlspecialchars($text);
        }
        return '/' . implode('/', $path);
    }

    /**
     * Returns a node record from a given id
     *
     * @param int $nodeId
     * @param bool $unsetMovePointers
     * @return array
     */
    public static function getNodeRecord($nodeId, $unsetMovePointers = true)
    {
        $record = BackendUtility::getRecordWSOL('pages', $nodeId, '*', '', true, $unsetMovePointers);
        return $record;
    }

    /**
     * Returns the first configured domain name for a page
     *
     * @param int $uid
     * @return string
     */
    public static function getDomainName($uid)
    {
        $whereClause = 'pid=' . (int)$uid . BackendUtility::deleteClause('sys_domain') . BackendUtility::BEenableFields('sys_domain');
        $domain = $GLOBALS['TYPO3_DB']->exec_SELECTgetSingleRow('domainName', 'sys_domain', $whereClause, '', 'sorting');
        return is_array($domain) ? htmlspecialchars($domain['domainName']) : '';
    }

    /**
     * Creates a node with the given record information
     *
     * @param array $record
     * @param int $mountPoint
     * @return \TYPO3\CMS\Backend\Tree\Pagetree\PagetreeNode
     */
    public static function getNewNode($record, $mountPoint = 0)
    {
        $iconFactory = GeneralUtility::makeInstance(IconFactory::class);
        if (self::$titleLength === null) {
            self::$useNavTitle = $GLOBALS['BE_USER']->getTSConfigVal('options.pageTree.showNavTitle');
            self::$addIdAsPrefix = $GLOBALS['BE_USER']->getTSConfigVal('options.pageTree.showPageIdWithTitle');
            self::$addDomainName = $GLOBALS['BE_USER']->getTSConfigVal('options.pageTree.showDomainNameWithTitle');
            self::$backgroundColors = $GLOBALS['BE_USER']->getTSConfigProp('options.pageTree.backgroundColor');
            self::$titleLength = (int)$GLOBALS['BE_USER']->uc['titleLen'];
        }
        /** @var $subNode \TYPO3\CMS\Backend\Tree\Pagetree\PagetreeNode */
        $subNode = GeneralUtility::makeInstance(\TYPO3\CMS\Backend\Tree\Pagetree\PagetreeNode::class);
        $subNode->setRecord($record);
        $subNode->setCls($record['_CSSCLASS']);
        $subNode->setType('pages');
        $subNode->setId($record['uid']);
        $subNode->setStopPageTree($record['php_tree_stop']);
        $subNode->setMountPoint($mountPoint);
        $subNode->setWorkspaceId($record['_ORIG_uid'] ?: $record['uid']);
        $subNode->setBackgroundColor(self::$backgroundColors[$record['uid']]);
        $field = 'title';
        $text = $record['title'];
        if (self::$useNavTitle && trim($record['nav_title']) !== '') {
            $field = 'nav_title';
            $text = $record['nav_title'];
        }
        if (trim($text) === '') {
            $visibleText = '[' . $GLOBALS['LANG']->sL('LLL:EXT:lang/locallang_core.xlf:labels.no_title', true) . ']';
        } else {
            $visibleText = $text;
        }
        $visibleText = GeneralUtility::fixed_lgd_cs($visibleText, self::$titleLength);
        $suffix = '';
        if (self::$addDomainName) {
            $domain = self::getDomainName($record['uid']);
            $suffix = $domain !== '' ? ' [' . $domain . ']' : '';
        }
        $qtip = str_replace(' - ', '<br />', htmlspecialchars(BackendUtility::titleAttribForPages($record, '', false)));
        $prefix = '';
        $lockInfo = BackendUtility::isRecordLocked('pages', $record['uid']);
        if (is_array($lockInfo)) {
            $qtip .= '<br />' . htmlspecialchars($lockInfo['msg']);
            $prefix .= '<span class="typo3-pagetree-status">' . $iconFactory->getIcon('status-warning-in-use', Icon::SIZE_SMALL)->render() . '</span>';
        }
        // Call stats information hook
        $stat = '';
        if (is_array($GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS']['GLOBAL']['recStatInfoHooks'])) {
            $_params = ['pages', $record['uid']];
            $fakeThis = null;
            foreach ($GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS']['GLOBAL']['recStatInfoHooks'] as $_funcRef) {
                $stat .= GeneralUtility::callUserFunction($_funcRef, $_params, $fakeThis);
            }
        }
        $prefix .= htmlspecialchars(self::$addIdAsPrefix ? '[' . $record['uid'] . '] ' : '');
        $subNode->setEditableText($text);
        $subNode->setText(htmlspecialchars($visibleText), $field, $prefix, htmlspecialchars($suffix) . $stat);
        $subNode->setQTip($qtip);
        if ((int)$record['uid'] !== 0) {
            $spriteIconCode = $iconFactory->getIconForRecord('pages', $record, Icon::SIZE_SMALL)->render();
        } else {
            $spriteIconCode = $iconFactory->getIcon('apps-pagetree-root', Icon::SIZE_SMALL)->render();
        }
        $subNode->setSpriteIconCode($spriteIconCode);
        if (
            !$subNode->canCreateNewPages()
            || VersionState::cast($record['t3ver_state'])->equals(VersionState::DELETE_PLACEHOLDER)
        ) {
            $subNode->setIsDropTarget(false);
        }
        if (
            !$subNode->canBeEdited()
            || !$subNode->canBeRemoved()
            || VersionState::cast($record['t3ver_state'])->equals(VersionState::DELETE_PLACEHOLDER)
        ) {
            $subNode->setDraggable(false);
        }
        return $subNode;
    }
}
