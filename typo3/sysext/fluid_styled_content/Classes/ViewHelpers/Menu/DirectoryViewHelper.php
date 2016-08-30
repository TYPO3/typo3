<?php
namespace TYPO3\CMS\FluidStyledContent\ViewHelpers\Menu;

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

use TYPO3\CMS\Fluid\Core\ViewHelper\AbstractViewHelper;

/**
 * A view helper which returns the subpages of the given pages
 *
 * = Example =
 *
 * <code title="Directory of pages with uid = 1 and uid = 2">
 * <ce:menu.directory pageUids="{0: 1, 1: 2}" as="pages">
 *   <f:for each="{pages}" as="page">
 *     {page.title}
 *   </f:for>
 * </ce:menu.directory>
 * </code>
 *
 * <output>
 * Subpage 1 of page with uid = 1
 * Subpage 2 of page with uid = 1
 * Subpage 1 of page with uid = 2
 * </output>
 */
class DirectoryViewHelper extends AbstractViewHelper
{
    use MenuViewHelperTrait;

    /**
     * Initialize ViewHelper arguments
     *
     * @return void
     */
    public function initializeArguments()
    {
        $this->registerArgument('as', 'string', 'Name of template variable which will contain selected pages', true);
        $this->registerArgument('levelAs', 'string', 'Name of template variable which will contain current level', false, null);
        $this->registerArgument('pageUids', 'array', 'Page UIDs of parent pages', false, []);
        $this->registerArgument('entryLevel', 'integer', 'The entry level', false, null);
        $this->registerArgument('maximumLevel', 'integer', 'Maximum level for rendering of nested menus', false, 10);
        $this->registerArgument('includeNotInMenu', 'boolean', 'Include pages that are marked "hide in menu"?', false, false);
        $this->registerArgument('includeMenuSeparator', 'boolean', 'Include pages of the type "Menu separator"?', false, false);
    }

    /**
     * Render the view helper
     *
     * @return string
     */
    public function render()
    {
        $typoScriptFrontendController = $this->getTypoScriptFrontendController();
        $as = $this->arguments['as'];
        $pageUids = (array)$this->arguments['pageUids'];
        $entryLevel = $this->arguments['entryLevel'];
        $levelAs = $this->arguments['levelAs'];
        $maximumLevel = $this->arguments['maximumLevel'];
        $includeNotInMenu = (bool)$this->arguments['includeNotInMenu'];
        $includeMenuSeparator = (bool)$this->arguments['includeMenuSeparator'];

        $pageUids = $this->getPageUids($pageUids, $entryLevel);
        $pages = $typoScriptFrontendController->sys_page->getMenu(
            $pageUids,
            '*',
            'sorting',
            $this->getPageConstraints($includeNotInMenu, $includeMenuSeparator)
        );

        $output = '';

        if (!empty($pages)) {
            if (!$typoScriptFrontendController->register['ceMenuLevel_directory']) {
                $typoScriptFrontendController->register['ceMenuLevel_directory'] = 1;
                $typoScriptFrontendController->register['ceMenuMaximumLevel_directory'] = $maximumLevel;
            } else {
                $typoScriptFrontendController->register['ceMenuLevel_directory']++;
            }

            if ($typoScriptFrontendController->register['ceMenuLevel_directory'] > $typoScriptFrontendController->register['ceMenuMaximumLevel_directory']) {
                return '';
            }

            $variables = [
                $as => $pages
            ];
            if (!empty($levelAs)) {
                $variables[$levelAs] = $typoScriptFrontendController->register['ceMenuLevel_directory'];
            }
            $output = $this->renderChildrenWithVariables($variables);

            $typoScriptFrontendController->register['ceMenuLevel_directory']--;

            if ($typoScriptFrontendController->register['ceMenuLevel_directory'] === 0) {
                unset($typoScriptFrontendController->register['ceMenuLevel_directory']);
                unset($typoScriptFrontendController->register['ceMenuMaximumLevel_directory']);
            }
        }

        return $output;
    }
}
