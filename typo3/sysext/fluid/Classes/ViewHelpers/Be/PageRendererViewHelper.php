<?php
namespace TYPO3\CMS\Fluid\ViewHelpers\Be;

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

use TYPO3\CMS\Core\Page\PageRenderer;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Extbase\Utility\LocalizationUtility;
use TYPO3Fluid\Fluid\Core\Rendering\RenderingContextInterface;
use TYPO3Fluid\Fluid\Core\ViewHelper\AbstractViewHelper;
use TYPO3Fluid\Fluid\Core\ViewHelper\Traits\CompileWithRenderStatic;

/**
 * View helper which allows you to create extbase based modules in the style of TYPO3 default modules.
 *
 * = Examples =
 *
 * <code title="All options">
 * <f:be.pageRenderer pageTitle="foo" includeCssFiles="0: '{f:uri.resource(path:\'Css/Styles.css\')}'" includeJsFiles="0: '{f:uri.resource(path:\'JavaScript/Library1.js\')}', 1: '{f:uri.resource(path:\'JavaScript/Library2.js\')}'" addJsInlineLabels="{0: 'label1', 1: 'label2'}" />
 * </code>
 * <output>
 *
 * Custom CSS file EXT:your_extension/Resources/Public/Css/styles.css and
 * JavaScript files EXT:your_extension/Resources/Public/JavaScript/Library1.js and EXT:your_extension/Resources/Public/JavaScript/Library2.js
 * will be loaded, plus some inline labels for usage in JS code.
 * </output>
 */
class PageRendererViewHelper extends AbstractViewHelper
{
    use CompileWithRenderStatic;

    /**
     * Initialize arguments.
     *
     * @throws \TYPO3Fluid\Fluid\Core\ViewHelper\Exception
     */
    public function initializeArguments()
    {
        $this->registerArgument('pageTitle', 'string', 'title tag of the module. Not required by default, as BE modules are shown in a frame', false, '');
        $this->registerArgument('includeCssFiles', 'array', 'List of custom CSS file to be loaded');
        $this->registerArgument('includeJsFiles', 'array', 'List of custom JavaScript file to be loaded');
        $this->registerArgument('addJsInlineLabels', 'array', 'Custom labels to add to JavaScript inline labels');
        $this->registerArgument('includeRequireJsModules', 'array', 'List of RequireJS modules to be loaded');
        $this->registerArgument('addInlineSettings', 'array', 'Adds Javascript Inline Setting');
    }

    /**
     * @param array $arguments
     * @param \Closure $renderChildrenClosure
     * @param RenderingContextInterface $renderingContext
     */
    public static function renderStatic(array $arguments, \Closure $renderChildrenClosure, RenderingContextInterface $renderingContext)
    {
        $pageRenderer = static::getPageRenderer();
        $pageTitle = $arguments['pageTitle'];
        $includeCssFiles = $arguments['includeCssFiles'];
        $includeJsFiles = $arguments['includeJsFiles'];
        $addJsInlineLabels = $arguments['addJsInlineLabels'];
        $includeRequireJsModules = $arguments['includeRequireJsModules'];
        $addInlineSettings = $arguments['addInlineSettings'];

        if ($pageTitle) {
            $pageRenderer->setTitle($pageTitle);
        }

        // Include custom CSS and JS files
        if (is_array($includeCssFiles) && count($includeCssFiles) > 0) {
            foreach ($includeCssFiles as $addCssFile) {
                $pageRenderer->addCssFile($addCssFile);
            }
        }
        if (is_array($includeJsFiles) && count($includeJsFiles) > 0) {
            foreach ($includeJsFiles as $addJsFile) {
                $pageRenderer->addJsFile($addJsFile);
            }
        }
        if (is_array($includeRequireJsModules) && count($includeRequireJsModules) > 0) {
            foreach ($includeRequireJsModules as $addRequireJsFile) {
                $pageRenderer->loadRequireJsModule($addRequireJsFile);
            }
        }

        if (is_array($addInlineSettings) && count($addInlineSettings) > 0) {
            $pageRenderer->addInlineSettingArray(null, $addInlineSettings);
        }

        // Add inline language labels
        if (is_array($addJsInlineLabels) && count($addJsInlineLabels) > 0) {
            $extensionKey = $renderingContext->getControllerContext()->getRequest()->getControllerExtensionKey();
            foreach ($addJsInlineLabels as $key) {
                $label = LocalizationUtility::translate($key, $extensionKey);
                $pageRenderer->addInlineLanguageLabel($key, $label);
            }
        }
    }

    /**
     * @return PageRenderer
     */
    protected static function getPageRenderer()
    {
        return GeneralUtility::makeInstance(PageRenderer::class);
    }
}
