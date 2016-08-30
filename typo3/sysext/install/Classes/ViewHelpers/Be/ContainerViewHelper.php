<?php
namespace TYPO3\CMS\Install\ViewHelpers\Be;

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

/**
 * View helper which allows you to create extbase based modules in the
 * style of TYPO3 default modules.
 * Note: This feature is experimental!
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
 * <f:be.container pageTitle="foo">your module content</f:be.container>
 * </code>
 * <output>
 * "your module content" wrapped with proper head & body tags.
 * Custom CSS file EXT:your_extension/Resources/Public/styles/backend.css and JavaScript file EXT:your_extension/Resources/Public/scripts/main.js will be loaded
 * </output>
 *
 * @internal
 */
class ContainerViewHelper extends \TYPO3\CMS\Fluid\ViewHelpers\Be\AbstractBackendViewHelper
{
    /**
     * Render start page with \TYPO3\CMS\Backend\Template\DocumentTemplate and pageTitle
     *
     * @param string $pageTitle title tag of the module. Not required by default, as BE modules are shown in a frame
     * @param array $addCssFiles Custom CSS files to be loaded
     * @param array $addJsFiles Custom JavaScript files to be loaded
     *
     * @return string
     * @see \TYPO3\CMS\Backend\Template\DocumentTemplate
     * @see \TYPO3\CMS\Core\Page\PageRenderer
     */
    public function render($pageTitle = '', $addCssFiles = [], $addJsFiles = [])
    {
        $doc = $this->getDocInstance();
        $pageRenderer = $this->getPageRenderer();

        if (is_array($addCssFiles) && !empty($addCssFiles)) {
            foreach ($addCssFiles as $addCssFile) {
                $pageRenderer->addCssFile($addCssFile);
            }
        }
        if (is_array($addJsFiles) && !empty($addJsFiles)) {
            foreach ($addJsFiles as $addJsFile) {
                $pageRenderer->addJsFile($addJsFile);
            }
        }
        $output = $this->renderChildren();
        $output = $doc->startPage($pageTitle) . $output;
        $output .= $doc->endPage();
        return $output;
    }
}
