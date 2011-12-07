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
 * Extends the ExtJS-Tree Nodes with custom features implemented for the TYPO3 Navigation Panel
 *
 * @author Steffen Ritter <info@steffen-ritter.net>
 * @package TYPO3
 * @subpackage t3lib
 */
class t3lib_tree_NavigationTree_Node extends t3lib_tree_ExtJs_Node {


	/**
	 * Database record (not serialized or merged into the result array!)
	 *
	 * @var array
	 */
	protected $record = array();


	/**
	 * Indicator if the node can have children's
	 *
	 * @var bool
	 */
	protected $allowChildren = TRUE;

	/**
	 * Node type
	 *
	 * @var string
	 */
	protected $type = '';


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
	 * Context Info
	 *
	 * @var array
	 */
	protected $contextInfo = array();

	/**
	 * Editable Label text
	 *
	 * @var string
	 */
	protected $editableText = '';


	/**
	 * Indicator if the label is editable
	 *
	 * @var bool
	 */
	protected $labelIsEditable = TRUE;


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
	 * Sets the indicator if the node can have child nodes
	 *
	 * @param boolean $allowChildren
	 * @return void
	 */
	public function setAllowChildren($allowChildren) {
		$this->allowChildren = ($allowChildren == TRUE);
	}

	/**
	 * Checks if the node can have child nodes
	 *
	 * @return bool
	 */
	public function canHaveChildren() {
		return $this->allowChildren;
	}


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
		$this->t3InCopyMode = ($inCopyMode == TRUE);
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
		$this->t3InCutMode = ($inCutMode == TRUE);
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
	 * Returns the suffix text of the label
	 *
	 * @return string
	 */
	public function getSuffix() {
		return $this->suffix;
	}
	
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
	 * Sets the indicator if the label is editable
	 *
	 * @param bool $labelIsEditable
	 * @return void
	 */
	public function setLabelIsEditable($labelIsEditable) {
		$this->labelIsEditable = ($labelIsEditable == TRUE);
	}

	/**
	 * Returns the editable label indicator
	 *
	 * @return bool
	 */
	public function isLabelEditable() {
		return $this->labelIsEditable;
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
	 * Sets data of the node by a given data array
	 *
	 * @param array $data
	 * @return void
	 */
	public function dataFromArray($data) {
		parent::dataFromArray($data);

		$this->setText($data['label'], $data['t3TextSourceField'], $data['prefix'], $data['suffix']);
		$this->setType($data['type']);
		$this->setEditableText($data['editableText']);
		$this->setSpriteIconCode($data['spriteIconCode']);
		$this->setInCopyMode($data['t3InCopyMode']);
		$this->setInCutMode($data['t3InCutMode']);
		$this->setContextInfo($data['t3ContextInfo']);
		$this->setLabelIsEditable($data['editable']);
		$this->setAllowChildren($data['allowChildren']);
	}

	/**
	 * Returns the node in an array representation that can be used for serialization
	 *
	 * @param bool $addChildNodes
	 * @return array
	 */
	public function toArray($addChildNodes = TRUE) {
		$arrayRepresentation = parent::toArray($addChildNodes);

		$arrayRepresentation2 = array(
			'type' => $this->getType(),
			'editableText' => $this->getEditableText(),
			'text' => $this->getPrefix() . $this->getText() . $this->getSuffix(),
			'prefix' => $this->getPrefix(),
			'suffix' => $this->getSuffix(),
			'spriteIconCode' => $this->getSpriteIconCode(),
			't3TextSourceField' => $this->getTextSourceField(),
			't3InCopyMode' => $this->isInCopyMode(),
			't3InCutMode' => $this->isInCutMode(),
			't3ContextInfo' => $this->getContextInfo(),
			'editable' => $this->isLabelEditable(),
			'allowChildren' => $this->canHaveChildren(),
		);

			// only set the leaf attribute if the node has children's,
			// otherwise you cannot add child's to real leaf nodes
		if (!$this->isLeafNode()) {
			$arrayRepresentation2['leaf'] = FALSE;
		}

			// Do not nest nodeData and children arrays
		unset($arrayRepresentation['nodeData']);
		unset($arrayRepresentation['children']);
		$arrayRepresentation = array_merge($arrayRepresentation, $arrayRepresentation2);

			// Suhosin(?) or some other strange environment thingy prevents
			// the direct copy of an array into an index of the same array
		$copy = $arrayRepresentation;
		$arrayRepresentation['nodeData'] = $copy;

		if ($this->hasChildNodes() && $addChildNodes) {
			$arrayRepresentation['children'] = $this->childNodes->toArray();
		}

		return $arrayRepresentation;
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


}

if (defined('TYPO3_MODE') && isset($GLOBALS['TYPO3_CONF_VARS'][TYPO3_MODE]['XCLASS']['t3lib/tree/navigationtree/class.t3lib_tree_navigationtree_node.php'])) {
	include_once($GLOBALS['TYPO3_CONF_VARS'][TYPO3_MODE]['XCLASS']['t3lib/tree/navigationtree/class.t3lib_tree_navigationtree_node.php']);
}

?>