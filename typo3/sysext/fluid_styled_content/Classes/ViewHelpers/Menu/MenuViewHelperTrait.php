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

use TYPO3\CMS\Frontend\Controller\TypoScriptFrontendController;
use TYPO3\CMS\Frontend\Page\PageRepository;

/**
 * Trait for Menu-ViewHelpers that require support functions for
 * working with menus that require page selection constraints.
 */
trait MenuViewHelperTrait
{
    /**
     * Get the constraints for the page based on doktype and field "nav_hide"
     *
     * By default the following doktypes are always ignored:
     * - 6: Backend User Section
     * - > 200: Folder (254)
     *          Recycler (255)
     *
     * Optional are:
     * - 199: Menu separator
     * - nav_hide: Not in menu
     *
     * @param bool $includeNotInMenu Should pages which are hidden for menu's be included
     * @param bool $includeMenuSeparator Should pages of type "Menu separator" be included
     * @return string
     */
    protected function getPageConstraints($includeNotInMenu = false, $includeMenuSeparator = false)
    {
        $constraints = [];

        $constraints[] = 'doktype NOT IN (' . PageRepository::DOKTYPE_BE_USER_SECTION . ',' . PageRepository::DOKTYPE_RECYCLER . ',' . PageRepository::DOKTYPE_SYSFOLDER . ')';

        if (!$includeNotInMenu) {
            $constraints[] = 'nav_hide = 0';
        }

        if (!$includeMenuSeparator) {
            $constraints[] = 'doktype != ' . PageRepository::DOKTYPE_SPACER;
        }

        return 'AND ' . implode(' AND ', $constraints);
    }

    /**
     * Get a filtered list of page UIDs according to initial list
     * of UIDs and entryLevel parameter.
     *
     * @param array $pageUids
     * @param int|NULL $entryLevel
     * @return array
     */
    protected function getPageUids(array $pageUids, $entryLevel = 0)
    {
        $typoScriptFrontendController = $this->getTypoScriptFrontendController();

        // Remove empty entries from array
        $pageUids = array_filter($pageUids);

        // If no pages have been defined, use the current page
        if (empty($pageUids)) {
            if ($entryLevel !== null) {
                if ($entryLevel < 0) {
                    $entryLevel = count($typoScriptFrontendController->tmpl->rootLine) - 1 + $entryLevel;
                }
                $pageUids = [$typoScriptFrontendController->tmpl->rootLine[$entryLevel]['uid']];
            } else {
                $pageUids = [$typoScriptFrontendController->id];
            }
        }

        return $pageUids;
    }

    /**
     * @param array $variables
     * @return mixed
     */
    protected function renderChildrenWithVariables(array $variables)
    {
        foreach ($variables as $name => $value) {
            $this->templateVariableContainer->add($name, $value);
        }

        $output = $this->renderChildren();

        foreach ($variables as $name => $_) {
            $this->templateVariableContainer->remove($name);
        }

        return $output;
    }

    /**
     * @return TypoScriptFrontendController
     */
    protected function getTypoScriptFrontendController()
    {
        return $GLOBALS['TSFE'];
    }
}
