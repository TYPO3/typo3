<?php
namespace TYPO3\CMS\Core\Tests\Unit\TypoScript;

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

use TYPO3\CMS\Core\Utility\ExtensionManagementUtility;
use TYPO3\CMS\Core\Utility\GeneralUtility;

/**
 * Testcase for \TYPO3\CMS\Core\TypoScript\TemplateService
 */
class TemplateServiceTest extends \TYPO3\CMS\Core\Tests\UnitTestCase
{
    /**
     * @var \TYPO3\CMS\Core\TypoScript\TemplateService
     */
    protected $templateService;

    /**
     * @var \PHPUnit_Framework_MockObject_MockObject|\TYPO3\CMS\Core\Tests\AccessibleObjectInterface|\TYPO3\CMS\Core\TypoScript\TemplateService
     */
    protected $templateServiceMock;

    /**
     * Sets up this test case.
     *
     * @return void
     */
    protected function setUp()
    {
        $GLOBALS['TYPO3_LOADED_EXT'] = [];
        $this->templateService = new \TYPO3\CMS\Core\TypoScript\TemplateService();
        $this->templateService->tt_track = false;
        $this->templateServiceMock = $this->getAccessibleMock(\TYPO3\CMS\Core\TypoScript\TemplateService::class, ['dummy']);
        $this->templateServiceMock->tt_track = false;
    }

    /**
     * @test
     */
    public function versionOlCallsVersionOlOfPageSelectClassWithGivenRow()
    {
        $row = ['foo'];
        $GLOBALS['TSFE'] = new \stdClass();
        $sysPageMock = $this->getMock(\TYPO3\CMS\Frontend\Page\PageRepository::class);
        $sysPageMock->expects($this->once())->method('versionOL')->with('sys_template', $row);
        $GLOBALS['TSFE']->sys_page = $sysPageMock;
        $this->templateService->versionOL($row);
    }

    /**
     * @test
     */
    public function extensionStaticFilesAreNotProcessedIfNotExplicitlyRequested()
    {
        $identifier = $this->getUniqueId('test');
        $GLOBALS['TYPO3_LOADED_EXT'] = [
            $identifier => [
                'ext_typoscript_setup.txt' => ExtensionManagementUtility::extPath(
                    'core', 'Tests/Unit/TypoScript/Fixtures/ext_typoscript_setup.txt'
                ),
            ],
        ];

        $this->templateService->runThroughTemplates([], 0);
        $this->assertFalse(
            in_array('test.Core.TypoScript = 1', $this->templateService->config)
        );
    }

    /**
     * @test
     */
    public function extensionStaticsAreProcessedIfExplicitlyRequested()
    {
        $identifier = $this->getUniqueId('test');
        $GLOBALS['TYPO3_LOADED_EXT'] = [
            $identifier => [
                'ext_typoscript_setup.txt' => ExtensionManagementUtility::extPath(
                        'core', 'Tests/Unit/TypoScript/Fixtures/ext_typoscript_setup.txt'
                    ),
                'ext_typoscript_constants.txt' => ''
            ],
        ];

        $mockPackage = $this->getMock(\TYPO3\CMS\Core\Package\Package::class, ['getPackagePath'], [], '', false);
        $mockPackage->expects($this->any())->method('getPackagePath')->will($this->returnValue(''));

        $mockPackageManager = $this->getMock(\TYPO3\CMS\Core\Package\PackageManager::class, ['isPackageActive', 'getPackage']);
        $mockPackageManager->expects($this->any())->method('isPackageActive')->will($this->returnValue(true));
        $mockPackageManager->expects($this->any())->method('getPackage')->will($this->returnValue($mockPackage));
        ExtensionManagementUtility::setPackageManager($mockPackageManager);

        $this->templateService->setProcessExtensionStatics(true);
        $this->templateService->runThroughTemplates([], 0);

        $this->assertTrue(
            in_array('test.Core.TypoScript = 1', $this->templateService->config)
        );

        ExtensionManagementUtility::setPackageManager(GeneralUtility::makeInstance(\TYPO3\CMS\Core\Package\PackageManager::class));
    }

    /**
     * @test
     */
    public function updateRootlineDataOverwritesOwnArrayData()
    {
        $originalRootline = [
            0 => ['uid' => 2, 'title' => 'originalTitle'],
            1 => ['uid' => 3, 'title' => 'originalTitle2'],
        ];

        $updatedRootline = [
            0 => ['uid' => 1, 'title' => 'newTitle'],
            1 => ['uid' => 2, 'title' => 'newTitle2'],
            2 => ['uid' => 3, 'title' => 'newTitle3'],
        ];

        $expectedRootline = [
            0 => ['uid' => 2, 'title' => 'newTitle2'],
            1 => ['uid' => 3, 'title' => 'newTitle3'],
        ];

        $this->templateServiceMock->_set('rootLine', $originalRootline);
        $this->templateServiceMock->updateRootlineData($updatedRootline);
        $this->assertEquals($expectedRootline, $this->templateServiceMock->_get('rootLine'));
    }

