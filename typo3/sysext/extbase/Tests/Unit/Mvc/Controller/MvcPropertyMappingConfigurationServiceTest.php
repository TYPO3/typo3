<?php
namespace TYPO3\CMS\Extbase\Tests\Unit\Mvc\Controller;

/*                                                                        *
 * This script belongs to the Extbase framework                           *
 *                                                                        *
 * It is free software; you can redistribute it and/or modify it under    *
 * the terms of the GNU Lesser General Public License as published by the *
 * Free Software Foundation, either version 3 of the License, or (at your *
 * option) any later version.                                             *
 *                                                                        *
 * This script is distributed in the hope that it will be useful, but     *
 * WITHOUT ANY WARRANTY; without even the implied warranty of MERCHAN-    *
 * TABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the GNU Lesser       *
 * General Public License for more details.                               *
 *                                                                        *
 * You should have received a copy of the GNU Lesser General Public       *
 * License along with the script.                                         *
 * If not, see http://www.gnu.org/licenses/lgpl.html                      *
 *                                                                        *
 * The TYPO3 project - inspiring people to share!                         *
 *                                                                        */

/**
 * Test case
 */
class MvcPropertyMappingConfigurationServiceTest extends \TYPO3\CMS\Core\Tests\UnitTestCase
{
    /**
     * Data provider for generating the list of trusted properties
     *
     * @return array
     */
    public function dataProviderForgenerateTrustedPropertiesToken()
    {
        return [
            'Simple Case - Empty' => [
                [],
                [],
            ],
            'Simple Case - Single Value' => [
                ['field1'],
                ['field1' => 1],
            ],
            'Simple Case - Two Values' => [
                ['field1', 'field2'],
                [
                    'field1' => 1,
                    'field2' => 1
                ],
            ],
            'Recursion' => [
                ['field1', 'field[subfield1]', 'field[subfield2]'],
                [
                    'field1' => 1,
                    'field' => [
                        'subfield1' => 1,
                        'subfield2' => 1
                    ]
                ],
            ],
            'recursion with duplicated field name' => [
                ['field1', 'field[subfield1]', 'field[subfield2]', 'field1'],
                [
                    'field1' => 1,
                    'field' => [
                        'subfield1' => 1,
                        'subfield2' => 1
                    ]
                ],
            ],
            'Recursion with un-named fields at the end (...[]). There, they should be made explicit by increasing the counter' => [
                ['field1', 'field[subfield1][]', 'field[subfield1][]', 'field[subfield2]'],
                [
                    'field1' => 1,
                    'field' => [
                        'subfield1' => [
                            0 => 1,
                            1 => 1
                        ],
                        'subfield2' => 1
                    ]
                ],
            ],
        ];
    }

    /**
     * Data Provider for invalid values in generating the list of trusted properties,
     * which should result in an exception
     *
     * @return array
     */
    public function dataProviderForgenerateTrustedPropertiesTokenWithUnallowedValues()
    {
        return [
            'Overriding form fields (string overridden by array) - 1' => [
                ['field1', 'field2', 'field2[bla]', 'field2[blubb]'],
            ],
            'Overriding form fields (string overridden by array) - 2' => [
                ['field1', 'field2[bla]', 'field2[bla][blubb][blubb]'],
            ],
            'Overriding form fields (array overridden by string) - 1' => [
                ['field1', 'field2[bla]', 'field2[blubb]', 'field2'],
            ],
            'Overriding form fields (array overridden by string) - 2' => [
                ['field1', 'field2[bla][blubb][blubb]', 'field2[bla]'],
            ],
            'Empty [] not as last argument' => [
                ['field1', 'field2[][bla]'],
            ]

        ];
    }

