<?php
namespace TYPO3\CMS\Extbase\Tests\Unit\Service;

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
use Doctrine\DBAL\Statement;
use Prophecy\Argument;
use Prophecy\Prophecy\ObjectProphecy;
use TYPO3\CMS\Core\Database\Connection;
use TYPO3\CMS\Core\Database\ConnectionPool;
use TYPO3\CMS\Core\Database\Query\Expression\ExpressionBuilder;
use TYPO3\CMS\Core\Database\Query\QueryBuilder;
use TYPO3\CMS\Core\Tests\Unit\Database\Mocks\MockPlatform;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Extbase\Exception;

/**
 * Test case
 */
class ExtensionServiceTest extends \TYPO3\CMS\Core\Tests\UnitTestCase
{
    /**
     * @var \TYPO3\CMS\Extbase\Configuration\ConfigurationManagerInterface
     */
    protected $mockConfigurationManager;

    /**
     * @var \TYPO3\CMS\Extbase\Service\ExtensionService
     */
    protected $extensionService;

    protected function setUp()
    {
        $GLOBALS['TYPO3_DB'] = $this->getMockBuilder(\TYPO3\CMS\Core\Database\DatabaseConnection::class)
            ->setMethods(array('fullQuoteStr', 'exec_SELECTgetRows'))
            ->getMock();
        $GLOBALS['TSFE'] = new \stdClass();
        $GLOBALS['TSFE']->gr_list = '';
        $this->extensionService = $this->getAccessibleMock(\TYPO3\CMS\Extbase\Service\ExtensionService::class, array('dummy'));
        $this->mockConfigurationManager = $this->createMock(\TYPO3\CMS\Extbase\Configuration\ConfigurationManagerInterface::class);
        $this->extensionService->_set('configurationManager', $this->mockConfigurationManager);
        $GLOBALS['TYPO3_CONF_VARS']['EXTCONF']['extbase']['extensions'] = array(
            'ExtensionName' => array(
                'plugins' => array(
                    'SomePlugin' => array(
                        'controllers' => array(
                            'ControllerName' => array(
                                'actions' => array('index', 'otherAction')
                            )
                        )
                    ),
                    'ThirdPlugin' => array(
                        'controllers' => array(
                            'ControllerName' => array(
                                'actions' => array('otherAction', 'thirdAction')
                            )
                        )
                    )
                )
            ),
            'SomeOtherExtensionName' => array(
                'plugins' => array(
                    'SecondPlugin' => array(
                        'controllers' => array(
                            'ControllerName' => array(
                                'actions' => array('index', 'otherAction')
                            ),
                            'SecondControllerName' => array(
                                'actions' => array('someAction', 'someOtherAction'),
                                'nonCacheableActions' => array('someOtherAction')
                            )
                        )
                    )
                )
            )
        );
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
    public function getPluginNamespaceDataProvider()
    {
        return array(
            array('SomeExtension', 'SomePlugin', 'tx_someextension_someplugin'),
            array('NonExistingExtension', 'SomePlugin', 'tx_nonexistingextension_someplugin'),
            array('Invalid', '', 'tx_invalid_')
        );
    }

    /**
     * @test
     * @dataProvider getPluginNamespaceDataProvider
     * @param string $extensionName
     * @param string $pluginName
     * @param mixed $expectedResult
     */
    public function getPluginNamespaceTests($extensionName, $pluginName, $expectedResult)
    {
        $this->mockConfigurationManager->expects($this->once())->method('getConfiguration')->will($this->returnValue(array()));
        $actualResult = $this->extensionService->getPluginNamespace($extensionName, $pluginName);
        $this->assertEquals($expectedResult, $actualResult, 'Failing for extension: "' . $extensionName . '", plugin: "' . $pluginName . '"');
    }

    /**
     * @test
     */
    public function pluginNamespaceCanBeOverridden()
    {
        $this->mockConfigurationManager->expects($this->once())->method('getConfiguration')->with(\TYPO3\CMS\Extbase\Configuration\ConfigurationManagerInterface::CONFIGURATION_TYPE_FRAMEWORK, 'SomeExtension', 'SomePlugin')->will($this->returnValue(array('view' => array('pluginNamespace' => 'overridden_plugin_namespace'))));
        $expectedResult = 'overridden_plugin_namespace';
        $actualResult = $this->extensionService->getPluginNamespace('SomeExtension', 'SomePlugin');
        $this->assertEquals($expectedResult, $actualResult);
    }

    /**
     * DataProvider for getPluginNameByActionTests()
     *
     * @return array
     */
    public function getPluginNameByActionDataProvider()
    {
        return array(
            array('ExtensionName', 'ControllerName', 'someNonExistingAction', null),
            array('ExtensionName', 'ControllerName', 'index', 'SomePlugin'),
            array('ExtensionName', 'ControllerName', 'thirdAction', 'ThirdPlugin'),
            array('eXtEnSiOnNaMe', 'cOnTrOlLeRnAmE', 'thirdAction', null),
            array('eXtEnSiOnNaMe', 'cOnTrOlLeRnAmE', 'ThIrDaCtIoN', null),
            array('SomeOtherExtensionName', 'ControllerName', 'otherAction', 'SecondPlugin')
        );
    }

    /**
     * @test
     * @dataProvider getPluginNameByActionDataProvider
     * @param string $extensionName
     * @param string $controllerName
     * @param string $actionName
     * @param mixed $expectedResult
     */
    public function getPluginNameByActionTests($extensionName, $controllerName, $actionName, $expectedResult)
    {
        $this->mockConfigurationManager->expects($this->once())->method('getConfiguration')->with(\TYPO3\CMS\Extbase\Configuration\ConfigurationManagerInterface::CONFIGURATION_TYPE_FRAMEWORK)->will($this->returnValue(array('view' => array('pluginNamespace' => 'overridden_plugin_namespace'))));
        $actualResult = $this->extensionService->getPluginNameByAction($extensionName, $controllerName, $actionName);
        $this->assertEquals($expectedResult, $actualResult, 'Failing for $extensionName: "' . $extensionName . '", $controllerName: "' . $controllerName . '", $actionName: "' . $actionName . '" - ');
    }

    /**
     * @test
     */
    public function getPluginNameByActionThrowsExceptionIfMoreThanOnePluginMatches()
    {
        $this->expectException(Exception::class);
        $this->expectExceptionCode(1280825466);
        $this->mockConfigurationManager->expects($this->once())->method('getConfiguration')->with(\TYPO3\CMS\Extbase\Configuration\ConfigurationManagerInterface::CONFIGURATION_TYPE_FRAMEWORK)->will($this->returnValue(array('view' => array('pluginNamespace' => 'overridden_plugin_namespace'))));
        $this->extensionService->getPluginNameByAction('ExtensionName', 'ControllerName', 'otherAction');
    }

    /**
     * @test
     */
    public function getPluginNameByActionReturnsCurrentIfItCanHandleTheActionEvenIfMoreThanOnePluginMatches()
    {
        $this->mockConfigurationManager->expects($this->once())->method('getConfiguration')->with(\TYPO3\CMS\Extbase\Configuration\ConfigurationManagerInterface::CONFIGURATION_TYPE_FRAMEWORK)->will($this->returnValue(array('extensionName' => 'CurrentExtension', 'pluginName' => 'CurrentPlugin', 'controllerConfiguration' => array('ControllerName' => array('actions' => array('otherAction'))))));
        $actualResult = $this->extensionService->getPluginNameByAction('CurrentExtension', 'ControllerName', 'otherAction');
        $expectedResult = 'CurrentPlugin';
        $this->assertEquals($expectedResult, $actualResult);
    }

    /**
     * @test
     */
    public function isActionCacheableReturnsTrueByDefault()
    {
        $mockConfiguration = array();
        $this->mockConfigurationManager->expects($this->any())->method('getConfiguration')->will($this->returnValue($mockConfiguration));
        $actualResult = $this->extensionService->isActionCacheable('SomeExtension', 'SomePlugin', 'SomeController', 'someAction');
        $this->assertTrue($actualResult);
    }

    /**
     * @test
     */
    public function isActionCacheableReturnsFalseIfActionIsNotCacheable()
    {
        $mockConfiguration = array(
            'controllerConfiguration' => array(
                'SomeController' => array(
                    'nonCacheableActions' => array('someAction')
                )
            )
        );
        $this->mockConfigurationManager->expects($this->any())->method('getConfiguration')->will($this->returnValue($mockConfiguration));
        $actualResult = $this->extensionService->isActionCacheable('SomeExtension', 'SomePlugin', 'SomeController', 'someAction');
        $this->assertFalse($actualResult);
    }

    /**
     * @test
     */
    public function getTargetPidByPluginSignatureReturnsNullIfConfigurationManagerIsNotInitialized()
    {
        $this->mockConfigurationManager->expects($this->once())->method('getConfiguration')->will($this->returnValue(null));
        $this->assertNull($this->extensionService->getTargetPidByPlugin('ExtensionName', 'PluginName'));
    }

    /**
     * @test
     */
    public function getTargetPidByPluginSignatureReturnsNullIfDefaultPidIsZero()
    {
        $this->mockConfigurationManager->expects($this->once())->method('getConfiguration')->will($this->returnValue(array('view' => array('defaultPid' => 0))));
        $this->assertNull($this->extensionService->getTargetPidByPlugin('ExtensionName', 'PluginName'));
    }

    /**
     * @test
     */
    public function getTargetPidByPluginSignatureReturnsTheConfiguredDefaultPid()
    {
        $this->mockConfigurationManager->expects($this->once())->method('getConfiguration')->will($this->returnValue(array('view' => array('defaultPid' => 123))));
        $expectedResult = 123;
        $actualResult = $this->extensionService->getTargetPidByPlugin('ExtensionName', 'SomePlugin');
        $this->assertEquals($expectedResult, $actualResult);
    }

    /**
     * @test
     * @todo This should rather be a functional test since it needs a connection / querybuilder
     */
    public function getTargetPidByPluginSignatureDeterminesTheTargetPidIfDefaultPidIsAuto()
    {
        $this->mockConfigurationManager->expects($this->once())->method('getConfiguration')->will(
            $this->returnValue(['view' => ['defaultPid' => 'auto']])
        );
        $expectedResult = 321;

        $statement = $this->prophesize(Statement::class);
        $statement->fetchAll()->shouldBeCalled()->willReturn([['pid' => (string)$expectedResult]]);

        $connection = $this->getMockDatabaseConnection();
        $connection->executeQuery(
            'SELECT pid FROM tt_content WHERE (list_type = :dcValue1) AND (CType = :dcValue2) AND (sys_language_uid = 0) LIMIT 2',
            ['dcValue1' => 'extensionname_someplugin', 'dcValue2' => 'list'],
            Argument::cetera()
        )->shouldBeCalled()->willReturn($statement->reveal());

        $actualResult = $this->extensionService->getTargetPidByPlugin('ExtensionName', 'SomePlugin');
        $this->assertEquals($expectedResult, $actualResult);
    }

    /**
     * @test
     * @todo This should rather be a functional test since it needs a connection / querybuilder
     */
    public function getTargetPidByPluginSignatureReturnsNullIfTargetPidCouldNotBeDetermined()
    {
        $this->mockConfigurationManager->expects($this->once())->method('getConfiguration')->will(
            $this->returnValue(['view' => ['defaultPid' => 'auto']])
        );

        $statement = $this->prophesize(Statement::class);
        $statement->fetchAll()->shouldBeCalled()->willReturn([]);

        $connection = $this->getMockDatabaseConnection();
        $connection->executeQuery(
            'SELECT pid FROM tt_content WHERE (list_type = :dcValue1) AND (CType = :dcValue2) AND (sys_language_uid = 0) LIMIT 2',
            ['dcValue1' => 'extensionname_someplugin', 'dcValue2' => 'list'],
            Argument::cetera()
        )->shouldBeCalled()->willReturn($statement->reveal());

        $this->assertNull($this->extensionService->getTargetPidByPlugin('ExtensionName', 'SomePlugin'));
    }

    /**
     * @test
     * @todo This should rather be a functional test since it needs a connection / querybuilder
     */
    public function getTargetPidByPluginSignatureThrowsExceptionIfMoreThanOneTargetPidsWereFound()
    {
        $this->expectException(Exception::class);
        $this->expectExceptionCode(1280773643);

        $this->mockConfigurationManager->expects($this->once())->method('getConfiguration')->will(
            $this->returnValue(['view' => ['defaultPid' => 'auto']])
        );

        $statement = $this->prophesize(Statement::class);
        $statement->fetchAll()->shouldBeCalled()->willReturn([['pid' => 123], ['pid' => 124]]);

        $connection = $this->getMockDatabaseConnection();
        $connection->executeQuery(
            'SELECT pid FROM tt_content WHERE (list_type = :dcValue1) AND (CType = :dcValue2) AND (sys_language_uid = 0) LIMIT 2',
            ['dcValue1' => 'extensionname_someplugin', 'dcValue2' => 'list'],
            Argument::cetera()
        )->shouldBeCalled()->willReturn($statement->reveal());

        $this->expectException(\TYPO3\CMS\Extbase\Exception::class);
        $this->expectExceptionCode(1280773643);

        $this->extensionService->getTargetPidByPlugin('ExtensionName', 'SomePlugin');
    }

    /**
     * @test
     */
    public function getDefaultControllerNameByPluginReturnsNullIfGivenExtensionCantBeFound()
    {
        $this->assertNull($this->extensionService->getDefaultControllerNameByPlugin('NonExistingExtensionName', 'SomePlugin'));
    }

    /**
     * @test
     */
    public function getDefaultControllerNameByPluginReturnsNullIfGivenPluginCantBeFound()
    {
        $this->assertNull($this->extensionService->getDefaultControllerNameByPlugin('ExtensionName', 'NonExistingPlugin'));
    }

    /**
     * @test
     */
    public function getDefaultControllerNameByPluginReturnsFirstControllerNameOfGivenPlugin()
    {
        $expectedResult = 'ControllerName';
        $actualResult = $this->extensionService->getDefaultControllerNameByPlugin('ExtensionName', 'SomePlugin');
        $this->assertEquals($expectedResult, $actualResult);
    }

    /**
     * @test
     */
    public function getDefaultActionNameByPluginAndControllerReturnsNullIfGivenExtensionCantBeFound()
    {
        $this->assertNull($this->extensionService->getDefaultActionNameByPluginAndController('NonExistingExtensionName', 'SomePlugin', 'ControllerName'));
    }

    /**
     * @test
     */
    public function getDefaultActionNameByPluginAndControllerReturnsNullIfGivenPluginCantBeFound()
    {
        $this->assertNull($this->extensionService->getDefaultActionNameByPluginAndController('ExtensionName', 'NonExistingPlugin', 'ControllerName'));
    }

    /**
     * @test
     */
    public function getDefaultActionNameByPluginAndControllerReturnsNullIfGivenControllerCantBeFound()
    {
        $this->assertNull($this->extensionService->getDefaultActionNameByPluginAndController('ExtensionName', 'SomePlugin', 'NonExistingControllerName'));
    }

    /**
     * @test
     */
    public function getDefaultActionNameByPluginAndControllerReturnsFirstActionNameOfGivenController()
    {
        $expectedResult = 'someAction';
        $actualResult = $this->extensionService->getDefaultActionNameByPluginAndController('SomeOtherExtensionName', 'SecondPlugin', 'SecondControllerName');
        $this->assertEquals($expectedResult, $actualResult);
    }
}
