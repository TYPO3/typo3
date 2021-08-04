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

namespace TYPO3\CMS\Backend\Routing;

use TYPO3\CMS\Backend\Utility\BackendUtility;
use TYPO3\CMS\Core\Http\Uri;
use TYPO3\CMS\Core\Page\PageRenderer;
use TYPO3\CMS\Core\Routing\UnableToLinkToPageException;
use TYPO3\CMS\Core\Utility\GeneralUtility;

/**
 * Substitution for `BackendUtility::getPreviewUrl`.
 * Internally `BackendUtility::getPreviewUrl` is still called due to hooks being invoked
 * there - in the future it basically aims to be a replacement for mentioned function.
 */
class PreviewUriBuilder
{
    public const OPTION_SWITCH_FOCUS = 'switchFocus';
    public const OPTION_WINDOW_NAME = 'windowName';
    public const OPTION_WINDOW_FEATURES = 'windowFeatures';
    public const OPTION_WINDOW_SCOPE = 'windowScope';

    public const OPTION_WINDOW_SCOPE_LOCAL = 'local';
    public const OPTION_WINDOW_SCOPE_GLOBAL = 'global';

    /**
     * @var int
     */
    protected $pageId;

    /**
     * @var string|null
     */
    protected $alternativeUri;

    /**
     * @var array|null
     */
    protected $rootLine;

    /**
     * @var string|null
     */
    protected $section;

    /**
     * @var string|null
     */
    protected $additionalQueryParameters;

    /**
     * @var string|null
     * @internal Not used, kept for potential compatibility issues
     */
    protected $backPath;

    /**
     * @var bool
     */
    protected $moduleLoading = true;

    /**
     * @param int $pageId Page ID to be previewed
     * @param string|null $alternativeUri Alternative URL to be used instead of `/index.php?id=`
     * @return static
     */
    public static function create(int $pageId, string $alternativeUri = null): self
    {
        return GeneralUtility::makeInstance(static::class, $pageId, $alternativeUri);
    }

    /**
     * @param int $pageId Page ID to be previewed
     * @param string|null $alternativeUri Alternative URL to be used instead of `/index.php?id=`
     */
    public function __construct(int $pageId, string $alternativeUri = null)
    {
        $this->pageId = $pageId;
        $this->alternativeUri = $alternativeUri;
    }

    /**
     * @param bool $moduleLoading whether to enable JavaScript module loading
     * @return static
     */
    public function withModuleLoading(bool $moduleLoading): self
    {
        if ($this->moduleLoading === $moduleLoading) {
            return $this;
        }
        $target = clone $this;
        $target->moduleLoading = $moduleLoading;
        return $target;
    }

    /**
     * @param array $rootLine (alternative) root-line of pages
     * @return static
     */
    public function withRootLine(array $rootLine): self
    {
        if ($this->rootLine === $rootLine) {
            return $this;
        }
        $target = clone $this;
        $target->rootLine = $rootLine;
        return $this;
    }

    /**
     * @param string $section particular section (anchor element)
     * @return static
     */
    public function withSection(string $section): self
    {
        if ($this->section === $section) {
            return $this;
        }
        $target = clone $this;
        $target->section = $section;
        return $target;
    }

    /**
     * @param string $additionalQueryParameters additional URI query parameters
     * @return static
     */
    public function withAdditionalQueryParameters(string $additionalQueryParameters): self
    {
        if ($this->additionalQueryParameters === $additionalQueryParameters) {
            return $this;
        }
        $target = clone $this;
        $target->additionalQueryParameters = $additionalQueryParameters;
        return $target;
    }

    /**
     * Builds preview URI (still using `BackendUtility::getPreviewUrl`).
     *
     * @param array|null $options
     * @return Uri|null
     */
    public function buildUri(array $options = null): ?Uri
    {
        try {
            $options = $this->enrichOptions($options);
            $switchFocus = $options[self::OPTION_SWITCH_FOCUS] ?? true;
            $uriString = BackendUtility::getPreviewUrl(
                $this->pageId,
                $this->backPath ?? '',
                $this->rootLine,
                $this->section ?? '',
                $this->alternativeUri ?? '',
                $this->additionalQueryParameters ?? '',
                $switchFocus
            );
            return GeneralUtility::makeInstance(Uri::class, $uriString);
        } catch (UnableToLinkToPageException $exception) {
            return null;
        }
    }