    /**
     * @test
     * @dataProvider dataProviderForgenerateTrustedPropertiesToken
     */
    public function generateTrustedPropertiesTokenGeneratesTheCorrectHashesInNormalOperation($input, $expected)
    {
        $requestHashService = $this->getMock(\TYPO3\CMS\Extbase\Mvc\Controller\MvcPropertyMappingConfigurationService::class, ['serializeAndHashFormFieldArray']);
        $requestHashService->expects($this->once())->method('serializeAndHashFormFieldArray')->with($expected);
        $requestHashService->generateTrustedPropertiesToken($input);
    }

    /**
     * @test
     * @dataProvider dataProviderForgenerateTrustedPropertiesTokenWithUnallowedValues
     * @expectedException \TYPO3\CMS\Extbase\Security\Exception\InvalidArgumentForHashGenerationException
     */
    public function generateTrustedPropertiesTokenThrowsExceptionInWrongCases($input)
    {
        $requestHashService = $this->getMock(\TYPO3\CMS\Extbase\Mvc\Controller\MvcPropertyMappingConfigurationService::class, ['serializeAndHashFormFieldArray']);
        $requestHashService->generateTrustedPropertiesToken($input);
    }

    /**
     * @test
     */
    public function serializeAndHashFormFieldArrayWorks()
    {
        $formFieldArray = [
            'bla' => [
                'blubb' => 1,
                'hu' => 1
            ]
        ];
        $mockHash = '12345';

        $hashService = $this->getMock($this->buildAccessibleProxy(\TYPO3\CMS\Extbase\Mvc\Controller\MvcPropertyMappingConfigurationService::class), ['appendHmac']);
        $hashService->expects($this->once())->method('appendHmac')->with(serialize($formFieldArray))->will($this->returnValue(serialize($formFieldArray) . $mockHash));

        $requestHashService = $this->getMock($this->buildAccessibleProxy(\TYPO3\CMS\Extbase\Mvc\Controller\MvcPropertyMappingConfigurationService::class), ['dummy']);
        $requestHashService->_set('hashService', $hashService);

        $expected = serialize($formFieldArray) . $mockHash;
        $actual = $requestHashService->_call('serializeAndHashFormFieldArray', $formFieldArray);
        $this->assertEquals($expected, $actual);
    }

    /**
     * @test
     */
    public function initializePropertyMappingConfigurationDoesNothingIfTrustedPropertiesAreNotSet()
    {
        $request = $this->getMockBuilder(\TYPO3\CMS\Extbase\Mvc\Request::class)->setMethods(['getInternalArgument'])->disableOriginalConstructor()->getMock();
        $request->expects($this->any())->method('getInternalArgument')->with('__trustedProperties')->will($this->returnValue(null));
        $arguments = new \TYPO3\CMS\Extbase\Mvc\Controller\Arguments();

        $requestHashService = new \TYPO3\CMS\Extbase\Mvc\Controller\MvcPropertyMappingConfigurationService;
        $requestHashService->initializePropertyMappingConfigurationFromRequest($request, $arguments);
    }

    /**
     * @test
     */
    public function initializePropertyMappingConfigurationReturnsEarlyIfNoTrustedPropertiesAreSet()
    {
        $trustedProperties = [
            'foo' => 1
        ];
        $this->initializePropertyMappingConfiguration($trustedProperties);
    }

    /**
     * @test
     */
    public function initializePropertyMappingConfigurationReturnsEarlyIfArgumentIsUnknown()
    {
        $trustedProperties = [
            'nonExistingArgument' => 1
        ];
        $arguments = $this->initializePropertyMappingConfiguration($trustedProperties);
        $this->assertFalse($arguments->hasArgument('nonExistingArgument'));
    }

