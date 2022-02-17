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
use TYPO3\CMS\Backend\Template\Components\Buttons\PositionInterface;
use TYPO3\CMS\Backend\Template\ModuleTemplate;
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
 *       ->setRouteIdentifier('web_ViewpageView')
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
     * @deprecated since v11, will be removed in v12
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
     * @var bool
     */
    protected bool $copyUrlToClipboard = true;

    /**
     * Gets the route identifier for the shortcut.
     *
     * @return string
     */
    public function getRouteIdentifier(): string
    {
        return $this->routeIdentifier;
    }

    /**
     * Sets the route identifier for the shortcut.
     *
     * @param string $routeIdentifier
     * @return ShortcutButton
     */
    public function setRouteIdentifier(string $routeIdentifier): self
    {
        $this->routeIdentifier = $routeIdentifier;
        return $this;
    }

    /**
     * Gets the name of the module.
     *
     * @return string
     * @deprecated since v11, will be removed in v12
     */
    public function getModuleName()
    {
        trigger_error('Method getModuleName() is deprecated and will be removed in v12. Use getRouteIdentifier() instead.', E_USER_DEPRECATED);
        return $this->moduleName;
    }

    /**
     * Sets the name of the module.
     *
     * @param string $moduleName
     * @return ShortcutButton
     * @deprecated since v11, will be removed in v12
     */
    public function setModuleName($moduleName)
    {
        trigger_error('Method setModuleName() is deprecated and will be removed in v12. Use setRouteIdentifier() instead.', E_USER_DEPRECATED);
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
     * Defines whether the shortcut button should be extended to also
     * allow copying the current URL to the operating systems' clipboard.
     *
     * @param bool $copyUrlToClipboard
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
        return $this->moduleName !== '' || $this->routeIdentifier !== '' || (string)($this->arguments['route'] ?? '') !== '';
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
            if (!empty($this->routeIdentifier) || !empty($this->arguments)) {
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
        $routeIdentifier = $this->routeIdentifier;
        $arguments = $this->arguments;
        $iconFactory = GeneralUtility::makeInstance(IconFactory::class);

        if (str_contains($routeIdentifier, '/')) {
            trigger_error('Automatic fallback for the route path is deprecated and will be removed in v12.', E_USER_DEPRECATED);
            $routeIdentifier = $this->getRouteIdentifierByRoutePath($routeIdentifier);
        }

        if ($routeIdentifier === '' && $this->moduleName !== '') {
            trigger_error('Using ShortcutButton::$moduleNname is deprecated and will be removed in v12. Use ShortcutButton::$routeIdentifier instead.', E_USER_DEPRECATED);
            $routeIdentifier = $this->getRouteIdentifierByModuleName($this->moduleName);
        }

        if (isset($arguments['route'])) {
            trigger_error('Using route as an argument is deprecated and will be removed in v12. Set the route identifier with ShortcutButton::setRouteIdentifier() instead.', E_USER_DEPRECATED);
            if ($routeIdentifier === '' && is_string($arguments['route'])) {
                $routeIdentifier = $this->getRouteIdentifierByRoutePath($arguments['route']);
            }
            unset($arguments['route']);
        }

        // No route found so no shortcut button will be rendered
        if ($routeIdentifier === '' || !$this->routeExists($routeIdentifier)) {
            return '';
        }

        // returnUrl will not longer be stored in the database
        unset($arguments['returnUrl']);

        // Encode arguments to be stored in the database
        $encodedArguments = json_encode($arguments) ?: '';

        $confirmationText = $this->getLanguageService()->sL('LLL:EXT:core/Resources/Private/Language/locallang_core.xlf:labels.makeBookmark');
        $alreadyBookmarkedText = $this->getLanguageService()->sL('LLL:EXT:core/Resources/Private/Language/locallang_core.xlf:labels.alreadyBookmarked');

        if (!$this->copyUrlToClipboard) {
            return GeneralUtility::makeInstance(ShortcutRepository::class)->shortcutExists($routeIdentifier, $encodedArguments)
                ? '<button type="button" class="active btn btn-default btn-sm" title="' . htmlspecialchars($alreadyBookmarkedText) . '">'
                    . $iconFactory->getIcon('actions-system-shortcut-active', Icon::SIZE_SMALL)->render()
                    . '</button>'
                : '<button type="button" class="btn btn-default btn-sm" title="' . htmlspecialchars($confirmationText) . '" ' . $this->getDispatchActionAttrs($routeIdentifier, $encodedArguments, $confirmationText) . '>'
                    . $iconFactory->getIcon('actions-system-shortcut-new', Icon::SIZE_SMALL)->render()
                    . '</button>';
        }

        $menuItems = [];

        if (GeneralUtility::makeInstance(ShortcutRepository::class)->shortcutExists($routeIdentifier, $encodedArguments)) {
            $menuItems[] =
                '<li>' .
                    '<button type="button" class="dropdown-item btn btn-link disabled">' .
                        $iconFactory->getIcon('actions-system-shortcut-active', Icon::SIZE_SMALL)->render() . ' ' .
                        htmlspecialchars($alreadyBookmarkedText) .
                    '</button>' .
                '</li>';
        } else {
            $menuItems[] = '
                <li>' .
                    '<button type="button" class="dropdown-item btn btn-link" ' . $this->getDispatchActionAttrs($routeIdentifier, $encodedArguments, $confirmationText) . '>' .
                        $iconFactory->getIcon('actions-system-shortcut-new', Icon::SIZE_SMALL)->render() . ' ' .
                        htmlspecialchars($confirmationText) .
                    '</button>' .
                '</li>';
        }

        $pageRenderer = GeneralUtility::makeInstance(PageRenderer::class);
        $pageRenderer->loadRequireJsModule('TYPO3/CMS/Backend/CopyToClipboard');
        $pageRenderer->addInlineLanguageLabelFile('EXT:backend/Resources/Private/Language/locallang_copytoclipboard.xlf');

        $currentUrl = (string)GeneralUtility::makeInstance(UriBuilder::class)->buildUriFromRoute(
            $routeIdentifier,
            $arguments,
            UriBuilder::SHAREABLE_URL
        );

        $menuItems[] = '
            <li>
                <typo3-copy-to-clipboard text="' . htmlspecialchars($currentUrl) . '">
                    <button type="button" class="dropdown-item btn btn-link">' .
                        $iconFactory->getIcon('actions-link', Icon::SIZE_SMALL)->render() . ' ' .
                        htmlspecialchars($this->getLanguageService()->sL('LLL:EXT:core/Resources/Private/Language/locallang_core.xlf:labels.copyCurrentUrl')) .
                    '</button>' .
                '</typo3-copy-to-clipboard>' .
            '</li>';

        return '
            <button type="button" class="btn btn-default btn-sm" id="dropdownShortcutMenu" data-bs-toggle="dropdown" aria-expanded="false" title="' . htmlspecialchars($this->getLanguageService()->sL('LLL:EXT:core/Resources/Private/Language/locallang_core.xlf:labels.share')) . '">' .
                $iconFactory->getIcon('actions-share-alt', Icon::SIZE_SMALL)->render() .
            '</button>' .
            '<ul class="dropdown-menu" aria-labelledby="dropdownShortcutMenu">' .
                implode(LF, $menuItems) .
            '</ul>';
    }

    /**
     * Map a given route path to its route identifier
     *
     * @param string $routePath
     * @return string
     * @deprecated Only for backwards compatibility. Can be removed in v12.
     */
    protected function getRouteIdentifierByRoutePath(string $routePath): string
    {
        foreach ($this->getRoutes() as $identifier => $route) {
            if ($route->getPath() === $routePath
                && (
                    $route->hasOption('moduleName')
                    || in_array($identifier, ['record_edit', 'file_edit', 'wizard_rte'], true)
                )
            ) {
                return $identifier;
            }
        }

        return '';
    }

    /**
     * Map a given module name to its route identifier by respecting some special cases
     *
     * @param string $moduleName
     * @return string
     * @deprecated Only for backwards compatibility. Can be removed in v12.
     */
    protected function getRouteIdentifierByModuleName(string $moduleName): string
    {
        $identifier = '';

        // Special case module names
        switch ($moduleName) {
            case 'xMOD_alt_doc.php':
                $identifier = 'record_edit';
                break;
            case 'file_edit':
            case 'wizard_rte':
                $identifier = $moduleName;
                break;
        }

        if ($identifier !== '') {
            return $identifier;
        }

        foreach ($this->getRoutes() as $identifier => $route) {
            if ($route->hasOption('moduleName') && $route->getOption('moduleName') === $moduleName) {
                return $identifier;
            }
        }

        return '';
    }

    /**
     * Returns HTML attributes for client-side `ActionDispatcher` of the "add shortcut" button.
     *
     * @param string $routeIdentifier
     * @param string $encodedArguments
     * @param string $confirmationText
     * @return string
     */
    protected function getDispatchActionAttrs(string $routeIdentifier, string $encodedArguments, string $confirmationText): string
    {
        $attrs = [
            'data-dispatch-action' => 'TYPO3.ShortcutMenu.createShortcut',
            'data-dispatch-args' => GeneralUtility::jsonEncodeForHtmlAttribute([
                $routeIdentifier,
                $encodedArguments,
                $this->displayName,
                $confirmationText,
                '{$target}',
            ], false),
        ];
        return GeneralUtility::implodeAttributes($attrs, true);
    }

    protected function routeExists(string $routeIdentifier): bool
    {
        return (bool)($this->getRoutes()[$routeIdentifier] ?? false);
    }

    protected function getRoutes(): iterable
    {
        return GeneralUtility::makeInstance(Router::class)->getRoutes();
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
