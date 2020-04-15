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

use TYPO3\CMS\Extbase\Error\Error;
use TYPO3\CMS\Extbase\Property\PropertyMappingConfigurationInterface;

/**
 * Converter which transforms a simple type to an integer, by simply casting it.
 */
class IntegerConverter extends AbstractTypeConverter
{
    /**
     * @var string[]
     */
    protected $sourceTypes = ['integer', 'string'];

    /**
     * @var string
     */
    protected $targetType = 'integer';

    /**
     * @var int
     */
    protected $priority = 10;

    /**
     * Actually convert from $source to $targetType, in fact a noop here.
     *
     * @param int|string|null $source
     * @param string $targetType
     * @param array $convertedChildProperties
     * @param \TYPO3\CMS\Extbase\Property\PropertyMappingConfigurationInterface $configuration
     * @return int|\TYPO3\CMS\Extbase\Error\Error
     */
    public function convertFrom($source, string $targetType, array $convertedChildProperties = [], PropertyMappingConfigurationInterface $configuration = null)
    {
        if ($source === null || (string)$source === '') {
            return null;
        }
        if (!is_numeric($source)) {
            return new Error('"%s" is no integer.', 1332933658, [$source]);
        }
        return (int)$source;
    }
}
