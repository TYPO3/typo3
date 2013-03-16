<?php
namespace TYPO3\CMS\Backend\Tree;

/***************************************************************
 *  Copyright notice
 *
 *  (c) 2010-2013 TYPO3 Tree Team <http://forge.typo3.org/projects/typo3v4-extjstrees>
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
 * Node for the usage with ExtDirect and ExtJS
 *
 * @author Stefan Galinski <stefan.galinski@gmail.com>
 */
class ExtDirectNode extends \TYPO3\CMS\Backend\Tree\TreeNode {

	/**
	 * Node type
	 *
	 * @var string
	 */
	protected $type = '';

	/**
	 * Leaf Node Indicator
	 *
	 * @var bool
	 */
	protected $leaf = TRUE;

	/**
	 * Indicator if the node is expanded
	 *
	 * @var boolean
	 */
	protected $expanded = FALSE;

	/**
	 * Indicator if the node can be expanded
	 *
	 * @var boolean
	 */
	protected $expandable = FALSE;

	/**
	 * Indicator if the node is draggable
	 *
	 * @var boolean
	 */
	protected $draggable = TRUE;

	/**
	 * Indicator if the node is allowed as a drop target
	 *
	 * @var boolean
	 */
	protected $isDropTarget = TRUE;

	/**
	 * Label
	 *
	 * @var string
	 */
	protected $text = '';

	/**
	 * Editable Label text
	 *
	 * @var string
	 */
	protected $editableText = '';

	/**
	 * Prefix text of the label
	 *
	 * @var string
	 */
	protected $prefix = '';

	/**
	 * Suffix text of the label
	 *
	 * @var string
	 */
	protected $suffix = '';

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
	 * Indicator if the copy mode is activated
	 *
	 * @var boolean
	 */
	protected $t3InCopyMode = FALSE;

	/**
	 * Indicator if the cut mode is activated
	 *
	 * @var boolean
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
	 * Indicator if the label is editable
	 *
	 * @var boolean
	 */
	protected $labelIsEditable = TRUE;

	/**
	 * Indicator if the node can have children's
	 *
	 * @var boolean
	 */
	protected $allowChildren = TRUE;

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
	 * @param boolean $isLeaf
	 * @return void
	 */
	public function setLeaf($isLeaf) {
		$this->leaf = $isLeaf == TRUE;
	}

	/**
	 * Returns if the node is a leaf node
	 *
	 * @return boolean
	 */
	public function isLeafNode() {
		return $this->leaf;
	}

	/**
	 * Sets the expandable indicator
	 *
	 * @param boolean $expandable
	 * @return void
	 */
	public function setExpandable($expandable) {
		$this->expandable = $expandable == TRUE;
	}

	/**
	 * Returns the expandable indicator
	 *
	 * @return boolean
	 */
	public function isExpandable() {
		return $this->expandable;
	}

	/**
	 * Sets the expanded indicator
	 *
	 * @param boolean $expanded
	 * @return void
	 */
	public function setExpanded($expanded) {
		$this->expanded = $expanded == TRUE;
	}

	/**
	 * Returns the expanded indicator
	 *
	 * @return boolean
	 */
	public function isExpanded() {
		if ($this->isLeafNode()) {
			return TRUE;
		}
		return $this->expanded;
	}

	/**
	 * Sets the draggable indicator
	 *
	 * @param boolean $draggable
	 * @return void
	 */
	public function setDraggable($draggable) {
		$this->draggable = $draggable == TRUE;
	}

	/**
	 * Returns the draggable indicator
	 *
	 * @return boolean
	 */
	public function isDraggable() {
		return $this->draggable;
	}

	/**
	 * Sets the indicator if the node can be a drop target
	 *
	 * @param boolean $isDropTarget
	 * @return void
	 */
	public function setIsDropTarget($isDropTarget) {
		$this->isDropTarget = $isDropTarget == TRUE;
	}

	/**
	 * Returns the indicator if the node is a drop target
	 *
	 * @return boolean
	 */
	public function isDropTarget() {
		return $this->isDropTarget;
	}

