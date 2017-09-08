<?php
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

use TYPO3\CMS\Extensionmanager\Utility\ConfigurationUtility;

/**
 * Configuration utility test
 */
class ConfigurationUtilityTest extends \TYPO3\TestingFramework\Core\Unit\UnitTestCase
{
    /**
     * @test
     */
    public function getCurrentConfigurationReturnsExtensionConfigurationAsValuedConfiguration()
    {
        /** @var $configurationUtility ConfigurationUtility|\TYPO3\TestingFramework\Core\AccessibleObjectInterface|\PHPUnit_Framework_MockObject_MockObject */
        $configurationUtility = $this->getMockBuilder(ConfigurationUtility::class)
            ->setMethods(['getDefaultConfigurationFromExtConfTemplateAsValuedArray'])
            ->getMock();
        $configurationUtility
            ->expects($this->once())
            ->method('getDefaultConfigurationFromExtConfTemplateAsValuedArray')
            ->will($this->returnValue([]));
        $extensionKey = $this->getUniqueId('some-extension');

        $currentConfiguration = [
            'key1' => 'value1',
            'key2.' => [
                'subkey1' => 'value2'
            ]
        ];

        $expected = [
            'key1' => [
                'value' => 'value1',
            ],
            'key2.subkey1' => [
                'value' => 'value2',
            ],
        ];

        $GLOBALS['TYPO3_CONF_VARS']['EXT']['extConf'][$extensionKey] = serialize($currentConfiguration);
        $actual = $configurationUtility->getCurrentConfiguration($extensionKey);
        $this->assertEquals($expected, $actual);
    }

    /**
     * @test
     */
    public function getCurrentConfigurationReturnsExtensionConfigurationWithExtconfBasedConfigurationPrioritized()
    {
        /** @var $configurationUtility \TYPO3\CMS\Extensionmanager\Utility\ConfigurationUtility|\TYPO3\TestingFramework\Core\AccessibleObjectInterface|\PHPUnit_Framework_MockObject_MockObject */
        $configurationUtility = $this->getMockBuilder(\TYPO3\CMS\Extensionmanager\Utility\ConfigurationUtility::class)
            ->setMethods(['getDefaultConfigurationFromExtConfTemplateAsValuedArray'])
            ->getMock();
        $configurationUtility
            ->expects($this->once())
            ->method('getDefaultConfigurationFromExtConfTemplateAsValuedArray')
            ->will($this->returnValue([]));
        $extensionKey = $this->getUniqueId('some-extension');

        $serializedConfiguration = [
            'key1' => 'value1',
            'key2.' => [
                'subkey1' => 'somevalue'
            ]
        ];
        $currentExtconfConfiguration = [
            'key1' => 'value1',
            'key2.' => [
                'subkey1' => 'overwritten'
            ]
        ];

        $expected = [
            'key1' => [
                'value' => 'value1',
            ],
            'key2.subkey1' => [
                'value' => 'overwritten',
            ],
        ];

        $GLOBALS['TYPO3_CONF_VARS']['EXT']['extConf'][$extensionKey] = serialize($serializedConfiguration);
        $GLOBALS['TYPO3_CONF_VARS']['EXTENSIONS'][$extensionKey] = $currentExtconfConfiguration;
        $actual = $configurationUtility->getCurrentConfiguration($extensionKey);
        $this->assertEquals($expected, $actual);
    }

    /**
     * @test
     */
    public function mergeDefaultConfigurationReturnsEmptyArrayIfNoConfigurationForExtensionExists()
    {
        $configurationUtility = new ConfigurationUtility();
        $result = $configurationUtility->getCurrentConfiguration('not_existing_extension');
        self::assertSame([], $result);
    }

