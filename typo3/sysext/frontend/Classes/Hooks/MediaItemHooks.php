<?php
namespace TYPO3\CMS\Frontend\Hooks;

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
 * Adds extra fields into 'media' flexform
 */
class MediaItemHooks implements \TYPO3\CMS\Core\SingletonInterface
{
    /**
     * Load extra render types if they exist
     *
     * @param array $params Existing types by reference
     * @param array $conf Config array
     */
    public function customMediaRenderTypes(&$params, $conf)
    {
        if (is_array($GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS']['tslib/hooks/class.tx_cms_mediaitems.php']['customMediaRenderTypes'])) {
            foreach ($GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS']['tslib/hooks/class.tx_cms_mediaitems.php']['customMediaRenderTypes'] as $classRef) {
                $hookObj = GeneralUtility::getUserObj($classRef);
                $hookObj->customMediaRenderTypes($params, $conf);
            }
        }
    }

    /**
     * Load extra predefined media params if they exist
     *
     * @param array $params Existing types by reference
     * @param array $conf Config array
     */
    public function customMediaParams(&$params, $conf)
    {
        if (is_array($GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS']['tslib/hooks/class.tx_cms_mediaitems.php']['customMediaParams'])) {
            foreach ($GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS']['tslib/hooks/class.tx_cms_mediaitems.php']['customMediaParams'] as $classRef) {
                $hookObj = GeneralUtility::getUserObj($classRef);
                $hookObj->customMediaParams($params, $conf);
            }
        }
    }
}
