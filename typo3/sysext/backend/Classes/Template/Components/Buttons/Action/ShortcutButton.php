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

use TYPO3\CMS\Backend\Backend\Shortcut\ShortcutRepository;
use TYPO3\CMS\Backend\Template\Components\ButtonBar;
use TYPO3\CMS\Backend\Template\Components\Buttons\ButtonInterface;
use TYPO3\CMS\Backend\Template\Components\Buttons\PositionInterface;
use TYPO3\CMS\Backend\Template\ModuleTemplate;
use TYPO3\CMS\Core\Authentication\BackendUserAuthentication;
use TYPO3\CMS\Core\Imaging\Icon;
use TYPO3\CMS\Core\Imaging\IconFactory;
use TYPO3\CMS\Core\Localization\LanguageService;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Core\Utility\HttpUtility;

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
 *       ->setArguments([
 *          'route' => $request->getQueryParams()['route']
 *       ])
 *       ->setModuleName('my_info');
 * $buttonBar->addButton($myButton);
 */
class ShortcutButton implements ButtonInterface, PositionInterface
{
    /**
     * @var string
     */
    protected $moduleName = '';

    /**
     * @var string
     */
    protected $displayName = '';

    /**
     * @var array List of parameter/value pairs relevant for this shortcut
     */
    protected $arguments = [];

    /**
     * @var array
     * @deprecated since v11, will be removed in v12
     */
    protected $setVariables = [];

    /**
     * @var array
     * @deprecated since v11, will be removed in v12
     */
    protected $getVariables = [];

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
     * @param array $arguments
     * @return $this
     */
    public function setArguments(array $arguments): self
    {
        $this->arguments = $arguments;
        return $this;
    }

    /**
     * Gets the SET variables.
     *
     * @return array
     * @deprecated since v11, will be removed in v12
     */
    public function getSetVariables()
    {
        trigger_error('Method getSetVariables() is deprecated and will be removed in v12. Please use ShortcutButton->setArguments() instead.', E_USER_DEPRECATED);
        return $this->setVariables;
    }

    /**
     * Sets the SET variables.
     *
     * @param array $setVariables
     * @return ShortcutButton
     * @deprecated since v11, will be removed in v12. Deprecation logged by ModuleTemplate->makeShortcutIcon()
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
     * @deprecated since v11, will be removed in v12
     */
    public function getGetVariables()
    {
        trigger_error('Method getGetVariables() is deprecated and will be removed in v12. Please use ShortcutButton->setArguments() instead.', E_USER_DEPRECATED);
        return $this->getVariables;
    }

    /**
     * Sets the GET variables.
     *
     * @param array $getVariables
     * @return ShortcutButton
     * @deprecated since v11, will be removed in v12. Deprecation logged by ModuleTemplate->makeShortcutIcon()
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
        return static::class;
    }

    /**
     * Determines whether the button shall be rendered.
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
        if ($this->getBackendUser()->mayMakeShortcut()) {
            if ($this->displayName === '') {
                trigger_error('Creating a shortcut button without a display name is deprecated and fallbacks will be removed in v12. Please use ShortcutButton->setDisplayName() to set a display name.', E_USER_DEPRECATED);
            }
            if (!empty($this->arguments)) {
                $shortcutMarkup = $this->createShortcutMarkup();
            } else {
                // @deprecated since v11, the else branch will be removed in v12. Deprecation thrown by makeShortcutIcon() below
                if (empty($this->getVariables)) {
                    $this->getVariables = ['id', 'route'];
                }
                $moduleTemplate = GeneralUtility::makeInstance(ModuleTemplate::class);
                $shortcutMarkup = $moduleTemplate->makeShortcutIcon(
                    implode(',', $this->getVariables),
                    implode(',', $this->setVariables),
                    $this->moduleName,
                    '',
                    $this->displayName
                );
            }
        } else {
            $shortcutMarkup = '';
        }
        return $shortcutMarkup;
    }

    protected function createShortcutMarkup(): string
    {
        $moduleName = $this->moduleName;
        $storeUrl = HttpUtility::buildQueryString($this->arguments, '&');

        // Find out if this shortcut exists already. Note this is a hack based on the fact
        // that sys_be_shortcuts stores the entire request string and not just needed params as array.
        $pathInfo = parse_url(GeneralUtility::getIndpEnv('REQUEST_URI'));
        $shortcutUrl = $pathInfo['path'] . '?' . $storeUrl;
        $shortcutRepository = GeneralUtility::makeInstance(ShortcutRepository::class);
        $shortcutExist = $shortcutRepository->shortcutExists($shortcutUrl);

        $iconFactory = GeneralUtility::makeInstance(IconFactory::class);
        if ($shortcutExist) {
            $shortcutMarkup = '<a class="active btn btn-default btn-sm" title="">'
                . $iconFactory->getIcon('actions-system-shortcut-active', Icon::SIZE_SMALL)->render()
                . '</a>';
        } else {
            $languageService = $this->getLanguageService();
            $confirmationText = $languageService->sL('LLL:EXT:core/Resources/Private/Language/locallang_core.xlf:labels.makeBookmark');
            $onClick = 'top.TYPO3.ShortcutMenu.createShortcut('
                . GeneralUtility::quoteJSvalue(rawurlencode($moduleName))
                . ', ' . GeneralUtility::quoteJSvalue(rawurlencode($shortcutUrl))
                . ', ' . GeneralUtility::quoteJSvalue($confirmationText)
                . ', \'\''
                . ', this'
                . ', ' . GeneralUtility::quoteJSvalue($this->displayName) . ');return false;';
            $shortcutMarkup = '<a href="#" class="btn btn-default btn-sm" onclick="' . htmlspecialchars($onClick) . '" title="'
                . htmlspecialchars($languageService->sL('LLL:EXT:core/Resources/Private/Language/locallang_core.xlf:labels.makeBookmark')) . '">'
                . $iconFactory->getIcon('actions-system-shortcut-new', Icon::SIZE_SMALL)->render() . '</a>';
        }
        return $shortcutMarkup;
    }

    protected function getBackendUser(): BackendUserAuthentication
    {
        return $GLOBALS['BE_USER'];
    }

    protected function getLanguageService(): LanguageService
    {
        return $GLOBALS['LANG'];
    }
}
