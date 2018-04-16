<?php
declare(strict_types = 1);
namespace TYPO3\CMS\Form\Mvc\Property\TypeConverter;

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

use TYPO3\CMS\Core\Utility\ArrayUtility;
use TYPO3\CMS\Extbase\Property\Exception as PropertyException;
use TYPO3\CMS\Extbase\Property\PropertyMappingConfigurationInterface;
use TYPO3\CMS\Extbase\Property\TypeConverter\AbstractTypeConverter;
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

        $rawFormDefinitionArray = ArrayUtility::stripTagsFromValuesRecursive($rawFormDefinitionArray);
        $rawFormDefinitionArray = $this->transformMultiValueElementsForFormFramework($rawFormDefinitionArray);
        $formDefinitionArray = new FormDefinitionArray($rawFormDefinitionArray);

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
     * This method transform this into:
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
}
