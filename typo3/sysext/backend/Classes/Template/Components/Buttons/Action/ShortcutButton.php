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
use TYPO3\CMS\Backend\Routing\Router;
use TYPO3\CMS\Backend\Routing\UriBuilder;
use TYPO3\CMS\Backend\Template\Components\ButtonBar;
use TYPO3\CMS\Backend\Template\Components\Buttons\ButtonInterface;
use TYPO3\CMS\Backend\Template\Components\Buttons\DropDown\DropDownItem;
use TYPO3\CMS\Backend\Template\Components\Buttons\DropDownButton;
use TYPO3\CMS\Backend\Template\Components\Buttons\GenericButton;
use TYPO3\CMS\Backend\Template\Components\Buttons\PositionInterface;
use TYPO3\CMS\Core\Authentication\BackendUserAuthentication;
use TYPO3\CMS\Core\Imaging\Icon;
use TYPO3\CMS\Core\Imaging\IconFactory;
use TYPO3\CMS\Core\Localization\LanguageService;
use TYPO3\CMS\Core\Page\PageRenderer;
use TYPO3\CMS\Core\Utility\GeneralUtility;

/**
 * ShortcutButton
 *
 * Renders a shortcut button in the DocHeader which will be rendered
 * to the right position using button group "91".
 *
 * EXAMPLE USAGE TO ADD A SHORTCUT BUTTON:
 *
 * $buttonBar = $this->moduleTemplate->getDocHeaderComponent()->getButtonBar();
 * $pageId = (int)($request->getQueryParams()['id'] ?? 0);
 * $myButton = $buttonBar->makeShortcutButton()
 *       ->setRouteIdentifier('page_preview')
 *       ->setDisplayName('View page ' . $pageId)
 *       ->setArguments([
 *          'id' => $pageId
 *       ]);
 * $buttonBar->addButton($myButton);
 */
class ShortcutButton implements ButtonInterface, PositionInterface
{
    /**
     * @var string The route identifier of the shortcut
     */
    protected string $routeIdentifier = '';

    /**
     * @var string
     */
    protected $displayName = '';

    /**
     * @var array List of parameter/value pairs relevant for this shortcut
     */
    protected $arguments = [];

    protected bool $copyUrlToClipboard = true;

    /**
     * Gets the route identifier for the shortcut.
     */
    public function getRouteIdentifier(): string
    {
        return $this->routeIdentifier;
    }

