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

use TYPO3\CMS\Core\Localization\LanguageService;
use TYPO3\CMS\Core\Page\PageRenderer;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Extbase\Mvc\RequestInterface;
use TYPO3\CMS\Extbase\Utility\LocalizationUtility;
use TYPO3\CMS\Fluid\Core\Rendering\RenderingContext;
use TYPO3Fluid\Fluid\Core\Rendering\RenderingContextInterface;
use TYPO3Fluid\Fluid\Core\ViewHelper\AbstractViewHelper;
use TYPO3Fluid\Fluid\Core\ViewHelper\Traits\CompileWithRenderStatic;

/**
 * ViewHelper to register backend module resources like CSS and JavaScript using the PageRenderer.
 *
 * Examples
 * ========
 *
 * All options::
 *
 *    <f:be.pageRenderer
 *        pageTitle="foo"
 *        includeCssFiles="{0: 'EXT:my_ext/Resources/Public/Css/Stylesheet.css'}"
 *        includeJsFiles="{0: 'EXT:my_ext/Resources/Public/JavaScript/Library1.js', 1: 'EXT:my_ext/Resources/Public/JavaScript/Library2.js'}"
 *        addJsInlineLabels="{'my_ext.label1': 'LLL:EXT:my_ext/Resources/Private/Language/locallang.xlf:label1'}"
 *        includeJavaScriptModules="{0: '@my-vendor/my-ext/my-module.js'}"
 *        includeRequireJsModules="{0: 'EXT:my_ext/Resources/Public/JavaScript/RequireJsModule'}"
 *        addInlineSettings="{'some.setting.key': 'some.setting.value'}"
 *    />
 *
 * This will load the specified css, js files and requireJs modules, adds a custom js
 * inline setting, and adds a resolved label to be used in js.
 */
final class PageRendererViewHelper extends AbstractViewHelper
{
    use CompileWithRenderStatic;

    public function initializeArguments(): void
    {
        $this->registerArgument('pageTitle', 'string', 'title tag of the module. Not required by default, as BE modules are shown in a frame', false, '');
        $this->registerArgument('includeCssFiles', 'array', 'List of custom CSS file to be loaded');
        $this->registerArgument('includeJsFiles', 'array', 'List of custom JavaScript file to be loaded');
        $this->registerArgument('addJsInlineLabels', 'array', 'Custom labels to add to JavaScript inline labels');
        $this->registerArgument('includeJavaScriptModules', 'array', 'List of JavaScript modules to be loaded');
        $this->registerArgument('includeRequireJsModules', 'array', 'List of RequireJS modules to be loaded');
        $this->registerArgument('addInlineSettings', 'array', 'Adds Javascript Inline Setting');
    }

    public static function renderStatic(array $arguments, \Closure $renderChildrenClosure, RenderingContextInterface $renderingContext): void
    {
        $pageRenderer = self::getPageRenderer();
        $pageTitle = $arguments['pageTitle'];
        $includeCssFiles = $arguments['includeCssFiles'];
        $includeJsFiles = $arguments['includeJsFiles'];
        $addJsInlineLabels = $arguments['addJsInlineLabels'];
        $includeJavaScriptModules = $arguments['includeJavaScriptModules'];
        $includeRequireJsModules = $arguments['includeRequireJsModules'];
        $addInlineSettings = $arguments['addInlineSettings'];

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
        if (is_array($includeRequireJsModules)) {
            foreach ($includeRequireJsModules as $addRequireJsFile) {
                $pageRenderer->loadRequireJsModule($addRequireJsFile);
            }
        }

        if (is_array($addInlineSettings)) {
            $pageRenderer->addInlineSettingArray(null, $addInlineSettings);
        }

        // Add inline language labels
        if (is_array($addJsInlineLabels) && count($addJsInlineLabels) > 0) {
            /** @var RenderingContext $renderingContext */
            $request = $renderingContext->getRequest();
            if ($request instanceof RequestInterface) {
                // Extbase request resolves extension key and allows overriding labels using TypoScript configuration.
                $extensionKey = $request->getControllerExtensionKey();
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
    }

    protected static function getPageRenderer(): PageRenderer
    {
        return GeneralUtility::makeInstance(PageRenderer::class);
    }

    protected static function getLanguageService(): LanguageService
    {
        return $GLOBALS['LANG'];
    }
}
