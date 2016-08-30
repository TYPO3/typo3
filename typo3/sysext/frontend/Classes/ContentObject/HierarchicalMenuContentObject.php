<?php
namespace TYPO3\CMS\Frontend\ContentObject;

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

use TYPO3\CMS\Core\Utility\GeneralUtility;

/**
 * Contains HMENU class object.
 */
class HierarchicalMenuContentObject extends AbstractContentObject
{
    /**
     * Rendering the cObject, HMENU
     *
     * @param array $conf Array of TypoScript properties
     * @return string Output
     */
    public function render($conf = [])
    {
        if (!empty($conf['if.']) && !$this->cObj->checkIf($conf['if.'])) {
            return '';
        }

        $theValue = '';
        $menuType = $conf[1];
        try {
            /** @var $menuObjectFactory Menu\MenuContentObjectFactory */
            $menuObjectFactory = GeneralUtility::makeInstance(Menu\MenuContentObjectFactory::class);
            $menu = $menuObjectFactory->getMenuObjectByType($menuType);
            $GLOBALS['TSFE']->register['count_HMENU']++;
            $GLOBALS['TSFE']->register['count_HMENU_MENUOBJ'] = 0;
            $GLOBALS['TSFE']->register['count_MENUOBJ'] = 0;
            $GLOBALS['TSFE']->applicationData['GMENU_LAYERS']['WMid'] = [];
            $GLOBALS['TSFE']->applicationData['GMENU_LAYERS']['WMparentId'] = [];
            $menu->parent_cObj = $this->cObj;
            $menu->start($GLOBALS['TSFE']->tmpl, $GLOBALS['TSFE']->sys_page, '', $conf, 1);
            $menu->makeMenu();
            $theValue .= $menu->writeMenu();
        } catch (Menu\Exception\NoSuchMenuTypeException $e) {
        }
        $wrap = isset($conf['wrap.']) ? $this->cObj->stdWrap($conf['wrap'], $conf['wrap.']) : $conf['wrap'];
        if ($wrap) {
            $theValue = $this->cObj->wrap($theValue, $wrap);
        }
        if (isset($conf['stdWrap.'])) {
            $theValue = $this->cObj->stdWrap($theValue, $conf['stdWrap.']);
        }
        return $theValue;
    }
}