    /**
     * @test
     */
    public function getDefaultConfigurationFromExtConfTemplateAsValuedArrayReturnsExpectedExampleArray()
    {
        /** @var $configurationUtility \TYPO3\CMS\Extensionmanager\Utility\ConfigurationUtility|\TYPO3\TestingFramework\Core\AccessibleObjectInterface|\PHPUnit_Framework_MockObject_MockObject */
        $configurationUtility = $this->getAccessibleMock(
            \TYPO3\CMS\Extensionmanager\Utility\ConfigurationUtility::class,
            ['getDefaultConfigurationRawString']
        );
        $configurationUtility
            ->expects($this->once())
            ->method('getDefaultConfigurationRawString')
            ->will($this->returnValue('foo'));

        $tsStyleConfig = $this->getMockBuilder(\TYPO3\CMS\Core\TypoScript\ConfigurationForm::class)->getMock();

        $objectManagerMock = $this->getMockBuilder(\TYPO3\CMS\Extbase\Object\ObjectManagerInterface::class)->getMock();
        $configurationUtility->_set('objectManager', $objectManagerMock);
        $objectManagerMock
            ->expects($this->once())
            ->method('get')
            ->with(\TYPO3\CMS\Core\TypoScript\ConfigurationForm::class)
            ->will($this->returnValue($tsStyleConfig));

        $constants = [
            'checkConfigurationFE' => [
                'cat' => 'basic',
                'subcat_name' => 'enable',
                'subcat' => 'a/enable/z',
                'type' => 'user[TYPO3\\CMS\\Saltedpasswords\\Utility\\ExtensionManagerConfigurationUtility->checkConfigurationFrontend]',
                'label' => 'Frontend configuration check',
                'name' => 'checkConfigurationFE',
                'value' => '0',
                'default_value' => '0'
            ],
            'BE.forceSalted' => [
                'cat' => 'advancedbackend',
                'subcat' => 'x/z',
                'type' => 'boolean',
                'label' => 'Force salted passwords: Enforce usage of SaltedPasswords. Old MD5 hashed passwords will stop working.',
                'name' => 'BE.forceSalted',
                'value' => '0',
                'default_value' => '0'
            ]
        ];
        $tsStyleConfig
            ->expects($this->once())
            ->method('ext_initTSstyleConfig')
            ->will($this->returnValue($constants));

        $expected = [
            'checkConfigurationFE' => [
                'cat' => 'basic',
                'subcat_name' => 'enable',
                'subcat' => 'a/enable/z',
                'type' => 'user[TYPO3\\CMS\\Saltedpasswords\\Utility\\ExtensionManagerConfigurationUtility->checkConfigurationFrontend]',
                'label' => 'Frontend configuration check',
                'name' => 'checkConfigurationFE',
                'value' => '0',
                'default_value' => '0',
                'subcat_label' => 'Enable features',
            ],
            'BE.forceSalted' => [
                'cat' => 'advancedbackend',
                'subcat' => 'x/z',
                'type' => 'boolean',
                'label' => 'Force salted passwords: Enforce usage of SaltedPasswords. Old MD5 hashed passwords will stop working.',
                'name' => 'BE.forceSalted',
                'value' => '0',
                'default_value' => '0',
            ],
        ];

        $result = $configurationUtility->getDefaultConfigurationFromExtConfTemplateAsValuedArray($this->getUniqueId('some_extension'));
        $this->assertEquals($expected, $result);
    }

