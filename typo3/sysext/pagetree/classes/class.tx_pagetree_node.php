<?php
/***************************************************************
*  Copyright notice
*
*  (c) 2010 TYPO3 Tree Team <http://forge.typo3.org/projects/typo3v4-extjstrees>
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
 * @subpackage tx_pagetree
 */
class tx_pagetree_Node extends t3lib_tree_Node {
	/**
	 * Node type
	 *
	 * @var string
	 */
	protected $type = 'page';

	/**
	 * Leaf Node Indicator
	 *
	 * @var bool
	 */
	protected $leaf = TRUE;

	/**
	 * Expanded Node Indicator
	 *
	 * @var bool
	 */
	protected $expanded = FALSE;

	/**
	 * Label
	 *
	 * @var string
	 */
	protected $text = '';

	/**
	 * Prefix text of the label
	 *
	 * @var string
	 */
	protected $prefix = '';

	/**
	 * CSS Class
	 *
	 * @var string
	 */
	protected $cls = '';

	/**
	 * Quick Tip
	 *
	 * @var string
	 */
	protected $qtip = '';

	/**
	 * Sprite Icon HTML
	 *
	 * @var string
	 */
	protected $spriteIconCode = '';

	/**
	 * Text source field (title, nav_title, ...)
	 *
	 * @var string
	 */
	protected $t3TextSourceField = '';

	/**
	 * Callback action on a node click
	 *
	 * @var string
	 */
	protected $t3CallbackAction = 'TYPO3.Components.PageTree.PageActions.singleClick';

	/**
	 * Indicator if the copy mode is activated
	 *
	 * @var bool
	 */
	protected $t3InCopyMode = FALSE;

	/**
	 * Indicator if the cut mode is activated
	 *
	 * @var bool
	 */
	protected $t3InCutMode = FALSE;

	/**
	 * Database record (not serialized or merged into the result array!)
	 *
	 * @var array
	 */
	protected $record = array();

	/**
	 * Context Info
	 *
	 * @var array
	 */
	protected $contextInfo = array();

	/**
	 * Cached access rights to save some performance
	 *
	 * @var array
	 */
	protected $cachedAccessRights = array();

	/**
	 * Indicator if the label is editable
	 *
	 * @var bool
	 */
	protected $editableLabel = TRUE;

	/**
	 * Set's the node type
	 *
	 * @param string $type
	 * @return void
	 */
	public function setType($type) {
		$this->type = $type;
	}

	/**
	 * Returns the node type
	 *
	 * @return string
	 */
	public function getType() {
		return $this->type;
	}

	/**
	 * Sets the leaf node indicator
	 *
	 * @param bool $isLeaf
	 * @return void
	 */
	public function setLeaf($isLeaf) {
		$this->leaf = $isLeaf;
	}

	/**
	 * Returns if the node is a leaf node
	 *
	 * @return bool
	 */
	public function isLeafNode() {
		return $this->leaf;
	}

	/**
	 * Sets the expanded indicator
	 *
	 * @param bool $expanded
	 * @return void
	 */
	public function setExpanded($expanded) {
		$this->expanded = $expanded;
	}

	/**
	 * Returns the expanded indicator
	 *
	 * @return bool
	 */
	public function isExpanded() {
		return $this->expanded;
	}

	/**
	 * Sets the label of the node with the source field and the prefix
	 *
	 * @param string $text
	 * @param string $textSourceField
	 * @param string $prefix
	 * @return void
	 */
	public function setText($text, $textSourceField = 'title', $prefix = '') {
		$this->text = $text;
		$this->t3TextSourceField = $textSourceField;
		$this->prefix = $prefix;
	}

	/**
	 * Returns the label
	 *
	 * @return string
	 */
	public function getText() {
		return $this->text;
	}

	/**
	 * Returns the source field of the label
	 *
	 * @return string
	 */
	public function getTextSourceField() {
		return $this->t3TextSourceField;
	}

	/**
	 * Sets the paste copy indicator
	 *
	 * @param boolean $inCopyMode
	 * @return void
	 */
	public function setInCopyMode($inCopyMode) {
		$this->t3InCopyMode = $inCopyMode;
	}

	/**
	 * Returns the copy mode indicator
	 * 
	 * @return bool
	 */
	public function isInCopyMode() {
		return $this->t3InCopyMode;
	}

	/**
	 * Sets the paste cut indicator
	 *
	 * @param boolean $inCutMode
	 * @return void
	 */
	public function setInCutMode($inCutMode) {
		$this->t3InCutMode = $inCutMode;
	}

	/**
	 * Returns the cut mode indicator
	 *
	 * @return bool
	 */
	public function isInCutMode() {
		return $this->t3InCutMode;
	}

	/**
	 * Returns the prefix text of the label
	 *
	 * @return string
	 */
	public function getPrefix() {
		return $this->prefix;
	}

	/**
	 * Sets the css class(es)
	 *
	 * @param string $class
	 * @return void
	 */
	public function setCls($class) {
		$this->cls = $class;
	}

