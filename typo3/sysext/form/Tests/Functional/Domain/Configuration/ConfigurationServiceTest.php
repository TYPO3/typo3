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

namespace TYPO3\CMS\Form\Tests\Functional\Domain\Configuration;

use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Test;
use TYPO3\CMS\Core\Cache\Frontend\FrontendInterface;
use TYPO3\CMS\Core\Cache\Frontend\PhpFrontend;
use TYPO3\CMS\Form\Domain\Configuration\ConfigurationService;
use TYPO3\CMS\Form\Domain\Configuration\Exception\PropertyException;
use TYPO3\CMS\Form\Domain\Configuration\Exception\PrototypeNotFoundException;
use TYPO3\CMS\Form\Domain\Configuration\FormDefinition\Validators\ValidationDto;
use TYPO3\CMS\Form\Mvc\Configuration\ConfigurationManagerInterface;
use TYPO3\CMS\Form\Service\TranslationService;
use TYPO3\TestingFramework\Core\Functional\FunctionalTestCase;

final class ConfigurationServiceTest extends FunctionalTestCase
{
    protected bool $initializeDatabase = false;

    #[Test]
    public function getPrototypeConfigurationReturnsPrototypeConfiguration(): void
    {
        $configurationManagerMock = $this->createMock(ConfigurationManagerInterface::class);
        $configurationManagerMock->method('getConfiguration')->with(ConfigurationManagerInterface::CONFIGURATION_TYPE_YAML_SETTINGS, 'form')
            ->willReturn([
                'prototypes' => [
                    'standard' => [
                        'key' => 'value',
                    ],
                ],
            ]);
        $subject = new ConfigurationService(
            $configurationManagerMock,
            $this->createMock(TranslationService::class),
            $this->createMock(PhpFrontend::class),
            $this->createMock(PhpFrontend::class),
        );
        $expected = [
            'key' => 'value',
        ];
        self::assertSame($expected, $subject->getPrototypeConfiguration('standard'));
    }

    #[Test]
    public function getPrototypeConfigurationThrowsExceptionIfNoPrototypeFound(): void
    {
        $configurationManagerMock = $this->createMock(ConfigurationManagerInterface::class);
        $configurationManagerMock->method('getConfiguration')->with(ConfigurationManagerInterface::CONFIGURATION_TYPE_YAML_SETTINGS, 'form')
            ->willReturn([
                'prototypes' => [
                    'noStandard' => [],
                ],
            ]);
        $subject = new ConfigurationService(
            $configurationManagerMock,
            $this->createMock(TranslationService::class),
            $this->createMock(PhpFrontend::class),
            $this->createMock(PhpFrontend::class),
        );
        $this->expectException(PrototypeNotFoundException::class);
        $this->expectExceptionCode(1475924277);
        $subject->getPrototypeConfiguration('standard');
    }

    #[Test]
    public function getSelectablePrototypeNamesDefinedInFormEditorSetupReturnsPrototypes(): void
    {
        $configurationManagerMock = $this->createMock(ConfigurationManagerInterface::class);
        $configurationManagerMock->method('getConfiguration')->with(ConfigurationManagerInterface::CONFIGURATION_TYPE_YAML_SETTINGS, 'form')
            ->willReturn([
                'formManager' => [
                    'selectablePrototypesConfiguration' => [
                        0 => [
                            'identifier' => 'standard',
                        ],
                        1 => [
                            'identifier' => 'custom',
                        ],
                        'a' => [
                            'identifier' => 'custom-2',
                        ],
                    ],
                ],
            ]);
        $subject = new ConfigurationService(
            $configurationManagerMock,
            $this->createMock(TranslationService::class),
            $this->createMock(PhpFrontend::class),
            $this->createMock(PhpFrontend::class),
        );
        $expected = [
            'standard',
            'custom',
        ];
        self::assertSame($expected, $subject->getSelectablePrototypeNamesDefinedInFormEditorSetup());
    }

    public static function isFormElementPropertyDefinedInFormEditorSetupDataProvider(): array
    {
        return [
            [
                ['formElements' => ['Text' => ['propertyPaths' => ['properties.foo.1']]]],
                new ValidationDto('standard', 'Text', null, 'properties.foo.1'),
                true,
            ],
            [
                ['formElements' => ['Text' => ['additionalElementPropertyPaths' => ['properties.foo.1']]]],
                new ValidationDto('standard', 'Text', null, 'properties.foo.1'),
                true,
            ],
            [
                ['formElements' => ['Text' => ['multiValueProperties' => ['properties.foo.1']]]],
                new ValidationDto('standard', 'Text', null, 'properties.foo.1'),
                true,
            ],
            [
                ['formElements' => ['Text' => ['multiValueProperties' => ['properties.foo.1']]]],
                new ValidationDto('standard', 'Text', null, 'properties.foo.1.bar'),
                true,
            ],
            [
                ['formElements' => ['Text' => ['additionalPropertyPaths' => ['properties.foo.1']]]],
                new ValidationDto('standard', 'Text', null, 'properties.foo.1'),
                true,
            ],
            [
                ['formElements' => ['Text' => ['propertyPaths' => ['properties.foo.1']]]],
                new ValidationDto('standard', 'Foo', null, 'properties.foo.1'),
                false,
            ],
            [
                ['formElements' => ['Text' => ['propertyPaths' => ['properties.foo.1']]]],
                new ValidationDto('standard', 'Text', null, 'properties.bar.1'),
                false,
            ],
            [
                ['formElements' => ['Text' => ['additionalElementPropertyPaths' => ['properties.foo.1']]]],
                new ValidationDto('standard', 'Foo', null, 'properties.foo.1'),
                false,
            ],
            [
                ['formElements' => ['Text' => ['additionalElementPropertyPaths' => ['properties.foo.1']]]],
                new ValidationDto('standard', 'Text', null, 'properties.bar.1'),
                false,
            ],
            [
                ['formElements' => ['Text' => ['multiValueProperties' => ['properties.foo.1']]]],
                new ValidationDto('standard', 'Foo', null, 'properties.foo.1'),
                false,
            ],
            [
                ['formElements' => ['Text' => ['multiValueProperties' => ['properties.foo.1']]]],
                new ValidationDto('standard', 'Text', null, 'properties.bar.1'),
                false,
            ],
            [
                ['formElements' => ['Text' => ['multiValueProperties' => ['properties.foo.1']]]],
                new ValidationDto('standard', 'Foo', null, 'properties.foo.1.bar'),
                false,
            ],
            [
                ['formElements' => ['Text' => ['multiValueProperties' => ['properties.foo.1']]]],
                new ValidationDto('standard', 'Text', null, 'properties.bar.1.foo'),
                false,
            ],
            [
                ['formElements' => ['Text' => ['additionalPropertyPaths' => ['properties.foo.1']]]],
                new ValidationDto('standard', 'Foo', null, 'properties.foo.1'),
                false,
            ],
            [
                ['formElements' => ['Text' => ['additionalPropertyPaths' => ['properties.foo.1']]]],
                new ValidationDto('standard', 'Text', null, 'properties.bar.1'),
                false,
            ],
        ];
    }

