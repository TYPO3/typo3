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

namespace TYPO3\CMS\Form\Domain\Configuration;

use TYPO3\CMS\Core\Cache\CacheManager;
use TYPO3\CMS\Core\Cache\Frontend\FrontendInterface;
use TYPO3\CMS\Core\SingletonInterface;
use TYPO3\CMS\Core\Utility\ArrayUtility;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Form\Domain\Configuration\ArrayProcessing\ArrayProcessing;
use TYPO3\CMS\Form\Domain\Configuration\ArrayProcessing\ArrayProcessor;
use TYPO3\CMS\Form\Domain\Configuration\Exception\PropertyException;
use TYPO3\CMS\Form\Domain\Configuration\Exception\PrototypeNotFoundException;
use TYPO3\CMS\Form\Domain\Configuration\FormDefinition\Validators\ValidationDto;
use TYPO3\CMS\Form\Domain\Configuration\FrameworkConfiguration\Extractors\AdditionalElementPropertyPathsExtractor;
use TYPO3\CMS\Form\Domain\Configuration\FrameworkConfiguration\Extractors\ExtractorDto;
use TYPO3\CMS\Form\Domain\Configuration\FrameworkConfiguration\Extractors\FormElement\IsCreatableFormElementExtractor;
use TYPO3\CMS\Form\Domain\Configuration\FrameworkConfiguration\Extractors\FormElement\MultiValuePropertiesExtractor;
use TYPO3\CMS\Form\Domain\Configuration\FrameworkConfiguration\Extractors\FormElement\PredefinedDefaultsExtractor;
use TYPO3\CMS\Form\Domain\Configuration\FrameworkConfiguration\Extractors\FormElement\PropertyPathsExtractor;
use TYPO3\CMS\Form\Domain\Configuration\FrameworkConfiguration\Extractors\FormElement\SelectOptionsExtractor;
use TYPO3\CMS\Form\Domain\Configuration\FrameworkConfiguration\Extractors\PropertyCollectionElement\IsCreatablePropertyCollectionElementExtractor;
use TYPO3\CMS\Form\Domain\Configuration\FrameworkConfiguration\Extractors\PropertyCollectionElement\MultiValuePropertiesExtractor as CollectionMultiValuePropertiesExtractor;
use TYPO3\CMS\Form\Domain\Configuration\FrameworkConfiguration\Extractors\PropertyCollectionElement\PredefinedDefaultsExtractor as CollectionPredefinedDefaultsExtractor;
use TYPO3\CMS\Form\Domain\Configuration\FrameworkConfiguration\Extractors\PropertyCollectionElement\PropertyPathsExtractor as CollectionPropertyPathsExtractor;
use TYPO3\CMS\Form\Domain\Configuration\FrameworkConfiguration\Extractors\PropertyCollectionElement\SelectOptionsExtractor as CollectionSelectOptionsExtractor;
use TYPO3\CMS\Form\Mvc\Configuration\ConfigurationManagerInterface;
use TYPO3\CMS\Form\Service\TranslationService;

/**
 * Helper for configuration settings
 * Scope: frontend / backend
 */
class ConfigurationService implements SingletonInterface
{

    /**
     * @var array
     */
    protected $formSettings;

    /**
     * @var array
     */
    protected $firstLevelCache = [];

    /**
     * @var TranslationService
     */
    protected $translationService;

    public function __construct(ConfigurationManagerInterface $configurationManager)
    {
        $this->formSettings = $configurationManager->getConfiguration(ConfigurationManagerInterface::CONFIGURATION_TYPE_YAML_SETTINGS, 'form');
    }

    /**
     * Get the prototype configuration
     *
     * @param string $prototypeName name of the prototype to get the configuration for
     * @return array the prototype configuration
     * @throws PrototypeNotFoundException if prototype with the name $prototypeName was not found
     */
    public function getPrototypeConfiguration(string $prototypeName): array
    {
        if (!isset($this->formSettings['prototypes'][$prototypeName])) {
            throw new PrototypeNotFoundException(
                sprintf('The Prototype "%s" was not found.', $prototypeName),
                1475924277
            );
        }
        return $this->formSettings['prototypes'][$prototypeName];
    }

    /**
     * Return all prototype names which are defined within "formManager.selectablePrototypesConfiguration.*.identifier"
     *
     * @return array
     * @internal
     */
    public function getSelectablePrototypeNamesDefinedInFormEditorSetup(): array
    {
        $returnValue = GeneralUtility::makeInstance(
            ArrayProcessor::class,
            $this->formSettings['formManager']['selectablePrototypesConfiguration'] ?? []
        )->forEach(
            GeneralUtility::makeInstance(
                ArrayProcessing::class,
                'selectablePrototypeNames',
                '^([\d]+)\.identifier$',
                static function ($_, $value) {
                    return $value;
                }
            )
        );

        return array_values($returnValue['selectablePrototypeNames'] ?? []);
    }

