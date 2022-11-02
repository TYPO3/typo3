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

class TemplateServiceTest extends UnitTestCase
{
    protected ?TemplateService $templateService;
    protected MockObject&AccessibleObjectInterface&TemplateService $templateServiceMock;
    protected ?PackageManager $backupPackageManager;
    protected MockObject&PackageManager $packageManagerMock;

    protected function setUp(): void
    {
        parent::setUp();
        $GLOBALS['SIM_ACCESS_TIME'] = time();
        $GLOBALS['ACCESS_TIME'] = time();
        $this->packageManagerMock = $this->createMock(PackageManager::class);
        $frontendControllerMock = $this->createMock(TypoScriptFrontendController::class);
        $frontendControllerMock->method('getSite')->willReturn(new Site('dummy', 13, [
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
            $this->packageManagerMock,
            $frontendControllerMock
        );
        $this->backupPackageManager = ExtensionManagementUtilityAccessibleProxy::getPackageManager();
    }

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
        $queryBuilderMock = $this->createMock(QueryBuilder::class);
        $connectionPoolMock = $this->createMock(ConnectionPool::class);
        $connectionPoolMock->method('getQueryBuilderForTable')->with(self::anything())->willReturn($queryBuilderMock);
        GeneralUtility::addInstance(ConnectionPool::class, $connectionPoolMock);

        $this->packageManagerMock->expects(self::never())->method('getActivePackages');

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
        $queryBuilderMock = $this->createMock(QueryBuilder::class);
        $connectionPoolMock = $this->createMock(ConnectionPool::class);
        $connectionPoolMock->method('getQueryBuilderForTable')->with(self::anything())->willReturn($queryBuilderMock);
        GeneralUtility::addInstance(ConnectionPool::class, $connectionPoolMock);

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
        $this->packageManagerMock->method('getActivePackages')->willReturn(['core' => $mockPackage]);

        $this->templateService->setProcessExtensionStatics(true);
        $this->templateService->runThroughTemplates([], 0);

        self::assertContains(
            'test.Core.TypoScript = 1',
            $this->templateService->config
        );
    }
}