    #[DataProvider('isFormElementPropertyDefinedInFormEditorSetupDataProvider')]
    #[Test]
    public function isFormElementPropertyDefinedInFormEditorSetup(array $configuration, ValidationDto $validationDto, bool $expectedReturn): void
    {
        $subjectMock = $this->getAccessibleMock(
            ConfigurationService::class,
            ['buildFormDefinitionValidationConfigurationFromFormEditorSetup'],
            [],
            '',
            false
        );
        $subjectMock->method('buildFormDefinitionValidationConfigurationFromFormEditorSetup')->willReturn($configuration);
        self::assertSame($expectedReturn, $subjectMock->isFormElementPropertyDefinedInFormEditorSetup($validationDto));
    }

    public static function isPropertyCollectionPropertyDefinedInFormEditorSetupDataProvider(): array
    {
        return [
            [
                ['formElements' => ['Text' => ['collections' => ['validators' => ['StringLength' => ['propertyPaths' => ['properties.foo.1']]]]]]],
                new ValidationDto('standard', 'Text', null, 'properties.foo.1', 'validators', 'StringLength'),
                true,
            ],
            [
                ['formElements' => ['Text' => ['collections' => ['validators' => ['StringLength' => ['multiValueProperties' => ['properties.foo.1']]]]]]],
                new ValidationDto('standard', 'Text', null, 'properties.foo.1', 'validators', 'StringLength'),
                true,
            ],
            [
                ['formElements' => ['Text' => ['collections' => ['validators' => ['StringLength' => ['multiValueProperties' => ['properties.foo.1']]]]]]],
                new ValidationDto('standard', 'Text', null, 'properties.foo.1.bar', 'validators', 'StringLength'),
                true,
            ],
            [
                ['formElements' => ['Text' => ['collections' => ['validators' => ['StringLength' => ['additionalPropertyPaths' => ['properties.foo.1']]]]]]],
                new ValidationDto('standard', 'Text', null, 'properties.foo.1', 'validators', 'StringLength'),
                true,
            ],
            [
                ['formElements' => ['Text' => ['collections' => ['validators' => ['StringLength' => ['propertyPaths' => ['properties.foo.1']]]]]]],
                new ValidationDto('standard', 'Text', null, 'properties.foo.2', 'validators', 'StringLength'),
                false,
            ],
            [
                ['formElements' => ['Text' => ['collections' => ['validators' => ['StringLength' => ['propertyPaths' => ['properties.foo.1']]]]]]],
                new ValidationDto('standard', 'Foo', null, 'properties.foo.1', 'validators', 'StringLength'),
                false,
            ],
            [
                ['formElements' => ['Text' => ['collections' => ['validators' => ['StringLength' => ['propertyPaths' => ['properties.foo.1']]]]]]],
                new ValidationDto('standard', 'Text', null, 'properties.foo.1', 'foo', 'StringLength'),
                false,
            ],
            [
                ['formElements' => ['Text' => ['collections' => ['validators' => ['StringLength' => ['propertyPaths' => ['properties.foo.1']]]]]]],
                new ValidationDto('standard', 'Text', null, 'properties.foo.1', 'validators', 'Foo'),
                false,
            ],
            [
                ['formElements' => ['Text' => ['collections' => ['validators' => ['StringLength' => ['multiValueProperties' => ['properties.foo.1']]]]]]],
                new ValidationDto('standard', 'Text', null, 'properties.foo.2', 'validators', 'StringLength'),
                false,
            ],
            [
                ['formElements' => ['Text' => ['collections' => ['validators' => ['StringLength' => ['multiValueProperties' => ['properties.foo.1']]]]]]],
                new ValidationDto('standard', 'Foo', null, 'properties.foo.1.bar', 'validators', 'StringLength'),
                false,
            ],
            [
                ['formElements' => ['Text' => ['collections' => ['validators' => ['StringLength' => ['multiValueProperties' => ['properties.foo.1']]]]]]],
                new ValidationDto('standard', 'Text', null, 'properties.foo.1.bar', 'foo', 'StringLength'),
                false,
            ],
            [
                ['formElements' => ['Text' => ['collections' => ['validators' => ['StringLength' => ['multiValueProperties' => ['properties.foo.1']]]]]]],
                new ValidationDto('standard', 'Text', null, 'properties.foo.1.bar', 'validators', 'Foo'),
                false,
            ],
            [
                ['formElements' => ['Text' => ['collections' => ['validators' => ['StringLength' => ['additionalPropertyPaths' => ['properties.foo.1']]]]]]],
                new ValidationDto('standard', 'Foo', null, 'properties.foo.1', 'validators', 'StringLength'),
                false,
            ],
            [
                ['formElements' => ['Text' => ['collections' => ['validators' => ['StringLength' => ['additionalPropertyPaths' => ['properties.foo.1']]]]]]],
                new ValidationDto('standard', 'Text', null, 'properties.foo.1', 'foo', 'StringLength'),
                false,
            ],
            [
                ['formElements' => ['Text' => ['collections' => ['validators' => ['StringLength' => ['additionalPropertyPaths' => ['properties.foo.1']]]]]]],
                new ValidationDto('standard', 'Text', null, 'properties.foo.1', 'validators', 'Foo'),
                false,
            ],
        ];
    }

    #[DataProvider('isPropertyCollectionPropertyDefinedInFormEditorSetupDataProvider')]
    #[Test]
    public function isPropertyCollectionPropertyDefinedInFormEditorSetup(array $configuration, ValidationDto $validationDto, bool $expectedReturn): void
    {
        $subjectMock = $this->getAccessibleMock(
            ConfigurationService::class,
            ['buildFormDefinitionValidationConfigurationFromFormEditorSetup'],
            [],
            '',
            false
        );
        $subjectMock->method('buildFormDefinitionValidationConfigurationFromFormEditorSetup')->willReturn($configuration);
        self::assertSame($expectedReturn, $subjectMock->isPropertyCollectionPropertyDefinedInFormEditorSetup($validationDto));
    }