    /**
     * Check if a form element property is defined in the form setup.
     * If a form element property is defined in the form setup then it
     * means that the form element property can be written by the form editor.
     * A form element property can be written if the property path is defined within
     * the following form editor properties:
     * * formElementsDefinition.<formElementType>.formEditor.editors.<index>.propertyPath
     * * formElementsDefinition.<formElementType>.formEditor.editors.<index>.*.propertyPath
     * * formElementsDefinition.<formElementType>.formEditor.editors.<index>.additionalElementPropertyPaths
     * * formElementsDefinition.<formElementType>.formEditor.propertyCollections.<finishers|validators>.<index>.editors.<index>.additionalElementPropertyPaths
     * If a form editor property "templateName" is
     * "Inspector-PropertyGridEditor" or "Inspector-MultiSelectEditor" or "Inspector-ValidationErrorMessageEditor"
     * it means that the form editor property "propertyPath" is interpreted as a so called "multiValueProperty".
     * A "multiValueProperty" can contain any subproperties relative to the value from "propertyPath" which are valid.
     * If "formElementsDefinition.<formElementType>.formEditor.editors.<index>.templateName = Inspector-PropertyGridEditor"
     * and
     * "formElementsDefinition.<formElementType>.formEditor.editors.<index>.propertyPath = options.xxx"
     * then (for example) "options.xxx.yyy" is a valid property path to write.
     * If you use a custom form editor "inspector editor" implementation which does not define the writable
     * property paths by one of the above described inspector editor properties (e.g "propertyPath") within
     * the form setup, you must provide the writable property paths with a hook.
     *
     * @see executeBuildFormDefinitionValidationConfigurationHooks()
     * @param ValidationDto $dto
     * @return bool
     * @internal
     */
    public function isFormElementPropertyDefinedInFormEditorSetup(ValidationDto $dto): bool
    {
        $formDefinitionValidationConfiguration = $this->buildFormDefinitionValidationConfigurationFromFormEditorSetup(
            $dto->getPrototypeName()
        );

        $subConfig = $formDefinitionValidationConfiguration['formElements'][$dto->getFormElementType()] ?? [];
        return $this->isPropertyDefinedInFormEditorSetup($dto->getPropertyPath(), $subConfig);
    }

    /**
     * Check if a form elements finisher|validator property is defined in the form setup.
     * If a form elements finisher|validator property is defined in the form setup then it
     * means that the form elements finisher|validator property can be written by the form editor.
     * A form elements finisher|validator property can be written if the property path is defined within
     * the following form editor properties:
     * * formElementsDefinition.<formElementType>.formEditor.propertyCollections.<finishers|validators>.<index>.editors.<index>.propertyPath
     * * formElementsDefinition.<formElementType>.formEditor.propertyCollections.<finishers|validators>.<index>.editors.<index>.*.propertyPath
     * If a form elements finisher|validator property "templateName" is
     * "Inspector-PropertyGridEditor" or "Inspector-MultiSelectEditor" or "Inspector-ValidationErrorMessageEditor"
     * it means that the form editor property "propertyPath" is interpreted as a so called "multiValueProperty".
     * A "multiValueProperty" can contain any subproperties relative to the value from "propertyPath" which are valid.
     * If "formElementsDefinition.<formElementType>.formEditor.propertyCollections.<finishers|validators>.<index>.editors.<index>.templateName = Inspector-PropertyGridEditor"
     * and
     * "formElementsDefinition.<formElementType>.formEditor.propertyCollections.<finishers|validators>.<index>.editors.<index>.propertyPath = options.xxx"
     * that (for example) "options.xxx.yyy" is a valid property path to write.
     * If you use a custom form editor "inspector editor" implementation which not defines the writable
     * property paths by one of the above described inspector editor properties (e.g "propertyPath") within
     * the form setup, you must provide the writable property paths with a hook.
     *
     * @see executeBuildFormDefinitionValidationConfigurationHooks()
     * @param ValidationDto $dto
     * @return bool
     * @internal
     */
    public function isPropertyCollectionPropertyDefinedInFormEditorSetup(ValidationDto $dto): bool
    {
        $formDefinitionValidationConfiguration = $this->buildFormDefinitionValidationConfigurationFromFormEditorSetup(
            $dto->getPrototypeName()
        );
        $subConfig = $formDefinitionValidationConfiguration['formElements'][$dto->getFormElementType()]['collections'][$dto->getPropertyCollectionName()][$dto->getPropertyCollectionElementIdentifier()] ?? [];

        return $this->isPropertyDefinedInFormEditorSetup($dto->getPropertyPath(), $subConfig);
    }

