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

namespace TYPO3\CMS\Core\Tests\Unit\Configuration;

use TYPO3\CMS\Core\Configuration\Features;
use TYPO3\TestingFramework\Core\Unit\UnitTestCase;

/**
 * Test case
 */
class FeaturesTest extends UnitTestCase
{
    /**
     * @test
     */
    public function nonExistingFeatureReturnsFalse()
    {
        $features = new Features();
        self::assertFalse($features->isFeatureEnabled('nonExistingFeature'));
    }

    /**
     * @test
     */
    public function checkIfExistingDisabledFeatureIsDisabled()
    {
        $GLOBALS['TYPO3_CONF_VARS']['SYS']['features']['nativeFunctionality'] = false;
        $features = new Features();
        self::assertFalse($features->isFeatureEnabled('nativeFunctionality'));
    }

    /**
     * @test
     */
    public function checkIfExistingEnabledFeatureIsEnabled()
    {
        $GLOBALS['TYPO3_CONF_VARS']['SYS']['features']['nativeFunctionality'] = true;
        $features = new Features();
        self::assertTrue($features->isFeatureEnabled('nativeFunctionality'));
    }

    /**
     * @test
     */
    public function checkIfExistingEnabledFeatureIsDisabled()
    {
        $GLOBALS['TYPO3_CONF_VARS']['SYS']['features']['nativeFunctionality'] = false;
        $features = new Features();
        self::assertFalse($features->isFeatureEnabled('nativeFunctionality'));
    }
}