    public static function isFormElementPropertyDefinedInPredefinedDefaultsInFormEditorSetupDataProvider(): array
    {
        return [
            [
                ['formElements' => ['Text' => ['predefinedDefaults' => ['properties.foo.1' => 'bar']]]],
                new ValidationDto('standard', 'Text', null, 'properties.foo.1'),
                true,
            ],
            [
                ['formElements' => ['Text' => ['predefinedDefaults' => ['properties.foo.1' => 'bar']]]],
                new ValidationDto('standard', 'Text', null, 'properties.foo.2'),
                false,
            ],
            [
                ['formElements' => ['Text' => ['predefinedDefaults' => ['properties.foo.1' => 'bar']]]],
                new ValidationDto('standard', 'Foo', null, 'properties.foo.1'),
                false,
            ],
        ];
    }

    #[DataProvider('isFormElementPropertyDefinedInPredefinedDefaultsInFormEditorSetupDataProvider')]
    #[Test]
    public function isFormElementPropertyDefinedInPredefinedDefaultsInFormEditorSetup(array $configuration, ValidationDto $validationDto, bool $expectedReturn): void
    {
        $subjectMock = $this->getAccessibleMock(
            ConfigurationService::class,
            ['buildFormDefinitionValidationConfigurationFromFormEditorSetup'],
            [],
            '',
            false
        );
        $subjectMock->method('buildFormDefinitionValidationConfigurationFromFormEditorSetup')->willReturn($configuration);
        self::assertSame($expectedReturn, $subjectMock->isFormElementPropertyDefinedInPredefinedDefaultsInFormEditorSetup($validationDto));
    }

    public static function isPropertyCollectionPropertyDefinedInPredefinedDefaultsInFormEditorSetupDataProvider(): array
    {
        return [
            [
                ['collections' => ['validators' => ['StringLength' => ['predefinedDefaults' => ['properties.foo.1' => 'bar']]]]],
                new ValidationDto('standard', null, null, 'properties.foo.1', 'validators', 'StringLength'),
                true,
            ],
            [
                ['collections' => ['validators' => ['StringLength' => ['predefinedDefaults' => ['properties.foo.1' => 'bar']]]]],
                new ValidationDto('standard', null, null, 'properties.foo.2', 'validators', 'StringLength'),
                false,
            ],
            [
                ['collections' => ['validators' => ['StringLength' => ['predefinedDefaults' => ['properties.foo.1' => 'bar']]]]],
                new ValidationDto('standard', null, null, 'properties.foo.1', 'foo', 'StringLength'),
                false,
            ],
            [
                ['collections' => ['validators' => ['StringLength' => ['predefinedDefaults' => ['properties.foo.1' => 'bar']]]]],
                new ValidationDto('standard', null, null, 'properties.foo.1', 'validators', 'Foo'),
                false,
            ],
        ];
    }

    #[DataProvider('isPropertyCollectionPropertyDefinedInPredefinedDefaultsInFormEditorSetupDataProvider')]
    #[Test]
    public function isPropertyCollectionPropertyDefinedInPredefinedDefaultsInFormEditorSetup(array $configuration, ValidationDto $validationDto, bool $expectedReturn): void
    {
        $subjectMock = $this->getAccessibleMock(
            ConfigurationService::class,
            ['buildFormDefinitionValidationConfigurationFromFormEditorSetup'],
            [],
            '',
            false
        );
        $subjectMock->method('buildFormDefinitionValidationConfigurationFromFormEditorSetup')->willReturn($configuration);
        $result = $subjectMock->isPropertyCollectionPropertyDefinedInPredefinedDefaultsInFormEditorSetup($validationDto);
        self::assertSame($expectedReturn, $result);
    }

    #[Test]
    public function getFormElementPredefinedDefaultValueFromFormEditorSetupThrowsExceptionIfNoPredefinedDefaultIsAvailable(): void
    {
        $this->expectException(PropertyException::class);
        $this->expectExceptionCode(1528578401);
        $subjectMock = $this->getAccessibleMock(
            ConfigurationService::class,
            ['isFormElementPropertyDefinedInPredefinedDefaultsInFormEditorSetup'],
            [],
            '',
            false
        );
        $subjectMock->method('isFormElementPropertyDefinedInPredefinedDefaultsInFormEditorSetup')->willReturn(false);
        $validationDto = new ValidationDto(null, 'Text', null, 'properties.foo.1');
        $subjectMock->getFormElementPredefinedDefaultValueFromFormEditorSetup($validationDto);
    }

    #[Test]
    public function getFormElementPredefinedDefaultValueFromFormEditorSetupReturnsDefaultValue(): void
    {
        $subjectMock = $this->getAccessibleMock(
            ConfigurationService::class,
            [
                'buildFormDefinitionValidationConfigurationFromFormEditorSetup',
                'isPropertyCollectionPropertyDefinedInPredefinedDefaultsInFormEditorSetup',
            ],
            [],
            '',
            false
        );
        $configuration = ['formElements' => ['Text' => ['predefinedDefaults' => ['properties.foo.1' => 'foo']]]];
        $subjectMock->method('buildFormDefinitionValidationConfigurationFromFormEditorSetup')->willReturn($configuration);
        $subjectMock->method('isPropertyCollectionPropertyDefinedInPredefinedDefaultsInFormEditorSetup')->willReturn(true);
        $validationDto = new ValidationDto('standard', 'Text', null, 'properties.foo.1');
        self::assertSame('foo', $subjectMock->getFormElementPredefinedDefaultValueFromFormEditorSetup($validationDto));
    }

    #[Test]
    public function getPropertyCollectionPredefinedDefaultValueFromFormEditorSetupThrowsExceptionIfNoPredefinedDefaultIsAvailable(): void
    {
        $this->expectException(PropertyException::class);
        $this->expectExceptionCode(1528578402);
        $subjectMock = $this->getAccessibleMock(
            ConfigurationService::class,
            ['isPropertyCollectionPropertyDefinedInPredefinedDefaultsInFormEditorSetup'],
            [],
            '',
            false
        );
        $subjectMock->method('isPropertyCollectionPropertyDefinedInPredefinedDefaultsInFormEditorSetup')->willReturn(false);
        $validationDto = new ValidationDto(null, null, null, 'properties.foo.1', 'validators', 'StringLength');
        $subjectMock->getPropertyCollectionPredefinedDefaultValueFromFormEditorSetup($validationDto);
    }

