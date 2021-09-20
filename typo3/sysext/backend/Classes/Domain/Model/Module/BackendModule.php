<?php

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

namespace TYPO3\CMS\Backend\Domain\Model\Module;

/**
 * Model for menu entries
 */
class BackendModule
{
    /**
     * @var string
     */
    protected $title = '';

    /**
     * @var string
     */
    protected $name = '';

    /**
     * @var string
     */
    protected $icon = '';

    /**
     * @var string
     */
    protected $link = '';

    /**
     * @var string
     */
    protected $component = '';

    /**
     * @var string
     * @deprecated using inline JavaScript is deprecated, will be removed in TYPO3 v12.0
     */
    protected $onClick = '';

    /**
     * @var string
     */
    protected $description = '';

    /**
     * @var string
     */
    protected $navigationComponentId = '';

    /**
     * @var string
     * @deprecated since TYPO3 v11, will be removed in TYPO3 v12.0
     */
    protected $navigationFrameScript = '';

    /**
     * @var string
     * @deprecated since TYPO3 v11, will be removed in TYPO3 v12.0
     */
    protected $navigationFrameScriptParameters = '';

    /**
     * @var \SplObjectStorage
     */
    protected $children;

    /**
     * @var bool
     */
    protected $collapsed = false;

    /**
     * Standalone modules are top-level modules without a group
     *
     * @var bool
     */
    protected $standalone = false;

    /**
     * construct
     */
    public function __construct()
    {
        $this->children = new \SplObjectStorage();
    }

    /**
     * Set children
     *
     * @param \SplObjectStorage $children
     */
    public function setChildren($children)
    {
        $this->children = $children;
    }

    /**
     * Get children
     *
     * @return \SplObjectStorage
     */
    public function getChildren()
    {
        return $this->children;
    }

    /**
     * Add Child
     *
     * @param \TYPO3\CMS\Backend\Domain\Model\Module\BackendModule $child
     */
    public function addChild(\TYPO3\CMS\Backend\Domain\Model\Module\BackendModule $child)
    {
        $this->children->attach($child);
    }

    /**
     * Set icon
     *
     * @param string $icon
     */
    public function setIcon($icon)
    {
        $this->icon = $icon;
    }

    /**
     * Get icon
     *
     * @return string
     */
    public function getIcon()
    {
        return $this->icon;
    }

    /**
     * Set name
     *
     * @param string $name
     */
    public function setName($name)
    {
        $this->name = $name;
    }

    /**
     * Get name
     *
     * @return string
     */
    public function getName()
    {
        return $this->name;
    }

    /**
     * Set title
     *
     * @param string $title
     */
    public function setTitle($title)
    {
        $this->title = $title;
    }

    /**
     * Get Title
     *
     * @return string
     */
    public function getTitle()
    {
        return $this->title;
    }

    /**
     * Set Link
     *
     * @param string $link
     */
    public function setLink($link)
    {
        $this->link = $link;
    }

    /**
     * Get Link
     *
     * @return string
     */
    public function getLink()
    {
        return $this->link;
    }

    /**
     * Set Component
     *
     * @param string $component
     */
    public function setComponent($component)
    {
        $this->component = $component;
    }

    /**
     * Get Component
     *
     * @return string
     */
    public function getComponent()
    {
        return $this->component;
    }

    /**
     * Set Description
     *
     * @param string $description
     */
    public function setDescription($description)
    {
        $this->description = $description;
    }

    /**
     * Get Description
     *
     * @return string
     */
    public function getDescription()
    {
        return $this->description;
    }

    /**
     * Set Navigation Component Id
     *
     * @param string $navigationComponentId
     */
    public function setNavigationComponentId($navigationComponentId)
    {
        $this->navigationComponentId = $navigationComponentId;
    }

    /**
     * Get Navigation Component Id
     *
     * @return string
     */
    public function getNavigationComponentId()
    {
        return $this->navigationComponentId;
    }

    /**
     * @param string $navigationFrameScript
     * @deprecated since TYPO3 v11, will be removed in TYPO3 v12.0
     */
    public function setNavigationFrameScript($navigationFrameScript)
    {
        $this->navigationFrameScript = $navigationFrameScript;
    }

    /**
     * @return string
     * @deprecated since TYPO3 v11, will be removed in TYPO3 v12.0
     */
    public function getNavigationFrameScript()
    {
        return $this->navigationFrameScript;
    }

    /**
     * @param string $navigationFrameScriptParameters
     * @deprecated since TYPO3 v11, will be removed in TYPO3 v12.0
     */
    public function setNavigationFrameScriptParameters($navigationFrameScriptParameters)
    {
        $this->navigationFrameScriptParameters = $navigationFrameScriptParameters;
    }

    /**
     * @return string
     * @deprecated since TYPO3 v11, will be removed in TYPO3 v12.0
     */
    public function getNavigationFrameScriptParameters()
    {
        return $this->navigationFrameScriptParameters;
    }

    /**
     * Set onClick
     *
     * @param string $onClick
     * @deprecated using inline JavaScript is deprecated, will be removed in TYPO3 v12.0
     */
    public function setOnClick($onClick)
    {
        trigger_error('BackendModule::setOnClick() is deprecated and will be removed in v12.', E_USER_DEPRECATED);
        $this->onClick = $onClick;
    }

    /**
     * Get onClick
     *
     * @return string
     * @deprecated using inline JavaScript is deprecated, will be removed in TYPO3 v12.0
     */
    public function getOnClick()
    {
        trigger_error('BackendModule::getOnClick() is deprecated and will be removed in v12.', E_USER_DEPRECATED);
        return $this->onClick;
    }

    public function setCollapsed(bool $collapsed): void
    {
        $this->collapsed = $collapsed;
    }

    public function getCollapsed(): bool
    {
        return $this->collapsed;
    }

    /**
     * @return bool
     */
    public function isStandalone(): bool
    {
        return $this->standalone;
    }

    /**
     * @param bool $standalone
     */
    public function setStandalone(bool $standalone): void
    {
        $this->standalone = $standalone;
    }
}
