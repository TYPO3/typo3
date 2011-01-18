<?php
/***************************************************************
*  Copyright notice
*
*  (c) 2010-2011 TYPO3 Tree Team <http://forge.typo3.org/projects/typo3v4-extjstrees>
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
 * Node designated for the page tree
 *
 * @author Stefan Galinski <stefan.galinski@gmail.com>
 * @package TYPO3
 * @subpackage t3lib
 */
class t3lib_tree_pagetree_Node extends t3lib_tree_extdirect_Node {
	/**
	 * Cached access rights to save some performance
	 *
	 * @var array
	 */
	protected $cachedAccessRights = array();

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
	protected $isMountPoint = FALSE;

	/**
	 * Set's the original id of the element
	 *
	 * @param int $workspaceId
	 * @return void
	 */
	public function setWorkspaceId($workspaceId) {
		$this->workspaceId = intval($workspaceId);
	}

	/**
	 * Returns the original id of the element
	 *
	 * @return int
	 */
	public function getWorkspaceId() {
		return $this->workspaceId;
	}

	/**
	 * Sets the mount point id
	 *
	 * @param int $mountPoint
	 * @return void
	 */
	public function setMountPoint($mountPoint) {
		$this->mountPoint = intval($mountPoint);
	}

	/**
	 * Returns the mount point id
	 *
	 * @return int
	 */
	public function getMountPoint() {
		return $this->mountPoint;
	}

	/**
	 * Sets the indicator if the node is a mount point
	 *
	 * @param boolean $isMountPoint
	 * @return void
	 */
	public function setIsMountPoint($isMountPoint) {
		$this->isMountPoint = ($isMountPoint == TRUE);
	}

	/**
	 * Returns true if the node is a mount point
	 *
	 * @return bool
	 */
	public function isMountPoint() {
		return $this->isMountPoint;
	}

	/**
	 * Sets the readable rootline
	 *
	 * @param string $rootline
	 * @return void
	 */
	public function setReadableRootline($rootline) {
		$this->readableRootline = $rootline;
	}

	/**
	 * Returns the readable rootline
	 *
	 * @return string
	 */
	public function getReadableRootline() {
		return $this->readableRootline;
	}

	/**
	 * Checks if the user may create pages below the given page
	 *
	 * @return void
	 */
	protected function canCreate() {
		if (!isset($this->cachedAccessRights['create'])) {
			$this->cachedAccessRights['create'] =
				$GLOBALS['BE_USER']->doesUserHaveAccess($this->record, 8);
		}

		return $this->cachedAccessRights['create'];
	}

	/**
	 * Checks if the user has editing rights
	 *
	 * @return void
	 */
	protected function canEdit() {
		if (!isset($this->cachedAccessRights['edit'])) {
			$this->cachedAccessRights['edit'] =
				$GLOBALS['BE_USER']->doesUserHaveAccess($this->record, 2);
		}

		return $this->cachedAccessRights['edit'];
	}

	/**
	 * Checks if the user has the right to delete the page
	 *
	 * @return void
	 */
	protected function canRemove()	{
		if (!isset($this->cachedAccessRights['remove'])) {
			$this->cachedAccessRights['remove'] =
				$GLOBALS['BE_USER']->doesUserHaveAccess($this->record, 4);

			if (!$this->isLeafNode() && !$GLOBALS['BE_USER']->uc['recursiveDelete']) {
				$this->cachedAccessRights['remove'] = FALSE;
			}
		}

		return $this->cachedAccessRights['remove'];
	}

	/**
	 * Checks if the page can be disabled
	 *
	 * @return void
	 */
	public function canBeDisabledAndEnabled() {
		return $this->canEdit($this->record);
	}

	/**
	 * Checks if the page is allowed to can be cut
	 *
	 * @return void
	 */
	public function canBeCut() {
		return $this->canEdit($this->record) && intval($this->record['t3ver_state']) !== 2;
	}

	/**
	 * Checks if the page is allowed to be edited
	 *
	 * @return void
	 */
	public function canBeEdited() {
		return $this->canEdit($this->record);
	}

	/**
	 * Checks if the page is allowed to be copied
	 *
	 * @return void
	 */
	public function canBeCopied() {
		return $this->canCreate($this->record) && intval($this->record['t3ver_state']) !== 2;
	}

	/**
	 * Checks if there can be new pages created
	 *
	 * @return void
	 */
	public function canCreateNewPages() {
		return $this->canCreate($this->record);
	}

	/**
	 * Checks if the page is allowed to be removed
	 *
	 * @return void
	 */
	public function canBeRemoved() {
		return $this->canRemove($this->record) && intval($this->record['t3ver_state']) !== 2;
	}

	/**
	 * Checks if something can be pasted into the node
	 *
	 * @return bool
	 */
	public function canBePastedInto() {
		return intval($this->record['t3ver_state']) !== 2;
	}

	/**
	 * Checks if something can be pasted after the node
	 *
	 * @return bool
	 */
	public function canBePastedAfter() {
		return intval($this->record['t3ver_state']) !== 2;
	}

	/**
	 * Checks if the page is allowed to show history
	 *
	 * @return void
	 */
	public function canShowHistory() {
		return TRUE;
	}

	/**
	 * Checks if the page is allowed to be viewed
	 *
	 * @return void
	 */
	public function canBeViewed() {
		return TRUE;
	}

	/**
	 * Checks if the page is allowed to show info
	 *
	 * @return void
	 */
	public function canShowInfo() {
		return TRUE;
	}

	/**
	 * Checks if the page is allowed to be a temporary mount point
	 *
	 * @return void
	 */
	public function canBeTemporaryMountPoint() {
		return TRUE;
	}

	/**
	 * Returns the node in an array representation that can be used for serialization
	 *
	 * @return array
	 */
	public function toArray() {
		$arrayRepresentation = parent::toArray();

		$arrayRepresentation['id'] = 'mp-' . $this->getMountPoint() . '-' . $this->getId();
		$arrayRepresentation['realId'] = $this->getId();
		$arrayRepresentation['nodeData']['id'] = $this->getId();

		$arrayRepresentation['readableRootline'] = $this->getReadableRootline();
		$arrayRepresentation['nodeData']['readableRootline'] = $this->getReadableRootline();

		$arrayRepresentation['nodeData']['mountPoint'] = $this->getMountPoint();
		$arrayRepresentation['nodeData']['workspaceId'] = $this->getWorkspaceId();
		$arrayRepresentation['nodeData']['isMountPoint'] = $this->isMountPoint();
		$arrayRepresentation['nodeData']['serializeClassName'] = get_class($this);

		return $arrayRepresentation;
	}

	/**
	 * Sets data of the node by a given data array
	 *
	 * @param array $data
	 * @return void
	 */
	public function dataFromArray($data) {
		parent::dataFromArray($data);
		$this->setWorkspaceId($data['workspaceId']);
		$this->setMountPoint($data['mountPoint']);
		$this->setReadableRootline($data['readableRootline']);
		$this->setIsMountPoint($data['isMountPoint']);
	}
}

if (defined('TYPO3_MODE') && isset($GLOBALS['TYPO3_CONF_VARS'][TYPO3_MODE]['XCLASS']['t3lib/tree/pagetree/class.t3lib_tree_pagetree_node.php'])) {
	include_once($GLOBALS['TYPO3_CONF_VARS'][TYPO3_MODE]['XCLASS']['t3lib/tree/pagetree/class.t3lib_tree_pagetree_node.php']);
}

?>