    #[Test]
    public function getPropertyCollectionPredefinedDefaultValueFromFormEditorSetupReturnsDefaultValue(): void
    {
        $subjectMock = $this->getAccessibleMock(
            ConfigurationService::class,
            [
                'buildFormDefinitionValidationConfigurationFromFormEditorSetup',
                'isPropertyCollectionPropertyDefinedInPredefinedDefaultsInFormEditorSetup',
            ],
            [],
            '',
            false
        );
        $configuration = ['collections' => ['validators' => ['StringLength' => ['predefinedDefaults' => ['properties.foo.1' => 'foo']]]]];
        $subjectMock->method('buildFormDefinitionValidationConfigurationFromFormEditorSetup')->willReturn($configuration);
        $subjectMock->method('isPropertyCollectionPropertyDefinedInPredefinedDefaultsInFormEditorSetup')->willReturn(true);
        $validationDto = new ValidationDto('standard', null, null, 'properties.foo.1', 'validators', 'StringLength');
        self::assertSame('foo', $subjectMock->getPropertyCollectionPredefinedDefaultValueFromFormEditorSetup($validationDto));
    }

    public static function isFormElementTypeCreatableByFormEditorDataProvider(): array
    {
        return [
            [
                [],
                new ValidationDto('standard', 'Form'),
                true,
            ],
            [
                ['formElements' => ['Text' => ['creatable' => true]]],
                new ValidationDto('standard', 'Text'),
                true,
            ],
            [
                ['formElements' => ['Text' => ['creatable' => false]]],
                new ValidationDto('standard', 'Text'),
                false,
            ],
            [
                ['formElements' => ['Foo' => ['creatable' => true]]],
                new ValidationDto('standard', 'Text'),
                false,
            ],
        ];
    }

    #[DataProvider('isFormElementTypeCreatableByFormEditorDataProvider')]
    #[Test]
    public function isFormElementTypeCreatableByFormEditor(array $configuration, ValidationDto $validationDto, bool $expectedReturn): void
    {
        $subjectMock = $this->getAccessibleMock(
            ConfigurationService::class,
            ['buildFormDefinitionValidationConfigurationFromFormEditorSetup'],
            [],
            '',
            false
        );
        $subjectMock->method('buildFormDefinitionValidationConfigurationFromFormEditorSetup')->willReturn($configuration);
        self::assertSame($expectedReturn, $subjectMock->isFormElementTypeCreatableByFormEditor($validationDto));
    }

    public static function isPropertyCollectionElementIdentifierCreatableByFormEditorDataProvider(): array
    {
        return [
            [
                ['formElements' => ['Text' => ['collections' => ['validators' => ['StringLength' => ['creatable' => true]]]]]],
                new ValidationDto('standard', 'Text', null, null, 'validators', 'StringLength'),
                true,
            ],
            [
                ['formElements' => ['Text' => ['collections' => ['validators' => ['StringLength' => ['creatable' => false]]]]]],
                new ValidationDto('standard', 'Text', null, null, 'validators', 'StringLength'),
                false,
            ],
            [
                ['formElements' => ['Foo' => ['collections' => ['validators' => ['StringLength' => ['creatable' => true]]]]]],
                new ValidationDto('standard', 'Text', null, null, 'validators', 'StringLength'),
                false,
            ],
            [
                ['formElements' => ['Text' => ['collections' => ['foo' => ['StringLength' => ['creatable' => true]]]]]],
                new ValidationDto('standard', 'Text', null, null, 'validators', 'StringLength'),
                false,
            ],
            [
                ['formElements' => ['Text' => ['collections' => ['validators' => ['Foo' => ['creatable' => true]]]]]],
                new ValidationDto('standard', 'Text', null, null, 'validators', 'StringLength'),
                false,
            ],
        ];
    }

    #[DataProvider('isPropertyCollectionElementIdentifierCreatableByFormEditorDataProvider')]
    #[Test]
    public function isPropertyCollectionElementIdentifierCreatableByFormEditor(array $configuration, ValidationDto $validationDto, bool $expectedReturn): void
    {
        $subjectMock = $this->getAccessibleMock(
            ConfigurationService::class,
            ['buildFormDefinitionValidationConfigurationFromFormEditorSetup'],
            [],
            '',
            false
        );
        $subjectMock->method('buildFormDefinitionValidationConfigurationFromFormEditorSetup')->willReturn($configuration);
        self::assertSame($expectedReturn, $subjectMock->isPropertyCollectionElementIdentifierCreatableByFormEditor($validationDto));
    }

    #[Test]
    public function isFormElementTypeDefinedInFormSetup(): void
    {
        $subjectMock = $this->getAccessibleMock(
            ConfigurationService::class,
            ['getPrototypeConfiguration'],
            [],
            '',
            false
        );
        $configuration = [
            'formElementsDefinition' => [
                'Text' => [],
            ],
        ];
        $subjectMock->method('getPrototypeConfiguration')->willReturn($configuration);
        $validationDto = new ValidationDto('standard', 'Text');
        self::assertTrue($subjectMock->isFormElementTypeDefinedInFormSetup($validationDto));
        $validationDto = new ValidationDto('standard', 'Foo');
        self::assertFalse($subjectMock->isFormElementTypeDefinedInFormSetup($validationDto));
    }

    #[Test]
    public function addAdditionalPropertyPathsFromHookThrowsExceptionIfHookResultIsNoFormDefinitionValidation(): void
    {
        $this->expectException(PropertyException::class);
        $this->expectExceptionCode(1528633966);
        $subjectMock = $this->getAccessibleMock(ConfigurationService::class, null, [], '', false);
        $input = ['dummy'];
        $subjectMock->_call('addAdditionalPropertyPathsFromHook', '', '', $input, []);
    }

    #[Test]
    public function addAdditionalPropertyPathsFromHookThrowsExceptionIfPrototypeDoesNotMatch(): void
    {
        $this->expectException(PropertyException::class);
        $this->expectExceptionCode(1528634966);
        $subjectMock = $this->getAccessibleMock(ConfigurationService::class, null, [], '', false);
        $validationDto = new ValidationDto('Bar', 'Foo');
        $input = [$validationDto];
        $subjectMock->_call('addAdditionalPropertyPathsFromHook', '', 'standard', $input, []);
    }

