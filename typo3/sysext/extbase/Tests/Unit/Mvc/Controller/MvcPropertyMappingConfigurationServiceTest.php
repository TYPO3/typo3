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

namespace TYPO3\CMS\Extbase\Tests\Unit\Mvc\Controller;

use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\DoesNotPerformAssertions;
use PHPUnit\Framework\Attributes\Test;
use TYPO3\CMS\Core\Crypto\HashService;
use TYPO3\CMS\Core\Error\Http\BadRequestException;
use TYPO3\CMS\Core\Http\ServerRequest;
use TYPO3\CMS\Extbase\Mvc\Controller\Argument;
use TYPO3\CMS\Extbase\Mvc\Controller\Arguments;
use TYPO3\CMS\Extbase\Mvc\Controller\MvcPropertyMappingConfiguration;
use TYPO3\CMS\Extbase\Mvc\Controller\MvcPropertyMappingConfigurationService;
use TYPO3\CMS\Extbase\Mvc\ExtbaseRequestParameters;
use TYPO3\CMS\Extbase\Mvc\Request;
use TYPO3\CMS\Extbase\Property\TypeConverter\PersistentObjectConverter;
use TYPO3\CMS\Extbase\Security\Exception\InvalidArgumentForHashGenerationException;
use TYPO3\CMS\Extbase\Security\HashScope;
use TYPO3\TestingFramework\Core\Unit\UnitTestCase;

