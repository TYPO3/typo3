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

namespace TYPO3\CMS\Form\Mvc\Property\TypeConverter;

use TYPO3\CMS\Core\Authentication\BackendUserAuthentication;
use TYPO3\CMS\Core\Utility\ArrayUtility;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Extbase\Property\PropertyMappingConfigurationInterface;
use TYPO3\CMS\Extbase\Property\TypeConverter\AbstractTypeConverter;
use TYPO3\CMS\Form\Domain\Configuration\ConfigurationService;
use TYPO3\CMS\Form\Domain\Configuration\Exception\PropertyException;
use TYPO3\CMS\Form\Domain\Configuration\FormDefinitionConversionService;
use TYPO3\CMS\Form\Domain\Configuration\FormDefinitionValidationService;
use TYPO3\CMS\Form\Type\FormDefinitionArray;

/**
 * Converter for form definition arrays
 *
 * @internal
 */
class FormDefinitionArrayConverter extends AbstractTypeConverter
{
    /**
     * @var array<string>
     */
    protected $sourceTypes = ['string'];

    /**
     * @var string
     */
    protected $targetType = FormDefinitionArray::class;

    /**
     * @var int
     */
    protected $priority = 10;

    /**
     * @var ConfigurationService
     */
    protected $configurationService;

    /**
     * Convert from $source to $targetType, a noop if the source is an array.
     * If it is an empty string it will be converted to an empty array.
     *
     * @param string $source
     * @param string $targetType
     * @param array $convertedChildProperties
     * @param PropertyMappingConfigurationInterface $configuration
     * @return FormDefinitionArray
     * @throws PropertyException
     */
    public function convertFrom($source, $targetType, array $convertedChildProperties = [], PropertyMappingConfigurationInterface $configuration = null)
    {
        $rawFormDefinitionArray = json_decode($source, true);

        if (json_last_error() !== JSON_ERROR_NONE) {
            throw new PropertyException('Unable to decode JSON source: ' . json_last_error_msg(), 1512578002);
        }

        $formDefinitionValidationService = $this->getFormDefinitionValidationService();
        $formDefinitionConversionService = $this->getFormDefinitionConversionService();

        // Extend the hmac hashing key with the "per form editor session (load / save)" unique key.
        // @see \TYPO3\CMS\Form\Domain\Configuration\FormDefinitionConversionService::addHmacData
        $sessionToken = $this->retrieveSessionToken();

        $prototypeName = $rawFormDefinitionArray['prototypeName'] ?? null;
        $identifier = $rawFormDefinitionArray['identifier'] ?? null;

        // A modification of the properties "prototypeName" and "identifier" from the root form element
        // through the form editor is always forbidden.
        try {
            if (!$formDefinitionValidationService->isPropertyValueEqualToHistoricalValue([$identifier, 'identifier'], $identifier, $rawFormDefinitionArray['_orig_identifier'] ?? [], $sessionToken)) {
                throw new PropertyException('Unauthorized modification of "identifier".', 1528538324);
            }

            if (!$formDefinitionValidationService->isPropertyValueEqualToHistoricalValue([$identifier, 'prototypeName'], $prototypeName, $rawFormDefinitionArray['_orig_prototypeName'] ?? [], $sessionToken)) {
                throw new PropertyException('Unauthorized modification of "prototype name".', 1528538323);
            }
        } catch (PropertyException $e) {
            throw new PropertyException('Unauthorized modification of "prototype name" or "identifier".', 1528538322);
        }

        $formDefinitionValidationService->validateFormDefinitionProperties($rawFormDefinitionArray, $prototypeName, $sessionToken);

        // @todo move all the transformations to FormDefinitionConversionService
        $rawFormDefinitionArray = $this->filterEmptyArrays($rawFormDefinitionArray);
        $rawFormDefinitionArray = $this->transformMultiValueElementsForFormFramework($rawFormDefinitionArray);
        // @todo: replace with rte parsing
        $rawFormDefinitionArray = ArrayUtility::stripTagsFromValuesRecursive($rawFormDefinitionArray);
        $rawFormDefinitionArray = $formDefinitionConversionService->removeHmacData($rawFormDefinitionArray);

        $formDefinitionArray = GeneralUtility::makeInstance(FormDefinitionArray::class, $rawFormDefinitionArray);
        return $formDefinitionArray;
    }

    /**
     * Some data which is build by the form editor needs a transformation before
     * it can be used by the framework.
     * Multivalue elements like select elements produce data like:
     *
     * [
     *   _label => 'label'
     *   _value => 'value'
     * ]
     *
     * This method transforms this into:
     *
     * [
     *   'value' => 'label'
     * ]
     *
     * @param array $input
     * @return array
     */
    protected function transformMultiValueElementsForFormFramework(array $input): array
    {
        $output = [];

        foreach ($input as $key => $value) {
            if (is_int($key) && is_array($value) && isset($value['_label']) && isset($value['_value'])) {
                $key = $value['_value'];
                $value = $value['_label'];
            }

            if (is_array($value)) {
                $output[$key] = $this->transformMultiValueElementsForFormFramework($value);
            } else {
                $output[$key] = $value;
            }
        }

        return $output;
    }

    /**
     * Remove keys from an array if the key value is an empty array
     *
     * @todo ArrayUtility?
     * @param array $array
     * @return array
     */
    protected function filterEmptyArrays(array $array): array
    {
        foreach ($array as $key => $value) {
            if (!is_array($value)) {
                continue;
            }
            if (empty($value)) {
                unset($array[$key]);
                continue;
            }
            $array[$key] = $this->filterEmptyArrays($value);
            if (empty($array[$key])) {
                unset($array[$key]);
            }
        }

        return $array;
    }

    /**
     * @return string
     */
    protected function retrieveSessionToken(): string
    {
        return $this->getBackendUser()->getSessionData('extFormProtectionSessionToken');
    }

    /**
     * @return FormDefinitionValidationService
     */
    protected function getFormDefinitionValidationService(): FormDefinitionValidationService
    {
        return GeneralUtility::makeInstance(FormDefinitionValidationService::class);
    }

    /**
     * @return FormDefinitionConversionService
     */
    protected function getFormDefinitionConversionService(): FormDefinitionConversionService
    {
        return GeneralUtility::makeInstance(FormDefinitionConversionService::class);
    }

    /**
     * @return BackendUserAuthentication
     */
    protected function getBackendUser(): BackendUserAuthentication
    {
        return $GLOBALS['BE_USER'];
    }
}