    #[Test]
    public function addAdditionalPropertyPathsFromHookThrowsExceptionIfFormElementTypeDoesNotMatch(): void
    {
        $this->expectException(PropertyException::class);
        $this->expectExceptionCode(1528633967);
        $subjectMock = $this->getAccessibleMock(
            ConfigurationService::class,
            ['isFormElementTypeDefinedInFormSetup'],
            [],
            '',
            false
        );
        $subjectMock->method('isFormElementTypeDefinedInFormSetup')->willReturn(false);
        $validationDto = new ValidationDto('standard', 'Text');
        $input = [$validationDto];
        $subjectMock->_call('addAdditionalPropertyPathsFromHook', '', 'standard', $input, []);
    }

    #[Test]
    public function addAdditionalPropertyPathsFromHookThrowsExceptionIfPropertyCollectionNameIsInvalid(): void
    {
        $this->expectException(PropertyException::class);
        $this->expectExceptionCode(1528636941);
        $subjectMock = $this->getAccessibleMock(
            ConfigurationService::class,
            ['isFormElementTypeDefinedInFormSetup'],
            [],
            '',
            false
        );
        $subjectMock->method('isFormElementTypeDefinedInFormSetup')->willReturn(true);
        $validationDto = new ValidationDto('standard', 'Text', null, null, 'Bar', 'Baz');
        $input = [$validationDto];
        $subjectMock->_call('addAdditionalPropertyPathsFromHook', '', 'standard', $input, []);
    }

    #[Test]
    public function addAdditionalPropertyPathsFromHookAddPaths(): void
    {
        $subjectMock = $this->getAccessibleMock(
            ConfigurationService::class,
            ['isFormElementTypeDefinedInFormSetup'],
            [],
            '',
            false
        );
        $subjectMock->method('isFormElementTypeDefinedInFormSetup')->willReturn(true);
        $input = [
            new ValidationDto('standard', 'Text', null, 'options.xxx', 'validators', 'Baz'),
            new ValidationDto('standard', 'Text', null, 'options.yyy', 'validators', 'Baz'),
            new ValidationDto('standard', 'Text', null, 'options.zzz', 'validators', 'Custom'),
            new ValidationDto('standard', 'Text', null, 'properties.xxx'),
            new ValidationDto('standard', 'Text', null, 'properties.yyy'),
            new ValidationDto('standard', 'Custom', null, 'properties.xxx'),
        ];
        $expected = [
            'formElements' => [
                'Text' => [
                    'collections' => [
                        'validators' => [
                            'Baz' => [
                                'additionalPropertyPaths' => [
                                    'options.xxx',
                                    'options.yyy',
                                ],
                            ],
                            'Custom' => [
                                'additionalPropertyPaths' => [
                                    'options.zzz',
                                ],
                            ],
                        ],
                    ],
                    'additionalPropertyPaths' => [
                        'properties.xxx',
                        'properties.yyy',
                    ],
                ],
                'Custom' => [
                    'additionalPropertyPaths' => [
                        'properties.xxx',
                    ],
                ],
            ],
        ];
        self::assertSame($expected, $subjectMock->_call('addAdditionalPropertyPathsFromHook', '', 'standard', $input, []));
    }

