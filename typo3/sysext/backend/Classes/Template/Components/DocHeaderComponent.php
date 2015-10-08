<?php
namespace TYPO3\CMS\Backend\Template\Components;

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

use TYPO3\CMS\Core\Utility\GeneralUtility;

/**
 * DocHeader component class
 */
class DocHeaderComponent
{
    /**
     * MenuRegistry Object
     *
     * @var MenuRegistry
     */
    protected $menuRegistry;

    /**
     * Meta information
     *
     * @var MetaInformation
     */
    protected $metaInformation;

    /**
     * Registry Container for Buttons
     *
     * @var ButtonBar
     */
    protected $buttonBar;

    /**
     * @var bool
     */
    protected $enabled = true;

    /**
     * Sets up buttonBar and MenuRegistry
     */
    public function __construct()
    {
        $this->buttonBar = GeneralUtility::makeInstance(ButtonBar::class);
        $this->menuRegistry = GeneralUtility::makeInstance(MenuRegistry::class);
        $this->metaInformation = GeneralUtility::makeInstance(MetaInformation::class);
    }

    /**
     * Set page information
     *
     * @param array $metaInformation Record array
     *
     * @return void
     */
    public function setMetaInformation(array $metaInformation)
    {
        $this->metaInformation->setRecordArray($metaInformation);
    }

    /**
     * Get moduleMenuRegistry
     *
     * @return MenuRegistry
     */
    public function getMenuRegistry()
    {
        return $this->menuRegistry;
    }

    /**
     * Get ButtonBar
     *
     * @return ButtonBar
     */
    public function getButtonBar()
    {
        return $this->buttonBar;
    }

    /**
     * Determines whether this components is enabled.
     *
     * @return bool
     */
    public function isEnabled()
    {
        return $this->enabled;
    }

    /**
     * Sets the enabled property to TRUE.
     */
    public function enable()
    {
        $this->enabled = true;
    }

    /**
     * Sets the enabled property to FALSE (disabled).
     */
    public function disable()
    {
        $this->enabled = false;
    }

    /**
     * Returns the abstract content of the docHeader as an array
     *
     * @return array
     */
    public function docHeaderContent()
    {
        return [
            'enabled' => $this->isEnabled(),
            'buttons' => $this->buttonBar->getButtons(),
            'menus' => $this->menuRegistry->getMenus(),
            'metaInformation' => $this->metaInformation
        ];
    }
}
