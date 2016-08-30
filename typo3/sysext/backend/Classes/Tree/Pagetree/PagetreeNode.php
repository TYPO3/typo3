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

 use TYPO3\CMS\Core\Versioning\VersionState;

 /**
 * Node designated for the page tree
 */
class PagetreeNode extends \TYPO3\CMS\Backend\Tree\ExtDirectNode
{
    /**
     * Cached access rights to save some performance
     *
     * @var array
     */
    protected $cachedAccessRights = [];

    /**
     * Workspace Overlay Id
     *
     * @var int
     */
    protected $workspaceId = 0;

    /**
     * Mount Point Id
     *
     * @var int
     */
    protected $mountPoint = 0;

    /**
     * Readable Rootline
     *
     * @var string
     */
    protected $readableRootline = '';

    /**
     * Indicator if the node is a mount point
     *
     * @var bool
     */
    protected $isMountPoint = false;

    /**
     * Indicator if the page tree should stop here
     *
     * @var bool
     */
    protected $stopPageTree = false;

    /**
     * Background color for the node
     *
     * @var string
     */
    protected $backgroundColor = '';

    /**
     * Sets the background color
     *
     * @param string $backgroundColor
     * @return void
     */
    public function setBackgroundColor($backgroundColor)
    {
        $this->backgroundColor = $backgroundColor;
    }

    /**
     * Returns the background color
     *
     * @return string
     */
    public function getBackgroundColor()
    {
        return $this->backgroundColor;
    }

    /**
     * Set's the original id of the element
     *
     * @param int $workspaceId
     * @return void
     */
    public function setWorkspaceId($workspaceId)
    {
        $this->workspaceId = (int)$workspaceId;
    }

    /**
     * Returns the original id of the element
     *
     * @return int
     */
    public function getWorkspaceId()
    {
        return $this->workspaceId;
    }

    /**
     * Sets if the pagetree should stop here
     *
     * @param bool $stopPageTree
     */
    public function setStopPageTree($stopPageTree)
    {
        $this->stopPageTree = (bool)$stopPageTree;
    }

    /**
     * Returns if the pagetree should stop here
     *
     * @return int
     */
    public function getStopPageTree()
    {
        return $this->stopPageTree;
    }

    /**
     * Sets the mount point id
     *
     * @param int $mountPoint
     * @return void
     */
    public function setMountPoint($mountPoint)
    {
        $this->mountPoint = (int)$mountPoint;
    }

    /**
     * Returns the mount point id
     *
     * @return int
     */
    public function getMountPoint()
    {
        return $this->mountPoint;
    }

    /**
     * Sets the indicator if the node is a mount point
     *
     * @param bool $isMountPoint
     * @return void
     */
    public function setIsMountPoint($isMountPoint)
    {
        $this->isMountPoint = $isMountPoint == true;
    }

    /**
     * Returns TRUE if the node is a mount point
     *
     * @return bool
     */
    public function isMountPoint()
    {
        return $this->isMountPoint;
    }

    /**
     * Sets the readable rootline
     *
     * @param string $rootline
     * @return void
     */
    public function setReadableRootline($rootline)
    {
        $this->readableRootline = $rootline;
    }

    /**
     * Returns the readable rootline
     *
     * @return string
     */
    public function getReadableRootline()
    {
        return $this->readableRootline;
    }

    /**
     * Checks if the user may create pages below the given page
     *
     * @return bool
     */
    protected function canCreate()
    {
        if (!isset($this->cachedAccessRights['create'])) {
            $this->cachedAccessRights['create'] = $GLOBALS['BE_USER']->doesUserHaveAccess($this->record, 8);
        }
        return $this->cachedAccessRights['create'];
    }

    /**
     * Checks if the user has editing rights
     *
     * @return bool
     */
    protected function canEdit()
    {
        if (!isset($this->cachedAccessRights['edit'])) {
            $this->cachedAccessRights['edit'] =
                $GLOBALS['BE_USER']->isAdmin()
                || (
                    (int)$this->record['editlock'] === 0
                    && $GLOBALS['BE_USER']->doesUserHaveAccess($this->record, 2)
                );
        }
        return $this->cachedAccessRights['edit'];
    }

    /**
     * Checks if the user has the right to delete the page
     *
     * @return bool
     */
    protected function canRemove()
    {
        if (!isset($this->cachedAccessRights['remove'])) {
            $this->cachedAccessRights['remove'] =
                $GLOBALS['BE_USER']->isAdmin()
                || (
                    (int)$this->record['editlock'] === 0
                    && $GLOBALS['BE_USER']->doesUserHaveAccess($this->record, 4)
                );
            if (!$this->isLeafNode() && !$GLOBALS['BE_USER']->uc['recursiveDelete']) {
                $this->cachedAccessRights['remove'] = false;
            }
        }
        return $this->cachedAccessRights['remove'];
    }

    /**
     * Checks if the page can be disabled
     *
     * @return bool
     */
    public function canBeDisabledAndEnabled()
    {
        return $this->canEdit($this->record) && $GLOBALS['BE_USER']->checkLanguageAccess(0);
    }

