<?php

namespace TYPO3\CMS\Core\Tests\Functional\Utility\ExtensionManagementUtility;

use TYPO3\TestingFramework\Core\Functional\FunctionalTestCase;

class ExtensionManagementUtilityTcaOverrideRequireTest extends FunctionalTestCase
{
    protected array $testExtensionsToLoad = [
        'typo3/sysext/core/Tests/Functional/Utility/ExtensionManagementUtility/Fixtures/Extensions/test_tcaoverride_a',
        'typo3/sysext/core/Tests/Functional/Utility/ExtensionManagementUtility/Fixtures/Extensions/test_tcaoverride_b',
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
