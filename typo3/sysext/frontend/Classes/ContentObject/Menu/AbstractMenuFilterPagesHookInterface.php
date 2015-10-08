<?php
namespace TYPO3\CMS\Frontend\ContentObject\Menu;

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
 * interface for classes which hook into AbstractMenuContentObject
 */
interface AbstractMenuFilterPagesHookInterface
{
    /**
     * Checks if a page is OK to include in the final menu item array.
     *
     * @param array $data Array of menu items
     * @param array $banUidArray Array of page uids which are to be excluded
     * @param bool $spacer If set, then the page is a spacer.
     * @param \TYPO3\CMS\Frontend\ContentObject\Menu\AbstractMenuContentObject $obj The menu object
     * @return bool Returns TRUE if the page can be safely included.
     */
    public function processFilter(array &$data, array $banUidArray, $spacer, AbstractMenuContentObject $obj);
}