    public static function buildFormDefinitionValidationConfigurationFromFormEditorSetupDataProvider(): array
    {
        return [
            [
                [
                    'formElementsDefinition' => [
                        'Text' => [
                            'formEditor' => [
                                'editors' => [
                                    [
                                        'templateName' => 'Foo',
                                        'propertyPath' => 'properties.foo',
                                        'setup' => [
                                            'propertyPath' => 'properties.bar',
                                        ],
                                    ],
                                ],
                            ],
                        ],
                    ],
                ],
                [
                    'formElements' => [
                        'Text' => [
                            'propertyPaths' => [
                                'properties.foo',
                                'properties.bar',
                            ],
                        ],
                    ],
                ],
            ],

            [
                [
                    'formElementsDefinition' => [
                        'Text' => [
                            'formEditor' => [
                                'editors' => [
                                    [
                                        'templateName' => 'Inspector-GridColumnViewPortConfigurationEditor',
                                        'propertyPath' => 'properties.{@viewPortIdentifier}.foo',
                                        'configurationOptions' => [
                                            'viewPorts' => [
                                                ['viewPortIdentifier' => 'viewFoo'],
                                                ['viewPortIdentifier' => 'viewBar'],
                                            ],
                                        ],
                                    ],
                                ],
                            ],
                        ],
                    ],
                ],
                [
                    'formElements' => [
                        'Text' => [
                            'propertyPaths' => [
                                'properties.viewFoo.foo',
                                'properties.viewBar.foo',
                            ],
                        ],
                    ],
                ],
            ],

            [
                [
                    'formElementsDefinition' => [
                        'Text' => [
                            'formEditor' => [
                                'editors' => [
                                    [
                                        'additionalElementPropertyPaths' => [
                                            'properties.foo',
                                            'properties.bar',
                                        ],
                                    ],
                                ],
                            ],
                        ],
                    ],
                ],
                [
                    'formElements' => [
                        'Text' => [
                            'additionalElementPropertyPaths' => [
                                'properties.foo',
                                'properties.bar',
                            ],
                        ],
                    ],
                ],
            ],

            [
                [
                    'formElementsDefinition' => [
                        'Text' => [
                            'formEditor' => [
                                'editors' => [
                                    [
                                        'templateName' => 'Inspector-PropertyGridEditor',
                                        'propertyPath' => 'properties.foo.1',
                                    ],
                                    [
                                        'templateName' => 'Inspector-MultiSelectEditor',
                                        'propertyPath' => 'properties.foo.2',
                                    ],
                                    [
                                        'templateName' => 'Inspector-ValidationErrorMessageEditor',
                                        'propertyPath' => 'properties.foo.3',
                                    ],
                                    [
                                        'templateName' => 'Inspector-RequiredValidatorEditor',
                                        'propertyPath' => 'properties.fluidAdditionalAttributes.required',
                                        'configurationOptions' => [
                                            'validationErrorMessage' => [
                                                'propertyPath' => 'properties.validationErrorMessages',
                                            ],
                                        ],
                                    ],
                                ],
                            ],
                        ],
                    ],
                ],
                [
                    'formElements' => [
                        'Text' => [
                            'multiValueProperties' => [
                                'properties.foo.1',
                                'defaultValue',
                                'properties.foo.2',
                                'properties.foo.3',
                                'properties.validationErrorMessages',
                            ],
                            'propertyPaths' => [
                                'properties.foo.1',
                                'properties.foo.2',
                                'properties.foo.3',
                                'properties.fluidAdditionalAttributes.required',
                                'properties.validationErrorMessages',
                            ],
                        ],
                    ],
                ],
            ],

            [
                [
                    'formElementsDefinition' => [
                        'Text' => [
                            'formEditor' => [
                                'predefinedDefaults' => [
                                    'foo' => [
                                        'bar' => 'xxx',
                                    ],
                                ],
                            ],
                        ],
                    ],
                ],
                [
                    'formElements' => [
                        'Text' => [
                            'predefinedDefaults' => [
                                'foo.bar' => 'xxx',
                            ],
                            'untranslatedPredefinedDefaults' => [
                                'foo.bar' => 'xxx',
                            ],
                        ],
                    ],
                ],
            ],

            [
                [
                    'formEditor' => [
                        'formElementGroups' => [
                            'Dummy' => [],
                        ],
                    ],
                    'formElementsDefinition' => [
                        'Text' => [
                            'formEditor' => [
                                'group' => 'Dummy',
                                'groupSorting' => 10,
                            ],
                        ],
                    ],
                ],
                [
                    'formElements' => [
                        'Text' => [
                            'creatable' => true,
                        ],
                    ],
                ],
            ],

            [
                [
                    'formEditor' => [
                        'formElementGroups' => [
                            'Dummy' => [],
                        ],
                    ],
                    'formElementsDefinition' => [
                        'Text' => [
                            'formEditor' => [
                                'group' => 'Dummy',
                            ],
                        ],
                    ],
                ],
                [
                    'formElements' => [
                        'Text' => [
                            'creatable' => false,
                        ],
                    ],
                ],
            ],

            [
                [
                    'formEditor' => [
                        'formElementGroups' => [
                            'Foo' => [],
                        ],
                    ],
                    'formElementsDefinition' => [
                        'Text' => [
                            'formEditor' => [
                                'group' => 'Dummy',
                                'groupSorting' => 10,
                            ],
                        ],
                    ],
                ],
                [
                    'formElements' => [
                        'Text' => [
                            'creatable' => false,
                        ],
                    ],
                ],
            ],

            [
                [
                    'formEditor' => [
                        'formElementGroups' => [
                            'Dummy' => [],
                        ],
                    ],
                    'formElementsDefinition' => [
                        'Text' => [
                            'formEditor' => [
                                'group' => 'Foo',
                                'groupSorting' => 10,
                            ],
                        ],
                    ],
                ],
                [
                    'formElements' => [
                        'Text' => [
                            'creatable' => false,
                        ],
                    ],
                ],
            ],

            [
                [
                    'formElementsDefinition' => [
                        'Text' => [
                            'formEditor' => [
                                'editors' => [
                                    [
                                        'templateName' => 'Inspector-FinishersEditor',
                                        'selectOptions' => [
                                            [
                                                'value' => 'FooFinisher',
                                            ],
                                            [
                                                'value' => 'BarFinisher',
                                            ],
                                        ],
                                    ],
                                    [
                                        'templateName' => 'Inspector-ValidatorsEditor',
                                        'selectOptions' => [
                                            [
                                                'value' => 'FooValidator',
                                            ],
                                            [
                                                'value' => 'BarValidator',
                                            ],
                                        ],
                                    ],
                                    [
                                        'templateName' => 'Inspector-RequiredValidatorEditor',
                                        'validatorIdentifier' => 'NotEmpty',
                                    ],
                                ],
                            ],
                        ],
                    ],
                ],
                [
                    'formElements' => [
                        'Text' => [
                            'collections' => [
                                'finishers' => [
                                    'FooFinisher' => [
                                        'creatable' => true,
                                    ],
                                    'BarFinisher' => [
                                        'creatable' => true,
                                    ],
                                ],
                                'validators' => [
                                    'FooValidator' => [
                                        'creatable' => true,
                                    ],
                                    'BarValidator' => [
                                        'creatable' => true,
                                    ],
                                    'NotEmpty' => [
                                        'creatable' => true,
                                    ],
                                ],
                            ],
                            'selectOptions' => [
                                '_finishers' => [
                                    'FooFinisher',
                                    'BarFinisher',
                                ],
                                '_validators' => [
                                    'FooValidator',
                                    'BarValidator',
                                ],
                            ],
                            'untranslatedSelectOptions' => [
                                '_finishers' => [
                                    'FooFinisher',
                                    'BarFinisher',
                                ],
                                '_validators' => [
                                    'FooValidator',
                                    'BarValidator',
                                ],
                            ],
                        ],
                    ],
                ],
            ],

            [
                [
                    'formElementsDefinition' => [
                        'Text' => [
                            'formEditor' => [
                                'propertyCollections' => [
                                    'validators' => [
                                        [
                                            'identifier' => 'fooValidator',
                                            'editors' => [
                                                [
                                                    'propertyPath' => 'options.xxx',
                                                ],
                                                [
                                                    'propertyPath' => 'options.yyy',
                                                    'setup' => [
                                                        'propertyPath' => 'options.zzz',
                                                    ],
                                                ],
                                            ],
                                        ],
                                    ],
                                ],
                            ],
                        ],
                    ],
                ],
                [
                    'formElements' => [
                        'Text' => [
                            'collections' => [
                                'validators' => [
                                    'fooValidator' => [
                                        'propertyPaths' => [
                                            'options.xxx',
                                            'options.yyy',
                                            'options.zzz',
                                        ],
                                    ],
                                ],
                            ],
                        ],
                    ],
                ],
            ],

            [
                [
                    'formElementsDefinition' => [
                        'Text' => [
                            'formEditor' => [
                                'propertyCollections' => [
                                    'validators' => [
                                        [
                                            'identifier' => 'fooValidator',
                                            'editors' => [
                                                [
                                                    'additionalElementPropertyPaths' => [
                                                        'options.xxx',
                                                    ],
                                                ],
                                                [
                                                    'additionalElementPropertyPaths' => [
                                                        'options.yyy',
                                                        'options.zzz',
                                                    ],
                                                ],
                                            ],
                                        ],
                                    ],
                                ],
                            ],
                        ],
                    ],
                ],
                [
                    'formElements' => [
                        'Text' => [
                            'additionalElementPropertyPaths' => [
                                'options.xxx',
                                'options.yyy',
                                'options.zzz',
                            ],
                        ],
                    ],
                ],
            ],

            [
                [
                    'formElementsDefinition' => [
                        'Text' => [
                            'formEditor' => [
                                'propertyCollections' => [
                                    'validators' => [
                                        [
                                            'identifier' => 'fooValidator',
                                            'editors' => [
                                                [
                                                    'templateName' => 'Inspector-PropertyGridEditor',
                                                    'propertyPath' => 'options.xxx',
                                                ],
                                                [
                                                    'templateName' => 'Inspector-MultiSelectEditor',
                                                    'propertyPath' => 'options.yyy',
                                                ],
                                                [
                                                    'templateName' => 'Inspector-ValidationErrorMessageEditor',
                                                    'propertyPath' => 'options.zzz',
                                                ],
                                            ],
                                        ],
                                    ],
                                ],
                            ],
                        ],
                    ],
                ],
                [
                    'formElements' => [
                        'Text' => [
                            'collections' => [
                                'validators' => [
                                    'fooValidator' => [
                                        'multiValueProperties' => [
                                            'options.xxx',
                                            'defaultValue',
                                            'options.yyy',
                                        ],
                                        'propertyPaths' => [
                                            'options.xxx',
                                            'options.yyy',
                                            'options.zzz',
                                        ],
                                    ],
                                ],
                            ],
                            'multiValueProperties' => [
                                'options.zzz',
                            ],
                        ],
                    ],
                ],
            ],

            [
                [
                    'validatorsDefinition' => [
                        'someValidator' => [
                            'formEditor' => [
                                'predefinedDefaults' => [
                                    'some' => [
                                        'property' => 'value',
                                    ],
                                ],
                            ],
                        ],
                    ],
                ],
                [
                    'collections' => [
                        'validators' => [
                            'someValidator' => [
                                'predefinedDefaults' => [
                                    'some.property' => 'value',
                                ],
                                'untranslatedPredefinedDefaults' => [
                                    'some.property' => 'value',
                                ],
                            ],
                        ],
                    ],
                ],
            ],
        ];
    }