    /**
     * If a form element editor has a property called "selectOptions"
     * (e.g. editors with templateName "Inspector-SingleSelectEditor" or "Inspector-MultiSelectEditor")
     * then only the defined values within the selectOptions are allowed to be written
     * by the form editor.
     *
     * @param ValidationDto $dto
     * @return bool
     * @internal
     */
    public function formElementPropertyHasLimitedAllowedValuesDefinedWithinFormEditorSetup(
        ValidationDto $dto
    ): bool {
        $formDefinitionValidationConfiguration = $this->buildFormDefinitionValidationConfigurationFromFormEditorSetup(
            $dto->getPrototypeName()
        );

        $propertyPath = $this->getBasePropertyPathFromMultiValueFormElementProperty($dto);
        return isset(
            $formDefinitionValidationConfiguration['formElements'][$dto->getFormElementType()]['selectOptions'][$propertyPath]
        );
    }

    /**
     * Get the "selectOptions" value for a form element property from the form setup.
     *
     * @param ValidationDto $dto
     * @return array
     * @param bool $translated
     * @throws PropertyException
     * @internal
     */
    public function getAllowedValuesForFormElementPropertyFromFormEditorSetup(
        ValidationDto $dto,
        bool $translated = true
    ): array {
        if (!$this->formElementPropertyHasLimitedAllowedValuesDefinedWithinFormEditorSetup($dto)) {
            throw new PropertyException(
                sprintf(
                    'No selectOptions found for form element type "%s" and property path "%s"',
                    $dto->getFormElementType(),
                    $dto->getPropertyPath()
                ),
                1614264312
            );
        }

        $formDefinitionValidationConfiguration = $this->buildFormDefinitionValidationConfigurationFromFormEditorSetup(
            $dto->getPrototypeName()
        );

        $property = $translated ? 'selectOptions' : 'untranslatedSelectOptions';
        $propertyPath = $this->getBasePropertyPathFromMultiValueFormElementProperty($dto);
        return $formDefinitionValidationConfiguration['formElements'][$dto->getFormElementType()][$property][$propertyPath];
    }

    /**
     * If a form elements finisher|validator editor has a property called "selectOptions"
     * (e.g. editors with templateName "Inspector-SingleSelectEditor" or "Inspector-MultiSelectEditor")
     * then only the defined values within the selectOptions are allowed to be written
     * by the form editor.
     *
     * @param ValidationDto $dto
     * @return bool
     * @internal
     */
    public function propertyCollectionPropertyHasLimitedAllowedValuesDefinedWithinFormEditorSetup(
        ValidationDto $dto
    ): bool {
        $formDefinitionValidationConfiguration = $this->buildFormDefinitionValidationConfigurationFromFormEditorSetup(
            $dto->getPrototypeName()
        );

        $propertyPath = $this->getBasePropertyPathFromMultiValuePropertyCollectionElement($dto);
        return isset(
            $formDefinitionValidationConfiguration['collections'][$dto->getPropertyCollectionName()][$dto->getPropertyCollectionElementIdentifier()]['selectOptions'][$propertyPath]
        );
    }

    /**
     * Get the "selectOptions" value for a form elements finisher|validator property from the form setup.
     *
     * @param ValidationDto $dto
     * @param bool $translated
     * @return array
     * @throws PropertyException
     * @internal
     */
    public function getAllowedValuesForPropertyCollectionPropertyFromFormEditorSetup(
        ValidationDto $dto,
        bool $translated = true
    ): array {
        if (!$this->propertyCollectionPropertyHasLimitedAllowedValuesDefinedWithinFormEditorSetup($dto)) {
            throw new PropertyException(
                sprintf(
                    'No selectOptions found for property collection "%s" and identifier "%s" and property path "%s"',
                    $dto->getPropertyCollectionName(),
                    $dto->getPropertyCollectionElementIdentifier(),
                    $dto->getPropertyPath()
                ),
                1614264313
            );
        }

        $formDefinitionValidationConfiguration = $this->buildFormDefinitionValidationConfigurationFromFormEditorSetup(
            $dto->getPrototypeName()
        );

        $property = $translated ? 'selectOptions' : 'untranslatedSelectOptions';
        $propertyPath = $this->getBasePropertyPathFromMultiValuePropertyCollectionElement($dto);
        return $formDefinitionValidationConfiguration['collections'][$dto->getPropertyCollectionName()][$dto->getPropertyCollectionElementIdentifier()][$property][$propertyPath];
    }

    /**
     * @param ValidationDto $dto
     * @return string
     */
    protected function getBasePropertyPathFromMultiValueFormElementProperty(
        ValidationDto $dto
    ): string {
        $formDefinitionValidationConfiguration = $this->buildFormDefinitionValidationConfigurationFromFormEditorSetup(
            $dto->getPrototypeName()
        );

        $propertyPath = $dto->getPropertyPath();
        $multiValueProperties = $formDefinitionValidationConfiguration['formElements'][$dto->getFormElementType()]['multiValueProperties'] ?? [];
        foreach ($multiValueProperties as $multiValueProperty) {
            if (strpos($propertyPath, $multiValueProperty) === 0) {
                $propertyPath = $multiValueProperty;
                continue;
            }
        }

        return $propertyPath;
    }

