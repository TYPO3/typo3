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
 * Converter which transforms a simple type to a float.
 *
 * This is basically done by simply casting it.
 *
 * @api
 */
class FloatConverter extends \TYPO3\CMS\Extbase\Property\TypeConverter\AbstractTypeConverter {

	/**
	 * @var array<string>
	 */
	protected $sourceTypes = array('float', 'integer', 'string');

	/**
	 * @var string
	 */
	protected $targetType = 'float';

	/**
	 * @var int
	 */
	protected $priority = 1;

	/**
	 * Actually convert from $source to $targetType, by doing a typecast.
	 *
	 * @param mixed $source
	 * @param string $targetType
	 * @param array $convertedChildProperties
	 * @param \TYPO3\CMS\Extbase\Property\PropertyMappingConfigurationInterface $configuration
	 * @return float|\TYPO3\CMS\Extbase\Error\Error
	 * @api
	 */
	public function convertFrom($source, $targetType, array $convertedChildProperties = array(), \TYPO3\CMS\Extbase\Property\PropertyMappingConfigurationInterface $configuration = NULL) {
		if ($source === NULL || (string)$source === '') {
			return NULL;
		}
		// We won't backport the full flavored locale parsing of floats from Flow here

		if (!is_numeric($source)) {
			return new \TYPO3\CMS\Extbase\Error\Error('"%s" cannot be converted to a float value.', 1332934124, array($source));
		}
		return (float) $source;
	}

}
