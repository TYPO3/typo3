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

namespace TYPO3\CMS\Core\Tests\Unit\TypoScript;

use Prophecy\Argument;
use Prophecy\Prophecy\ObjectProphecy;
use TYPO3\CMS\Core\Context\Context;
use TYPO3\CMS\Core\Database\ConnectionPool;
use TYPO3\CMS\Core\Database\Query\QueryBuilder;
use TYPO3\CMS\Core\Package\Package;
use TYPO3\CMS\Core\Package\PackageManager;
use TYPO3\CMS\Core\Site\Entity\Site;
use TYPO3\CMS\Core\Tests\Unit\Utility\AccessibleProxies\ExtensionManagementUtilityAccessibleProxy;
use TYPO3\CMS\Core\TypoScript\TemplateService;
use TYPO3\CMS\Core\Utility\ExtensionManagementUtility;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Frontend\Controller\TypoScriptFrontendController;
use TYPO3\TestingFramework\Core\AccessibleObjectInterface;
use TYPO3\TestingFramework\Core\Unit\UnitTestCase;

/**
 * Test case
 */
class TemplateServiceTest extends UnitTestCase
{
    /**
     * @var TemplateService
     */
    protected $templateService;

    /**
     * @var \PHPUnit\Framework\MockObject\MockObject|AccessibleObjectInterface|TemplateService
     */
    protected $templateServiceMock;

    /**
     * @var PackageManager
     */
    protected $backupPackageManager;

    /**
     * @var PackageManager|ObjectProphecy
     */
    protected $packageManagerProphecy;

    /**
     * Set up
     */
    protected function setUp(): void
    {
        parent::setUp();
        $GLOBALS['SIM_ACCESS_TIME'] = time();
        $GLOBALS['ACCESS_TIME'] = time();
        $this->packageManagerProphecy = $this->prophesize(PackageManager::class);
        $frontendController = $this->prophesize(TypoScriptFrontendController::class);
        $frontendController->getSite()->willReturn(new Site('dummy', 13, [
            'base' => 'https://example.com',
            'settings' => [
                'random' => 'value',
                'styles' => [
                    'content' => [
                        'loginform' => [
                            'pid' => 123
                        ],
                    ],
                ],
                'numberedThings' => [
                    1 => 'foo',
                    99 => 'bar',
                ]
            ]
        ]));
        $this->templateService = new TemplateService(
            new Context(),
            $this->packageManagerProphecy->reveal(),
            $frontendController->reveal()
        );
        $this->backupPackageManager = ExtensionManagementUtilityAccessibleProxy::getPackageManager();
    }

    /**
     * Tear down
     */
    public function tearDown(): void
    {
        ExtensionManagementUtilityAccessibleProxy::setPackageManager($this->backupPackageManager);
        parent::tearDown();
    }

    /**
     * @test
     */
    public function extensionStaticFilesAreNotProcessedIfNotExplicitlyRequested(): void
    {
        $queryBuilderProphet = $this->prophesize(QueryBuilder::class);
        $connectionPoolProphet = $this->prophesize(ConnectionPool::class);
        $connectionPoolProphet->getQueryBuilderForTable(Argument::cetera())->willReturn($queryBuilderProphet->reveal());
        GeneralUtility::addInstance(ConnectionPool::class, $connectionPoolProphet->reveal());

        $this->packageManagerProphecy->getActivePackages()->shouldNotBeCalled();

        $this->templateService->runThroughTemplates([], 0);
        self::assertNotContains(
            'test.Core.TypoScript = 1',
            $this->templateService->config
        );
    }

    /**
     * @test
     */
    public function extensionStaticsAreProcessedIfExplicitlyRequested(): void
    {
        $queryBuilderProphet = $this->prophesize(QueryBuilder::class);
        $connectionPoolProphet = $this->prophesize(ConnectionPool::class);
        $connectionPoolProphet->getQueryBuilderForTable(Argument::cetera())->willReturn($queryBuilderProphet->reveal());
        GeneralUtility::addInstance(ConnectionPool::class, $connectionPoolProphet->reveal());

        $mockPackage = $this->getMockBuilder(Package::class)
            ->setMethods(['getPackagePath', 'getPackageKey'])
            ->disableOriginalConstructor()
            ->getMock();
        $mockPackage->expects(self::any())->method('getPackagePath')->willReturn(__DIR__ . '/Fixtures/');
        $mockPackage->expects(self::any())->method('getPackageKey')->willReturn('core');

        $mockPackageManager = $this->getMockBuilder(PackageManager::class)
            ->setMethods(['isPackageActive', 'getPackage'])
            ->disableOriginalConstructor()
            ->getMock();
        $mockPackageManager->expects(self::any())->method('isPackageActive')->willReturn(true);
        $mockPackageManager->expects(self::any())->method('getPackage')->willReturn($mockPackage);
        ExtensionManagementUtility::setPackageManager($mockPackageManager);
        $this->packageManagerProphecy->getActivePackages()->willReturn(['core' => $mockPackage]);

        $this->templateService->setProcessExtensionStatics(true);
        $this->templateService->runThroughTemplates([], 0);

        self::assertContains(
            'test.Core.TypoScript = 1',
            $this->templateService->config
        );
    }

    /**
     * @test
     */
    public function updateRootlineDataOverwritesOwnArrayData(): void
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

        $this->templateService->rootLine = $originalRootline;
        $this->templateService->updateRootlineData($updatedRootline);
        self::assertEquals($expectedRootline, $this->templateService->rootLine);
    }

    /**
     * @test
     */
    public function updateRootlineDataWithInvalidNewRootlineThrowsException(): void
    {
        $originalRootline = [
            0 => ['uid' => 2, 'title' => 'originalTitle'],
            1 => ['uid' => 3, 'title' => 'originalTitle2'],
        ];

        $newInvalidRootline = [
            0 => ['uid' => 1, 'title' => 'newTitle'],
            1 => ['uid' => 2, 'title' => 'newTitle2'],
        ];

        $this->expectException(\RuntimeException::class);
        $this->expectExceptionCode(1370419654);

        $this->templateService->rootLine = $originalRootline;
        $this->templateService->updateRootlineData($newInvalidRootline);
    }
}