	/**
	 * Sets the label of the node with the source field and the prefix
	 *
	 * @param string $text
	 * @param string $textSourceField
	 * @param string $prefix
	 * @param string $suffix
	 * @return void
	 */
	public function setText($text, $textSourceField = 'title', $prefix = '', $suffix = '') {
		$this->text = $text;
		$this->t3TextSourceField = $textSourceField;
		$this->prefix = $prefix;
		$this->suffix = $suffix;
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
	 * Sets the editable text
	 *
	 * @param string $editableText
	 * @return void
	 */
	public function setEditableText($editableText) {
		$this->editableText = $editableText;
	}

	/**
	 * Returns the editable text
	 *
	 * @return string
	 */
	public function getEditableText() {
		return $this->editableText;
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
		$this->t3InCopyMode = $inCopyMode == TRUE;
	}

	/**
	 * Returns the copy mode indicator
	 *
	 * @return boolean
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
		$this->t3InCutMode = $inCutMode == TRUE;
	}

	/**
	 * Returns the cut mode indicator
	 *
	 * @return boolean
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
	 * Returns the suffix text of the label
	 *
	 * @return string
	 */
	public function getSuffix() {
		return $this->suffix;
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
	 * Sets the indicator if the label is editable
	 *
	 * @param boolean $labelIsEditable
	 * @return void
	 */
	public function setLabelIsEditable($labelIsEditable) {
		$this->labelIsEditable = $labelIsEditable == TRUE;
	}

	/**
	 * Returns the editable label indicator
	 *
	 * @return boolean
	 */
	public function isLabelEditable() {
		return $this->labelIsEditable;
	}

	/**
	 * Sets the database record array
	 *
	 * @param array $record
	 * @return void
	 */
	public function setRecord($record) {
		$this->record = (array) $record;
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
		$this->contextInfo = (array) $contextInfo;
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
	 * @param \TYPO3\CMS\Backend\Tree\TreeNodeCollection $childNodes
	 * @return void
	 */
	public function setChildNodes(\TYPO3\CMS\Backend\Tree\TreeNodeCollection $childNodes) {
		parent::setChildNodes($childNodes);
		if ($childNodes->count()) {
			$this->setLeaf(FALSE);
		}
	}

	/**
	 * Sets the indicator if the node can have child nodes
	 *
	 * @param boolean $allowChildren
	 * @return void
	 */
	public function setAllowChildren($allowChildren) {
		$this->allowChildren = $allowChildren == TRUE;
	}

	/**
	 * Checks if the node can have child nodes
	 *
	 * @return boolean
	 */
	public function canHaveChildren() {
		return $this->allowChildren;
	}

	/**
	 * Returns the node in an array representation that can be used for serialization
	 *
	 * @param boolean $addChildNodes
	 * @return array
	 */
	public function toArray($addChildNodes = TRUE) {
		$arrayRepresentation = array(
			'serializeClassName' => get_class($this),
			'id' => $this->getId(),
			'type' => $this->getType(),
			'editableText' => $this->getEditableText(),
			'text' => $this->getPrefix() . $this->getText() . $this->getSuffix(),
			'cls' => $this->getCls(),
			'prefix' => $this->getPrefix(),
			'suffix' => $this->getSuffix(),
			'qtip' => $this->getQTip(),
			'expanded' => $this->isExpanded(),
			'expandable' => $this->isExpandable(),
			'draggable' => $this->isDraggable(),
			'isTarget' => $this->isDropTarget(),
			'spriteIconCode' => $this->getSpriteIconCode(),
			't3TextSourceField' => $this->getTextSourceField(),
			't3InCopyMode' => $this->isInCopyMode(),
			't3InCutMode' => $this->isInCutMode(),
			't3ContextInfo' => $this->getContextInfo(),
			'editable' => $this->isLabelEditable(),
			'allowChildren' => $this->canHaveChildren()
		);
		// only set the leaf attribute if the node has children's,
		// otherwise you cannot add child's to real leaf nodes
		if (!$this->isLeafNode()) {
			$arrayRepresentation['leaf'] = FALSE;
		}
		// Suhosin(?) or some other strange environment thingy prevents
		// the direct copy of an array into an index of the same array
		$copy = $arrayRepresentation;
		$arrayRepresentation['nodeData'] = $copy;
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
		$this->setText($data['label'], $data['t3TextSourceField'], $data['prefix'], $data['suffix']);
		$this->setEditableText($data['editableText']);
		$this->setCls($data['cls']);
		$this->setQTip($data['qtip']);
		$this->setExpanded($data['expanded']);
		$this->setExpandable($data['expandable']);
		$this->setDraggable($data['draggable']);
		$this->setIsDropTarget($data['isTarget']);
		$this->setSpriteIconCode($data['spriteIconCode']);
		$this->setInCopyMode($data['t3InCopyMode']);
		$this->setInCutMode($data['t3InCutMode']);
		$this->setContextInfo($data['t3ContextInfo']);
		$this->setLabelIsEditable($data['editable']);
		$this->setAllowChildren($data['allowChildren']);
		// only set the leaf attribute if it's applied
		// otherwise you cannot insert nodes into this one
		if (isset($data['leaf'])) {
			$this->setLeaf(FALSE);
		}
	}

}


?>