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

use PHPUnit\Framework\MockObject\MockObject;
use Prophecy\Argument;
use Prophecy\PhpUnit\ProphecyTrait;
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
    use ProphecyTrait;

    protected ?TemplateService $templateService;

    /**
     * @var MockObject|AccessibleObjectInterface|TemplateService
     */
    protected $templateServiceMock;

    protected ?PackageManager $backupPackageManager;

    /** @var ObjectProphecy<PackageManager> */
    protected ObjectProphecy $packageManagerProphecy;

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
                            'pid' => 123,
                        ],
                    ],
                ],
                'numberedThings' => [
                    1 => 'foo',
                    99 => 'bar',
                ],
            ],
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
            ->onlyMethods(['getPackagePath', 'getPackageKey'])
            ->disableOriginalConstructor()
            ->getMock();
        $mockPackage->method('getPackagePath')->willReturn(__DIR__ . '/Fixtures/');
        $mockPackage->method('getPackageKey')->willReturn('core');

        $mockPackageManager = $this->getMockBuilder(PackageManager::class)
            ->onlyMethods(['isPackageActive', 'getPackage'])
            ->disableOriginalConstructor()
            ->getMock();
        $mockPackageManager->method('isPackageActive')->willReturn(true);
        $mockPackageManager->method('getPackage')->willReturn($mockPackage);
        ExtensionManagementUtility::setPackageManager($mockPackageManager);
        $this->packageManagerProphecy->getActivePackages()->willReturn(['core' => $mockPackage]);

        $this->templateService->setProcessExtensionStatics(true);
        $this->templateService->runThroughTemplates([], 0);

        self::assertContains(
            'test.Core.TypoScript = 1',
            $this->templateService->config
        );
    }
}
