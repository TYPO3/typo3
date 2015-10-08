<?php
namespace TYPO3\CMS\Frontend\Tests\Unit\ContentObject\Fixtures;

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
 * Fixture for TYPO3\CMS\Core\Utility\GeneralUtility
 */
class GeneralUtilityFixture extends \TYPO3\CMS\Core\Utility\GeneralUtility
{
    /**
     * @param \TYPO3\CMS\Core\Core\ApplicationContext $applicationContext
     * @return void
     */
    public static function setApplicationContext($applicationContext)
    {
        static::$applicationContext = $applicationContext;
    }
}
