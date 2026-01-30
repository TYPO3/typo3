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

namespace TYPO3\CMS\Backend\Template\Components\Buttons\Action;

use TYPO3\CMS\Backend\Routing\Router;
use TYPO3\CMS\Backend\Routing\UriBuilder;
use TYPO3\CMS\Backend\Template\Components\ButtonBar;
use TYPO3\CMS\Backend\Template\Components\Buttons\ButtonInterface;
use TYPO3\CMS\Backend\Template\Components\Buttons\PositionInterface;
use TYPO3\CMS\Backend\Template\Components\ComponentFactory;
use TYPO3\CMS\Core\Authentication\BackendUserAuthentication;
use TYPO3\CMS\Core\Imaging\IconFactory;
use TYPO3\CMS\Core\Imaging\IconSize;
use TYPO3\CMS\Core\Localization\LanguageService;
use TYPO3\CMS\Core\Page\PageRenderer;
use TYPO3\CMS\Core\Utility\GeneralUtility;

/**
 * Shortcut button for the DocHeader that enables bookmarking of backend module states.
 *
 * This button allows users to create bookmarks to specific module views
 * with their context (e.g., specific page, record, or configuration). The shortcut
 * preserves the route and arguments for quick access later.
 *
 * The button is automatically added to all backend modules by default when shortcut
 * context is provided. It's positioned on the right side of the button bar as the
 * last button (group 91).
 *
 * Example - Using automatic bookmark button (recommended):
 *
 * ```
 * public function myAction(ServerRequestInterface $request): ResponseInterface
 * {
 *     $view = $this->moduleTemplateFactory->create($request);
 *
 *     // Set shortcut context - button is added automatically
 *     $view->getDocHeaderComponent()->setShortcutContext(
 *         routeIdentifier: 'my_module',
 *         displayName: 'My Module: ' . $pageTitle,
 *         arguments: ['id' => $pageId]
 *     );
 *
 *     return $view->renderResponse('MyTemplate');
 * }
 * ```
 *
 * Note: As of TYPO3 v14, manually adding ShortcutButton is deprecated.
 * Use DocHeaderComponent::setShortcutContext() instead.
 */
class ShortcutButton implements ButtonInterface, PositionInterface
{
    protected string $routeIdentifier = '';

    protected string $displayName = '';

    /**
     * @var array List of parameter/value pairs relevant for this bookmark
     */
    protected array $arguments = [];

    protected bool $copyUrlToClipboard = true;

    protected bool $disabled = false;

    public function getRouteIdentifier(): string
    {
        return $this->routeIdentifier;
    }

    public function setRouteIdentifier(string $routeIdentifier): static
    {
        $this->routeIdentifier = $routeIdentifier;
        return $this;
    }

    public function getDisplayName(): string
    {
        return $this->displayName;
    }

    public function setDisplayName(string $displayName): static
    {
        $this->displayName = $displayName;
        return $this;
    }

    public function setArguments(array $arguments): static
    {
        $this->arguments = $arguments;
        return $this;
    }

    /**
     * Defines whether the button should be extended to also allow
     * copying the current URL to the operating systems' clipboard.
     */
    public function setCopyUrlToClipboard(bool $copyUrlToClipboard): static
    {
        $this->copyUrlToClipboard = $copyUrlToClipboard;
        return $this;
    }

    public function isDisabled(): bool
    {
        return $this->disabled;
    }

    public function setDisabled(bool $disabled): static
    {
        $this->disabled = $disabled;
        return $this;
    }

    public function getPosition(): string
    {
        return ButtonBar::BUTTON_POSITION_RIGHT;
    }

    public function getGroup(): int
    {
        return 91;
    }

    public function getType(): string
    {
        return static::class;
    }

    public function isValid(): bool
    {
        return $this->displayName !== '' && $this->routeExists($this->routeIdentifier);
    }

    public function __toString(): string
    {
        return $this->render();
    }