    /**
     * @param ValidationDto $dto
     * @return string
     */
    protected function getBasePropertyPathFromMultiValuePropertyCollectionElement(
        ValidationDto $dto
    ): string {
        $formDefinitionValidationConfiguration = $this->buildFormDefinitionValidationConfigurationFromFormEditorSetup(
            $dto->getPrototypeName()
        );

        $propertyPath = $dto->getPropertyPath();
        $multiValueProperties = $formDefinitionValidationConfiguration['collections'][$dto->getPropertyCollectionName()][$dto->getPropertyCollectionElementIdentifier()]['multiValueProperties'] ?? [];
        foreach ($multiValueProperties as $multiValueProperty) {
            if (strpos($propertyPath, $multiValueProperty) === 0) {
                $propertyPath = $multiValueProperty;
                continue;
            }
        }

        return $propertyPath;
    }

    /**
     * Check if a form element property is defined in "predefinedDefaults" in the form setup.
     * If a form element property is defined in the "predefinedDefaults" in the form setup then it
     * means that the form element property can be written by the form editor.
     * A form element default property is defined within the following form editor properties:
     * * formElementsDefinition.<formElementType>.formEditor.predefinedDefaults.<propertyPath> = "default value"
     *
     * @param ValidationDto $dto
     * @return bool
     * @internal
     */
    public function isFormElementPropertyDefinedInPredefinedDefaultsInFormEditorSetup(
        ValidationDto $dto
    ): bool {
        $formDefinitionValidationConfiguration = $this->buildFormDefinitionValidationConfigurationFromFormEditorSetup(
            $dto->getPrototypeName()
        );
        return isset(
            $formDefinitionValidationConfiguration['formElements'][$dto->getFormElementType()]['predefinedDefaults'][$dto->getPropertyPath()]
        );
    }

    /**
     * Get the "predefinedDefaults" value for a form element property from the form setup.
     * A form element default property is defined within the following form editor properties:
     * * formElementsDefinition.<formElementType>.formEditor.predefinedDefaults.<propertyPath> = "default value"
     *
     * @param ValidationDto $dto
     * @param bool $translated
     * @return mixed
     * @throws PropertyException
     * @internal
     */
    public function getFormElementPredefinedDefaultValueFromFormEditorSetup(ValidationDto $dto, bool $translated = true)
    {
        if (!$this->isFormElementPropertyDefinedInPredefinedDefaultsInFormEditorSetup($dto)) {
            throw new PropertyException(
                sprintf(
                    'No predefinedDefaults found for form element type "%s" and property path "%s"',
                    $dto->getFormElementType(),
                    $dto->getPropertyPath()
                ),
                1528578401
            );
        }

        $formDefinitionValidationConfiguration = $this->buildFormDefinitionValidationConfigurationFromFormEditorSetup(
            $dto->getPrototypeName()
        );

        $property = $translated ? 'predefinedDefaults' : 'untranslatedPredefinedDefaults';
        return $formDefinitionValidationConfiguration['formElements'][$dto->getFormElementType()][$property][$dto->getPropertyPath()];
    }

    /**
     * Check if a form elements finisher|validator property is defined in "predefinedDefaults" in the form setup.
     * If a form elements finisher|validator property is defined in "predefinedDefaults" in the form setup then it
     * means that the form elements finisher|validator property can be written by the form editor.
     * A form elements finisher|validator default property is defined within the following form editor properties:
     * * <validatorsDefinition|finishersDefinition>.<index>.formEditor.predefinedDefaults.<propertyPath> = "default value"
     *
     * @param ValidationDto $dto
     * @return bool
     * @internal
     */
    public function isPropertyCollectionPropertyDefinedInPredefinedDefaultsInFormEditorSetup(
        ValidationDto $dto
    ): bool {
        $formDefinitionValidationConfiguration = $this->buildFormDefinitionValidationConfigurationFromFormEditorSetup(
            $dto->getPrototypeName()
        );
        return isset(
            $formDefinitionValidationConfiguration['collections'][$dto->getPropertyCollectionName()][$dto->getPropertyCollectionElementIdentifier()]['predefinedDefaults'][$dto->getPropertyPath()]
        );
    }