	/**
	 * Returns the css class(es)
	 *
	 * @return string
	 */
	public function getCls() {
		return $this->cls;
	}

	/**
	 * Sets the quick tip
	 *
	 * @param string $qtip
	 * @return void
	 */
	public function setQTip($qtip) {
		$this->qtip = $qtip;
	}

	/**
	 * Returns the quick tip
	 *
	 * @return string
	 */
	public function getQTip() {
		return $this->qtip;
	}

	/**
	 * Sets the sprite icon code
	 *
	 * @param string $spriteIcon
	 * @return void
	 */
	public function setSpriteIconCode($spriteIcon) {
		$this->spriteIconCode = $spriteIcon;
	}

	/**
	 * Returns the sprite icon code
	 *
	 * @return string
	 */
	public function getSpriteIconCode() {
		return $this->spriteIconCode;
	}

	/**
	 * Sets the callback action
	 *
	 * @param string $callbackAction
	 * @return void
	 */
	public function setCallbackAction($callbackAction) {
		$this->t3CallbackAction = $callbackAction;
	}

	/**
	 * Returns the callback action
	 *
	 * @return string
	 */
	public function getCallbackAction() {
		return $this->t3CallbackAction;
	}

	/**
	 * Sets the indicator if the label is editable
	 *
	 * @param bool $labelIsEditable
	 * @return void
	 */
	public function setEditableLable($labelIsEditable) {
		$this->editableLabel = $labelIsEditable;
	}

	/**
	 * Returns the editable label indicator
	 *
	 * @return bool
	 */
	public function isLabelEditable() {
		return $this->editableLabel;
	}

	/**
	 * Sets the database record array
	 *
	 * @param array $record
	 * @return void
	 */
	public function setRecord($record) {
		$this->record = $record;
	}

	/**
	 * Returns the database record array
	 *
	 * @return array
	 */
	public function getRecord() {
		return $this->record;
	}

	/**
	 * Sets the context info
	 *
	 * @param array $contextInfo
	 * @return void
	 */
	public function setContextInfo($contextInfo) {
		$this->contextInfo = $contextInfo;
	}

	/**
	 * Returns the context info
	 *
	 * @return array
	 */
	public function getContextInfo() {
		return (array) $this->contextInfo;
	}

	/**
	 * Sets the child nodes collection
	 *
	 * @param t3lib_tree_NodeCollection $childNodes
	 * @return void
	 */
	public function setChildNodes(t3lib_tree_NodeCollection $childNodes) {
		parent::setChildNodes($childNodes);

		if ($childNodes->count()) {
			$this->setLeaf(FALSE);			
		}
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
		return $this->canEdit($this->record);
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
		return $this->canCreate($this->record);
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
		return $this->canRemove($this->record);
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
		$arrayRepresentation = array(
			'serializeClassName' => get_class($this),
			'id' => $this->getId(),
			'type' => $this->getType(),
			'leaf' => $this->isLeafNode(),
			'label' => $this->getText(),
			'text' => $this->getPrefix() . $this->getText(),
			'cls' => $this->getCls(),
			'prefix' => $this->getPrefix(),
			'qtip' => $this->getQTip(),
			'expanded' => $this->isExpanded(),
			'spriteIconCode' => $this->getSpriteIconCode(),
			't3TextSourceField' => $this->getTextSourceField(),
			't3CallbackAction' => $this->getCallbackAction(),
			't3InCopyMode' => $this->isInCopyMode(),
			't3InCutMode' => $this->isInCutMode(),
			't3ContextInfo' => $this->getContextInfo(),
			'editable' => $this->isLabelEditable(),
		);
		$arrayRepresentation['nodeData'] = $arrayRepresentation;

		$arrayRepresentation['children'] = '';
		if ($this->hasChildNodes()) {
			$arrayRepresentation['children'] = $this->childNodes->toArray();
		}

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

		$this->setType($data['type']);
		$this->setLeaf($data['leaf']);
		$this->setText($data['label'], $data['t3TextSourceField'], $data['prefix']);
		$this->setCls($data['cls']);
		$this->setQTip($data['qtip']);
		$this->setExpanded($data['expanded']);
		$this->setCallbackAction($data['t3CallbackAction']);
		$this->setSpriteIconCode($data['spriteIconCode']);
		$this->setInCopyMode($data['t3InCopyMode']);
		$this->setInCutMode($data['t3InCutMode']);
		$this->setContextInfo($data['t3ContextInfo']);
		$this->setEditableLable($data['editable']);
	}
}

if (defined('TYPO3_MODE') && $TYPO3_CONF_VARS[TYPO3_MODE]['XCLASS']['typo3/sysext/pagetree/classes/class.tx_pagetree_node.php']) {
	include_once($TYPO3_CONF_VARS[TYPO3_MODE]['XCLASS']['typo3/sysext/pagetree/classes/class.tx_pagetree_node.php']);
}

?>