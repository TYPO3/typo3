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
namespace TYPO3\CMS\Filelist\ViewHelpers\Be;

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
 * <f:be.container pageTitle="foo" enableClickMenu="false" loadExtJs="true" loadExtJsTheme="false" extJsAdapter="jQuery" enableExtJsDebug="true" loadJQuery="true" includeCssFiles="{0: '{f:uri.resource(path:\'Css/Styles.css\')}'}" includeJsFiles="{0: '{f:uri.resource(path:\'JavaScript/Library1.js\')}', 1: '{f:uri.resource(path:\'JavaScript/Library2.js\')}'}" addJsInlineLabels="{0: 'label1', 1: 'label2'}">your module content</f:be.container>
 * </code>
 * <output>
 * "your module content" wrapped with proper head & body tags.
 * Custom CSS file EXT:your_extension/Resources/Public/Css/styles.css and
 * JavaScript files EXT:your_extension/Resources/Public/JavaScript/Library1.js and EXT:your_extension/Resources/Public/JavaScript/Library2.js
 * will be loaded, plus ExtJS and jQuery and some inline labels for usage in JS code.
 * </output>
 */
class ContainerViewHelper extends \TYPO3\CMS\Fluid\ViewHelpers\Be\ContainerViewHelper
{
    /**
     * Render start page with \TYPO3\CMS\Backend\Template\DocumentTemplate and pageTitle
     *
     * @param string $pageTitle title tag of the module. Not required by default, as BE modules are shown in a frame
     * @param bool $enableClickMenu If TRUE, loads clickmenu.js required by BE context menus. Defaults to TRUE
     * @param bool $loadExtJs specifies whether to load ExtJS library. Defaults to FALSE
     * @param bool $loadExtJsTheme whether to load ExtJS "grey" theme. Defaults to FALSE
     * @param bool $enableExtJsDebug if TRUE, debug version of ExtJS is loaded. Use this for development only
     * @param bool $loadJQuery whether to load jQuery library. Defaults to FALSE
     * @param array $includeCssFiles List of custom CSS file to be loaded
     * @param array $includeJsFiles List of custom JavaScript file to be loaded
     * @param array $addJsInlineLabels Custom labels to add to JavaScript inline labels
     * @param array $includeRequireJsModules List of RequireJS modules to be loaded
     * @param array $addJsInlineLabelFiles List of files containing custom labels to add to JavaScript inline labels
     * @param string $addJsInline Custom inline JavaScript
     * @return string
     * @see \TYPO3\CMS\Backend\Template\DocumentTemplate
     * @see \TYPO3\CMS\Core\Page\PageRenderer
     */
    public function render($pageTitle = '', $enableClickMenu = true, $loadExtJs = false, $loadExtJsTheme = true, $enableExtJsDebug = false, $loadJQuery = false, $includeCssFiles = null, $includeJsFiles = null, $addJsInlineLabels = null, $includeRequireJsModules = null, $addJsInlineLabelFiles = null, $addJsInline = null)
    {
        if (is_array($addJsInlineLabelFiles)) {
            foreach ($addJsInlineLabelFiles as $addJsInlineLabelFile) {
                $this->getPageRenderer()->addInlineLanguageLabelFile($addJsInlineLabelFile['file'], $addJsInlineLabelFile['prefix']);
            }
        }

        $content = parent::render($pageTitle, $enableClickMenu, $loadExtJs, $loadExtJsTheme, $enableExtJsDebug, $loadJQuery, $includeCssFiles, $includeJsFiles, $addJsInlineLabels, $includeRequireJsModules);

        $doc = $this->getDocInstance();
        $doc->JScode .= $doc->wrapScriptTags($addJsInline);
        return $doc->insertStylesAndJS($content);
    }
}
