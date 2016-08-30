<?php
namespace TYPO3\CMS\Version\ClickMenu;

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

use TYPO3\CMS\Backend\ClickMenu\ClickMenu;
use TYPO3\CMS\Core\Imaging\Icon;
use TYPO3\CMS\Core\Imaging\IconFactory;
use TYPO3\CMS\Core\Utility\GeneralUtility;

/**
 * "Versioning" item added to click menu of elements.
 */
class VersionClickMenu
{
    /**
     * @var IconFactory
     */
    protected $iconFactory;

    /**
     * Initialize
     */
    public function __construct()
    {
        $this->iconFactory = GeneralUtility::makeInstance(IconFactory::class);
    }

    /**
     * Main function, adding the item to input menuItems array
     *
     * @param ClickMenu $backRef References to parent clickmenu objects.
     * @param array $menuItems Array of existing menu items accumulated. New element added to this.
     * @param string $table Table name of the element
     * @param int $uid Record UID of the element
     * @return array Modified menuItems array
     */
    public function main(&$backRef, $menuItems, $table, $uid)
    {
        $localItems = [];
        if (!$backRef->cmLevel && $uid > 0 && $GLOBALS['BE_USER']->check('modules', 'web_txversionM1')) {
            // Returns directly, because the clicked item was not from the pages table
            if (in_array('versioning', $backRef->disabledItems) || !$GLOBALS['TCA'][$table] || !$GLOBALS['TCA'][$table]['ctrl']['versioningWS']) {
                return $menuItems;
            }
            // Adds the regular item
            $LL = $this->includeLL();
            // "Versioning" element added:
            $url = \TYPO3\CMS\Backend\Utility\BackendUtility::getModuleUrl('web_txversionM1', ['table' => $table, 'uid' => $uid]);
            $localItems[] = $backRef->linkItem(
                $GLOBALS['LANG']->getLLL('title', $LL),
                $backRef->excludeIcon($this->iconFactory->getIcon('actions-version-page-open', Icon::SIZE_SMALL)->render()),
                $backRef->urlRefForCM($url),
                true
            );
            // Find position of "delete" element:
            $c = 0;
            foreach ($menuItems as $k => $value) {
                $c++;
                if ($k === 'delete') {
                    break;
                }
            }
            // .. subtract two (delete item + divider line)
            $c -= 2;
            // ... and insert the items just before the delete element.
            array_splice($menuItems, $c, 0, $localItems);
        }
        return $menuItems;
    }

    /**
     * Includes the [extDir]/locallang.xlf and returns the translations found in that file.
     *
     * @return array Local lang array
     */
    public function includeLL()
    {
        return $GLOBALS['LANG']->includeLLFile('EXT:version/Resources/Private/Language/locallang.xlf', false);
    }
}
