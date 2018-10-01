<?php
namespace TYPO3\CMS\Extbase\Property\TypeConverter;

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

/**
 * Converter which transforms arrays to arrays.
 */
class ArrayConverter extends \TYPO3\CMS\Extbase\Property\TypeConverter\AbstractTypeConverter
{
    /**
     * @var array<string>
     */
    protected $sourceTypes = ['array', 'string'];

    /**
     * @var string
     */
    protected $targetType = 'array';

    /**
     * @var int
     */
    protected $priority = 10;

    /**
     * We can only convert empty strings to array or array to array.
     *
     * @param mixed $source
     * @param string $targetType
     * @return bool
     * @internal only to be used within Extbase, not part of TYPO3 Core API.
     */
    public function canConvertFrom($source, $targetType)
    {
        return is_string($source) && $source === '' || is_array($source);
    }

    /**
     * Convert from $source to $targetType, a noop if the source is an array.
     * If it is an empty string it will be converted to an empty array.
     *
     * @param string|array $source
     * @param string $targetType
     * @param array $convertedChildProperties
     * @param \TYPO3\CMS\Extbase\Property\PropertyMappingConfigurationInterface $configuration
     * @return array
     */
    public function convertFrom($source, $targetType, array $convertedChildProperties = [], \TYPO3\CMS\Extbase\Property\PropertyMappingConfigurationInterface $configuration = null)
    {
        if (is_string($source)) {
            if ($source === '') {
                $source = [];
            }
        }

        return $source;
    }
}
