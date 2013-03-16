<?php
namespace TYPO3\CMS\Backend\ContextMenu;

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
 * Context Menu Action
 *
 * @author Stefan Galinski <stefan.galinski@gmail.com>
 */
class ContextMenuAction {

	/**
	 * Label
	 *
	 * @var string
	 */
	protected $label = '';

	/**
	 * Identifier
	 *
	 * @var string
	 */
	protected $id = '';

	/**
	 * Icon
	 *
	 * @var string
	 */
	protected $icon = '';

	/**
	 * Class
	 *
	 * @var string
	 */
	protected $class = '';

	/**
	 * Callback Action
	 *
	 * @var string
	 */
	protected $callbackAction = '';

	/**
	 * Type
	 *
	 * @var string
	 */
	protected $type = '';

	/**
	 * Child Action Collection
	 *
	 * @var \TYPO3\CMS\Backend\ContextMenu\ContextMenuActionCollection
	 */
	protected $childActions = NULL;

	/**
	 * Custom Action Attributes
	 *
	 * @var array
	 */
	protected $customAttributes = array();

	/**
	 * Returns the label
	 *
	 * @return string
	 */
	public function getLabel() {
		return $this->label;
	}

	/**
	 * Sets the label
	 *
	 * @param string $label
	 */
	public function setLabel($label) {
		$this->label = $label;
	}

	/**
	 * Returns the identifier
	 *
	 * @return string
	 */
	public function getId() {
		return $this->id;
	}

	/**
	 * Sets the identifier
	 *
	 * @param string $id
	 */
	public function setId($id) {
		$this->id = $id;
	}

	/**
	 * Returns the icon
	 *
	 * @return string
	 */
	public function getIcon() {
		return $this->icon;
	}

	/**
	 * Sets the icon
	 *
	 * @param string $icon
	 * @return void
	 */
	public function setIcon($icon) {
		$this->icon = $icon;
	}

	/**
	 * Returns the class
	 *
	 * @return string
	 */
	public function getClass() {
		return $this->class;
	}

	/**
	 * Sets the class
	 *
	 * @param string $class
	 */
	public function setClass($class) {
		$this->class = $class;
	}

	/**
	 * Returns the callback action
	 *
	 * @return string
	 */
	public function getCallbackAction() {
		return $this->callbackAction;
	}

	/**
	 * Sets the callback action
	 *
	 * @param string $callbackAction
	 */
	public function setCallbackAction($callbackAction) {
		$this->callbackAction = $callbackAction;
	}

	/**
	 * Returns the type
	 *
	 * @return string
	 */
	public function getType() {
		return $this->type;
	}

	/**
	 * Sets the type
	 *
	 * @param string $type
	 * @return void
	 */
	public function setType($type) {
		$this->type = $type;
	}

	/**
	 * Returns the child actions
	 *
	 * @return \TYPO3\CMS\Backend\ContextMenu\ContextMenuActionCollection
	 */
	public function getChildActions() {
		return $this->childActions;
	}

	/**
	 * Sets the child actions
	 *
	 * @param \TYPO3\CMS\Backend\ContextMenu\ContextMenuActionCollection $actions
	 * @return void
	 */
	public function setChildActions(\TYPO3\CMS\Backend\ContextMenu\ContextMenuActionCollection $actions) {
		$this->childActions = $actions;
	}

	/**
	 * Returns TRUE if the action has child actions
	 *
	 * @return boolean
	 */
	public function hasChildActions() {
		if ($this->childActions !== NULL) {
			return TRUE;
		}
		return FALSE;
	}

	/**
	 * Sets the custom attributes
	 *
	 * @param array $customAttributes
	 * @return void
	 */
	public function setCustomAttributes(array $customAttributes) {
		$this->customAttributes = $customAttributes;
	}

	/**
	 * Returns the custom attributes
	 *
	 * @return array
	 */
	public function getCustomAttributes() {
		return $this->customAttributes;
	}

	/**
	 * Returns the action as an array
	 *
	 * @return array
	 */
	public function toArray() {
		$arrayRepresentation = array(
			'label' => $this->getLabel(),
			'id' => $this->getId(),
			'icon' => $this->getIcon(),
			'class' => $this->getClass(),
			'callbackAction' => $this->getCallbackAction(),
			'type' => $this->getType(),
			'customAttributes' => $this->getCustomAttributes()
		);
		$arrayRepresentation['childActions'] = '';
		if ($this->hasChildActions()) {
			$arrayRepresentation['childActions'] = $this->childActions->toArray();
		}
		return $arrayRepresentation;
	}

}


?>