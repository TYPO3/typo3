<?php

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

namespace TYPO3\CMS\Extbase\Tests\Unit\Mvc\Controller;

use TYPO3\CMS\Core\Error\Http\BadRequestException;
use TYPO3\CMS\Extbase\Mvc\Controller\Argument;
use TYPO3\CMS\Extbase\Mvc\Controller\Arguments;
use TYPO3\CMS\Extbase\Mvc\Controller\MvcPropertyMappingConfiguration;
use TYPO3\CMS\Extbase\Mvc\Controller\MvcPropertyMappingConfigurationService;
use TYPO3\CMS\Extbase\Mvc\Request;
use TYPO3\CMS\Extbase\Object\ObjectManagerInterface;
use TYPO3\CMS\Extbase\Property\TypeConverter\PersistentObjectConverter;
use TYPO3\CMS\Extbase\Security\Cryptography\HashService;
use TYPO3\CMS\Extbase\Security\Exception\InvalidArgumentForHashGenerationException;
use TYPO3\TestingFramework\Core\Unit\UnitTestCase;

/**
 * Test case
 */
class MvcPropertyMappingConfigurationServiceTest extends UnitTestCase
{
    /**
     * Data provider for generating the list of trusted properties
     *
     * @return array
     */
    public function dataProviderForGenerateTrustedPropertiesToken()
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
    public function dataProviderForGenerateTrustedPropertiesTokenWithUnallowedValues()
    {
        return [
            'Overriding form fields (string overridden by array) - 1' => [
                ['field1', 'field2', 'field2[bla]', 'field2[blubb]'],
                1255072196
            ],
            'Overriding form fields (string overridden by array) - 2' => [
                ['field1', 'field2[bla]', 'field2[bla][blubb][blubb]'],
                1255072196
            ],
            'Overriding form fields (array overridden by string) - 1' => [
                ['field1', 'field2[bla]', 'field2[blubb]', 'field2'],
                1255072587
            ],
            'Overriding form fields (array overridden by string) - 2' => [
                ['field1', 'field2[bla][blubb][blubb]', 'field2[bla]'],
                1255072587
            ],
            'Empty [] not as last argument' => [
                ['field1', 'field2[][bla]'],
                1255072832
            ]

        ];
    }

    /**
     * @test
     * @dataProvider dataProviderForGenerateTrustedPropertiesToken
     */
    public function generateTrustedPropertiesTokenGeneratesTheCorrectHashesInNormalOperation($input, $expected)
    {
        $requestHashService = $this->getMockBuilder(MvcPropertyMappingConfigurationService::class)
            ->setMethods(['serializeAndHashFormFieldArray'])
            ->getMock();
        $requestHashService->expects(self::once())->method('serializeAndHashFormFieldArray')->with($expected);
        $requestHashService->generateTrustedPropertiesToken($input);
    }

    /**
     * @param $input
     * @param $expectExceptionCode
     * @test
     * @dataProvider dataProviderForGenerateTrustedPropertiesTokenWithUnallowedValues
     */
    public function generateTrustedPropertiesTokenThrowsExceptionInWrongCases($input, $expectExceptionCode)
    {
        $this->expectException(InvalidArgumentForHashGenerationException::class);
        $this->expectExceptionCode($expectExceptionCode);
        $requestHashService = $this->getMockBuilder(MvcPropertyMappingConfigurationService::class)
            ->setMethods(['serializeAndHashFormFieldArray'])
            ->getMock();
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

        $hashService = $this->getMockBuilder(HashService::class)
            ->setMethods(['appendHmac'])
            ->getMock();
        $hashService->expects(self::once())->method('appendHmac')->with(json_encode($formFieldArray))->willReturn(json_encode($formFieldArray) . $mockHash);

        $requestHashService = $this->getAccessibleMock(MvcPropertyMappingConfigurationService::class, ['dummy']);
        $requestHashService->injectHashService($hashService);

        $expected = json_encode($formFieldArray) . $mockHash;
        $actual = $requestHashService->_call('serializeAndHashFormFieldArray', $formFieldArray);
        self::assertEquals($expected, $actual);
    }

    /**
     * @test
     */
    public function initializePropertyMappingConfigurationDoesNothingIfTrustedPropertiesAreNotSet()
    {
        $request = $this->getMockBuilder(Request::class)->setMethods(['getInternalArgument'])->disableOriginalConstructor()->getMock();
        $request->expects(self::any())->method('getInternalArgument')->with('__trustedProperties')->willReturn(null);
        $arguments = new Arguments();

        $requestHashService = new MvcPropertyMappingConfigurationService();
        $requestHashService->initializePropertyMappingConfigurationFromRequest($request, $arguments);
    }

