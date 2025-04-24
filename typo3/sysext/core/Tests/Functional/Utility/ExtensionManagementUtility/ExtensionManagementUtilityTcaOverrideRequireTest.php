<?php

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

use PHPUnit\Framework\Attributes\DoesNotPerformAssertions;
use PHPUnit\Framework\Attributes\Test;
use TYPO3\TestingFramework\Core\Functional\FunctionalTestCase;

final class ExtensionManagementUtilityTcaOverrideRequireTest extends FunctionalTestCase
{
    protected array $testExtensionsToLoad = [
        'typo3/sysext/core/Tests/Functional/Fixtures/Extensions/test_tcaoverride_a',
        'typo3/sysext/core/Tests/Functional/Fixtures/Extensions/test_tcaoverride_b',
    ];

    #[Test]
    #[DoesNotPerformAssertions]
    public function extensionManagementUtilityBuildBaseTcaFromSingleFiles(): void
    {
        // This is a dummy test of a general behaviour. If this test fails, this means that
        // variable leaked again, which should be avoided.
    }
}
