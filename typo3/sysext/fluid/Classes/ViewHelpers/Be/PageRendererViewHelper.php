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

namespace TYPO3\CMS\Fluid\ViewHelpers\Be;

use Psr\Http\Message\ServerRequestInterface;
use TYPO3\CMS\Core\Localization\LanguageService;
use TYPO3\CMS\Core\Page\PageRenderer;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Extbase\Mvc\RequestInterface;
use TYPO3\CMS\Extbase\Utility\LocalizationUtility;
use TYPO3Fluid\Fluid\Core\ViewHelper\AbstractViewHelper;

/**
 * ViewHelper to register backend module resources like CSS and JavaScript using the PageRenderer.
 *
 * ```
 *   <f:be.pageRenderer
 *        pageTitle="foo"
 *        includeCssFiles="{0: 'EXT:my_ext/Resources/Public/Css/Stylesheet.css'}"
 *        includeJsFiles="{0: 'EXT:my_ext/Resources/Public/JavaScript/Library1.js', 1: 'EXT:my_ext/Resources/Public/JavaScript/Library2.js'}"
 *        addJsInlineLabels="{'my_ext.label1': 'LLL:EXT:my_ext/Resources/Private/Language/locallang.xlf:label1'}"
 *        includeJavaScriptModules="{0: '@my-vendor/my-ext/my-module.js'}"
 *        addInlineSettings="{'some.setting.key': 'some.setting.value'}"
 *    />
 * ```
 * @see https://docs.typo3.org/permalink/t3viewhelper:typo3-fluid-be-pagerenderer
 */
final class PageRendererViewHelper extends AbstractViewHelper
{
    public function initializeArguments(): void
    {
        $this->registerArgument('pageTitle', 'string', 'title tag of the module. Not required by default, as BE modules are shown in a frame', false, '');
        $this->registerArgument('includeCssFiles', 'array', 'List of custom CSS file to be loaded');
        $this->registerArgument('includeJsFiles', 'array', 'List of custom JavaScript file to be loaded');
        $this->registerArgument('addJsInlineLabels', 'array', 'Custom labels to add to JavaScript inline labels');
        $this->registerArgument('includeJavaScriptModules', 'array', 'List of JavaScript modules to be loaded');
        $this->registerArgument('addInlineSettings', 'array', 'Adds Javascript Inline Setting');
    }

    public function render(): string
    {
        $pageRenderer = self::getPageRenderer();
        $pageTitle = $this->arguments['pageTitle'];
        $includeCssFiles = $this->arguments['includeCssFiles'];
        $includeJsFiles = $this->arguments['includeJsFiles'];
        $addJsInlineLabels = $this->arguments['addJsInlineLabels'];
        $includeJavaScriptModules = $this->arguments['includeJavaScriptModules'];
        $addInlineSettings = $this->arguments['addInlineSettings'];
        if ($pageTitle) {
            $pageRenderer->setTitle($pageTitle);
        }
        // Include custom CSS and JS files
        if (is_array($includeCssFiles)) {
            foreach ($includeCssFiles as $addCssFile) {
                $pageRenderer->addCssFile($addCssFile);
            }
        }
        if (is_array($includeJsFiles)) {
            foreach ($includeJsFiles as $addJsFile) {
                $pageRenderer->addJsFile($addJsFile);
            }
        }
        if (is_array($includeJavaScriptModules)) {
            foreach ($includeJavaScriptModules as $addJavaScriptModule) {
                $pageRenderer->loadJavaScriptModule($addJavaScriptModule);
            }
        }
        if (is_array($addInlineSettings)) {
            $pageRenderer->addInlineSettingArray(null, $addInlineSettings);
        }
        // Add inline language labels
        if (is_array($addJsInlineLabels) && count($addJsInlineLabels) > 0) {
            if ($this->renderingContext->hasAttribute(ServerRequestInterface::class)
                && $this->renderingContext->getAttribute(ServerRequestInterface::class) instanceof RequestInterface) {
                // Extbase request resolves extension key and allows overriding labels using TypoScript configuration.
                $extensionKey = $this->renderingContext->getAttribute(ServerRequestInterface::class)->getControllerExtensionKey();
                foreach ($addJsInlineLabels as $key) {
                    $label = LocalizationUtility::translate($key, $extensionKey);
                    $pageRenderer->addInlineLanguageLabel($key, $label);
                }
            } else {
                // No extbase request, labels should follow "LLL:EXT:some_ext/Resources/Private/someFile.xlf:key"
                // syntax, and are not overridden by TypoScript extbase module / plugin configuration.
                foreach ($addJsInlineLabels as &$labelKey) {
                    $labelKey = self::getLanguageService()->sL($labelKey);
                }
                $pageRenderer->addInlineLanguageLabelArray($addJsInlineLabels);
            }
        }
        return '';
    }

    private static function getPageRenderer(): PageRenderer
    {
        return GeneralUtility::makeInstance(PageRenderer::class);
    }

    private static function getLanguageService(): LanguageService
    {
        return $GLOBALS['LANG'];
    }
}
