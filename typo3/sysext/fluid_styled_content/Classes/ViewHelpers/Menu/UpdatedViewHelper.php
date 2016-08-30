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
 * A view helper which returns recently updated subpages (multiple levels) of the given pages
 *
 * = Example =
 *
 * <code title="Pages with the similar keyword(s) of page uid = 1 and uid = 2">
 * <ce:menu.updated pageUids="{0: 1, 1: 2}" as="pages">
 *   <f:for each="{pages}" as="page">
 *     {page.title}
 *   </f:for>
 * </ce:menu.updated>
 * </code>
 *
 * <output>
 * Recently updated subpage 1
 * Recently updated subpage 2
 * Recently updated subpage 3
 * </output>
 */
class UpdatedViewHelper extends AbstractViewHelper
{
    use MenuViewHelperTrait;

    /**
     * Initialize ViewHelper arguments
     *
     * @return void
     */
    public function initializeArguments()
    {
        $this->registerArgument('as', 'string', 'Name of the template variable that will contain selected pages', true);
        $this->registerArgument('pageUids', 'array', 'Page UIDs of parent pages', false, []);
        $this->registerArgument('sortField', 'string', 'Field to sort pages; possible values: starttime, lastUpdated, tstamp, crdate', false, 'SYS_LASTCHANGED');
        $this->registerArgument('maximumAge', 'string', 'Maximum age of pages to be included; supports mathematical expressions', false, '604800');
        $this->registerArgument('includeNotInMenu', 'boolean', 'Include pages that are marked "hide in menu"?', false, false);
        $this->registerArgument('includeMenuSeparator', 'boolean', 'Include pages of the type "Menu separator"?', false, false);
        $this->registerArgument('excludeNoSearchPages', 'boolean', 'Exclude pages that are NOT marked "include in search"?', false, true);
    }

    /**
     * Render the view helper
     *
     * @return string
     */
    public function render()
    {
        $typoScriptFrontendController = $this->getTypoScriptFrontendController();
        $as = (string)$this->arguments['as'];
        $pageUids = (array)$this->arguments['pageUids'];
        $sortField = $this->arguments['sortField'];
        $maximumAge = $this->arguments['maximumAge'];
        $includeNotInMenu = (bool)$this->arguments['includeNotInMenu'];
        $includeMenuSeparator = (bool)$this->arguments['includeMenuSeparator'];
        $excludeNoSearchPages = (bool)$this->arguments['excludeNoSearchPages'];

        // If no pages have been defined, use the current page
        if (empty($pageUids)) {
            $pageUids = [$typoScriptFrontendController->page['uid']];
        }

        $unfilteredPageTreeUids = [];
        foreach ($pageUids as $pageUid) {
            $unfilteredPageTreeUids = array_merge(
                $unfilteredPageTreeUids,
                explode(
                    ',',
                    $typoScriptFrontendController->cObj->getTreeList($pageUid, 20)
                )
            );
        }
        $pageTreeUids = array_unique($unfilteredPageTreeUids);

        $constraints = $this->getPageConstraints($includeNotInMenu, $includeMenuSeparator);

        if ($excludeNoSearchPages) {
            $constraints .= ' AND no_search = 0';
        }

        if (!in_array($sortField, ['starttime', 'lastUpdated', 'tstamp', 'crdate'])) {
            $sortField = 'SYS_LASTCHANGED';
        }

        $minimumTimeStamp = time() - (int)$typoScriptFrontendController->cObj->calc($maximumAge);
        $constraints .= ' AND ' . $sortField . ' >=' . $minimumTimeStamp;

        $pages = $typoScriptFrontendController->sys_page->getMenuForPages(
            $pageTreeUids,
            '*',
            $sortField . ' DESC',
            $constraints
        );
        return $this->renderChildrenWithVariables([
            $as => $pages
        ]);
    }
}
