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

namespace TYPO3\CMS\Core\Tests\Functional\Utility\ExtensionManagementUtility;

use TYPO3\TestingFramework\Core\Functional\FunctionalTestCase;

class ExtensionManagementUtilityTcaRequireTest extends FunctionalTestCase
{
    protected array $testExtensionsToLoad = [
        'typo3/sysext/core/Tests/Functional/Utility/ExtensionManagementUtility/Fixtures/Extensions/test_tca_a',
        'typo3/sysext/core/Tests/Functional/Utility/ExtensionManagementUtility/Fixtures/Extensions/test_tca_b',
    ];

    /**
     * @test
     * Regression test for https://forge.typo3.org/issues/96929
     */
    public function extensionManagementUtilityBuildBaseTcaFromSingleFiles(): void
    {
        // This is a dummy assertion to test a general behaviour. If this test fails, this means that
        // variable get leaked again, which should be avoided.
        self::assertTrue(true);
    }
}
