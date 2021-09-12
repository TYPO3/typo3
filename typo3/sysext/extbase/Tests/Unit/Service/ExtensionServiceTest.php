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

namespace TYPO3\CMS\Extbase\Tests\Unit\Service;

use Prophecy\Argument;
use Prophecy\PhpUnit\ProphecyTrait;
use Prophecy\Prophecy\ObjectProphecy;
use TYPO3\CMS\Core\Database\Connection;
use TYPO3\CMS\Core\Database\ConnectionPool;
use TYPO3\CMS\Core\Database\Query\Expression\ExpressionBuilder;
use TYPO3\CMS\Core\Database\Query\QueryBuilder;
use TYPO3\CMS\Core\Tests\Unit\Database\Mocks\MockPlatform;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Extbase\Configuration\ConfigurationManager;
use TYPO3\CMS\Extbase\Configuration\ConfigurationManagerInterface;
use TYPO3\CMS\Extbase\Exception;
use TYPO3\CMS\Extbase\Service\ExtensionService;
use TYPO3\TestingFramework\Core\Unit\UnitTestCase;

/**
 * Test case
 */
class ExtensionServiceTest extends UnitTestCase
{
    use ProphecyTrait;

    /**
     * @var ConfigurationManagerInterface
     */
    protected $mockConfigurationManager;

    protected ExtensionService $extensionService;

    /**
     * Due to nested PageRepository / FrontendRestriction Container issues, the Context object is set
     * @var bool
     */
    protected $resetSingletonInstances = true;

    protected function setUp(): void
    {
        parent::setUp();
        $GLOBALS['TSFE'] = new \stdClass();
        $this->extensionService = new ExtensionService();
        $this->mockConfigurationManager = $this->createMock(ConfigurationManagerInterface::class);
        $this->extensionService->injectConfigurationManager($this->mockConfigurationManager);
        $GLOBALS['TYPO3_CONF_VARS']['EXTCONF']['extbase']['extensions'] = [
            'ExtensionName' => [
                'plugins' => [
                    'SomePlugin' => [
                        'controllers' => [
                            'Fully\\Qualified\\ControllerName' => [
                                'alias' => 'ControllerName',
                                'actions' => ['index', 'otherAction']
                            ]
                        ]
                    ],
                    'ThirdPlugin' => [
                        'controllers' => [
                            'Fully\\Qualified\\ControllerName' => [
                                'alias' => 'ControllerName',
                                'actions' => ['otherAction', 'thirdAction']
                            ]
                        ]
                    ]
                ]
            ],
            'SomeOtherExtensionName' => [
                'plugins' => [
                    'SecondPlugin' => [
                        'controllers' => [
                            'Fully\\Qualified\\ControllerName' => [
                                'alias' => 'ControllerName',
                                'actions' => ['index', 'otherAction']
                            ],
                            'Fully\\Qualified\\SecondControllerName' => [
                                'actions' => ['someAction', 'someOtherAction'],
                                'alias' => 'SecondControllerName',
                                'nonCacheableActions' => ['someOtherAction']
                            ]
                        ]
                    ]
                ]
            ]
        ];
    }

    /**
     * Setup and return a mocked database connection that allows
     * the QueryBuilder to work.
     *
     * @return ObjectProphecy
     */
    protected function getMockDatabaseConnection(): ObjectProphecy
    {
        $connection = $this->prophesize(Connection::class);
        $connection->getDatabasePlatform()->willReturn(new MockPlatform());
        $connection->getExpressionBuilder()->willReturn(new ExpressionBuilder($connection->reveal()));
        $connection->quoteIdentifier(Argument::cetera())->willReturnArgument(0);

        $queryBuilder = new QueryBuilder(
            $connection->reveal(),
            null,
            new \Doctrine\DBAL\Query\QueryBuilder($connection->reveal())
        );

        $connectionPool = $this->prophesize(ConnectionPool::class);
        $connectionPool->getQueryBuilderForTable('tt_content')->willReturn($queryBuilder);
        GeneralUtility::addInstance(ConnectionPool::class, $connectionPool->reveal());

        return $connection;
    }

    /**
     * DataProvider for getPluginNamespaceByPluginSignatureTests()
     *
     * @return array
     */
    public function getPluginNamespaceDataProvider(): array
    {
        return [
            [null, null, 'tx__'],
            ['', '', 'tx__'],
            ['SomeExtension', 'SomePlugin', 'tx_someextension_someplugin'],
            ['NonExistingExtension', 'SomePlugin', 'tx_nonexistingextension_someplugin'],
            ['Invalid', '', 'tx_invalid_']
        ];
    }