    /**
     * Get the "predefinedDefaults" value for a form elements finisher|validator property from the form setup.
     * A form elements finisher|validator default property is defined within the following form editor properties:
     * * <validatorsDefinition|finishersDefinition>.<index>.formEditor.predefinedDefaults.<propertyPath> = "default value"
     *
     * @param ValidationDto $dto
     * @param bool $translated
     * @return mixed
     * @throws PropertyException
     * @internal
     */
    public function getPropertyCollectionPredefinedDefaultValueFromFormEditorSetup(ValidationDto $dto, bool $translated = true)
    {
        if (!$this->isPropertyCollectionPropertyDefinedInPredefinedDefaultsInFormEditorSetup($dto)) {
            throw new PropertyException(
                sprintf(
                    'No predefinedDefaults found for property collection "%s" and identifier "%s" and property path "%s"',
                    $dto->getPropertyCollectionName(),
                    $dto->getPropertyCollectionElementIdentifier(),
                    $dto->getPropertyPath()
                ),
                1528578402
            );
        }

        $formDefinitionValidationConfiguration = $this->buildFormDefinitionValidationConfigurationFromFormEditorSetup(
            $dto->getPrototypeName()
        );

        $property = $translated ? 'predefinedDefaults' : 'untranslatedPredefinedDefaults';
        return $formDefinitionValidationConfiguration['collections'][$dto->getPropertyCollectionName()][$dto->getPropertyCollectionElementIdentifier()][$property][$dto->getPropertyPath()];
    }

    /**
     * Check if the form element is creatable through the form editor.
     * A form element is creatable if the following properties are set:
     *  * formElementsDefinition.<formElementType>.formEditor.group
     *  * formElementsDefinition.<formElementType>.formEditor.groupSorting
     * And the value from "formElementsDefinition.<formElementType>.formEditor.group" is
     * one of the keys within "formEditor.formElementGroups"
     *
     * @param ValidationDto $dto
     * @return bool
     * @internal
     */
    public function isFormElementTypeCreatableByFormEditor(ValidationDto $dto): bool
    {
        if ($dto->getFormElementType() === 'Form') {
            return true;
        }
        $formDefinitionValidationConfiguration = $this->buildFormDefinitionValidationConfigurationFromFormEditorSetup(
            $dto->getPrototypeName()
        );
        return $formDefinitionValidationConfiguration['formElements'][$dto->getFormElementType()]['creatable'] ?? false;
    }

    /**
     * Check if the form elements finisher|validator is creatable through the form editor.
     * A form elements finisher|validator is creatable if the following conditions are true:
     * "formElementsDefinition.<formElementType>.formEditor.editors.<index>.templateName = Inspector-FinishersEditor"
     * or
     * "formElementsDefinition.<formElementType>.formEditor.editors.<index>.templateName = Inspector-ValidatorsEditor"
     * and
     * "formElementsDefinition.<formElementType>.formEditor.editors.<index>.selectOptions.<index>.value = <finisherIdentifier|validatorIdentifier>"
     *
     * @param ValidationDto $dto
     * @return bool
     * @internal
     */
    public function isPropertyCollectionElementIdentifierCreatableByFormEditor(ValidationDto $dto): bool
    {
        $formDefinitionValidationConfiguration = $this->buildFormDefinitionValidationConfigurationFromFormEditorSetup(
            $dto->getPrototypeName()
        );
        return $formDefinitionValidationConfiguration['formElements'][$dto->getFormElementType()]['collections'][$dto->getPropertyCollectionName()][$dto->getPropertyCollectionElementIdentifier()]['creatable'] ?? false;
    }

    /**
     * Check if the form elements type is defined within the form setup.
     *
     * @param ValidationDto $dto
     * @return bool
     * @internal
     */
    public function isFormElementTypeDefinedInFormSetup(ValidationDto $dto): bool
    {
        $prototypeConfiguration = $this->getPrototypeConfiguration($dto->getPrototypeName());
        return ArrayUtility::isValidPath(
            $prototypeConfiguration,
            'formElementsDefinition.' . $dto->getFormElementType(),
            '.'
        );
    }

    /**
     * @param array $keys
     * @param string $prototypeName
     * @return array
     * @internal
     */
    public function getAllBackendTranslationsForTranslationKeys(array $keys, string $prototypeName): array
    {
        $translations = [];
        foreach ($keys as $key) {
            if (!is_string($key)) {
                continue;
            }

            $translations[$key] = $this->getAllBackendTranslationsForTranslationKey($key, $prototypeName);
        }

        return $translations;
    }

    /**
     * @param string $key
     * @param string $prototypeName
     * @return array
     */
    public function getAllBackendTranslationsForTranslationKey(string $key, string $prototypeName): array
    {
        $prototypeConfiguration = $this->getPrototypeConfiguration($prototypeName);

        return $this->getTranslationService()->translateToAllBackendLanguages(
            $key,
            [],
            $prototypeConfiguration['formEditor']['translationFiles'] ?? []
        );
    }

