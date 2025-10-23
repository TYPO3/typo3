<?php

declare(strict_types=1);

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

namespace TYPO3\CMS\Backend\Template\Components;

use Symfony\Component\DependencyInjection\Attribute\Autoconfigure;
use TYPO3\CMS\Backend\Template\Components\Buttons\Action\ShortcutButton;
use TYPO3\CMS\Backend\Template\Components\Buttons\DropDownButton;
use TYPO3\CMS\Backend\Template\Components\Buttons\FullyRenderedButton;
use TYPO3\CMS\Backend\Template\Components\Buttons\GenericButton;
use TYPO3\CMS\Backend\Template\Components\Buttons\InputButton;
use TYPO3\CMS\Backend\Template\Components\Buttons\LinkButton;
use TYPO3\CMS\Backend\Template\Components\Buttons\SplitButton;
use TYPO3\CMS\Backend\Template\Components\Menu\Menu;
use TYPO3\CMS\Backend\Template\Components\Menu\MenuItem;
use TYPO3\CMS\Core\Imaging\IconFactory;
use TYPO3\CMS\Core\Imaging\IconSize;
use TYPO3\CMS\Core\Localization\LanguageService;
use TYPO3\CMS\Core\Utility\GeneralUtility;

/**
 * Factory for creating backend template components, e.g. buttons.
 *
 * This ComponentFactory serves as the central location for all component creation in the backend,
 * providing both pre-configured components for common patterns and basic component factory methods.
 *
 * Currently focused on button creation, but designed to be extensible for other component types
 * (menus, breadcrumbs, etc.) in the future.
 *
 * This reduces boilerplate code and ensures consistent UX across the backend by providing
 * standardized component configurations for recurring use cases.
 *
 * Example - Creating a back button:
 *
 * ```
 * public function __construct(
 *     protected readonly ComponentFactory $componentFactory,
 * ) {}
 *
 * public function myAction(): ResponseInterface
 * {
 *     $buttonBar = $this->moduleTemplate->getDocHeaderComponent()->getButtonBar();
 *
 *     // Use pre-configured back button
 *     $backButton = $this->componentFactory->createBackButton($returnUrl);
 *     $buttonBar->addButton($backButton, ButtonBar::BUTTON_POSITION_LEFT, 1);
 * }
 * ```
 */
#[Autoconfigure(public: true)]
readonly class ComponentFactory
{
    public function __construct(
        protected IconFactory $iconFactory,
    ) {}

    /**
     * Creates a standardized "back" navigation button.
     *
     * @param string $returnUrl The URL to navigate back to
     * @return LinkButton Fully configured back button
     */
    public function createBackButton(string $returnUrl): LinkButton
    {
        return $this->createLinkButton()
            ->setHref($returnUrl)
            ->setTitle($this->getLanguageService()->sL('LLL:EXT:core/Resources/Private/Language/locallang_core.xlf:labels.goBack'))
            ->setIcon($this->iconFactory->getIcon('actions-view-go-back', IconSize::SMALL))
            ->setShowLabelText(true);
    }

    /**
     * Creates a standardized "close" button.
     *
     * Similar to back button but uses "actions-close" icon and "Close" label.
     * Typically used for closing detail views or modal-like overlays.
     *
     * @param string $closeUrl The URL to navigate to when closing
     * @return LinkButton Fully configured close button
     */
    public function createCloseButton(string $closeUrl): LinkButton
    {
        return $this->createLinkButton()
            ->setHref($closeUrl)
            ->setTitle($this->getLanguageService()->sL('LLL:EXT:core/Resources/Private/Language/locallang_core.xlf:labels.close'))
            ->setIcon($this->iconFactory->getIcon('actions-close', IconSize::SMALL))
            ->setShowLabelText(true);
    }

    /**
     * Creates a standardized "refresh" button for reloading the current view.
     *
     *  Similar to back button but uses "actions-refresh" icon and without a displayed label.
     *
     * @return LinkButton Fully configured refresh button with current URL
     */
    public function createRefreshButton(string $requestUri): LinkButton
    {
        return $this->createLinkButton()
            ->setHref($requestUri)
            ->setTitle($this->getLanguageService()->sL('LLL:EXT:core/Resources/Private/Language/locallang_core.xlf:labels.reload'))
            ->setIcon($this->iconFactory->getIcon('actions-refresh', IconSize::SMALL));
    }

    /**
     * Creates a standardized "save" button for forms.
     *
     * Returns an InputButton configured with:
     * - "actions-document-save" icon
     * - Translated "Save" label (shown as text)
     * - name="_savedok" and value="1"
     * - Associated with the specified form
     *
     * @param string $formName The HTML form ID this button belongs to
     * @return InputButton Fully configured save button
     */
    public function createSaveButton(string $formName = ''): InputButton
    {
        $button = $this->createInputButton()
            ->setName('_savedok')
            ->setValue('1')
            ->setTitle($this->getLanguageService()->sL('LLL:EXT:core/Resources/Private/Language/locallang_core.xlf:cm.save') ?: 'Save')
            ->setIcon($this->iconFactory->getIcon('actions-document-save', IconSize::SMALL))
            ->setShowLabelText(true);

        if ($formName !== '') {
            $button->setForm($formName);
        }

        return $button;
    }

    public function createGenericButton(): GenericButton
    {
        return GeneralUtility::makeInstance(GenericButton::class);
    }

    public function createInputButton(): InputButton
    {
        return GeneralUtility::makeInstance(InputButton::class);
    }

    public function createSplitButton(): SplitButton
    {
        return GeneralUtility::makeInstance(SplitButton::class);
    }

    public function createDropDownButton(): DropDownButton
    {
        return GeneralUtility::makeInstance(DropDownButton::class);
    }

    public function createLinkButton(): LinkButton
    {
        return GeneralUtility::makeInstance(LinkButton::class);
    }

    public function createFullyRenderedButton(): FullyRenderedButton
    {
        return GeneralUtility::makeInstance(FullyRenderedButton::class);
    }

    public function createShortcutButton(): ShortcutButton
    {
        return GeneralUtility::makeInstance(ShortcutButton::class);
    }

    public function createMenuItem(): MenuItem
    {
        return GeneralUtility::makeInstance(MenuItem::class);
    }

    public function createMenu(): Menu
    {
        return GeneralUtility::makeInstance(Menu::class);
    }

    protected function getLanguageService(): LanguageService
    {
        return $GLOBALS['LANG'];
    }
}