    /**
     * Builds attributes array (e.g. `['dispatch-action' => ...]`).
     * CAVE: Attributes are NOT XSS-protected and need to be put through `htmlspecialchars`
     *
     * @param array|null $options
     * @return array|null
     */
    public function buildDispatcherDataAttributes(array $options = null): ?array
    {
        if (null === ($attributes = $this->buildAttributes($options))) {
            return null;
        }
        $this->loadActionDispatcher();
        return $this->prefixAttributeNames('dispatch-', $attributes);
    }

    /**
     * Builds attributes array (e.g. `['data-dispatch-action' => ...]`).
     * CAVE: Attributes are NOT XSS-protected and need to be put through `htmlspecialchars`
     *
     * @param array|null $options
     * @return array|null
     */
    public function buildDispatcherAttributes(array $options = null): ?array
    {
        if (null === ($attributes = $this->buildAttributes($options))) {
            return null;
        }
        $this->loadActionDispatcher();
        return $this->prefixAttributeNames('data-dispatch-', $attributes);
    }

    /**
     * Serialized attributes are processed with `htmlspecialchars` and ready to be used.
     *
     * @param array|null $options
     * @return string|null
     */
    public function serializeDispatcherAttributes(array $options = null): ?string
    {
        if (null === ($attributes = $this->buildDispatcherAttributes($options))) {
            return null;
        }
        return ' ' . GeneralUtility::implodeAttributes($attributes, true);
    }

    /**
     * `<typo3-immediate-action>` does not have a specific meaning and is used to
     * expose `data` attributes, see custom element in `ImmediateActionElement.ts`.
     *
     * @param array|null $options
     * @return string|null
     */
    public function buildImmediateActionElement(array $options = null): ?string
    {
        if (null === ($attributes = $this->buildAttributes($options))) {
            return null;
        }
        $this->loadImmediateActionElement();
        return sprintf(
            // `<typo3-immediate-action action="TYPO3.WindowManager.localOpen" args="[...]">`
            '<typo3-immediate-action %s></typo3-immediate-action>',
            GeneralUtility::implodeAttributes($attributes, true)
        );
    }

    protected function buildAttributes(array $options = null): ?array
    {
        $options = $this->enrichOptions($options);
        if (null === ($uri = $this->buildUri($options))) {
            return null;
        }
        $args = [
            // target URI
            (string)$uri,
            // whether to switch focus to that window
            $options[self::OPTION_SWITCH_FOCUS],
            // name of the window instance for JavaScript references
            $options[self::OPTION_WINDOW_NAME],
        ];
        if (isset($options[self::OPTION_WINDOW_FEATURES])) {
            // optional window features (e.g. 'width=500,height=300')
            $args[] = $options[self::OPTION_WINDOW_FEATURES];
        }
        return [
            'action' => $options[self::OPTION_WINDOW_SCOPE] === self::OPTION_WINDOW_SCOPE_GLOBAL
                ? 'TYPO3.WindowManager.globalOpen'
                : 'TYPO3.WindowManager.localOpen',
            'args' => json_encode($args),
        ];
    }

    /**
     * Handles options to used for opening preview URI in a new window/tab.
     * + `switchFocus` (bool): whether to focus new window in browser
     * + `windowName` (string): name of window for internal reference
     * + `windowScope` (string): `local` (current document) `global` (whole backend)
     *
     * @param array|null $options
     * @return array
     */
    protected function enrichOptions(array $options = null): array
    {
        return array_merge(
            [
                self::OPTION_SWITCH_FOCUS => null,
                // 'newTYPO3frontendWindow' was used in BackendUtility::viewOnClick
                self::OPTION_WINDOW_NAME => 'newTYPO3frontendWindow',
                self::OPTION_WINDOW_SCOPE => self::OPTION_WINDOW_SCOPE_LOCAL,
            ],
            $options ?? []
        );
    }

    protected function loadActionDispatcher(): void
    {
        if (!$this->moduleLoading) {
            return;
        }
        $pageRenderer = GeneralUtility::makeInstance(PageRenderer::class);
        $pageRenderer->loadRequireJsModule('TYPO3/CMS/Backend/ActionDispatcher');
    }

    protected function loadImmediateActionElement(): void
    {
        if (!$this->moduleLoading) {
            return;
        }
        $pageRenderer = GeneralUtility::makeInstance(PageRenderer::class);
        $pageRenderer->loadRequireJsModule('TYPO3/CMS/Backend/Element/ImmediateActionElement');
    }

    protected function prefixAttributeNames(string $prefix, array $attributes): array
    {
        $attributeNames = array_map(
            static function (string $name) use ($prefix): string {
                return $prefix . $name;
            },
            array_keys($attributes)
        );
        return array_combine(
            $attributeNames,
            array_values($attributes)
        );
    }
}