    public function render(): string
    {
        $canCreateBookmark = $this->getBackendUser()->mayMakeShortcut();
        // Early return in case the current user is not allowed to create bookmarks.
        // Note: This is not checked in isValid(), since it only concerns the current
        //       user and does not mean, the button is not configured properly.
        if (!$canCreateBookmark && !$this->copyUrlToClipboard) {
            return '';
        }

        $routeIdentifier = $this->routeIdentifier;
        $arguments = $this->arguments;
        $pageRenderer = GeneralUtility::makeInstance(PageRenderer::class);

        // The route parameter is not needed, since this is already provided with the $routeIdentifier
        unset($arguments['route']);

        // returnUrl should not be stored in the database
        unset($arguments['returnUrl']);

        // Encode arguments to be stored in the database
        $encodedArguments = json_encode($arguments) ?: '';

        if ($canCreateBookmark && !$this->copyUrlToClipboard) {
            // Load the bookmark button custom element
            $pageRenderer->loadJavaScriptModule('@typo3/backend/bookmark/element/bookmark-button-element.js');

            return $this->renderBookmarkButton($routeIdentifier, $encodedArguments);
        }

        $iconFactory = GeneralUtility::makeInstance(IconFactory::class);
        $dropdownItems = [];
        $componentFactory = GeneralUtility::makeInstance(ComponentFactory::class);

        // Bookmark Button
        if ($canCreateBookmark) {
            // Load the bookmark button custom element
            $pageRenderer->loadJavaScriptModule('@typo3/backend/bookmark/element/bookmark-button-element.js');

            $bookmarkItem = $componentFactory->createDropDownGeneric();
            $bookmarkItem->setTag('typo3-backend-bookmark-button');
            $bookmarkItem->setAttributes([
                'route' => $routeIdentifier,
                'arguments' => $encodedArguments,
                'display-name' => $this->displayName,
            ]);
            $dropdownItems[] = $bookmarkItem;
        }

        // Clipboard Button
        if ($this->copyUrlToClipboard) {
            $pageRenderer->loadJavaScriptModule('@typo3/backend/copy-to-clipboard.js');
            $pageRenderer->addInlineLanguageLabelFile('EXT:backend/Resources/Private/Language/locallang_copytoclipboard.xlf');
            $clipboardItem = $componentFactory->createDropDownItem();
            $clipboardItem->setTag('typo3-copy-to-clipboard');
            $clipboardItem->setLabel($this->getLanguageService()->sL('LLL:EXT:core/Resources/Private/Language/locallang_core.xlf:labels.copyCurrentUrl'));
            $clipboardItem->setAttributes([
                'text' => (string)GeneralUtility::makeInstance(UriBuilder::class)->buildUriFromRoute(
                    $routeIdentifier,
                    $arguments,
                    UriBuilder::SHAREABLE_URL
                ),
            ]);
            $clipboardItem->setIcon($iconFactory->getIcon('actions-link', IconSize::SMALL));
            $dropdownItems[] = $clipboardItem;
        }

        $dropdownButton = $componentFactory->createDropDownButton();
        $dropdownButton->setLabel($this->getLanguageService()->sL('LLL:EXT:core/Resources/Private/Language/locallang_core.xlf:labels.share'));
        $dropdownButton->setIcon($iconFactory->getIcon('actions-share-alt', IconSize::SMALL));
        $dropdownButton->setDisabled($this->isDisabled());
        foreach ($dropdownItems as $dropdownItem) {
            $dropdownButton->addItem($dropdownItem);
        }

        return (string)$dropdownButton;
    }

    /**
     * Renders the bookmark button custom element.
     */
    protected function renderBookmarkButton(string $routeIdentifier, string $encodedArguments): string
    {
        $attributes = [
            'class' => 'btn btn-default btn-sm',
            'route' => $routeIdentifier,
            'arguments' => $encodedArguments,
            'display-name' => $this->displayName,
            'hide-label-text' => 'true',
        ];

        return '<typo3-backend-bookmark-button ' . GeneralUtility::implodeAttributes($attributes, true) . '></typo3-backend-bookmark-button>';
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
