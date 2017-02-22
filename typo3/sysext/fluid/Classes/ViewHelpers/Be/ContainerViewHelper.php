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

/**
 * View helper which allows you to create extbase based modules in the style of TYPO3 default modules.
 *
 * = Examples =
 *
 * <code title="Simple">
 * <f:be.container>your module content</f:be.container>
 * </code>
 * <output>
 * "your module content" wrapped with proper head & body tags.
 * Default backend CSS styles and JavaScript will be included
 * </output>
 *
 * <code title="All options">
 * <f:be.container pageTitle="foo" enableClickMenu="false" loadExtJs="true" loadExtJsTheme="false" enableExtJsDebug="true" loadJQuery="true" includeCssFiles="{0: '{f:uri.resource(path:\'Css/Styles.css\')}'}" includeJsFiles="{0: '{f:uri.resource(path:\'JavaScript/Library1.js\')}', 1: '{f:uri.resource(path:\'JavaScript/Library2.js\')}'}" addJsInlineLabels="{0: 'label1', 1: 'label2'}">your module content</f:be.container>
 * </code>
 * <output>
 * "your module content" wrapped with proper head & body tags.
 * Custom CSS file EXT:your_extension/Resources/Public/Css/styles.css and
 * JavaScript files EXT:your_extension/Resources/Public/JavaScript/Library1.js and EXT:your_extension/Resources/Public/JavaScript/Library2.js
 * will be loaded, plus ExtJS and jQuery and some inline labels for usage in JS code.
 * </output>
 */
class ContainerViewHelper extends AbstractBackendViewHelper
{
    /**
     * @var bool
     */
    protected $escapeOutput = false;

    /**
     * Initialize arguments.
     *
     * @throws \TYPO3Fluid\Fluid\Core\ViewHelper\Exception
     */
    public function initializeArguments()
    {
        parent::initializeArguments();
        $this->registerArgument('pageTitle', 'string', 'Title tag of the module. Not required by default, as BE modules are shown in a frame', false, '');
        $this->registerArgument('enableClickMenu', 'bool', 'If TRUE, loads clickmenu.js required by BE context menus. Defaults to TRUE. This option will be removed in TYPO3 v9', false, true);
        $this->registerArgument('loadExtJs', 'bool', 'Specifies whether to load ExtJS library. Defaults to FALSE. This option will be removed in TYPO3 v9', false, false);
        $this->registerArgument('loadExtJsTheme', 'bool', 'Whether to load ExtJS "grey" theme. Defaults to FALSE. This option will be removed in TYPO3 v9', false, true);
        $this->registerArgument('enableExtJsDebug', 'bool', 'If TRUE, debug version of ExtJS is loaded. Use this for development only. This option will be removed in TYPO3 v9', false, false);
        $this->registerArgument('loadJQuery', 'bool', 'Whether to load jQuery library. Defaults to FALSE. This option will be removed in TYPO3 v9', false, false);
        $this->registerArgument('includeCssFiles', 'array', 'List of custom CSS file to be loaded');
        $this->registerArgument('includeJsFiles', 'array', 'List of custom JavaScript file to be loaded');
        $this->registerArgument('addJsInlineLabels', 'array', 'Custom labels to add to JavaScript inline labels');
        $this->registerArgument('includeRequireJsModules', 'array', 'List of RequireJS modules to be loaded');
        $this->registerArgument('jQueryNamespace', 'string', 'Store the jQuery object in a specific namespace. This option will be removed in TYPO3 v9');
    }

    /**
     * Render start page with \TYPO3\CMS\Backend\Template\DocumentTemplate and pageTitle
     *
     * @return string
     * @see \TYPO3\CMS\Backend\Template\DocumentTemplate
     * @see \TYPO3\CMS\Core\Page\PageRenderer
     */
    public function render()
    {
        $pageTitle = $this->arguments['pageTitle'];
        $enableClickMenu = $this->arguments['enableClickMenu'];
        $loadExtJs = $this->arguments['loadExtJs'];
        $loadExtJsTheme = $this->arguments['loadExtJsTheme'];
        $enableExtJsDebug = $this->arguments['enableExtJsDebug'];
        $loadJQuery = $this->arguments['loadJQuery'];
        $includeCssFiles = $this->arguments['includeCssFiles'];
        $includeJsFiles = $this->arguments['includeJsFiles'];
        $addJsInlineLabels = $this->arguments['addJsInlineLabels'];
        $includeRequireJsModules = $this->arguments['includeRequireJsModules'];
        $jQueryNamespace = $this->arguments['jQueryNamespace'];

        $pageRenderer = $this->getPageRenderer();
        $doc = $this->getDocInstance();
        $doc->JScode .= GeneralUtility::wrapJS($doc->redirectUrls());

        // Load various standard libraries
        if ($enableClickMenu) {
            GeneralUtility::logDeprecatedViewHelperAttribute(
                'enableClickMenu',
                $this->renderingContext,
                'Setting "enableClickMenu" in Container ViewHelper is deprecated, the option will be removed in TYPO3 v9'
            );
            $pageRenderer->loadJquery();
            $pageRenderer->loadRequireJsModule('TYPO3/CMS/Backend/ContextMenu');
        }
        if ($loadExtJs) {
            GeneralUtility::logDeprecatedViewHelperAttribute(
                'loadExtJs',
                $this->renderingContext,
                'Setting "loadExtJs" and "loadExtJsTheme" in Container ViewHelper is deprecated, the option will be removed in TYPO3 v9'
            );
            $pageRenderer->loadExtJS(true, $loadExtJsTheme);
            if ($enableExtJsDebug) {
                GeneralUtility::logDeprecatedViewHelperAttribute(
                    'enableExtJsDebug',
                    $this->renderingContext,
                    'Setting "enableExtJsDebug" in Container ViewHelper is deprecated, the option will be removed in TYPO3 v9'
                );
                $pageRenderer->enableExtJsDebug();
            }
        }
        if ($loadJQuery) {
            GeneralUtility::logDeprecatedViewHelperAttribute(
                'loadjQuery',
                $this->renderingContext,
                'Setting "loadjQuery" and "jQueryNamespace" in Container ViewHelper are deprecated, the option will be removed in TYPO3 v9'
            );
            $jQueryNamespace = $jQueryNamespace ?: PageRenderer::JQUERY_NAMESPACE_DEFAULT;
            $pageRenderer->loadJquery(null, null, $jQueryNamespace);
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
        // Add inline language labels
        if (is_array($addJsInlineLabels) && count($addJsInlineLabels) > 0) {
            $extensionKey = $this->controllerContext->getRequest()->getControllerExtensionKey();
            foreach ($addJsInlineLabels as $key) {
                $label = LocalizationUtility::translate($key, $extensionKey);
                $pageRenderer->addInlineLanguageLabel($key, $label);
            }
        }
        // Render the content and return it
        $output = $this->renderChildren();
        $output = $doc->startPage($pageTitle) . $output;
        $output .= $doc->endPage();
        return $output;
    }
}