    #[DataProvider('buildFormDefinitionValidationConfigurationFromFormEditorSetupDataProvider')]
    #[Test]
    public function buildFormDefinitionValidationConfigurationFromFormEditorSetup(array $configuration, array $expected): void
    {
        $translationServiceMock = $this->createMock(TranslationService::class);
        $translationServiceMock->method('translateValuesRecursive')->willReturnArgument(0);
        $subjectMock = $this->getAccessibleMock(
            ConfigurationService::class,
            [
                'getPrototypeConfiguration',
                'executeBuildFormDefinitionValidationConfigurationHooks',
            ],
            [
                $this->createMock(ConfigurationManagerInterface::class),
                $translationServiceMock,
                $this->createMock(FrontendInterface::class),
                $this->createMock(FrontendInterface::class),
            ],
        );
        $subjectMock->method('getPrototypeConfiguration')->willReturn($configuration);
        $subjectMock->method('executeBuildFormDefinitionValidationConfigurationHooks')->willReturnArgument(1);
        self::assertSame($expected, $subjectMock->_call('buildFormDefinitionValidationConfigurationFromFormEditorSetup', 'standard'));
    }

    public static function formElementPropertyHasLimitedAllowedValuesDefinedWithinFormEditorSetupDataProvider(): array
    {
        return [
            [
                ['formElements' => ['Text' => []]],
                new ValidationDto('standard', 'Text', null, 'properties.foo'),
                false,
            ],
            [
                ['formElements' => ['Text' => ['selectOptions' => []]]],
                new ValidationDto('standard', 'Text', null, 'properties.foo'),
                false,
            ],
            [
                ['formElements' => ['Text' => ['selectOptions' => ['properties.foo' => []]]]],
                new ValidationDto('standard', 'Text', null, 'properties.foo'),
                true,
            ],
            [
                ['formElements' => ['Text' => ['selectOptions' => ['properties.foo' => []], 'multiValueProperties' => []]]],
                new ValidationDto('standard', 'Text', null, 'properties.foo.1'),
                false,
            ],
            [
                ['formElements' => ['Text' => ['selectOptions' => ['properties.foo' => []], 'multiValueProperties' => ['properties.foo']]]],
                new ValidationDto('standard', 'Text', null, 'properties.foo.1'),
                true,
            ],
        ];
    }

    #[DataProvider('formElementPropertyHasLimitedAllowedValuesDefinedWithinFormEditorSetupDataProvider')]
    #[Test]
    public function formElementPropertyHasLimitedAllowedValuesDefinedWithinFormEditorSetup(array $configuration, ValidationDto $validationDto, bool $expectedReturn): void
    {
        $subjectMock = $this->getAccessibleMock(
            ConfigurationService::class,
            ['buildFormDefinitionValidationConfigurationFromFormEditorSetup'],
            [],
            '',
            false
        );
        $subjectMock->method('buildFormDefinitionValidationConfigurationFromFormEditorSetup')->willReturn($configuration);
        self::assertSame($expectedReturn, $subjectMock->formElementPropertyHasLimitedAllowedValuesDefinedWithinFormEditorSetup($validationDto));
    }

    #[Test]
    public function getAllowedValuesForFormElementPropertyFromFormEditorSetupThrowsExceptionIfNoLimitedAllowedValuesAreAvailable(): void
    {
        $this->expectException(PropertyException::class);
        $this->expectExceptionCode(1614264312);
        $subjectMock = $this->getAccessibleMock(
            ConfigurationService::class,
            ['formElementPropertyHasLimitedAllowedValuesDefinedWithinFormEditorSetup'],
            [],
            '',
            false
        );
        $subjectMock->method('formElementPropertyHasLimitedAllowedValuesDefinedWithinFormEditorSetup')->willReturn(false);
        $validationDto = new ValidationDto('standard', 'Text', null, 'properties.foo');
        $subjectMock->getAllowedValuesForFormElementPropertyFromFormEditorSetup($validationDto);
    }

    public static function getAllowedValuesForFormElementPropertyFromFormEditorSetupDataProvider(): array
    {
        return [
            [
                ['formElements' => ['Text' => ['selectOptions' => ['properties.foo' => []]]]],
                new ValidationDto('standard', 'Text', null, 'properties.foo'),
                [],
            ],
            [
                ['formElements' => ['Text' => ['selectOptions' => ['properties.foo' => []], 'multiValueProperties' => ['properties.foo']]]],
                new ValidationDto('standard', 'Text', null, 'properties.foo.1'),
                [],
            ],
            [
                ['formElements' => ['Text' => ['selectOptions' => ['properties.foo' => ['bar', 'baz']]]]],
                new ValidationDto('standard', 'Text', null, 'properties.foo'),
                ['bar', 'baz'],
            ],
            [
                ['formElements' => ['Text' => ['selectOptions' => ['properties.foo' => ['bar', 'baz']], 'multiValueProperties' => ['properties.foo']]]],
                new ValidationDto('standard', 'Text', null, 'properties.foo.1'),
                ['bar', 'baz'],
            ],
        ];
    }

