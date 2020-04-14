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

namespace TYPO3\CMS\Frontend\Page;

/**
 * Finds the proper layout for a page, using the database fields "backend_layout"
 * and "backend_layout_next_level".
 *
 * The most crucial part is that "backend_layout" is only applied for the CURRENT level,
 * whereas backend_layout_next_level.
 *
 * Used in TypoScript as "getData: pagelayout".
 *
 * @internal as this might get moved to EXT:core if usages in TYPO3 Backend are helpful as well.
 */
class PageLayoutResolver
{
    /**
     * Check if the current page has a value in the DB field "backend_layout"
     * if empty, check the root line for "backend_layout_next_level"
     * Same as TypoScript:
     *   field = backend_layout
     *   ifEmpty.data = levelfield:-2, backend_layout_next_level, slide
     *   ifEmpty.ifEmpty = default
     *
     * @param array $page
     * @param array $rootLine
     * @return string
     */
    public function getLayoutForPage(array $page, array $rootLine): string
    {
        $selectedLayout = $page['backend_layout'] ?? '';

        // If it is set to "none" - don't use any
        if ($selectedLayout === '-1') {
            return 'none';
        }

        if ($selectedLayout === '' || $selectedLayout === '0') {
            // If it not set check the root-line for a layout on next level and use this
            // Remove first element, which is the current page
            // See also \TYPO3\CMS\Backend\View\BackendLayoutView::getSelectedCombinedIdentifier()
            array_shift($rootLine);
            foreach ($rootLine as $rootLinePage) {
                $selectedLayout = (string)($rootLinePage['backend_layout_next_level'] ?? '');
                // If layout for "next level" is set to "none" - don't use any and stop searching
                if ($selectedLayout === '-1') {
                    $selectedLayout = 'none';
                    break;
                }
                if ($selectedLayout !== '' && $selectedLayout !== '0') {
                    // Stop searching if a layout for "next level" is set
                    break;
                }
            }
        }
        if ($selectedLayout === '0' || $selectedLayout === '') {
            $selectedLayout = 'default';
        }
        return $selectedLayout;
    }
}
