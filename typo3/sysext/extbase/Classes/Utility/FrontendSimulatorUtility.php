<?php
namespace TYPO3\CMS\Extbase\Utility;

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
 * Utilities to simulate a frontend in backend context.
 *
 * ONLY USED INTERNALLY, MIGHT CHANGE WITHOUT NOTICE!
 */
class FrontendSimulatorUtility
{
    /**
     * @var mixed
     */
    protected static $tsfeBackup;

    /**
     * Sets the $TSFE->cObjectDepthCounter in Backend mode
     * This somewhat hacky work around is currently needed because the cObjGetSingle() function of \TYPO3\CMS\Frontend\ContentObject\ContentObjectRenderer relies on this setting
     *
     * @param \TYPO3\CMS\Frontend\ContentObject\ContentObjectRenderer|NULL $cObj
     * @return void
     */
    public static function simulateFrontendEnvironment(\TYPO3\CMS\Frontend\ContentObject\ContentObjectRenderer $cObj = null)
    {
        self::$tsfeBackup = isset($GLOBALS['TSFE']) ? $GLOBALS['TSFE'] : null;
        $GLOBALS['TSFE'] = new \stdClass();
        $GLOBALS['TSFE']->cObjectDepthCounter = 100;
        $GLOBALS['TSFE']->cObj = $cObj !== null ? $cObj : \TYPO3\CMS\Core\Utility\GeneralUtility::makeInstance(\TYPO3\CMS\Frontend\ContentObject\ContentObjectRenderer::class);
    }

    /**
     * Resets $GLOBALS['TSFE'] if it was previously changed by simulateFrontendEnvironment()
     *
     * @return void
     * @see simulateFrontendEnvironment()
     */
    public static function resetFrontendEnvironment()
    {
        if (!empty(self::$tsfeBackup)) {
            $GLOBALS['TSFE'] = self::$tsfeBackup;
        }
    }
}
