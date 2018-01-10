<?php
namespace TYPO3\CMS\Form\Tests\Unit\Mvc\Configuration;

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

use Prophecy\Argument;
use TYPO3\CMS\Core\Configuration\Loader\YamlFileLoader;
use TYPO3\CMS\Core\Configuration\Loader\YamlFileLoader\Configuration;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Extbase\Object\ObjectManager;
use TYPO3\CMS\Form\Mvc\Configuration\ConfigurationManager;
use TYPO3\CMS\Form\Mvc\Configuration\InheritancesResolverService;

/**
 * Test case
 */
class ConfigurationManagerTest extends \TYPO3\TestingFramework\Core\Unit\UnitTestCase
{

    /**
     * @var array A backup of registered singleton instances
     */
    protected $singletonInstances = [];

    /**
     * Set up
     */
    public function setUp()
    {
        $this->singletonInstances = GeneralUtility::getSingletonInstances();
    }

    /**
     * Tear down
     */
    public function tearDown()
    {
        GeneralUtility::resetSingletonInstances($this->singletonInstances);
        parent::tearDown();
    }

    /**
     * @test
     */
    public function getConfigurationFromYamlFile()
    {
        $mockConfigurationManager = $this->getAccessibleMock(ConfigurationManager::class, [
            'getYamlSettingsFromCache',
            'setYamlSettingsIntoCache',
            'getTypoScriptSettings',
            'overrideConfigurationByTypoScript',
        ], [], '', false);

        $objectManagerProphecy = $this->prophesize(ObjectManager::class);
        GeneralUtility::setSingletonInstance(ObjectManager::class, $objectManagerProphecy->reveal());

        $configuration = new Configuration();
        $objectManagerProphecy
            ->get(Configuration::class)
            ->willReturn($configuration);

        $objectManagerProphecy
            ->get(YamlFileLoader::class, Argument::type(Configuration::class))
            ->willReturn(new YamlFileLoader($configuration));

        $objectManagerProphecy
            ->get(InheritancesResolverService::class)
            ->willReturn(new InheritancesResolverService);

        $mockConfigurationManager->_set('objectManager', $objectManagerProphecy->reveal());

        $mockConfigurationManager
            ->expects($this->any())
            ->method('getYamlSettingsFromCache')
            ->willReturn([]);

        $mockConfigurationManager
            ->expects($this->any())
            ->method('getTypoScriptSettings')
            ->willReturn([
                'yamlConfigurations' => [
                    10 => 'EXT:form/Tests/Unit/Mvc/Configuration/Fixtures/File1.yaml'
                ]
            ]);

        $mockConfigurationManager
            ->expects($this->any())
            ->method('setYamlSettingsIntoCache')
            ->willReturn(null);

        $mockConfigurationManager
            ->expects($this->any())
            ->method('overrideConfigurationByTypoScript')
            ->willReturnArgument(0);

        $input = 'form';
        $expected = [
            'config' => [
                'value9' => 'File 3',
                'value10' => 'File 4',
                'value8' => 'File 3',
                'value1' => 'File 1',
                'value4' => 'File 1',
                'value5' => 'File 1',
                'value7' => 'File 2',
                'value11' => [
                    'key1' => 'File 1',
                    'key2' => 'File 1',
                ],
                'value12' => [
                    'key1' => 'File 2',
                ],
                'value3' => 'File 1',
            ],
            'mixins' => [
                'value11Mixin' => [
                    'key1' => 'File 1',
                    'key2' => 'File 1',
                ],
                'value12Mixin1' => [
                    'key1' => 'File 2',
                ],
                'value12Mixin2' => [
                    'key2' => 'File 2',
                ],
            ],
        ];

        $this->assertSame($expected, $mockConfigurationManager->_call('getConfigurationFromYamlFile', 'form'));
    }
}