    /**
     * @test
     */
    public function initializePropertyMappingConfigurationSetsModificationAllowedIfIdentityPropertyIsSet()
    {
        $trustedProperties = [
            'foo' => [
                '__identity' => 1,
                'nested' => [
                    '__identity' => 1,
                ]
            ]
        ];
        $arguments = $this->initializePropertyMappingConfiguration($trustedProperties);
        $propertyMappingConfiguration = $arguments->getArgument('foo')->getPropertyMappingConfiguration();
        $this->assertTrue($propertyMappingConfiguration->getConfigurationValue(\TYPO3\CMS\Extbase\Property\TypeConverter\PersistentObjectConverter::class, \TYPO3\CMS\Extbase\Property\TypeConverter\PersistentObjectConverter::CONFIGURATION_MODIFICATION_ALLOWED), 'ConfigurationValue is not CONFIGURATION_MODIFICATION_ALLOWED at line ' . __LINE__);
        $this->assertNull($propertyMappingConfiguration->getConfigurationValue(\TYPO3\CMS\Extbase\Property\TypeConverter\PersistentObjectConverter::class, \TYPO3\CMS\Extbase\Property\TypeConverter\PersistentObjectConverter::CONFIGURATION_CREATION_ALLOWED), 'ConfigurationValue is not NULL at line ' . __LINE__);
        $this->assertFalse($propertyMappingConfiguration->shouldMap('someProperty'), 'Value is not FALSE at line ' . __LINE__);

        $this->assertTrue($propertyMappingConfiguration->forProperty('nested')->getConfigurationValue(\TYPO3\CMS\Extbase\Property\TypeConverter\PersistentObjectConverter::class, \TYPO3\CMS\Extbase\Property\TypeConverter\PersistentObjectConverter::CONFIGURATION_MODIFICATION_ALLOWED), 'ConfigurationValue is not CONFIGURATION_MODIFICATION_ALLOWED at line ' . __LINE__);
        $this->assertNull($propertyMappingConfiguration->forProperty('nested')->getConfigurationValue(\TYPO3\CMS\Extbase\Property\TypeConverter\PersistentObjectConverter::class, \TYPO3\CMS\Extbase\Property\TypeConverter\PersistentObjectConverter::CONFIGURATION_CREATION_ALLOWED), 'ConfigurationValue is not NULL at line ' . __LINE__);
        $this->assertFalse($propertyMappingConfiguration->forProperty('nested')->shouldMap('someProperty'), 'Value is not FALSE at line ' . __LINE__);
    }

    /**
     * @test
     */
    public function initializePropertyMappingConfigurationSetsCreationAllowedIfIdentityPropertyIsNotSet()
    {
        $trustedProperties = [
            'foo' => [
                'bar' => []
            ]
        ];
        $arguments = $this->initializePropertyMappingConfiguration($trustedProperties);
        $propertyMappingConfiguration = $arguments->getArgument('foo')->getPropertyMappingConfiguration();
        $this->assertNull($propertyMappingConfiguration->getConfigurationValue(\TYPO3\CMS\Extbase\Property\TypeConverter\PersistentObjectConverter::class, \TYPO3\CMS\Extbase\Property\TypeConverter\PersistentObjectConverter::CONFIGURATION_MODIFICATION_ALLOWED));
        $this->assertTrue($propertyMappingConfiguration->getConfigurationValue(\TYPO3\CMS\Extbase\Property\TypeConverter\PersistentObjectConverter::class, \TYPO3\CMS\Extbase\Property\TypeConverter\PersistentObjectConverter::CONFIGURATION_CREATION_ALLOWED));
        $this->assertFalse($propertyMappingConfiguration->shouldMap('someProperty'));

        $this->assertNull($propertyMappingConfiguration->forProperty('bar')->getConfigurationValue(\TYPO3\CMS\Extbase\Property\TypeConverter\PersistentObjectConverter::class, \TYPO3\CMS\Extbase\Property\TypeConverter\PersistentObjectConverter::CONFIGURATION_MODIFICATION_ALLOWED));
        $this->assertTrue($propertyMappingConfiguration->forProperty('bar')->getConfigurationValue(\TYPO3\CMS\Extbase\Property\TypeConverter\PersistentObjectConverter::class, \TYPO3\CMS\Extbase\Property\TypeConverter\PersistentObjectConverter::CONFIGURATION_CREATION_ALLOWED));
        $this->assertFalse($propertyMappingConfiguration->forProperty('bar')->shouldMap('someProperty'));
    }

