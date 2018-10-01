<?php
namespace TYPO3\CMS\Felogin\Hooks;

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
 * Hook to display verbose information about the felogin plugin
 * @internal this is a TYPO3 hook implementation and solely used for EXT:felogin and not part of TYPO3's Core API.
 */
class CmsLayout implements \TYPO3\CMS\Backend\View\PageLayoutViewDrawItemHookInterface
{
    /**
     * Preprocesses the preview rendering of a content element.
     *
     * @param \TYPO3\CMS\Backend\View\PageLayoutView $parentObject Calling parent object
     * @param bool $drawItem Whether to draw the item using the default functionalities
     * @param string $headerContent Header content
     * @param string $itemContent Item content
     * @param array $row Record row of tt_content
     */
    public function preProcess(\TYPO3\CMS\Backend\View\PageLayoutView &$parentObject, &$drawItem, &$headerContent, &$itemContent, array &$row)
    {
        if ($row['CType'] === 'login') {
            $drawItem = false;
            $itemContent .= $parentObject->linkEditContent('<strong>' . htmlspecialchars($GLOBALS['LANG']->sL('LLL:EXT:backend/Resources/Private/Language/locallang_db_new_content_el.xlf:forms_login_title')) . '</strong>', $row);
        }
    }
}
