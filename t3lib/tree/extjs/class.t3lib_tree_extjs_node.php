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
 * Node for the usage with ExtDirect and ExtJS
 *
 * @author Stefan Galinski <stefan.galinski@gmail.com>
 * @package TYPO3
 * @subpackage t3lib
 */
class t3lib_tree_ExtJs_Node extends t3lib_tree_Node implements t3lib_tree_RenderableNode {

	/**
	 * Indicator if the node is allowDrag
	 *
	 * @var bool
	 */
	protected $allowDrag = TRUE;

	/**
	 * Indicator if the node is allowed as a drop target
	 *
	 * @var bool
	 */
	protected $allowDrop = TRUE;

	/**
	 * Indicator if the noded is checked
	 * three-state: TRUE|FALSE|NULL
	 * NULL won't show a checkbox
	 *
	 * @var boolean|NULL
	 */
	protected $checked = NULL;

	/**
	 * CSS Class
	 *
	 * @var string
	 */
	protected $cls = '';

	/**
	 * The number of parents this node has. ...
	 *
	 * @var int
	 */
	protected $depth;

	/**
	 * Indicator if the node can be expanded
	 *
	 * @var bool
	 */
	protected $expandable = TRUE;

	/**
	 * Indicator if the node is expanded
	 *
	 * @var bool
	 */
	protected $expanded = FALSE;

	/**
	 * An URL for a link that's created when this config is specified.
	 *
	 * @var string|NULL
	 */
	protected $href = NULL;

	/**
	 * Target for link in $href
	 *
	 * @var string
	 */
	protected $hrefTarget = '';

	/**
	 * URL for this node's icon file
	 *
	 * @var string
	 */
	protected $icon;

	/**
	 * URL for this node's icon css class
	 *
	 * @var string
	 */
	protected $iconCls;


	/**
	 * The position of the node inside its parents
	 * starting from 0
	 *
	 * @var int
	 */
	protected $index;


	/**
	 * True if this is the first node.
	 *
	 * @var boolean
	 */
	protected $isFirst;

	/**
	 * True if this is the last node
	 */
	protected $isLast;

	/**
	 * Leaf Node Indicator
	 *
	 * @var bool
	 */
	protected $leaf = TRUE;

	/**
	 * Quick Tip
	 *
	 * @var string
	 */
	protected $qtip = '';

	/**
	 * Quick Tip title
	 *
	 * @var string
	 */
	protected $qtitle = '';

	/**
	 * is root node?
	 *
	 * @var boolean
	 */
	protected $root = FALSE;

	/**
	 * Label
	 *
	 * @var string
	 */
	protected $text = '';