    /**
     * Checks if the page is allowed to can be cut
     *
     * @return bool
     */
    public function canBeCut()
    {
        return
            $this->canEdit($this->record)
            && !VersionState::cast($this->record['t3ver_state'])->equals(VersionState::DELETE_PLACEHOLDER)
            && $GLOBALS['BE_USER']->checkLanguageAccess(0)
        ;
    }

    /**
     * Checks if the page is allowed to be edited
     *
     * @return bool
     */
    public function canBeEdited()
    {
        return $this->canEdit($this->record) && $GLOBALS['BE_USER']->checkLanguageAccess(0);
    }

    /**
     * Checks if the page is allowed to be copied
     *
     * @return bool
     */
    public function canBeCopied()
    {
        return
            $GLOBALS['BE_USER']->doesUserHaveAccess($this->record, 1)
            && !VersionState::cast($this->record['t3ver_state'])->equals(VersionState::DELETE_PLACEHOLDER)
            && $GLOBALS['BE_USER']->checkLanguageAccess(0)
        ;
    }

    /**
     * Checks if there can be new pages created
     *
     * @return bool
     */
    public function canCreateNewPages()
    {
        return $this->canCreate($this->record) && $GLOBALS['BE_USER']->checkLanguageAccess(0);
    }

    /**
     * Checks if the page is allowed to be removed
     *
     * @return bool
     */
    public function canBeRemoved()
    {
        return
            $this->canRemove($this->record)
            && !VersionState::cast($this->record['t3ver_state'])->equals(VersionState::DELETE_PLACEHOLDER)
            && $GLOBALS['BE_USER']->checkLanguageAccess(0)
        ;
    }

    /**
     * Checks if something can be pasted into the node
     *
     * @return bool
     */
    public function canBePastedInto()
    {
        return
            $this->canCreate($this->record)
            && !VersionState::cast($this->record['t3ver_state'])->equals(VersionState::DELETE_PLACEHOLDER)
            && $GLOBALS['BE_USER']->checkLanguageAccess(0)
        ;
    }

    /**
     * Checks if something can be pasted after the node
     *
     * @return bool
     */
    public function canBePastedAfter()
    {
        return
            $this->canCreate($this->record)
            && !VersionState::cast($this->record['t3ver_state'])->equals(VersionState::DELETE_PLACEHOLDER)
            && $GLOBALS['BE_USER']->checkLanguageAccess(0)
        ;
    }

    /**
     * Checks if the page is allowed to show history
     *
     * @return bool
     */
    public function canShowHistory()
    {
        return $GLOBALS['BE_USER']->checkLanguageAccess(0);
    }

    /**
     * Checks if the page is allowed to be viewed
     *
     * @return bool
     */
    public function canBeViewed()
    {
        return !$this->isDeleted();
    }

    /**
     * Checks if the page is allowed to show info
     *
     * @return bool
     */
    public function canShowInfo()
    {
        return true;
    }

    /**
     * Checks if the page is allowed to be a temporary mount point
     *
     * @return bool
     */
    public function canBeTemporaryMountPoint()
    {
        return true;
    }

    /**
     * Determines whether this node is deleted.
     *
     * @return bool
     */
    public function isDeleted()
    {
        return
            !empty($this->record['deleted'])
            || VersionState::cast($this->record['t3ver_state'])->equals(VersionState::DELETE_PLACEHOLDER)
        ;
    }

    /**
     * Returns the calculated id representation of this node
     *
     * @param string $prefix Defaults to 'p'
     * @return string
     */
    public function calculateNodeId($prefix = 'p')
    {
        return $prefix . dechex($this->getId()) . ($this->getMountPoint() ? '-' . dechex($this->getMountPoint()) : '');
    }

    /**
     * Returns the node in an array representation that can be used for serialization
     *
     * @param bool $addChildNodes
     * @return array
     */
    public function toArray($addChildNodes = true)
    {
        $arrayRepresentation = parent::toArray();
        $arrayRepresentation['id'] = $this->calculateNodeId();
        $arrayRepresentation['realId'] = $this->getId();
        $arrayRepresentation['nodeData']['id'] = $this->getId();
        $arrayRepresentation['readableRootline'] = $this->getReadableRootline();
        $arrayRepresentation['nodeData']['readableRootline'] = $this->getReadableRootline();
        $arrayRepresentation['nodeData']['mountPoint'] = $this->getMountPoint();
        $arrayRepresentation['nodeData']['workspaceId'] = $this->getWorkspaceId();
        $arrayRepresentation['nodeData']['isMountPoint'] = $this->isMountPoint();
        $arrayRepresentation['nodeData']['backgroundColor'] = htmlspecialchars($this->getBackgroundColor());
        $arrayRepresentation['nodeData']['stopPageTree'] = $this->getStopPageTree();
        $arrayRepresentation['nodeData']['serializeClassName'] = get_class($this);
        return $arrayRepresentation;
    }

    /**
     * Sets data of the node by a given data array
     *
     * @param array $data
     * @return void
     */
    public function dataFromArray($data)
    {
        parent::dataFromArray($data);
        $this->setWorkspaceId($data['workspaceId']);
        $this->setMountPoint($data['mountPoint']);
        $this->setReadableRootline($data['readableRootline']);
        $this->setIsMountPoint($data['isMountPoint']);
        $this->setBackgroundColor($data['backgroundColor']);
    }
}