    /**
     * @test
     * @dataProvider getPluginNamespaceDataProvider
     * @param string $extensionName
     * @param string $pluginName
     * @param mixed $expectedResult
     */
    public function getPluginNamespaceTests($extensionName, $pluginName, $expectedResult): void
    {
        $this->mockConfigurationManager->expects(self::once())->method('getConfiguration')->willReturn([]);
        $actualResult = $this->extensionService->getPluginNamespace($extensionName, $pluginName);
        self::assertEquals($expectedResult, $actualResult, 'Failing for extension: "' . $extensionName . '", plugin: "' . $pluginName . '"');
    }

    /**
     * @test
     */
    public function pluginNamespaceCanBeOverridden(): void
    {
        $this->mockConfigurationManager->expects(self::once())->method('getConfiguration')->with(ConfigurationManagerInterface::CONFIGURATION_TYPE_FRAMEWORK, 'SomeExtension', 'SomePlugin')->willReturn(['view' => ['pluginNamespace' => 'overridden_plugin_namespace']]);
        $expectedResult = 'overridden_plugin_namespace';
        $actualResult = $this->extensionService->getPluginNamespace('SomeExtension', 'SomePlugin');
        self::assertEquals($expectedResult, $actualResult);
    }

    /**
     * DataProvider for getPluginNameByActionTests()
     *
     * @return array
     */
    public function getPluginNameByActionDataProvider(): array
    {
        return [
            ['ExtensionName', 'ControllerName', 'someNonExistingAction', null],
            ['ExtensionName', 'ControllerName', 'index', 'SomePlugin'],
            ['ExtensionName', 'ControllerName', 'thirdAction', 'ThirdPlugin'],
            ['eXtEnSiOnNaMe', 'cOnTrOlLeRnAmE', 'thirdAction', null],
            ['eXtEnSiOnNaMe', 'cOnTrOlLeRnAmE', 'ThIrDaCtIoN', null],
            ['SomeOtherExtensionName', 'ControllerName', 'otherAction', 'SecondPlugin']
        ];
    }

    /**
     * @test
     * @dataProvider getPluginNameByActionDataProvider
     * @param string $extensionName
     * @param string $controllerName
     * @param string $actionName
     * @param mixed $expectedResult
     */
    public function getPluginNameByActionTests(string $extensionName, string $controllerName, string $actionName, $expectedResult): void
    {
        $this->mockConfigurationManager->expects(self::once())->method('getConfiguration')->with(ConfigurationManagerInterface::CONFIGURATION_TYPE_FRAMEWORK)->willReturn(['view' => ['pluginNamespace' => 'overridden_plugin_namespace']]);
        $actualResult = $this->extensionService->getPluginNameByAction($extensionName, $controllerName, $actionName);
        self::assertEquals($expectedResult, $actualResult, 'Failing for $extensionName: "' . $extensionName . '", $controllerName: "' . $controllerName . '", $actionName: "' . $actionName . '" - ');
    }

    /**
     * @test
     */
    public function getPluginNameByActionThrowsExceptionIfMoreThanOnePluginMatches(): void
    {
        $this->expectException(Exception::class);
        $this->expectExceptionCode(1280825466);
        $this->mockConfigurationManager->expects(self::once())->method('getConfiguration')->with(ConfigurationManagerInterface::CONFIGURATION_TYPE_FRAMEWORK)->willReturn(['view' => ['pluginNamespace' => 'overridden_plugin_namespace']]);
        $this->extensionService->getPluginNameByAction('ExtensionName', 'ControllerName', 'otherAction');
    }

    /**
     * @test
     */
    public function getPluginNameByActionReturnsCurrentIfItCanHandleTheActionEvenIfMoreThanOnePluginMatches(): void
    {
        $frameworkConfiguration = [
            'extensionName' => 'CurrentExtension',
            'pluginName' => 'CurrentPlugin',
            'controllerConfiguration' => [
                'Fully\\Qualified\\ControllerName' => [
                    'alias' => 'ControllerName',
                    'actions' => ['otherAction']
                ]
            ]
        ];

        $this->mockConfigurationManager
            ->expects(self::once())
            ->method('getConfiguration')
            ->with(ConfigurationManagerInterface::CONFIGURATION_TYPE_FRAMEWORK)
            ->willReturn($frameworkConfiguration);

        $actualResult = $this->extensionService->getPluginNameByAction('CurrentExtension', 'ControllerName', 'otherAction');
        $expectedResult = 'CurrentPlugin';
        self::assertEquals($expectedResult, $actualResult);
    }

    /**
     * @test
     */
    public function getTargetPidByPluginSignatureReturnsNullIfConfigurationManagerIsNotInitialized(): void
    {
        $this->mockConfigurationManager->expects(self::once())->method('getConfiguration')->willReturn([]);
        self::assertNull($this->extensionService->getTargetPidByPlugin('ExtensionName', 'PluginName'));
    }

