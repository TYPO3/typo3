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

namespace TYPO3\CMS\Fluid\ViewHelpers\Be;

use TYPO3\CMS\Core\Page\PageRenderer;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Extbase\Utility\LocalizationUtility;

/**
 * ViewHelper which allows you to create extbase based modules in the style of TYPO3 default modules.
 *
 * Examples
 * ========
 *
 * Simple::
 *
 *    <f:be.container>your module content</f:be.container>
 *
 * "your module content" wrapped with proper head & body tags.
 * Default backend CSS styles and JavaScript will be included.
 *
 * All options::
 *
 *    <f:be.container pageTitle="foo"
 *        includeCssFiles="{0: '{f:uri.resource(path:\'Css/Styles.css\')}'}"
 *        includeJsFiles="{0: '{f:uri.resource(path:\'JavaScript/Library1.js\')}', 1: '{f:uri.resource(path:\'JavaScript/Library2.js\')}'}"
 *        addJsInlineLabels="{0: 'label1', 1: 'label2'}"
 *    >
 *        your module content
 *    </f:be.container>
 *
 * "your module content" wrapped with proper head & body tags.
 * Custom CSS file :file:`EXT:your_extension/Resources/Public/Css/styles.css` and
 * JavaScript files :file:`EXT:your_extension/Resources/Public/JavaScript/Library1.js` and
 * :file:`EXT:your_extension/Resources/Public/JavaScript/Library2.js`
 * will be loaded, plus some inline labels for usage in JS code.
 *
 * @deprecated since v11, will be removed in v12.
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
        $this->registerArgument('includeCssFiles', 'array', 'List of custom CSS file to be loaded');
        $this->registerArgument('includeJsFiles', 'array', 'List of custom JavaScript file to be loaded');
        $this->registerArgument('addJsInlineLabels', 'array', 'Custom labels to add to JavaScript inline labels');
        $this->registerArgument('includeRequireJsModules', 'array', 'List of RequireJS modules to be loaded');
        $this->registerArgument('enableDocHeader', 'bool', 'Add an empty doc header', false);
    }

    /**
     * Render start page with \TYPO3\CMS\Backend\Template\ModuleTemplate and pageTitle
     *
     * @return string
     * @see \TYPO3\CMS\Core\Page\PageRenderer
     */
    public function render()
    {
        trigger_error(__CLASS__ . ' will be removed in TYPO3 v12.', E_USER_DEPRECATED);
        $pageTitle = $this->arguments['pageTitle'];
        $includeCssFiles = $this->arguments['includeCssFiles'];
        $includeJsFiles = $this->arguments['includeJsFiles'];
        $addJsInlineLabels = $this->arguments['addJsInlineLabels'];
        $includeRequireJsModules = $this->arguments['includeRequireJsModules'];

        $moduleTemplate = $this->getModuleTemplate();
        $pageRenderer = GeneralUtility::makeInstance(PageRenderer::class);

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
            $extensionKey = $this->renderingContext->getRequest()->getControllerExtensionKey();
            foreach ($addJsInlineLabels as $key) {
                $label = LocalizationUtility::translate($key, $extensionKey);
                $pageRenderer->addInlineLanguageLabel($key, $label);
            }
        }
        // Render the content and return it
        $output = $this->renderChildren();
        if ($this->arguments['enableDocHeader'] ?? false) {
            $moduleTemplate->getDocHeaderComponent()->enable();
        } else {
            $moduleTemplate->getDocHeaderComponent()->disable();
            $moduleTemplate->getView()->setTemplate('EmptyModule.html');
        }
        $moduleTemplate->setTitle($pageTitle);
        $moduleTemplate->setContent($output);
        return $moduleTemplate->renderContent();
    }
}