    /**
     * @test
     * @expectedException \RuntimeException
     */
    public function updateRootlineDataWithInvalidNewRootlineThrowsException()
    {
        $originalRootline = [
            0 => ['uid' => 2, 'title' => 'originalTitle'],
            1 => ['uid' => 3, 'title' => 'originalTitle2'],
        ];

        $newInvalidRootline = [
            0 => ['uid' => 1, 'title' => 'newTitle'],
            1 => ['uid' => 2, 'title' => 'newTitle2'],
        ];

        $this->templateServiceMock->_set('rootLine', $originalRootline);
        $this->templateServiceMock->updateRootlineData($newInvalidRootline);
    }

    /**
     * @test
     */
    public function getFileNameReturnsUrlCorrectly()
    {
        $this->assertSame('http://example.com', $this->templateService->getFileName('http://example.com'));
        $this->assertSame('https://example.com', $this->templateService->getFileName('https://example.com'));
    }

    /**
     * @test
     */
    public function getFileNameReturnsFileCorrectly()
    {
        $this->assertSame('typo3/index.php', $this->templateService->getFileName('typo3/index.php'));
    }

    /**
     * @test
     */
    public function getFileNameReturnsNullIfDirectory()
    {
        $this->assertNull($this->templateService->getFileName(__DIR__));
    }

    /**
     * @test
     */
    public function getFileNameReturnsNullWithInvalidFileName()
    {
        $this->assertNull($this->templateService->getFileName('  '));
        $this->assertNull($this->templateService->getFileName('something/../else'));
    }

    public function splitConfDataProvider()
    {
        return [
            [
                ['splitConfiguration' => 'a'],
                3,
                [
                    0 => ['splitConfiguration' => 'a'],
                    1 => ['splitConfiguration' => 'a'],
                    2 => ['splitConfiguration' => 'a']
                ]
            ],
            [
                ['splitConfiguration' => 'a || b || c'],
                5,
                [
                    0 => ['splitConfiguration' => 'a'],
                    1 => ['splitConfiguration' => 'b'],
                    2 => ['splitConfiguration' => 'c'],
                    3 => ['splitConfiguration' => 'c'],
                    4 => ['splitConfiguration' => 'c']
                ]
            ],
            [
                ['splitConfiguration' => 'a || b |*| c'],
                5,
                [
                    0 => ['splitConfiguration' => 'a'],
                    1 => ['splitConfiguration' => 'b'],
                    2 => ['splitConfiguration' => 'c'],
                    3 => ['splitConfiguration' => 'c'],
                    4 => ['splitConfiguration' => 'c']
                ]
            ],
            [
                ['splitConfiguration' => 'a || b |*| c |*| d || e'],
                7,
                [
                    0 => ['splitConfiguration' => 'a'],
                    1 => ['splitConfiguration' => 'b'],
                    2 => ['splitConfiguration' => 'c'],
                    3 => ['splitConfiguration' => 'c'],
                    4 => ['splitConfiguration' => 'c'],
                    5 => ['splitConfiguration' => 'd'],
                    6 => ['splitConfiguration' => 'e']
                ]
            ],
            [
                ['splitConfiguration' => 'a || b |*| c |*| d || e'],
                4,
                [
                    0 => ['splitConfiguration' => 'a'],
                    1 => ['splitConfiguration' => 'b'],
                    2 => ['splitConfiguration' => 'd'],
                    3 => ['splitConfiguration' => 'e']
                ]
            ],
            [
                ['splitConfiguration' => 'a || b |*| c |*| d || e'],
                3,
                [
                    0 => ['splitConfiguration' => 'a'],
                    1 => ['splitConfiguration' => 'd'],
                    2 => ['splitConfiguration' => 'e']
                ]
            ],
            [
                ['splitConfiguration' => 'a || b |*||*| c || d'],
                7,
                [
                    0 => ['splitConfiguration' => 'a'],
                    1 => ['splitConfiguration' => 'b'],
                    2 => ['splitConfiguration' => 'b'],
                    3 => ['splitConfiguration' => 'b'],
                    4 => ['splitConfiguration' => 'b'],
                    5 => ['splitConfiguration' => 'c'],
                    6 => ['splitConfiguration' => 'd']
                ]
            ],
            [
                ['splitConfiguration' => '|*||*| a || b'],
                7,
                [
                    0 => ['splitConfiguration' => 'a'],
                    1 => ['splitConfiguration' => 'a'],
                    2 => ['splitConfiguration' => 'a'],
                    3 => ['splitConfiguration' => 'a'],
                    4 => ['splitConfiguration' => 'a'],
                    5 => ['splitConfiguration' => 'a'],
                    6 => ['splitConfiguration' => 'b']
                ]
            ],
            [
                ['splitConfiguration' => 'a |*| b || c |*|'],
                7,
                [
                    0 => ['splitConfiguration' => 'a'],
                    1 => ['splitConfiguration' => 'b'],
                    2 => ['splitConfiguration' => 'c'],
                    3 => ['splitConfiguration' => 'b'],
                    4 => ['splitConfiguration' => 'c'],
                    5 => ['splitConfiguration' => 'b'],
                    6 => ['splitConfiguration' => 'c']
                ]
            ],
        ];
    }

    /**
     * @test
     * @dataProvider splitConfDataProvider
     * @see https://docs.typo3.org/typo3cms/TyposcriptReference/ObjectsAndProperties/Index.html#objects-optionsplit
     */
    public function splitConfArraytest($configuration, $splitCount, $expected)
    {
        $actual = $this->templateService->splitConfArray($configuration, $splitCount);
        $this->assertSame($expected, $actual);
    }
}