    #[DataProvider('getAllowedValuesForFormElementPropertyFromFormEditorSetupDataProvider')]
    #[Test]
    public function getAllowedValuesForFormElementPropertyFromFormEditorSetup(array $configuration, ValidationDto $validationDto, array $expectedReturn): void
    {
        $subjectMock = $this->getAccessibleMock(
            ConfigurationService::class,
            ['buildFormDefinitionValidationConfigurationFromFormEditorSetup'],
            [],
            '',
            false
        );
        $subjectMock->method('buildFormDefinitionValidationConfigurationFromFormEditorSetup')->willReturn($configuration);
        self::assertSame($expectedReturn, $subjectMock->getAllowedValuesForFormElementPropertyFromFormEditorSetup($validationDto));
    }

    public static function propertyCollectionPropertyHasLimitedAllowedValuesDefinedWithinFormEditorSetupDataProvider(): array
    {
        return [
            [
                ['collections' => ['validators' => ['StringLength' => []]]],
                new ValidationDto('standard', null, null, 'properties.foo', 'validators', 'StringLength'),
                false,
            ],
            [
                ['collections' => ['validators' => ['StringLength' => ['selectOptions' => []]]]],
                new ValidationDto('standard', null, null, 'properties.foo', 'validators', 'StringLength'),
                false,
            ],
            [
                ['collections' => ['validators' => ['StringLength' => ['selectOptions' => ['properties.foo' => []]]]]],
                new ValidationDto('standard', null, null, 'properties.foo', 'validators', 'StringLength'),
                true,
            ],
            [
                ['collections' => ['validators' => ['StringLength' => ['selectOptions' => ['properties.foo' => []], 'multiValueProperties' => []]]]],
                new ValidationDto('standard', null, null, 'properties.foo.1', 'validators', 'StringLength'),
                false,
            ],
            [
                ['collections' => ['validators' => ['StringLength' => ['selectOptions' => ['properties.foo' => []], 'multiValueProperties' => ['properties.foo']]]]],
                new ValidationDto('standard', null, null, 'properties.foo.1', 'validators', 'StringLength'),
                true,
            ],
        ];
    }

    #[DataProvider('propertyCollectionPropertyHasLimitedAllowedValuesDefinedWithinFormEditorSetupDataProvider')]
    #[Test]
    public function propertyCollectionPropertyHasLimitedAllowedValuesDefinedWithinFormEditorSetup(array $configuration, ValidationDto $validationDto, bool $expectedReturn): void
    {
        $subjectMock = $this->getAccessibleMock(
            ConfigurationService::class,
            ['buildFormDefinitionValidationConfigurationFromFormEditorSetup'],
            [],
            '',
            false
        );
        $subjectMock->method('buildFormDefinitionValidationConfigurationFromFormEditorSetup')->willReturn($configuration);
        self::assertSame($expectedReturn, $subjectMock->propertyCollectionPropertyHasLimitedAllowedValuesDefinedWithinFormEditorSetup($validationDto));
    }

    #[Test]
    public function getAllowedValuesForPropertyCollectionPropertyFromFormEditorSetupThrowsExceptionIfNoLimitedAllowedValuesAreAvailable(): void
    {
        $this->expectException(PropertyException::class);
        $this->expectExceptionCode(1614264313);
        $subjectMock = $this->getAccessibleMock(
            ConfigurationService::class,
            ['propertyCollectionPropertyHasLimitedAllowedValuesDefinedWithinFormEditorSetup'],
            [],
            '',
            false
        );
        $subjectMock->method('propertyCollectionPropertyHasLimitedAllowedValuesDefinedWithinFormEditorSetup')->willReturn(false);
        $validationDto = new ValidationDto('standard', null, null, 'properties.foo', 'validators', 'StringLength');
        $subjectMock->getAllowedValuesForPropertyCollectionPropertyFromFormEditorSetup($validationDto);
    }

    public static function getAllowedValuesForPropertyCollectionPropertyFromFormEditorSetupDataProvider(): array
    {
        return [
            [
                ['collections' => ['validators' => ['StringLength' => ['selectOptions' => ['properties.foo' => []]]]]],
                new ValidationDto('standard', null, null, 'properties.foo', 'validators', 'StringLength'),
                [],
            ],
            [
                ['collections' => ['validators' => ['StringLength' => ['selectOptions' => ['properties.foo' => []], 'multiValueProperties' => ['properties.foo']]]]],
                new ValidationDto('standard', null, null, 'properties.foo.1', 'validators', 'StringLength'),
                [],
            ],
            [
                ['collections' => ['validators' => ['StringLength' => ['selectOptions' => ['properties.foo' => ['bar', 'baz']]]]]],
                new ValidationDto('standard', null, null, 'properties.foo', 'validators', 'StringLength'),
                ['bar', 'baz'],
            ],
            [
                ['collections' => ['validators' => ['StringLength' => ['selectOptions' => ['properties.foo' => ['bar', 'baz']], 'multiValueProperties' => ['properties.foo']]]]],
                new ValidationDto('standard', null, null, 'properties.foo.1', 'validators', 'StringLength'),
                ['bar', 'baz'],
            ],
        ];
    }

    #[DataProvider('getAllowedValuesForPropertyCollectionPropertyFromFormEditorSetupDataProvider')]
    #[Test]
    public function getAllowedValuesForPropertyCollectionPropertyFromFormEditorSetup(array $configuration, ValidationDto $validationDto, array $expectedReturn): void
    {
        $subjectMock = $this->getAccessibleMock(
            ConfigurationService::class,
            ['buildFormDefinitionValidationConfigurationFromFormEditorSetup'],
            [],
            '',
            false
        );
        $subjectMock->method('buildFormDefinitionValidationConfigurationFromFormEditorSetup')->willReturn($configuration);
        self::assertSame($expectedReturn, $subjectMock->getAllowedValuesForPropertyCollectionPropertyFromFormEditorSetup($validationDto));
    }
}