    /**
     * @test
     */
    public function initializePropertyMappingConfigurationSetsAllowedFields()
    {
        $trustedProperties = [
            'foo' => [
                'bar' => 1
            ]
        ];
        $arguments = $this->initializePropertyMappingConfiguration($trustedProperties);
        $propertyMappingConfiguration = $arguments->getArgument('foo')->getPropertyMappingConfiguration();
        $this->assertFalse($propertyMappingConfiguration->shouldMap('someProperty'));
        $this->assertTrue($propertyMappingConfiguration->shouldMap('bar'));
    }

    /**
     * @test
     */
    public function initializePropertyMappingConfigurationSetsAllowedFieldsRecursively()
    {
        $trustedProperties = [
            'foo' => [
                'bar' => [
                    'foo' => 1
                ]
            ]
        ];
        $arguments = $this->initializePropertyMappingConfiguration($trustedProperties);
        $propertyMappingConfiguration = $arguments->getArgument('foo')->getPropertyMappingConfiguration();
        $this->assertFalse($propertyMappingConfiguration->shouldMap('someProperty'));
        $this->assertTrue($propertyMappingConfiguration->shouldMap('bar'));
        $this->assertTrue($propertyMappingConfiguration->forProperty('bar')->shouldMap('foo'));
    }

    /**
     * Helper which initializes the property mapping configuration and returns arguments
     *
     * @param array $trustedProperties
     *
     * @return \TYPO3\CMS\Extbase\Mvc\Controller\Arguments
     */
    protected function initializePropertyMappingConfiguration(array $trustedProperties)
    {
        $request = $this->getMockBuilder(\TYPO3\CMS\Extbase\Mvc\Request::class)->setMethods(['getInternalArgument'])->disableOriginalConstructor()->getMock();
        $request->expects($this->any())->method('getInternalArgument')->with('__trustedProperties')->will($this->returnValue('fooTrustedProperties'));

        $mockHashService = $this->getMock(\TYPO3\CMS\Extbase\Security\Cryptography\HashService::class, ['validateAndStripHmac']);
        $mockHashService->expects($this->once())->method('validateAndStripHmac')->with('fooTrustedProperties')->will($this->returnValue(serialize($trustedProperties)));

        $requestHashService = $this->getAccessibleMock(\TYPO3\CMS\Extbase\Mvc\Controller\MvcPropertyMappingConfigurationService::class, ['dummy']);
        $requestHashService->_set('hashService', $mockHashService);

        $mockObjectManager = $this->getMock(\TYPO3\CMS\Extbase\Object\ObjectManagerInterface::class);
        $mockArgument = $this->getAccessibleMock(\TYPO3\CMS\Extbase\Mvc\Controller\Argument::class, ['getName'], [], '', false);

        $propertyMappingConfiguration = new \TYPO3\CMS\Extbase\Mvc\Controller\MvcPropertyMappingConfiguration();

        $mockArgument->_set('propertyMappingConfiguration', $propertyMappingConfiguration);
        $mockArgument->expects($this->any())->method('getName')->will($this->returnValue('foo'));
        $mockObjectManager->expects($this->once())->method('get')->with(\TYPO3\CMS\Extbase\Mvc\Controller\Argument::class)->will($this->returnValue($mockArgument));

        $arguments = $this->getAccessibleMock(\TYPO3\CMS\Extbase\Mvc\Controller\Arguments::class, ['dummy']);
        $arguments->_set('objectManager', $mockObjectManager);
        $arguments->addNewArgument('foo');

        $requestHashService->initializePropertyMappingConfigurationFromRequest($request, $arguments);

        return $arguments;
    }
}
