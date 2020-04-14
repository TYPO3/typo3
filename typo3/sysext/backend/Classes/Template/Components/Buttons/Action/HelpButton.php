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

namespace TYPO3\CMS\Backend\Template\Components\Buttons\Action;

use TYPO3\CMS\Backend\Template\Components\ButtonBar;
use TYPO3\CMS\Backend\Template\Components\Buttons\ButtonInterface;
use TYPO3\CMS\Backend\Template\Components\Buttons\PositionInterface;
use TYPO3\CMS\Backend\Utility\BackendUtility;

/**
 * HelpButton
 *
 * Renders a help button in the DocHeader which will be rendered
 * to the right position using button group "99".
 *
 * EXAMPLE USAGE TO ADD A HELP BUTTON:
 *
 * $buttonBar = $this->moduleTemplate->getDocHeaderComponent()->getButtonBar();
 * $myButton = $buttonBar->makeHelpButton()
 *       ->setModuleName('xMOD_csh_corebe')
 *       ->setFieldName('list_module');
 * $buttonBar->addButton($myButton);
 */
class HelpButton implements ButtonInterface, PositionInterface
{
    /**
     * @var string
     */
    protected $moduleName;

    /**
     * @var string
     */
    protected $fieldName = '';

    /**
     * Gets the name of the module.
     *
     * @return string
     */
    public function getModuleName()
    {
        return $this->moduleName;
    }

    /**
     * Sets the name of the module.
     *
     * @param string $moduleName
     * @return HelpButton
     */
    public function setModuleName($moduleName)
    {
        $this->moduleName = $moduleName;
        return $this;
    }

    /**
     * Gets the name of the field.
     *
     * @return string
     */
    public function getFieldName()
    {
        return $this->fieldName;
    }

    /**
     * Sets the name of the field.
     *
     * @param string $fieldName
     * @return HelpButton
     */
    public function setFieldName($fieldName)
    {
        $this->fieldName = $fieldName;
        return $this;
    }

    /**
     * Gets the button position.
     *
     * @return string
     */
    public function getPosition()
    {
        return ButtonBar::BUTTON_POSITION_RIGHT;
    }

    /**
     * Gets the button group.
     *
     * @return int
     */
    public function getGroup()
    {
        return 99;
    }

    /**
     * Gets the type of the button
     *
     * @return string
     */
    public function getType()
    {
        return static::class;
    }

    /**
     * Determines whether the button shall be rendered.
     * Depends on the defined module and field name.
     *
     * @return bool
     */
    public function isValid()
    {
        return !empty($this->moduleName);
    }

    /**
     * Renders the button
     *
     * @return string
     */
    public function __toString()
    {
        return $this->render();
    }

    /**
     * Renders the button
     *
     * @return string
     */
    public function render()
    {
        $helpMarkup = BackendUtility::cshItem($this->moduleName, $this->fieldName);
        if ($helpMarkup === '') {
            return '';
        }
        return '<span class="btn btn-sm btn-default">' . $helpMarkup . '</span>';
    }
}
