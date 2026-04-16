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

namespace TYPO3\CMS\Extensionmanager\Tests\Functional\Utility;

use PHPUnit\Framework\Attributes\Test;
use TYPO3\CMS\Extensionmanager\Utility\InstallUtility;
use TYPO3\TestingFramework\Core\Functional\FunctionalTestCase;

final class InstallUtilityTest extends FunctionalTestCase
{
    protected array $coreExtensionsToLoad = [
        'extensionmanager',
    ];

    protected array $testExtensionsToLoad = [
        'typo3/sysext/extensionmanager/Tests/Functional/Fixtures/Extensions/test_install_package_activation',
    ];

    #[Test]
    public function installDumpsClassLoadingInformationForActivatedExtension(): void
    {
        $this->expectException(\RuntimeException::class);
        $this->expectExceptionCode(1776680483);
        $this->expectExceptionMessage('Test afterPackageActivation event');

        $this->get(InstallUtility::class)->install('test_install_package_activation');
    }
}
