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

namespace TYPO3\CMS\Extensionmanager\Tests\Unit\Utility;

use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Test;
use TYPO3\CMS\Core\EventDispatcher\NoopEventDispatcher;
use TYPO3\CMS\Core\Package\Package;
use TYPO3\CMS\Core\Package\PackageManager;
use TYPO3\CMS\Extensionmanager\Domain\Repository\ExtensionRepository;
use TYPO3\CMS\Extensionmanager\Utility\EmConfUtility;
use TYPO3\CMS\Extensionmanager\Utility\ListUtility;
use TYPO3\TestingFramework\Core\Unit\UnitTestCase;

final class ListUtilityTest extends UnitTestCase
{
    private ListUtility $subject;

    protected function setUp(): void
    {
        parent::setUp();
        $this->subject = new ListUtility();
        $this->subject->injectEventDispatcher(new NoopEventDispatcher());
        $packageManagerMock = self::createStub(PackageManager::class);
        $packageManagerMock
                ->method('getActivePackages')
                ->willReturn([
                    'lang' => self::createStub(Package::class),
                    'news' => self::createStub(Package::class),
                    'felogin' => self::createStub(Package::class),
                ]);
        $this->subject->injectPackageManager($packageManagerMock);
    }

    public static function getAvailableAndInstalledExtensionsDataProvider(): array
    {
        return [
            'same extension lists' => [
                [
                    'lang' => [],
                    'news' => [],
                    'felogin' => [],
                ],
                [
                    'lang' => ['installed' => true],
                    'news' => ['installed' => true],
                    'felogin' => ['installed' => true],
                ],
            ],
            'different extension lists' => [
                [
                    'lang' => [],
                    'news' => [],
                    'felogin' => [],
                ],
                [
                    'lang' => ['installed' => true],
                    'news' => ['installed' => true],
                    'felogin' => ['installed' => true],
                ],
            ],
            'different extension lists - set2' => [
                [
                    'lang' => [],
                    'news' => [],
                    'felogin' => [],
                    'em' => [],
                ],
                [
                    'lang' => ['installed' => true],
                    'news' => ['installed' => true],
                    'felogin' => ['installed' => true],
                    'em' => [],
                ],
            ],
            'different extension lists - set3' => [
                [
                    'lang' => [],
                    'fluid' => [],
                    'news' => [],
                    'felogin' => [],
                    'em' => [],
                ],
                [
                    'lang' => ['installed' => true],
                    'fluid' => [],
                    'news' => ['installed' => true],
                    'felogin' => ['installed' => true],
                    'em' => [],
                ],
            ],
        ];
    }

    #[DataProvider('getAvailableAndInstalledExtensionsDataProvider')]
    #[Test]
    public function getAvailableAndInstalledExtensionsTest(array $availableExtensions, array $expectedResult): void
    {
        self::assertEquals($expectedResult, $this->subject->getAvailableAndInstalledExtensions($availableExtensions));
    }

    public static function enrichExtensionsWithEmConfInformationDataProvider(): array
    {
        return [
            'simple key value array emconf' => [
                [
                    'lang' => ['property1' => 'oldvalue'],
                    'news' => [],
                    'felogin' => [],
                ],
                [
                    'property1' => 'property value1',
                ],
                [
                    'lang' => ['property1' => 'oldvalue', 'state' => 'stable'],
                    'news' => ['property1' => 'property value1', 'state' => 'stable'],
                    'felogin' => ['property1' => 'property value1', 'state' => 'stable'],
                ],
            ],
        ];
    }

    #[DataProvider('enrichExtensionsWithEmConfInformationDataProvider')]
    #[Test]
    public function enrichExtensionsWithEmConfInformation(array $extensions, array $emConf, array $expectedResult): void
    {
        $this->subject->injectExtensionRepository(self::createStub(ExtensionRepository::class));
        $emConfUtilityStub = self::createStub(EmConfUtility::class);
        $emConfUtilityStub->method('includeEmConf')->willReturn($emConf);
        $this->subject->injectEmConfUtility($emConfUtilityStub);
        self::assertEquals($expectedResult, $this->subject->enrichExtensionsWithEmConfAndTerInformation($extensions));
    }
}
