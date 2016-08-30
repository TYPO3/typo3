<?php
namespace TYPO3\CMS\Saltedpasswords\Tests\Unit\Utility;

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
 * Testcase for SaltedPasswordsUtility
 */
class SaltedPasswordsUtilityTest extends \TYPO3\CMS\Core\Tests\UnitTestCase
{
    /**
     * @test
     */
    public function doesReturnExtConfReturnDefaultSettingsIfNoExtensionConfigurationIsFound()
    {
        $this->assertEquals(\TYPO3\CMS\Saltedpasswords\Utility\SaltedPasswordsUtility::returnExtConfDefaults(), \TYPO3\CMS\Saltedpasswords\Utility\SaltedPasswordsUtility::returnExtConf('TEST_MODE'));
    }

    /**
     * @test
     */
    public function doesReturnExtConfReturnMergedSettingsIfExtensionConfigurationIsFound()
    {
        $setting = ['setting' => 1];
        $GLOBALS['TYPO3_CONF_VARS']['EXT']['extConf']['saltedpasswords'] = serialize(['TEST_MODE.' => $setting]);
        $this->assertEquals(array_merge(\TYPO3\CMS\Saltedpasswords\Utility\SaltedPasswordsUtility::returnExtConfDefaults(), $setting), \TYPO3\CMS\Saltedpasswords\Utility\SaltedPasswordsUtility::returnExtConf('TEST_MODE'));
    }
}
