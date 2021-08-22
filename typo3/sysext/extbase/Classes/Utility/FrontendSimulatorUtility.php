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

namespace TYPO3\CMS\Extbase\Utility;

use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Frontend\ContentObject\ContentObjectRenderer;
use TYPO3\CMS\Frontend\Controller\TypoScriptFrontendController;

/**
 * Utilities to simulate a frontend in backend context.
 *
 * @internal ONLY USED INTERNALLY, MIGHT CHANGE WITHOUT NOTICE!
 */
class FrontendSimulatorUtility
{
    /**
     * @var TypoScriptFrontendController|null
     */
    protected static $tsfeBackup;

    /**
     * \TYPO3\CMS\Frontend\ContentObject\ContentObjectRenderer->cObjGetSingle() relies on $GLOBALS['TSFE']
     *
     * @param ContentObjectRenderer|null $cObj
     */
    public static function simulateFrontendEnvironment(ContentObjectRenderer $cObj = null): void
    {
        self::$tsfeBackup = $GLOBALS['TSFE'] ?? null;
        $GLOBALS['TSFE'] = new \stdClass();
        $GLOBALS['TSFE']->cObj = $cObj ?? GeneralUtility::makeInstance(ContentObjectRenderer::class);
    }

    /**
     * Resets $GLOBALS['TSFE'] if it was previously changed by simulateFrontendEnvironment()
     *
     * @see simulateFrontendEnvironment()
     */
    public static function resetFrontendEnvironment(): void
    {
        if (self::$tsfeBackup !== null) {
            $GLOBALS['TSFE'] = self::$tsfeBackup;
        }
    }
}