    /**
     * Collect all the form editor configurations which are needed to check if a
     * form definition property can be written or not.
     *
     * @param string $prototypeName
     * @return array
     */
    protected function buildFormDefinitionValidationConfigurationFromFormEditorSetup(string $prototypeName): array
    {
        $cacheKey = implode('_', ['buildFormDefinitionValidationConfigurationFromFormEditorSetup', $prototypeName]);
        $configuration = $this->getCacheEntry($cacheKey);

        if ($configuration === null) {
            $prototypeConfiguration = $this->getPrototypeConfiguration($prototypeName);
            $extractorDto = GeneralUtility::makeInstance(ExtractorDto::class, $prototypeConfiguration);

            GeneralUtility::makeInstance(ArrayProcessor::class, $prototypeConfiguration)->forEach(
                GeneralUtility::makeInstance(
                    ArrayProcessing::class,
                    'formElementPropertyPaths',
                    '^formElementsDefinition\.(.*)\.formEditor\.editors\.([\d]+)\.(propertyPath|.*\.propertyPath)$',
                    GeneralUtility::makeInstance(PropertyPathsExtractor::class, $extractorDto)
                ),
                GeneralUtility::makeInstance(
                    ArrayProcessing::class,
                    'formElementAdditionalElementPropertyPaths',
                    '^formElementsDefinition\.(.*)\.formEditor\.editors\.([\d]+)\.additionalElementPropertyPaths\.([\d]+)',
                    GeneralUtility::makeInstance(AdditionalElementPropertyPathsExtractor::class, $extractorDto)
                ),
                GeneralUtility::makeInstance(
                    ArrayProcessing::class,
                    'formElementRelativeMultiValueProperties',
                    '^formElementsDefinition\.(.*)\.formEditor\.editors\.([\d]+)\.templateName$',
                    GeneralUtility::makeInstance(MultiValuePropertiesExtractor::class, $extractorDto)
                ),
                GeneralUtility::makeInstance(
                    ArrayProcessing::class,
                    'formElementSelectOptions',
                    '^formElementsDefinition\.(.*)\.formEditor\.editors\.([\d]+)\.selectOptions\.([\d]+)\.value$',
                    GeneralUtility::makeInstance(SelectOptionsExtractor::class, $extractorDto)
                ),
                GeneralUtility::makeInstance(
                    ArrayProcessing::class,
                    'formElementPredefinedDefaults',
                    '^formElementsDefinition\.(.*)\.formEditor\.predefinedDefaults\.(.+)$',
                    GeneralUtility::makeInstance(PredefinedDefaultsExtractor::class, $extractorDto)
                ),
                GeneralUtility::makeInstance(
                    ArrayProcessing::class,
                    'formElementCreatable',
                    '^formElementsDefinition\.(.*)\.formEditor.group$',
                    GeneralUtility::makeInstance(IsCreatableFormElementExtractor::class, $extractorDto)
                ),
                GeneralUtility::makeInstance(
                    ArrayProcessing::class,
                    'propertyCollectionCreatable',
                    '^formElementsDefinition\.(.*)\.formEditor\.editors\.([\d]+)\.templateName$',
                    GeneralUtility::makeInstance(IsCreatablePropertyCollectionElementExtractor::class, $extractorDto)
                ),
                GeneralUtility::makeInstance(
                    ArrayProcessing::class,
                    'propertyCollectionPropertyPaths',
                    '^formElementsDefinition\.(.*)\.formEditor\.propertyCollections\.(finishers|validators)\.([\d]+)\.editors\.([\d]+)\.(propertyPath|.*\.propertyPath)$',
                    GeneralUtility::makeInstance(CollectionPropertyPathsExtractor::class, $extractorDto)
                ),
                GeneralUtility::makeInstance(
                    ArrayProcessing::class,
                    'propertyCollectionAdditionalElementPropertyPaths',
                    '^formElementsDefinition\.(.*)\.formEditor\.propertyCollections\.(finishers|validators)\.([\d]+)\.editors\.([\d]+)\.additionalElementPropertyPaths\.([\d]+)',
                    GeneralUtility::makeInstance(AdditionalElementPropertyPathsExtractor::class, $extractorDto)
                ),
                GeneralUtility::makeInstance(
                    ArrayProcessing::class,
                    'propertyCollectionRelativeMultiValueProperties',
                    '^formElementsDefinition\.(.*)\.formEditor\.propertyCollections\.(finishers|validators)\.([\d]+)\.editors\.([\d]+)\.templateName$',
                    GeneralUtility::makeInstance(CollectionMultiValuePropertiesExtractor::class, $extractorDto)
                ),
                GeneralUtility::makeInstance(
                    ArrayProcessing::class,
                    'propertyCollectionSelectOptions',
                    '^formElementsDefinition\.(.*)\.formEditor\.propertyCollections\.(finishers|validators)\.([\d]+)\.editors\.([\d]+)\.selectOptions\.([\d]+)\.value$',
                    GeneralUtility::makeInstance(CollectionSelectOptionsExtractor::class, $extractorDto)
                ),
                GeneralUtility::makeInstance(
                    ArrayProcessing::class,
                    'propertyCollectionPredefinedDefaults',
                    '^(validatorsDefinition|finishersDefinition)\.(.*)\.formEditor\.predefinedDefaults\.(.+)$',
                    GeneralUtility::makeInstance(CollectionPredefinedDefaultsExtractor::class, $extractorDto)
                )
            );
            $configuration = $extractorDto->getResult();

            $configuration = $this->translateValues($prototypeConfiguration, $configuration);

            $configuration = $this->executeBuildFormDefinitionValidationConfigurationHooks(
                $prototypeName,
                $configuration
            );

            $this->setCacheEntry($cacheKey, $configuration);
        }

        return $configuration;
    }