	/**
	 * Sets the leaf node indicator
	 *
	 * @param bool $isLeaf
	 * @return void
	 */
	public function setLeaf($isLeaf) {
		$this->leaf = ($isLeaf == TRUE);
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
	 * Sets the expandable indicator
	 *
	 * @param bool $expandable
	 * @return void
	 */
	public function setExpandable($expandable) {
		$this->expandable = ($expandable == TRUE);
	}

	/**
	 * Returns the expandable indicator
	 *
	 * @return bool
	 */
	public function isExpandable() {
		return $this->expandable;
	}

	/**
	 * Sets the expanded indicator
	 *
	 * @param bool $expanded
	 * @return void
	 */
	public function setExpanded($expanded) {
		$this->expanded = ($expanded == TRUE);
	}

	/**
	 * Returns the expanded indicator
	 *
	 * @return bool
	 */
	public function isExpanded() {
		if ($this->isLeafNode()) {
			return TRUE;
		}

		return $this->expanded;
	}

	/**
	 * Sets the allowDrag indicator
	 *
	 * @param bool $draggable
	 * @return void
	 */
	public function setAllowDrag($draggable) {
		$this->allowDrag = ($draggable == TRUE);
	}

	/**
	 * Returns the allowDrag indicator
	 *
	 * @return bool
	 */
	public function isDraggable() {
		return $this->allowDrag;
	}

	/**
	 * Sets the indicator if the node can be a drop target
	 *
	 * @param bool $isDropTarget
	 * @return void
	 */
	public function setAllowDrop($isDropTarget) {
		$this->allowDrop = ($isDropTarget == TRUE);
	}

	/**
	 * Returns the indicator if the node is a drop target
	 *
	 * @return bool
	 */
	public function allowDrop() {
		return $this->allowDrop;
	}

	/**
	 * Sets the label of the node with the source field and the prefix
	 *
	 * @param string $text
	 * @return void
	 */
	public function setText($text) {
		$this->text = $text;
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
	 * Returns the node in an array representation that can be used for serialization
	 *
	 * @param bool $addChildNodes
	 * @return array
	 */
	public function toArray($addChildNodes = TRUE) {
		$arrayRepresentation = array(
			'serializeClassName' => get_class($this),
			'allowDrag' => $this->isDraggable(),
			'allowDrop' => $this->allowDrop(),
			'checked'	=> $this->getChecked(),
			'cls' => $this->getCls(),
			'depth' => $this->getDepth(),
			'expandable' => $this->isExpandable(),
			'expanded' => $this->isExpanded(),
			'href' => $this->getHref(),
			'hrefTarget' => $this->getHrefTarget(),
			'id' => $this->getId(),
			'icon' => $this->getIcon(),
			'iconCls' => $this->getIconCls(),
			'index' => $this->getIndex(),
			'isFirst' => $this->getIsFirst(),
			'isLast' => $this->getIsLast(),
			'parentId' => (!$this->getRoot() && $this->getParentNode() ? $this->getParentNode()->getId() : ''),
			'qtip' => $this->getQTip(),
			'qtitle' => $this->getQtitle(),
			'root' => $this->getRoot(),
			'text' => $this->getText(),
		);

			// do not provide config if empty
		if (trim($this->href) == '') {
			unset($arrayRepresentation['href']);
			unset($arrayRepresentation['hrefTarget']);
		}

			// only added check option if checkbox should be shown
		if($this->getChecked() == NULL) {
			unset($arrayRepresentation['checked']);
		}
			// only set the leaf attribute if the node has children's,
			// otherwise you cannot add child's to real leaf nodes
		if (!$this->isLeafNode()) {
			$arrayRepresentation['leaf'] = FALSE;
		}

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
	 * Sets data of the node by a given data array
	 *
	 * @param array $data
	 * @return void
	 */
	public function dataFromArray($data) {
		parent::dataFromArray($data);

		$this->setText($data['label']);
		$this->setCls($data['cls']);
		$this->setQTip($data['qtip']);
		$this->setExpanded($data['expanded']);
		$this->setExpandable($data['expandable']);
		$this->setAllowDrag($data['allowDrag']);
		$this->setAllowDrop($data['isTarget']);
		$this->setChecked(isset($data['checked']) ? (bool)$data['checked'] : NULL);

		// only set the leaf attribute if it's applied
		// otherwise you cannot insert nodes into this one
		if (isset($data['leaf'])) {
			$this->setLeaf(FALSE);
		}
	}

	/**
	 * @param bool|NULL $checked
	 */
	public function setChecked($checked) {
		$this->checked = $checked;
	}

	/**
	 * @return bool|NULL
	 */
	public function getChecked() {
		return $this->checked;
	}

	/**
	 * @param int $depth
	 */
	public function setDepth($depth) {
		$this->depth = $depth;
	}

	/**
	 * @return int
	 */
	public function getDepth() {
		return $this->depth;
	}

	/**
	 * @param NULL|string $href
	 */
	public function setHref($href) {
		$this->href = $href;
	}

	/**
	 * @return NULL|string
	 */
	public function getHref() {
		return $this->href;
	}

	/**
	 * @param string $hrefTarget
	 */
	public function setHrefTarget($hrefTarget) {
		$this->hrefTarget = $hrefTarget;
	}

	/**
	 * @return string
	 */
	public function getHrefTarget() {
		return $this->hrefTarget;
	}

	/**
	 * @param string $icon
	 */
	public function setIcon($icon) {
		$this->icon = $icon;
	}

	/**
	 * @return string
	 */
	public function getIcon() {
		return $this->icon;
	}

	/**
	 * @param string $iconCls
	 */
	public function setIconCls($iconCls) {
		$this->iconCls = $iconCls;
	}

	/**
	 * @return string
	 */
	public function getIconCls() {
		return $this->iconCls;
	}

	/**
	 * @param int $index
	 */
	public function setIndex($index) {
		$this->index = $index;
	}

	/**
	 * @return int
	 */
	public function getIndex() {
		return $this->index;
	}

	/**
	 * @param boolean $isFirst
	 */
	public function setIsFirst($isFirst) {
		$this->isFirst = $isFirst;
	}

	/**
	 * @return boolean
	 */
	public function getIsFirst() {
		return $this->isFirst;
	}

	public function setIsLast($isLast) {
		$this->isLast = $isLast;
	}

	public function getIsLast() {
		return $this->isLast;
	}


	/**
	 * @param string $qtitle
	 */
	public function setQtitle($qtitle) {
		$this->qtitle = $qtitle;
	}

	/**
	 * @return string
	 */
	public function getQtitle() {
		return $this->qtitle;
	}

	/**
	 * @param boolean $root
	 */
	public function setRoot($root) {
		$this->root = $root;
	}

	/**
	 * @return boolean
	 */
	public function getRoot() {
		return $this->root;
	}

	/**
	 * returns a string representation
	 *
	 * @return string
	 */
	public function toString() {
		return $this->getText();
	}
}

if (defined('TYPO3_MODE') && isset($GLOBALS['TYPO3_CONF_VARS'][TYPO3_MODE]['XCLASS']['t3lib/tree/extjs/class.t3lib_tree_extjs_node.php'])) {
	include_once($GLOBALS['TYPO3_CONF_VARS'][TYPO3_MODE]['XCLASS']['t3lib/tree/extjs/class.t3lib_tree_extjs_node.php']);
}

?>