    /**
     * Sets the route identifier for the shortcut.
     */
    public function setRouteIdentifier(string $routeIdentifier): self
    {
        $this->routeIdentifier = $routeIdentifier;
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
     * @return $this
     */
    public function setArguments(array $arguments): self
    {
        $this->arguments = $arguments;
        return $this;
    }

    /**
     * Defines whether the shortcut button should be extended to also
     * allow copying the current URL to the operating systems' clipboard.
     *
     * @return $this
     */
    public function setCopyUrlToClipboard(bool $copyUrlToClipboard): self
    {
        $this->copyUrlToClipboard = $copyUrlToClipboard;
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
        return $this->displayName !== '' && $this->routeExists($this->routeIdentifier);
    }

    /**
     * Renders the button
     */
    public function __toString(): string
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
        if (!$this->getBackendUser()->mayMakeShortcut()) {
            // Early return in case the current user is not allowed to create shortcuts.
            // Note: This is not checked in isValid(), since it only concerns the current
            //       user and does not mean, the button is not configured properly.
            return '';
        }

        $routeIdentifier = $this->routeIdentifier;
        $arguments = $this->arguments;
        $iconFactory = GeneralUtility::makeInstance(IconFactory::class);

        // The route parameter is not needed, since this is already provided with the $routeIdentifier
        unset($arguments['route']);

        // returnUrl should not be stored in the database
        unset($arguments['returnUrl']);

        // Encode arguments to be stored in the database
        $encodedArguments = json_encode($arguments) ?: '';

        $confirmationText = $this->getLanguageService()->sL('LLL:EXT:core/Resources/Private/Language/locallang_core.xlf:labels.makeBookmark');
        $alreadyBookmarkedText = $this->getLanguageService()->sL('LLL:EXT:core/Resources/Private/Language/locallang_core.xlf:labels.alreadyBookmarked');

        if (!$this->copyUrlToClipboard) {
            $shortcutButton = GeneralUtility::makeInstance(GenericButton::class);
            if (GeneralUtility::makeInstance(ShortcutRepository::class)->shortcutExists($routeIdentifier, $encodedArguments)) {
                $shortcutButton->setLabel($alreadyBookmarkedText);
                $shortcutButton->setIcon($iconFactory->getIcon('actions-system-shortcut-active', Icon::SIZE_SMALL));
            } else {
                $shortcutButton->setIcon($iconFactory->getIcon('actions-system-shortcut-new', Icon::SIZE_SMALL));
                $shortcutButton->setLabel($confirmationText);
                $shortcutButton->setAttributes($this->getDispatchActionAttrs($routeIdentifier, $encodedArguments, $confirmationText));
            }
            return (string)$shortcutButton;
        }

        $dropdownItems = [];

        // Shortcut Button
        $shortcutItem = GeneralUtility::makeInstance(DropDownItem::class);
        $shortcutItem->setTag('button');
        $attributes = $this->getDispatchActionAttrs($routeIdentifier, $encodedArguments, $confirmationText);
        if (GeneralUtility::makeInstance(ShortcutRepository::class)->shortcutExists($routeIdentifier, $encodedArguments)) {
            $shortcutItem->setLabel($alreadyBookmarkedText);
            $shortcutItem->setIcon($iconFactory->getIcon('actions-system-shortcut-active', Icon::SIZE_SMALL));
            $shortcutItem->setAttributes($attributes + ['data-dispatch-disabled' => 'disabled', 'disabled' => 'disabled']);
        } else {
            $shortcutItem->setLabel($confirmationText);
            $shortcutItem->setIcon($iconFactory->getIcon('actions-system-shortcut-new', Icon::SIZE_SMALL));
            $shortcutItem->setAttributes($attributes);
        }
        $dropdownItems[] = $shortcutItem;

        // Clipboard Button
        $pageRenderer = GeneralUtility::makeInstance(PageRenderer::class);
        $pageRenderer->loadJavaScriptModule('@typo3/backend/copy-to-clipboard.js');
        $pageRenderer->addInlineLanguageLabelFile('EXT:backend/Resources/Private/Language/locallang_copytoclipboard.xlf');
        $clipboardItem = GeneralUtility::makeInstance(DropDownItem::class);
        $clipboardItem->setTag('typo3-copy-to-clipboard');
        $clipboardItem->setLabel($this->getLanguageService()->sL('LLL:EXT:core/Resources/Private/Language/locallang_core.xlf:labels.copyCurrentUrl'));
        $clipboardItem->setAttributes([
            'text' => GeneralUtility::makeInstance(UriBuilder::class)->buildUriFromRoute(
                $routeIdentifier,
                $arguments,
                UriBuilder::SHAREABLE_URL
            ),
        ]);
        $clipboardItem->setIcon($iconFactory->getIcon('actions-link', Icon::SIZE_SMALL));
        $dropdownItems[] = $clipboardItem;

        $dropdownButton = GeneralUtility::makeInstance(DropDownButton::class);
        $dropdownButton->setLabel($this->getLanguageService()->sL('LLL:EXT:core/Resources/Private/Language/locallang_core.xlf:labels.share'));
        $dropdownButton->setIcon($iconFactory->getIcon('actions-share-alt', Icon::SIZE_SMALL));
        foreach ($dropdownItems as $dropdownItem) {
            $dropdownButton->addItem($dropdownItem);
        }

        return (string)$dropdownButton;
    }

    /**
     * Returns HTML attributes for client-side `ActionDispatcher` of the "add shortcut" button.
     */
    protected function getDispatchActionAttrs(string $routeIdentifier, string $encodedArguments, string $confirmationText): array
    {
        return [
            'data-dispatch-action' => 'TYPO3.ShortcutMenu.createShortcut',
            'data-dispatch-args' => GeneralUtility::jsonEncodeForHtmlAttribute([
                $routeIdentifier,
                $encodedArguments,
                $this->displayName,
                $confirmationText,
                '{$target}',
            ]),
        ];
    }

    protected function routeExists(string $routeIdentifier): bool
    {
        return GeneralUtility::makeInstance(Router::class)->hasRoute($routeIdentifier);
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
