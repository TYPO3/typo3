<?php
namespace TYPO3\CMS\Backend\Template\Components\Buttons\Action;

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

use TYPO3\CMS\Backend\Template\Components\ButtonBar;
use TYPO3\CMS\Backend\Template\Components\Buttons\ButtonInterface;
use TYPO3\CMS\Backend\Template\Components\Buttons\PositionInterface;
use TYPO3\CMS\Backend\Template\ModuleTemplate;
use TYPO3\CMS\Core\Authentication\BackendUserAuthentication;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Extbase\Mvc\Controller\ControllerContext;

/**
 * ShortcutButton
 *
 * Renders a shortcut button in the DocHeader which will be rendered
 * to the right position using button group "91".
 *
 * EXAMPLE USAGE TO ADD A SHORTCUT BUTTON:
 *
 * $buttonBar = $this->moduleTemplate->getDocHeaderComponent()->getButtonBar();
 * $myButton = $buttonBar->makeShortcutButton()
 *       ->setModuleName('my_info');
 * $buttonBar->addButton($myButton);
 */
class ShortcutButton implements ButtonInterface, PositionInterface
{
    /**
     * @var string
     */
    protected $moduleName;

    /**
     * @var string
     */
    protected $displayName;

    /**
     * @var array
     */
    protected $setVariables = [];

    /**
     * @var array
     */
    protected $getVariables = [];

    /**
     * @var ControllerContext
     */
    protected $controllerContext;

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
     * @return ShortcutButton
     */
    public function setModuleName($moduleName)
    {
        $this->moduleName = $moduleName;
        return $this;
    }

    /**
     * Gets the display name of the module.
     *
     * @return string
     */
    public function getDisplayName()
    {
        return $this->displayName;
    }

    /**
     * Sets the display name of the module.
     *
     * @param string $displayName
     * @return ShortcutButton
     */
    public function setDisplayName($displayName)
    {
        $this->displayName = $displayName;
        return $this;
    }

    /**
     * Gets the SET variables.
     *
     * @return array
     */
    public function getSetVariables()
    {
        return $this->setVariables;
    }

    /**
     * Sets the SET variables.
     *
     * @param array $setVariables
     * @return ShortcutButton
     */
    public function setSetVariables(array $setVariables)
    {
        $this->setVariables = $setVariables;
        return $this;
    }

    /**
     * Gets the GET variables.
     *
     * @return array
     */
    public function getGetVariables()
    {
        return $this->getVariables;
    }

    /**
     * Sets the GET variables.
     *
     * @param array $getVariables
     * @return ShortcutButton
     */
    public function setGetVariables(array $getVariables)
    {
        $this->getVariables = $getVariables;
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
        return 91;
    }

    /**
     * Gets the type of the button
     *
     * @return string
     */
    public function getType()
    {
        return get_class($this);
    }

    /**
     * Determines whether the button shall be rendered.
     * Depends on the backend user permission to create
     * shortcuts and the defined module name.
     *
     * @return bool
     */
    public function isValid()
    {
        $this->preProcess();

        return
            !empty($this->moduleName)
        ;
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
        if ($this->getBackendUser()->mayMakeShortcut()) {
            /** @var ModuleTemplate $moduleTemplate */
            $moduleTemplate = GeneralUtility::makeInstance(ModuleTemplate::class);
            $shortcutMarkup = $moduleTemplate->makeShortcutIcon(
                implode(',', $this->getVariables),
                implode(',', $this->setVariables),
                $this->moduleName,
                '',
                $this->displayName
            );
        } else {
            $shortcutMarkup = '';
        }

        return $shortcutMarkup;
    }

    /**
     * Pre-processes class member values.
     */
    protected function preProcess()
    {
        $emptyGetVariables = (count($this->getVariables) === 0);

        // Set default GET parameters
        if ($emptyGetVariables) {
            $this->getVariables = ['id', 'M'];
        }

        // Automatically determine module name in Extbase context
        if ($this->controllerContext !== null) {
            $currentRequest = $this->controllerContext->getRequest();
            $extensionName = $currentRequest->getControllerExtensionName();
            $this->moduleName = $currentRequest->getPluginName();
            // Extend default GET parameters
            if ($emptyGetVariables) {
                $modulePrefix = strtolower('tx_' . $extensionName . '_' . $this->moduleName);
                $this->getVariables[] = $modulePrefix;
            }
        }
    }

    /**
     * @return BackendUserAuthentication
     */
    protected function getBackendUser()
    {
        return $GLOBALS['BE_USER'];
    }
}
