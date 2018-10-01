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
 * @internal this is a concrete TYPO3 hook implementation and solely used for EXT:frontend and not part of TYPO3's Core API.
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
        foreach ($GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS']['tslib/hooks/class.tx_cms_mediaitems.php']['customMediaRenderTypes'] ?? [] as $className) {
            $hookObj = GeneralUtility::makeInstance($className);
            $hookObj->customMediaRenderTypes($params, $conf);
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
        foreach ($GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS']['tslib/hooks/class.tx_cms_mediaitems.php']['customMediaParams'] ?? [] as $className) {
            $hookObj = GeneralUtility::makeInstance($className);
            $hookObj->customMediaParams($params, $conf);
        }
    }
}