    /**
     * If you use a custom form editor "inspector editor" implementation which does not define the writable
     * property paths by one of the described inspector editor properties (e.g "propertyPath") within
     * the form setup, you must provide the writable property paths with a hook.
     *
     * @see isFormElementPropertyDefinedInFormEditorSetup()
     * @see isPropertyCollectionPropertyDefinedInFormEditorSetup()
     * Connect to the hook:
     * $GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS']['ext/form']['buildFormDefinitionValidationConfiguration'][] = \Vendor\YourNamespace\YourClass::class;
     * Use the hook:
     * public function addAdditionalPropertyPaths(\TYPO3\CMS\Form\Domain\Configuration\FormDefinition\Validators\ValidationDto $validationDto): array
     * {
     *     $textValidationDto = $validationDto->withFormElementType('Text');
     *     $textValidatorsValidationDto = $textValidationDto->withPropertyCollectionName('validators');
     *     $dateValidationDto = $validationDto->withFormElementType('Date');
     *     $propertyPaths = [
     *         $textValidationDto->withPropertyPath('properties.my.custom.property'),
     *         $textValidationDto->withPropertyPath('properties.my.other.custom.property'),
     *         $textValidatorsValidationDto->withPropertyCollectionElementIdentifier('StringLength')->withPropertyPath('options.custom.property'),
     *         $textValidatorsValidationDto->withPropertyCollectionElementIdentifier('CustomValidator')->withPropertyPath('options.other.custom.property'),
     *         $dateValidationDto->withPropertyPath('properties.custom.property'),
     *         // ..
     *     ];
     *     return $propertyPaths;
     * }
     * @param string $prototypeName
     * @param array $configuration
     * @return array
     * @throws PropertyException
     */
    protected function executeBuildFormDefinitionValidationConfigurationHooks(
        string $prototypeName,
        array $configuration
    ): array {
        foreach ($GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS']['ext/form']['buildFormDefinitionValidationConfiguration'] ?? [] as $className) {
            $hookObj = GeneralUtility::makeInstance($className);
            if (method_exists($hookObj, 'addAdditionalPropertyPaths')) {
                $validationDto = GeneralUtility::makeInstance(ValidationDto::class, $prototypeName);
                $propertyPathsFromHook = $hookObj->addAdditionalPropertyPaths($validationDto);
                if (!is_array($propertyPathsFromHook)) {
                    $message = 'Return value of "%s->addAdditionalPropertyPaths() must be type "array"';
                    throw new PropertyException(sprintf($message, $className), 1528633965);
                }
                $configuration = $this->addAdditionalPropertyPathsFromHook(
                    $className,
                    $prototypeName,
                    $propertyPathsFromHook,
                    $configuration
                );
            }
        }

        return $configuration;
    }

    /**
     * @param string $hookClassName
     * @param string $prototypeName
     * @param array $propertyPathsFromHook
     * @param array $configuration
     * @return array
     * @throws PropertyException
     */
    protected function addAdditionalPropertyPathsFromHook(
        string $hookClassName,
        string $prototypeName,
        array $propertyPathsFromHook,
        array $configuration
    ): array {
        foreach ($propertyPathsFromHook as $index => $validationDto) {
            if (!($validationDto instanceof ValidationDto)) {
                $message = 'Return value of "%s->addAdditionalPropertyPaths()[%s] must be an instance of "%s"';
                throw new PropertyException(
                    sprintf($message, $hookClassName, $index, ValidationDto::class),
                    1528633966
                );
            }

            if ($validationDto->getPrototypeName() !== $prototypeName) {
                $message = 'The prototype name "%s" does not match "%s" on "%s->addAdditionalPropertyPaths()[%s]';
                throw new PropertyException(
                    sprintf(
                        $message,
                        $validationDto->getPrototypeName(),
                        $prototypeName,
                        $hookClassName,
                        $index,
                        ValidationDto::class
                    ),
                    1528634966
                );
            }

            $formElementType = $validationDto->getFormElementType();
            if (!$this->isFormElementTypeDefinedInFormSetup($validationDto)) {
                $message = 'Form element type "%s" does not exist in prototype configuration "%s"';
                throw new PropertyException(
                    sprintf($message, $formElementType, $validationDto->getPrototypeName()),
                    1528633967
                );
            }

            if ($validationDto->hasPropertyCollectionName() &&
                $validationDto->hasPropertyCollectionElementIdentifier()) {
                $propertyCollectionName = $validationDto->getPropertyCollectionName();
                $propertyCollectionElementIdentifier = $validationDto->getPropertyCollectionElementIdentifier();

                if ($propertyCollectionName !== 'finishers' && $propertyCollectionName !== 'validators') {
                    $message = 'The property collection name "%s" for form element "%s" must be "finishers" or "validators"';
                    throw new PropertyException(
                        sprintf($message, $propertyCollectionName, $formElementType),
                        1528636941
                    );
                }

                $configuration['formElements'][$formElementType]['collections'][$propertyCollectionName][$propertyCollectionElementIdentifier]['additionalPropertyPaths'][]
                    = $validationDto->getPropertyPath();
            } else {
                $configuration['formElements'][$formElementType]['additionalPropertyPaths'][]
                    = $validationDto->getPropertyPath();
            }
        }

        return $configuration;
    }