    /**
     * @test
     */
    public function getTargetPidByPluginSignatureReturnsNullIfDefaultPidIsZero(): void
    {
        $this->mockConfigurationManager->expects(self::once())->method('getConfiguration')->willReturn(['view' => ['defaultPid' => 0]]);
        self::assertNull($this->extensionService->getTargetPidByPlugin('ExtensionName', 'PluginName'));
    }

    /**
     * @test
     */
    public function getTargetPidByPluginSignatureReturnsTheConfiguredDefaultPid(): void
    {
        $this->mockConfigurationManager->expects(self::once())->method('getConfiguration')->willReturn(['view' => ['defaultPid' => 123]]);
        $expectedResult = 123;
        $actualResult = $this->extensionService->getTargetPidByPlugin('ExtensionName', 'SomePlugin');
        self::assertEquals($expectedResult, $actualResult);
    }

    /**
     * @test
     */
    public function getDefaultControllerNameByPluginReturnsNullIfGivenExtensionCantBeFound(): void
    {
        self::assertNull($this->extensionService->getDefaultControllerNameByPlugin('NonExistingExtensionName', 'SomePlugin'));
    }

    /**
     * @test
     */
    public function getDefaultControllerNameByPluginReturnsNullIfGivenPluginCantBeFound(): void
    {
        self::assertNull($this->extensionService->getDefaultControllerNameByPlugin('ExtensionName', 'NonExistingPlugin'));
    }

    /**
     * @test
     */
    public function getDefaultControllerNameByPluginReturnsFirstControllerNameOfGivenPlugin(): void
    {
        $expectedResult = 'ControllerName';
        $actualResult = $this->extensionService->getDefaultControllerNameByPlugin('ExtensionName', 'SomePlugin');
        self::assertEquals($expectedResult, $actualResult);
    }

    /**
     * @test
     */
    public function getDefaultActionNameByPluginAndControllerReturnsNullIfGivenExtensionCantBeFound(): void
    {
        self::assertNull($this->extensionService->getDefaultActionNameByPluginAndController('NonExistingExtensionName', 'SomePlugin', 'ControllerName'));
    }

    /**
     * @test
     */
    public function getDefaultActionNameByPluginAndControllerReturnsNullIfGivenPluginCantBeFound(): void
    {
        self::assertNull($this->extensionService->getDefaultActionNameByPluginAndController('ExtensionName', 'NonExistingPlugin', 'ControllerName'));
    }

    /**
     * @test
     */
    public function getDefaultActionNameByPluginAndControllerReturnsNullIfGivenControllerCantBeFound(): void
    {
        self::assertNull($this->extensionService->getDefaultActionNameByPluginAndController('ExtensionName', 'SomePlugin', 'NonExistingControllerName'));
    }

    /**
     * @test
     */
    public function getDefaultActionNameByPluginAndControllerReturnsFirstActionNameOfGivenController(): void
    {
        $expectedResult = 'someAction';
        $actualResult = $this->extensionService->getDefaultActionNameByPluginAndController('SomeOtherExtensionName', 'SecondPlugin', 'SecondControllerName');
        self::assertEquals($expectedResult, $actualResult);
    }

    /**
     * @test
     */
    public function getTargetPageTypeByFormatReturnsZeroIfNoMappingIsSet(): void
    {
        $configurationManagerProphecy = $this->prophesize(ConfigurationManager::class);
        $configurationManagerProphecy->getConfiguration(
            ConfigurationManagerInterface::CONFIGURATION_TYPE_FRAMEWORK,
            'extension'
        )->willReturn([]);
        $this->extensionService->injectConfigurationManager($configurationManagerProphecy->reveal());

        $result = $this->extensionService->getTargetPageTypeByFormat('extension', 'json');

        self::assertSame(0, $result);
    }

    /**
     * @test
     */
    public function getTargetPageTypeByFormatReturnsMappedPageTypeFromConfiguration(): void
    {
        $configurationManagerProphecy = $this->prophesize(ConfigurationManager::class);
        $configurationManagerProphecy->getConfiguration(
            ConfigurationManagerInterface::CONFIGURATION_TYPE_FRAMEWORK,
            'extension'
        )->willReturn([
            'view' => [
                'formatToPageTypeMapping' => [
                    'json' => 111
                ]
            ]
        ]);
        $this->extensionService->injectConfigurationManager($configurationManagerProphecy->reveal());

        $result = $this->extensionService->getTargetPageTypeByFormat('extension', 'json');

        self::assertSame(111, $result);
    }
}
