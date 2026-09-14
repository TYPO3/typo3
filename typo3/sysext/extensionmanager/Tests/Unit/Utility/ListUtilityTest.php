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
use TYPO3\CMS\Core\Core\Environment;
use TYPO3\CMS\Core\EventDispatcher\NoopEventDispatcher;
use TYPO3\CMS\Core\Package\MetaData;
use TYPO3\CMS\Core\Package\Package;
use TYPO3\CMS\Core\Package\PackageManager;
use TYPO3\CMS\Core\Package\Resource\ResourceCollectionInterface;
use TYPO3\CMS\Core\SystemResource\Publishing\SystemResourcePublisherInterface;
use TYPO3\CMS\Core\SystemResource\SystemResourceFactory;
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
        $packageManagerMock = $this->getMockBuilder(PackageManager::class)
            ->disableOriginalConstructor()
            ->getMock();
        $packageManagerMock
                ->method('getActivePackages')
                ->willReturn([
                    'lang' => $this->getMockBuilder(Package::class)->disableOriginalConstructor()->getMock(),
                    'news' => $this->getMockBuilder(Package::class)->disableOriginalConstructor()->getMock(),
                    'felogin' => $this->getMockBuilder(Package::class)->disableOriginalConstructor()->getMock(),
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

    /**
     * The list template renders the description as the title attribute of the
     * extension name. It has to come from the package metadata, which is fed
     * from composer.json; ext_emconf.php is not evaluated anymore.
     */
    #[Test]
    public function getAvailableExtensionsExposesTitleAndDescriptionFromThePackageMetaData(): void
    {
        $metaData = new MetaData('my_ext');
        $metaData->setPackageType('typo3-cms-extension');
        $metaData->setVersion('1.2.3');
        $metaData->setTitle('My Extension');
        $metaData->setDescription('Does things');
        $resources = self::createStub(ResourceCollectionInterface::class);
        $resources->method('getPackageIcon')->willReturn(null);
        $package = self::createStub(Package::class);
        $package->method('getPackageKey')->willReturn('my_ext');
        $package->method('getPackagePath')->willReturn(Environment::getExtensionsPath() . '/my_ext/');
        $package->method('getPackageMetaData')->willReturn($metaData);
        $package->method('getResources')->willReturn($resources);
        $packageManager = self::createStub(PackageManager::class);
        $packageManager->method('getAvailablePackages')->willReturn(['my_ext' => $package]);
        $subject = new ListUtility();
        $subject->injectEventDispatcher(new NoopEventDispatcher());
        $subject->injectPackageManager($packageManager);
        $subject->injectResourceFactory(self::createStub(SystemResourceFactory::class));
        $subject->injectResourcePublisher(self::createStub(SystemResourcePublisherInterface::class));

        $extensions = $subject->getAvailableExtensions();

        self::assertSame('My Extension', $extensions['my_ext']['title']);
        self::assertSame('Does things', $extensions['my_ext']['description']);
        self::assertSame('1.2.3', $extensions['my_ext']['version']);
    }
}
