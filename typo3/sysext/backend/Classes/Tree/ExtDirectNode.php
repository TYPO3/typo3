<?php
namespace TYPO3\CMS\Backend\Tree;

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
 * Node for the usage with ExtDirect and ExtJS
 */
class ExtDirectNode extends \TYPO3\CMS\Backend\Tree\TreeNode
{
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
    protected $leaf = true;

    /**
     * Indicator if the node is expanded
     *
     * @var bool
     */
    protected $expanded = false;

    /**
     * Indicator if the node can be expanded
     *
     * @var bool
     */
    protected $expandable = false;

    /**
     * Indicator if the node is draggable
     *
     * @var bool
     */
    protected $draggable = true;

    /**
     * Indicator if the node is allowed as a drop target
     *
     * @var bool
     */
    protected $isDropTarget = true;

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
     * @var bool
     */
    protected $t3InCopyMode = false;

    /**
     * Indicator if the cut mode is activated
     *
     * @var bool
     */
    protected $t3InCutMode = false;

    /**
     * Database record (not serialized or merged into the result array!)
     *
     * @var array
     */
    protected $record = [];

    /**
     * Context Info
     *
     * @var array
     */
    protected $contextInfo = [];

    /**
     * Indicator if the label is editable
     *
     * @var bool
     */
    protected $labelIsEditable = true;

    /**
     * Indicator if the node can have children's
     *
     * @var bool
     */
    protected $allowChildren = true;

    /**
     * Set's the node type
     *
     * @param string $type
     * @return void
     */
    public function setType($type)
    {
        $this->type = $type;
    }

    /**
     * Returns the node type
     *
     * @return string
     */
    public function getType()
    {
        return $this->type;
    }

    /**
     * Sets the leaf node indicator
     *
     * @param bool $isLeaf
     * @return void
     */
    public function setLeaf($isLeaf)
    {
        $this->leaf = $isLeaf == true;
    }

    /**
     * Returns if the node is a leaf node
     *
     * @return bool
     */
    public function isLeafNode()
    {
        return $this->leaf;
    }

    /**
     * Sets the expandable indicator
     *
     * @param bool $expandable
     * @return void
     */
    public function setExpandable($expandable)
    {
        $this->expandable = $expandable == true;
    }

    /**
     * Returns the expandable indicator
     *
     * @return bool
     */
    public function isExpandable()
    {
        return $this->expandable;
    }

    /**
     * Sets the expanded indicator
     *
     * @param bool $expanded
     * @return void
     */
    public function setExpanded($expanded)
    {
        $this->expanded = $expanded == true;
    }

    /**
     * Returns the expanded indicator
     *
     * @return bool
     */
    public function isExpanded()
    {
        if ($this->isLeafNode()) {
            return true;
        }
        return $this->expanded;
    }

    /**
     * Sets the draggable indicator
     *
     * @param bool $draggable
     * @return void
     */
    public function setDraggable($draggable)
    {
        $this->draggable = $draggable == true;
    }

    /**
     * Returns the draggable indicator
     *
     * @return bool
     */
    public function isDraggable()
    {
        return $this->draggable;
    }

    /**
     * Sets the indicator if the node can be a drop target
     *
     * @param bool $isDropTarget
     * @return void
     */
    public function setIsDropTarget($isDropTarget)
    {
        $this->isDropTarget = $isDropTarget == true;
    }