    /**
     * @param string $propertyPath
     * @param array $subConfig
     * @return bool
     */
    protected function isPropertyDefinedInFormEditorSetup(string $propertyPath, array $subConfig): bool
    {
        if (empty($subConfig)) {
            return false;
        }
        if (
            in_array($propertyPath, $subConfig['propertyPaths'] ?? [], true)
            || in_array($propertyPath, $subConfig['additionalElementPropertyPaths'] ?? [], true)
            || in_array($propertyPath, $subConfig['additionalPropertyPaths'] ?? [], true)
        ) {
            return true;
        }
        foreach ($subConfig['multiValueProperties'] ?? [] as $relativeMultiValueProperty) {
            if (strpos($propertyPath, $relativeMultiValueProperty) === 0) {
                return true;
            }
        }

        return false;
    }

    /**
     * @param array $prototypeConfiguration
     * @param array $configuration
     * @return array
     */
    protected function translateValues(array $prototypeConfiguration, array $configuration): array
    {
        if (isset($configuration['formElements'])) {
            $configuration['formElements'] = $this->translatePredefinedDefaults(
                $prototypeConfiguration,
                $configuration['formElements']
            );

            $configuration['formElements'] = $this->translateSelectOptions(
                $prototypeConfiguration,
                $configuration['formElements']
            );
        }

        foreach ($configuration['collections'] ?? [] as $name => $collections) {
            $configuration['collections'][$name] = $this->translatePredefinedDefaults($prototypeConfiguration, $collections);
            $configuration['collections'][$name] = $this->translateSelectOptions($prototypeConfiguration, $configuration['collections'][$name]);
        }
        return $configuration;
    }

    /**
     * @param array $prototypeConfiguration
     * @param array $formElements
     * @return array
     */
    protected function translatePredefinedDefaults(array $prototypeConfiguration, array $formElements): array
    {
        foreach ($formElements ?? [] as $name => $formElement) {
            if (!isset($formElement['predefinedDefaults'])) {
                continue;
            }
            $formElement['untranslatedPredefinedDefaults'] = $formElement['predefinedDefaults'];
            $formElement['predefinedDefaults'] = $this->getTranslationService()->translateValuesRecursive(
                $formElement['predefinedDefaults'],
                $prototypeConfiguration['formEditor']['translationFiles'] ?? []
            );
            $formElements[$name] = $formElement;
        }
        return $formElements;
    }

    /**
     * @param array $prototypeConfiguration
     * @param array $formElements
     * @return array
     */
    protected function translateSelectOptions(array $prototypeConfiguration, array $formElements): array
    {
        foreach ($formElements ?? [] as $name => $formElement) {
            if (empty($formElement['selectOptions']) || !is_array($formElement['selectOptions'])) {
                continue;
            }
            $formElement['untranslatedSelectOptions'] = $formElement['selectOptions'];

            $formElement['selectOptions'] = $this->getTranslationService()->translateValuesRecursive(
                $formElement['selectOptions'],
                $prototypeConfiguration['formEditor']['translationFiles'] ?? []
            );
            $formElements[$name] = $formElement;
        }
        return $formElements;
    }

    /**
     * @param string $cacheKey
     * @return mixed
     */
    protected function getCacheEntry(string $cacheKey)
    {
        if (isset($this->firstLevelCache[$cacheKey])) {
            return $this->firstLevelCache[$cacheKey];
        }
        $cacheValue = $this->getCacheFrontend()->get('form_' . $cacheKey);
        return $cacheValue === false ? null : $cacheValue;
    }

    /**
     * @param string $cacheKey
     * @param mixed $value
     */
    protected function setCacheEntry(string $cacheKey, $value): void
    {
        $this->getCacheFrontend()->set('form_' . $cacheKey, $value);
        $this->firstLevelCache[$cacheKey] = $value;
    }

    /**
     * @return TranslationService
     */
    protected function getTranslationService(): TranslationService
    {
        return $this->translationService instanceof TranslationService
            ? $this->translationService
            : GeneralUtility::makeInstance(TranslationService::class);
    }

    /**
     * @return FrontendInterface
     */
    protected function getCacheFrontend(): FrontendInterface
    {
        return GeneralUtility::makeInstance(CacheManager::class)->getCache('assets');
    }
}