    /**
     * Data provider for convertValuedToNestedConfiguration
     *
     * @return array
     */
    public function convertValuedToNestedConfigurationDataProvider()
    {
        return [
            'plain array' => [
                [
                    'first' => [
                        'value' => 'value1'
                    ],
                    'second' => [
                        'value' => 'value2'
                    ]
                ],
                [
                    'first' => 'value1',
                    'second' => 'value2'
                ]
            ],
            'nested value with 2 levels' => [
                [
                    'first.firstSub' => [
                        'value' => 'value1'
                    ],
                    'second.secondSub' => [
                        'value' => 'value2'
                    ]
                ],
                [
                    'first.' => [
                        'firstSub' => 'value1'
                    ],
                    'second.' => [
                        'secondSub' => 'value2'
                    ]
                ]
            ],
            'nested value with 3 levels' => [
                [
                    'first.firstSub.firstSubSub' => [
                        'value' => 'value1'
                    ],
                    'second.secondSub.secondSubSub' => [
                        'value' => 'value2'
                    ]
                ],
                [
                    'first.' => [
                        'firstSub.' => [
                            'firstSubSub' => 'value1'
                        ]
                    ],
                    'second.' => [
                        'secondSub.' => [
                            'secondSubSub' => 'value2'
                        ]
                    ]
                ]
            ],
            'mixed nested value with 2 levels' => [
                [
                    'first' => [
                        'value' => 'firstValue'
                    ],
                    'first.firstSub' => [
                        'value' => 'value1'
                    ],
                    'second.secondSub' => [
                        'value' => 'value2'
                    ]
                ],
                [
                    'first' => 'firstValue',
                    'first.' => [
                        'firstSub' => 'value1'
                    ],
                    'second.' => [
                        'secondSub' => 'value2'
                    ]
                ]
            ]
        ];
    }

    /**
     * @test
     * @dataProvider convertValuedToNestedConfigurationDataProvider
     *
     * @param array $configuration
     * @param array $expected
     */
    public function convertValuedToNestedConfiguration(array $configuration, array $expected)
    {
        /** @var $subject \TYPO3\CMS\Extensionmanager\Utility\ConfigurationUtility|\PHPUnit_Framework_MockObject_MockObject|\TYPO3\TestingFramework\Core\AccessibleObjectInterface */
        $subject = $this->getAccessibleMock(\TYPO3\CMS\Extensionmanager\Utility\ConfigurationUtility::class, ['dummy'], [], '', false);
        $this->assertEquals($expected, $subject->convertValuedToNestedConfiguration($configuration));
    }

    /**
     * Data provider for convertNestedToValuedConfiguration
     *
     * @return array
     */
    public function convertNestedToValuedConfigurationDataProvider()
    {
        return [
            'plain array' => [
                [
                    'first' => 'value1',
                    'second' => 'value2'
                ],
                [
                    'first' => ['value' => 'value1'],
                    'second' => ['value' => 'value2'],
                ]
            ],
            'two levels' => [
                [
                    'first.' => ['firstSub' => 'value1'],
                    'second.' => ['firstSub' => 'value2'],
                ],
                [
                    'first.firstSub' => ['value' => 'value1'],
                    'second.firstSub' => ['value' => 'value2'],
                ]
            ],
            'three levels' => [
                [
                    'first.' => ['firstSub.' => ['firstSubSub' => 'value1']],
                    'second.' => ['firstSub.' => ['firstSubSub' => 'value2']]
                ],
                [
                    'first.firstSub.firstSubSub' => ['value' => 'value1'],
                    'second.firstSub.firstSubSub' => ['value' => 'value2'],
                ]
            ],
            'mixed' => [
                [
                    'first.' => ['firstSub' => 'value1'],
                    'second.' => ['firstSub.' => ['firstSubSub' => 'value2']],
                    'third' => 'value3'
                ],
                [
                    'first.firstSub' => ['value' => 'value1'],
                    'second.firstSub.firstSubSub' => ['value' => 'value2'],
                    'third' => ['value' => 'value3']
                ]
            ]
        ];
    }

    /**
     * @test
     * @dataProvider convertNestedToValuedConfigurationDataProvider
     *
     * @param array $configuration
     * @param array $expected
     */
    public function convertNestedToValuedConfiguration(array $configuration, array $expected)
    {
        /** @var $subject \TYPO3\CMS\Extensionmanager\Utility\ConfigurationUtility|\PHPUnit_Framework_MockObject_MockObject|\TYPO3\TestingFramework\Core\AccessibleObjectInterface */
        $subject = $this->getAccessibleMock(\TYPO3\CMS\Extensionmanager\Utility\ConfigurationUtility::class, ['dummy'], [], '', false);
        $this->assertEquals($expected, $subject->convertNestedToValuedConfiguration($configuration));
    }
}