    /**
     * @test
     */
    public function initializePropertyMappingConfigurationThrowsBadRequestExceptionOnInvalidHmac()
    {
        $this->expectException(BadRequestException::class);
        $this->expectExceptionCode(1581862822);

        $request = $this->getMockBuilder(Request::class)->setMethods(['getInternalArgument'])->disableOriginalConstructor()->getMock();
        $request->expects(self::any())->method('getInternalArgument')->with('__trustedProperties')->willReturn('string with less than 40 characters');
        $arguments = new Arguments();

        $hashService = new HashService();
        $requestHashService = new MvcPropertyMappingConfigurationService();
        $requestHashService->injectHashService($hashService);
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
        self::assertFalse($arguments->hasArgument('nonExistingArgument'));
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
        self::assertTrue($propertyMappingConfiguration->getConfigurationValue(PersistentObjectConverter::class, PersistentObjectConverter::CONFIGURATION_MODIFICATION_ALLOWED), 'ConfigurationValue is not CONFIGURATION_MODIFICATION_ALLOWED at line ' . __LINE__);
        self::assertNull($propertyMappingConfiguration->getConfigurationValue(PersistentObjectConverter::class, PersistentObjectConverter::CONFIGURATION_CREATION_ALLOWED), 'ConfigurationValue is not NULL at line ' . __LINE__);
        self::assertFalse($propertyMappingConfiguration->shouldMap('someProperty'), 'Value is not FALSE at line ' . __LINE__);

        self::assertTrue($propertyMappingConfiguration->forProperty('nested')->getConfigurationValue(PersistentObjectConverter::class, PersistentObjectConverter::CONFIGURATION_MODIFICATION_ALLOWED), 'ConfigurationValue is not CONFIGURATION_MODIFICATION_ALLOWED at line ' . __LINE__);
        self::assertNull($propertyMappingConfiguration->forProperty('nested')->getConfigurationValue(PersistentObjectConverter::class, PersistentObjectConverter::CONFIGURATION_CREATION_ALLOWED), 'ConfigurationValue is not NULL at line ' . __LINE__);
        self::assertFalse($propertyMappingConfiguration->forProperty('nested')->shouldMap('someProperty'), 'Value is not FALSE at line ' . __LINE__);
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
        self::assertNull($propertyMappingConfiguration->getConfigurationValue(PersistentObjectConverter::class, PersistentObjectConverter::CONFIGURATION_MODIFICATION_ALLOWED));
        self::assertTrue($propertyMappingConfiguration->getConfigurationValue(PersistentObjectConverter::class, PersistentObjectConverter::CONFIGURATION_CREATION_ALLOWED));
        self::assertFalse($propertyMappingConfiguration->shouldMap('someProperty'));

        self::assertNull($propertyMappingConfiguration->forProperty('bar')->getConfigurationValue(PersistentObjectConverter::class, PersistentObjectConverter::CONFIGURATION_MODIFICATION_ALLOWED));
        self::assertTrue($propertyMappingConfiguration->forProperty('bar')->getConfigurationValue(PersistentObjectConverter::class, PersistentObjectConverter::CONFIGURATION_CREATION_ALLOWED));
        self::assertFalse($propertyMappingConfiguration->forProperty('bar')->shouldMap('someProperty'));
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
        self::assertFalse($propertyMappingConfiguration->shouldMap('someProperty'));
        self::assertTrue($propertyMappingConfiguration->shouldMap('bar'));
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
        self::assertFalse($propertyMappingConfiguration->shouldMap('someProperty'));
        self::assertTrue($propertyMappingConfiguration->shouldMap('bar'));
        self::assertTrue($propertyMappingConfiguration->forProperty('bar')->shouldMap('foo'));
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
        $request = $this->getMockBuilder(Request::class)->setMethods(['getInternalArgument'])->disableOriginalConstructor()->getMock();
        $request->expects(self::any())->method('getInternalArgument')->with('__trustedProperties')->willReturn('fooTrustedProperties');

        $mockHashService = $this->getMockBuilder(HashService::class)
            ->setMethods(['validateAndStripHmac'])
            ->getMock();
        $mockHashService->expects(self::once())->method('validateAndStripHmac')->with('fooTrustedProperties')->willReturn(json_encode($trustedProperties));

        $requestHashService = $this->getAccessibleMock(MvcPropertyMappingConfigurationService::class, ['dummy']);
        $requestHashService->_set('hashService', $mockHashService);

        $mockObjectManager = $this->createMock(ObjectManagerInterface::class);
        $mockArgument = $this->getAccessibleMock(Argument::class, ['getName'], [], '', false);

        $propertyMappingConfiguration = new MvcPropertyMappingConfiguration();

        $mockArgument->_set('propertyMappingConfiguration', $propertyMappingConfiguration);
        $mockArgument->expects(self::any())->method('getName')->willReturn('foo');
        $mockObjectManager->expects(self::once())->method('get')->with(Argument::class)->willReturn($mockArgument);

        $arguments = $this->getAccessibleMock(Arguments::class, ['dummy']);
        $arguments->_set('objectManager', $mockObjectManager);
        $arguments->addNewArgument('foo');

        $requestHashService->initializePropertyMappingConfigurationFromRequest($request, $arguments);

        return $arguments;
    }
}
