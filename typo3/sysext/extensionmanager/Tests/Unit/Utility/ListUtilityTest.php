<?php
declare(strict_types = 1);
namespace TYPO3\CMS\Extensionmanager\Tests\Unit\Utility;

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

use TYPO3\CMS\Core\Package\Package;
use TYPO3\CMS\Core\Package\PackageManager;
use TYPO3\CMS\Extensionmanager\Domain\Repository\ExtensionRepository;
use TYPO3\CMS\Extensionmanager\Utility\EmConfUtility;
use TYPO3\CMS\Extensionmanager\Utility\ListUtility;
use TYPO3\TestingFramework\Core\Unit\UnitTestCase;

/**
 * List utility test
 */
class ListUtilityTest extends UnitTestCase
{
    /**
     * @var ListUtility
     */
    protected $subject;

    /**
     */
    protected function setUp()
    {
        $this->subject = $this->getMockBuilder(ListUtility::class)
            ->setMethods(['emitPackagesMayHaveChangedSignal'])
            ->getMock();
        $packageManagerMock = $this->getMockBuilder(PackageManager::class)
            ->disableOriginalConstructor()
            ->getMock();
        $packageManagerMock
                ->expects($this->any())
                ->method('getActivePackages')
                ->will($this->returnValue([
                    'lang' => $this->getMockBuilder(Package::class)->disableOriginalConstructor()->getMock(),
                    'news' => $this->getMockBuilder(Package::class)->disableOriginalConstructor()->getMock(),
                    'saltedpasswords' => $this->getMockBuilder(Package::class)->disableOriginalConstructor()->getMock(),
                ]));
        $this->inject($this->subject, 'packageManager', $packageManagerMock);
    }

    /**
     * @return array
     */
    public function getAvailableAndInstalledExtensionsDataProvider(): array
    {
        return [
            'same extension lists' => [
                [
                    'lang' => [],
                    'news' => [],
                    'saltedpasswords' => [],
                ],
                [
                    'lang' => ['installed' => true],
                    'news' => ['installed' => true],
                    'saltedpasswords' => ['installed' => true],
                ]
            ],
            'different extension lists' => [
                [
                    'lang' => [],
                    'news' => [],
                    'saltedpasswords' => [],
                ],
                [
                    'lang' => ['installed' => true],
                    'news' => ['installed' => true],
                    'saltedpasswords' => ['installed' => true],
                ]
            ],
            'different extension lists - set2' => [
                [
                    'lang' => [],
                    'news' => [],
                    'saltedpasswords' => [],
                    'em' => []
                ],
                [
                    'lang' => ['installed' => true],
                    'news' => ['installed' => true],
                    'saltedpasswords' => ['installed' => true],
                    'em' => []
                ]
            ],
            'different extension lists - set3' => [
                [
                    'lang' => [],
                    'fluid' => [],
                    'news' => [],
                    'saltedpasswords' => [],
                    'em' => []
                ],
                [
                    'lang' => ['installed' => true],
                    'fluid' => [],
                    'news' => ['installed' => true],
                    'saltedpasswords' => ['installed' => true],
                    'em' => []
                ]
            ]
        ];
    }

    /**
     * @test
     * @dataProvider getAvailableAndInstalledExtensionsDataProvider
     * @param $availableExtensions
     * @param $expectedResult
     */
    public function getAvailableAndInstalledExtensionsTest($availableExtensions, $expectedResult)
    {
        $this->assertEquals($expectedResult, $this->subject->getAvailableAndInstalledExtensions($availableExtensions));
    }

    /**
     * @return array
     */
    public function enrichExtensionsWithEmConfInformationDataProvider(): array
    {
        return [
            'simple key value array emconf' => [
                [
                    'lang' => ['property1' => 'oldvalue'],
                    'news' => [],
                    'saltedpasswords' => [],
                ],
                [
                    'property1' => 'property value1'
                ],
                [
                    'lang' => ['property1' => 'oldvalue'],
                    'news' => ['property1' => 'property value1'],
                    'saltedpasswords' => ['property1' => 'property value1'],
                ]
            ]
        ];
    }

    /**
     * @test
     * @dataProvider enrichExtensionsWithEmConfInformationDataProvider
     * @param $extensions
     * @param $emConf
     * @param $expectedResult
     */
    public function enrichExtensionsWithEmConfInformation($extensions, $emConf, $expectedResult)
    {
        $this->inject($this->subject, 'extensionRepository', $this->getAccessibleMock(ExtensionRepository::class, ['findOneByExtensionKeyAndVersion', 'findHighestAvailableVersion'], [], '', false));
        $emConfUtilityMock = $this->getMockBuilder(EmConfUtility::class)->getMock();
        $emConfUtilityMock->expects($this->any())->method('includeEmConf')->will($this->returnValue($emConf));
        $this->inject($this->subject, 'emConfUtility', $emConfUtilityMock);
        $this->assertEquals($expectedResult, $this->subject->enrichExtensionsWithEmConfAndTerInformation($extensions));
    }
}