    /**
     * Returns the indicator if the node is a drop target
     *
     * @return bool
     */
    public function isDropTarget()
    {
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
    public function setText($text, $textSourceField = 'title', $prefix = '', $suffix = '')
    {
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
    public function getText()
    {
        return $this->text;
    }

    /**
     * Sets the editable text
     *
     * @param string $editableText
     * @return void
     */
    public function setEditableText($editableText)
    {
        $this->editableText = $editableText;
    }

    /**
     * Returns the editable text
     *
     * @return string
     */
    public function getEditableText()
    {
        return $this->editableText;
    }

    /**
     * Returns the source field of the label
     *
     * @return string
     */
    public function getTextSourceField()
    {
        return $this->t3TextSourceField;
    }

    /**
     * Sets the paste copy indicator
     *
     * @param bool $inCopyMode
     * @return void
     */
    public function setInCopyMode($inCopyMode)
    {
        $this->t3InCopyMode = $inCopyMode == true;
    }

    /**
     * Returns the copy mode indicator
     *
     * @return bool
     */
    public function isInCopyMode()
    {
        return $this->t3InCopyMode;
    }

    /**
     * Sets the paste cut indicator
     *
     * @param bool $inCutMode
     * @return void
     */
    public function setInCutMode($inCutMode)
    {
        $this->t3InCutMode = $inCutMode == true;
    }

    /**
     * Returns the cut mode indicator
     *
     * @return bool
     */
    public function isInCutMode()
    {
        return $this->t3InCutMode;
    }

    /**
     * Returns the prefix text of the label
     *
     * @return string
     */
    public function getPrefix()
    {
        return $this->prefix;
    }

    /**
     * Returns the suffix text of the label
     *
     * @return string
     */
    public function getSuffix()
    {
        return $this->suffix;
    }

    /**
     * Sets the css class(es)
     *
     * @param string $class
     * @return void
     */
    public function setCls($class)
    {
        $this->cls = $class;
    }

    /**
     * Returns the css class(es)
     *
     * @return string
     */
    public function getCls()
    {
        return $this->cls;
    }

    /**
     * Sets the quick tip
     *
     * @param string $qtip
     * @return void
     */
    public function setQTip($qtip)
    {
        $this->qtip = $qtip;
    }

    /**
     * Returns the quick tip
     *
     * @return string
     */
    public function getQTip()
    {
        return $this->qtip;
    }

    /**
     * Sets the sprite icon code
     *
     * @param string $spriteIcon
     * @return void
     */
    public function setSpriteIconCode($spriteIcon)
    {
        $this->spriteIconCode = $spriteIcon;
    }

    /**
     * Returns the sprite icon code
     *
     * @return string
     */
    public function getSpriteIconCode()
    {
        return $this->spriteIconCode;
    }

    /**
     * Sets the indicator if the label is editable
     *
     * @param bool $labelIsEditable
     * @return void
     */
    public function setLabelIsEditable($labelIsEditable)
    {
        $this->labelIsEditable = $labelIsEditable == true;
    }

    /**
     * Returns the editable label indicator
     *
     * @return bool
     */
    public function isLabelEditable()
    {
        return $this->labelIsEditable;
    }

    /**
     * Sets the database record array
     *
     * @param array $record
     * @return void
     */
    public function setRecord($record)
    {
        $this->record = (array)$record;
    }

    /**
     * Returns the database record array
     *
     * @return array
     */
    public function getRecord()
    {
        return $this->record;
    }

    /**
     * Sets the context info
     *
     * @param array $contextInfo
     * @return void
     */
    public function setContextInfo($contextInfo)
    {
        $this->contextInfo = (array)$contextInfo;
    }

    /**
     * Returns the context info
     *
     * @return array
     */
    public function getContextInfo()
    {
        return (array)$this->contextInfo;
    }

    /**
     * Sets the child nodes collection
     *
     * @param \TYPO3\CMS\Backend\Tree\TreeNodeCollection $childNodes
     * @return void
     */
    public function setChildNodes(\TYPO3\CMS\Backend\Tree\TreeNodeCollection $childNodes)
    {
        parent::setChildNodes($childNodes);
        if ($childNodes->count()) {
            $this->setLeaf(false);
        }
    }

    /**
     * Sets the indicator if the node can have child nodes
     *
     * @param bool $allowChildren
     * @return void
     */
    public function setAllowChildren($allowChildren)
    {
        $this->allowChildren = $allowChildren == true;
    }

    /**
     * Checks if the node can have child nodes
     *
     * @return bool
     */
    public function canHaveChildren()
    {
        return $this->allowChildren;
    }

    /**
     * Returns the node in an array representation that can be used for serialization
     *
     * @param bool $addChildNodes
     * @return array
     */
    public function toArray($addChildNodes = true)
    {
        $arrayRepresentation = [
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
        ];
        // only set the leaf attribute if the node has children's,
        // otherwise you cannot add child's to real leaf nodes
        if (!$this->isLeafNode()) {
            $arrayRepresentation['leaf'] = false;
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
    public function dataFromArray($data)
    {
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
            $this->setLeaf(false);
        }
    }
}
