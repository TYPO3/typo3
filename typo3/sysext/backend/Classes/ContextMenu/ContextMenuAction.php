<?php
namespace TYPO3\CMS\Backend\ContextMenu;

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

/**
 * Context Menu Action
 */
class ContextMenuAction
{
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
     * @deprecated since TYPO3 CMS 7, will be removed in TYPO3 CMS 8
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
    protected $childActions = null;

    /**
     * Custom Action Attributes
     *
     * @var array
     */
    protected $customAttributes = [];

    /**
     * Returns the label
     *
     * @return string
     */
    public function getLabel()
    {
        return $this->label;
    }

    /**
     * Sets the label
     *
     * @param string $label
     */
    public function setLabel($label)
    {
        $this->label = $label;
    }

    /**
     * Returns the identifier
     *
     * @return string
     */
    public function getId()
    {
        return $this->id;
    }

    /**
     * Sets the identifier
     *
     * @param string $id
     */
    public function setId($id)
    {
        $this->id = $id;
    }

    /**
     * Returns the icon
     *
     * @return string
     */
    public function getIcon()
    {
        return $this->icon;
    }

    /**
     * Sets the icon
     *
     * @param string $icon
     * @return void
     */
    public function setIcon($icon)
    {
        $this->icon = $icon;
    }

    /**
     * Returns the class
     *
     * @return string
     * @deprecated since TYPO3 CMS 7, will be removed in TYPO3 CMS 8
     */
    public function getClass()
    {
        GeneralUtility::logDeprecatedFunction();
        return $this->class;
    }

    /**
     * Sets the class
     *
     * @param string $class
     * @deprecated since TYPO3 CMS 7, will be removed in TYPO3 CMS 8
     */
    public function setClass($class)
    {
        GeneralUtility::logDeprecatedFunction();
        $this->class = $class;
    }

    /**
     * Returns the callback action
     *
     * @return string
     */
    public function getCallbackAction()
    {
        return $this->callbackAction;
    }

    /**
     * Sets the callback action
     *
     * @param string $callbackAction
     */
    public function setCallbackAction($callbackAction)
    {
        $this->callbackAction = $callbackAction;
    }

    /**
     * Returns the type
     *
     * @return string
     */
    public function getType()
    {
        return $this->type;
    }

    /**
     * Sets the type
     *
     * @param string $type
     * @return void
     */
    public function setType($type)
    {
        $this->type = $type;
    }

    /**
     * Returns the child actions
     *
     * @return \TYPO3\CMS\Backend\ContextMenu\ContextMenuActionCollection
     */
    public function getChildActions()
    {
        return $this->childActions;
    }

    /**
     * Sets the child actions
     *
     * @param \TYPO3\CMS\Backend\ContextMenu\ContextMenuActionCollection $actions
     * @return void
     */
    public function setChildActions(\TYPO3\CMS\Backend\ContextMenu\ContextMenuActionCollection $actions)
    {
        $this->childActions = $actions;
    }

    /**
     * Returns TRUE if the action has child actions
     *
     * @return bool
     */
    public function hasChildActions()
    {
        if ($this->childActions !== null) {
            return true;
        }
        return false;
    }

    /**
     * Sets the custom attributes
     *
     * @param array $customAttributes
     * @return void
     */
    public function setCustomAttributes(array $customAttributes)
    {
        $this->customAttributes = $customAttributes;
    }

    /**
     * Returns the custom attributes
     *
     * @return array
     */
    public function getCustomAttributes()
    {
        return $this->customAttributes;
    }

    /**
     * Returns the action as an array
     *
     * @return array
     */
    public function toArray()
    {
        $arrayRepresentation = [
            'label' => $this->getLabel(),
            'id' => $this->getId(),
            'icon' => $this->getIcon(),
            'callbackAction' => $this->getCallbackAction(),
            'type' => $this->getType(),
            'customAttributes' => $this->getCustomAttributes()
        ];
        $arrayRepresentation['childActions'] = '';
        if ($this->hasChildActions()) {
            $arrayRepresentation['childActions'] = $this->childActions->toArray();
        }
        return $arrayRepresentation;
    }
}
