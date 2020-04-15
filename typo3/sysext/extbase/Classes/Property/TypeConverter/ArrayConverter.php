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

namespace TYPO3\CMS\Extbase\Property\TypeConverter;

use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Extbase\Property\Exception\TypeConverterException;
use TYPO3\CMS\Extbase\Property\PropertyMappingConfigurationInterface;

/**
 * Converter which transforms strings/arrays to arrays.
 */
class ArrayConverter extends AbstractTypeConverter
{
    public const CONFIGURATION_DELIMITER = 'delimiter';
    public const CONFIGURATION_REMOVE_EMPTY_VALUES = 'removeEmptyValues';
    public const CONFIGURATION_LIMIT = 'limit';

    /**
     * @var string[]
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
    public function canConvertFrom($source, string $targetType): bool
    {
        return is_string($source) || is_array($source);
    }

    /**
     * Convert from $source to $targetType, a noop if the source is an array.
     * If it is an empty string it will be converted to an empty array.
     * If the type converter has a configuration, it can convert non-empty strings, too
     *
     * @param string|array $source
     * @param string $targetType
     * @param array $convertedChildProperties
     * @param \TYPO3\CMS\Extbase\Property\PropertyMappingConfigurationInterface $configuration
     * @return array|string
     */
    public function convertFrom($source, string $targetType, array $convertedChildProperties = [], PropertyMappingConfigurationInterface $configuration = null)
    {
        if (!is_string($source)) {
            return $source;
        }
        if ($source === '') {
            return [];
        }
        if ($configuration === null) {
            return $source;
        }
        $delimiter = $configuration->getConfigurationValue(self::class, self::CONFIGURATION_DELIMITER);
        $removeEmptyValues = $configuration->getConfigurationValue(self::class, self::CONFIGURATION_REMOVE_EMPTY_VALUES) ?? false;
        $limit = $configuration->getConfigurationValue(self::class, self::CONFIGURATION_LIMIT) ?? 0;
        if (!is_string($delimiter)) {
            throw new TypeConverterException('No delimiter configured for ' . self::class . ' and non-empty value given.', 1582877555);
        }
        $source = GeneralUtility::trimExplode($delimiter, $source, $removeEmptyValues, $limit);

        return $source;
    }
}