final class MvcPropertyMappingConfigurationServiceTest extends UnitTestCase
{
    /**
     * Data provider for generating the list of trusted properties
     */
    public static function dataProviderForGenerateTrustedPropertiesToken(): array
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
                    'field2' => 1,
                ],
            ],
            'Recursion' => [
                ['field1', 'field[subfield1]', 'field[subfield2]'],
                [
                    'field1' => 1,
                    'field' => [
                        'subfield1' => 1,
                        'subfield2' => 1,
                    ],
                ],
            ],
            'recursion with duplicated field name' => [
                ['field1', 'field[subfield1]', 'field[subfield2]', 'field1'],
                [
                    'field1' => 1,
                    'field' => [
                        'subfield1' => 1,
                        'subfield2' => 1,
                    ],
                ],
            ],
            'Recursion with un-named fields at the end (...[]). There, they should be made explicit by increasing the counter' => [
                ['field1', 'field[subfield1][]', 'field[subfield1][]', 'field[subfield2]'],
                [
                    'field1' => 1,
                    'field' => [
                        'subfield1' => [
                            0 => 1,
                            1 => 1,
                        ],
                        'subfield2' => 1,
                    ],
                ],
            ],
        ];
    }

    /**
     * Data Provider for invalid values in generating the list of trusted properties,
     * which should result in an exception
     */
    public static function dataProviderForGenerateTrustedPropertiesTokenWithUnallowedValues(): array
    {
        return [
            'Overriding form fields (string overridden by array) - 1' => [
                ['field1', 'field2', 'field2[bla]', 'field2[blubb]'],
                1255072196,
            ],
            'Overriding form fields (string overridden by array) - 2' => [
                ['field1', 'field2[bla]', 'field2[bla][blubb][blubb]'],
                1255072196,
            ],
            'Overriding form fields (array overridden by string) - 1' => [
                ['field1', 'field2[bla]', 'field2[blubb]', 'field2'],
                1255072587,
            ],
            'Overriding form fields (array overridden by string) - 2' => [
                ['field1', 'field2[bla][blubb][blubb]', 'field2[bla]'],
                1255072587,
            ],
            'Empty [] not as last argument' => [
                ['field1', 'field2[][bla]'],
                1255072832,
            ],

        ];
    }

    #[DataProvider('dataProviderForGenerateTrustedPropertiesToken')]
    #[Test]
    public function generateTrustedPropertiesTokenGeneratesTheCorrectHashesInNormalOperation($input, $expected): void
    {
        $requestHashService = $this->getMockBuilder(MvcPropertyMappingConfigurationService::class)
            ->onlyMethods(['encodeAndHashFormFieldArray'])
            ->getMock();
        $requestHashService->expects(self::once())->method('encodeAndHashFormFieldArray')->with($expected);
        $requestHashService->generateTrustedPropertiesToken($input);
    }

    #[DataProvider('dataProviderForGenerateTrustedPropertiesTokenWithUnallowedValues')]
    #[Test]
    public function generateTrustedPropertiesTokenThrowsExceptionInWrongCases(array $input, int $expectExceptionCode): void
    {
        $this->expectException(InvalidArgumentForHashGenerationException::class);
        $this->expectExceptionCode($expectExceptionCode);
        $requestHashService = $this->getMockBuilder(MvcPropertyMappingConfigurationService::class)
            ->onlyMethods(['encodeAndHashFormFieldArray'])
            ->getMock();
        $requestHashService->generateTrustedPropertiesToken($input);
    }

    #[Test]
    public function encodeAndHashFormFieldArrayWorks(): void
    {
        $formFieldArray = [
            'bla' => [
                'blubb' => 1,
                'hu' => 1,
            ],
        ];
        $expectedHash = 'b0f49cabac3153cee385184e17925f2184d88fe6';

        $GLOBALS['TYPO3_CONF_VARS']['SYS']['encryptionKey'] = 'bar';
        $hashService = new HashService();

        $requestHashService = $this->getAccessibleMock(MvcPropertyMappingConfigurationService::class, null);
        $requestHashService->injectHashService($hashService);

        $expected = json_encode($formFieldArray) . $expectedHash;
        $actual = $requestHashService->_call('encodeAndHashFormFieldArray', $formFieldArray);
        self::assertEquals($expected, $actual);
    }

    #[Test]
    #[DoesNotPerformAssertions]
    public function initializePropertyMappingConfigurationDoesNothingIfTrustedPropertiesAreNotSet(): void
    {
        $extbaseAttribute = (new ExtbaseRequestParameters())->setArgument('__trustedProperties', null);
        $coreRequest = (new ServerRequest())->withAttribute('extbase', $extbaseAttribute);
        $extbaseRequest = (new Request($coreRequest));

        $arguments = new Arguments();
        $requestHashService = new MvcPropertyMappingConfigurationService();
        $requestHashService->initializePropertyMappingConfigurationFromRequest($extbaseRequest, $arguments);
    }

    #[Test]
    public function initializePropertyMappingConfigurationThrowsBadRequestExceptionOnInvalidHmac(): void
    {
        $this->expectException(BadRequestException::class);
        $this->expectExceptionCode(1581862822);

        $extbaseAttribute = (new ExtbaseRequestParameters())->setArgument('__trustedProperties', 'string with less than 40 characters');
        $coreRequest = (new ServerRequest())->withAttribute('extbase', $extbaseAttribute);
        $extbaseRequest = (new Request($coreRequest));

        $arguments = new Arguments();
        $hashService = new HashService();
        $requestHashService = new MvcPropertyMappingConfigurationService();
        $requestHashService->injectHashService($hashService);
        $requestHashService->initializePropertyMappingConfigurationFromRequest($extbaseRequest, $arguments);
    }

    #[Test]
    public function initializePropertyMappingConfigurationWithNonDecodableTrustedPropertiesThrowsException(): void
    {
        $hashService = new HashService();
        $GLOBALS['TYPO3_CONF_VARS']['SYS']['encryptionKey'] = 'bar';
        $extbaseAttribute = (new ExtbaseRequestParameters())->setArgument('__trustedProperties', 'garbage' . $hashService->hmac('garbage', HashScope::TrustedProperties->prefix()));
        $coreRequest = (new ServerRequest())->withAttribute('extbase', $extbaseAttribute);
        $extbaseRequest = (new Request($coreRequest));

        $arguments = new Arguments();
        $requestHashService = new MvcPropertyMappingConfigurationService();
        $requestHashService->injectHashService($hashService);

        $this->expectException(BadRequestException::class);
        $this->expectExceptionMessage('The HMAC of the form could not be utilized.');
        $this->expectExceptionCode(1691267306);

        $requestHashService->initializePropertyMappingConfigurationFromRequest($extbaseRequest, $arguments);
    }

    #[Test]
    public function initializePropertyMappingConfigurationWithOutdatedTrustedPropertiesThrowsException(): void
    {
        $hashService = new HashService();
        $GLOBALS['TYPO3_CONF_VARS']['SYS']['encryptionKey'] = 'bar';
        $extbaseAttribute = (new ExtbaseRequestParameters())->setArgument('__trustedProperties', 'a:1:{s:3:"foo";s:3:"bar";}' . $hashService->hmac('a:1:{s:3:"foo";s:3:"bar";}', HashScope::TrustedProperties->prefix()));
        $coreRequest = (new ServerRequest())->withAttribute('extbase', $extbaseAttribute);
        $extbaseRequest = (new Request($coreRequest));

        $arguments = new Arguments();
        $requestHashService = new MvcPropertyMappingConfigurationService();
        $requestHashService->injectHashService($hashService);

        $this->expectException(BadRequestException::class);
        $this->expectExceptionMessage('Trusted properties used outdated serialization format instead json.');
        $this->expectExceptionCode(1699604555);

        $requestHashService->initializePropertyMappingConfigurationFromRequest($extbaseRequest, $arguments);
    }

    #[Test]
    public function initializePropertyMappingConfigurationReturnsEarlyIfArgumentIsUnknown(): void
    {
        $trustedProperties = [
            'nonExistingArgument' => 1,
        ];
        $arguments = $this->initializePropertyMappingConfiguration($trustedProperties);
        self::assertFalse($arguments->hasArgument('nonExistingArgument'));
    }

    #[Test]
    public function initializePropertyMappingConfigurationSetsModificationAllowedIfIdentityPropertyIsSet(): void
    {
        $trustedProperties = [
            'foo' => [
                '__identity' => 1,
                'nested' => [
                    '__identity' => 1,
                ],
            ],
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

    #[Test]
    public function initializePropertyMappingConfigurationSetsCreationAllowedIfIdentityPropertyIsNotSet(): void
    {
        $trustedProperties = [
            'foo' => [
                'bar' => [],
            ],
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

    #[Test]
    public function initializePropertyMappingConfigurationSetsAllowedFields(): void
    {
        $trustedProperties = [
            'foo' => [
                'bar' => 1,
            ],
        ];
        $arguments = $this->initializePropertyMappingConfiguration($trustedProperties);
        $propertyMappingConfiguration = $arguments->getArgument('foo')->getPropertyMappingConfiguration();
        self::assertFalse($propertyMappingConfiguration->shouldMap('someProperty'));
        self::assertTrue($propertyMappingConfiguration->shouldMap('bar'));
    }

    #[Test]
    public function initializePropertyMappingConfigurationSetsAllowedFieldsRecursively(): void
    {
        $trustedProperties = [
            'foo' => [
                'bar' => [
                    'foo' => 1,
                ],
            ],
        ];
        $arguments = $this->initializePropertyMappingConfiguration($trustedProperties);
        $propertyMappingConfiguration = $arguments->getArgument('foo')->getPropertyMappingConfiguration();
        self::assertFalse($propertyMappingConfiguration->shouldMap('someProperty'));
        self::assertTrue($propertyMappingConfiguration->shouldMap('bar'));
        self::assertTrue($propertyMappingConfiguration->forProperty('bar')->shouldMap('foo'));
    }

    /**
     * Helper which initializes the property mapping configuration and returns arguments
     */
    protected function initializePropertyMappingConfiguration(array $trustedProperties): Arguments
    {
        $GLOBALS['TYPO3_CONF_VARS']['SYS']['encryptionKey'] = 'bar';
        $hashService = new HashService();
        $trustedPropertiesToken = $hashService->appendHmac(json_encode($trustedProperties), HashScope::TrustedProperties->prefix());

        $extbaseAttribute = (new ExtbaseRequestParameters())->setArgument('__trustedProperties', $trustedPropertiesToken);
        $coreRequest = (new ServerRequest())->withAttribute('extbase', $extbaseAttribute);
        $extbaseRequest = (new Request($coreRequest));

        $requestHashService = $this->getAccessibleMock(MvcPropertyMappingConfigurationService::class, null);
        $requestHashService->_set('hashService', $hashService);

        $mockArgument = $this->getAccessibleMock(Argument::class, ['getName'], [], '', false);

        $propertyMappingConfiguration = new MvcPropertyMappingConfiguration();

        $mockArgument->_set('propertyMappingConfiguration', $propertyMappingConfiguration);
        $mockArgument->method('getName')->willReturn('foo');

        $arguments = $this->getAccessibleMock(Arguments::class, null);
        $arguments->addNewArgument('foo');

        $requestHashService->initializePropertyMappingConfigurationFromRequest($extbaseRequest, $arguments);

        return $arguments;
    }
}
