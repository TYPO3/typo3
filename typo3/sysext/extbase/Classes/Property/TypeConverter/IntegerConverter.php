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
 * Converter which transforms a simple type to an integer, by simply casting it.
 *
 * @api
 */
class IntegerConverter extends \TYPO3\CMS\Extbase\Property\TypeConverter\AbstractTypeConverter implements \TYPO3\CMS\Core\SingletonInterface {

	/**
	 * @var array<string>
	 */
	protected $sourceTypes = array('integer', 'string');

	/**
	 * @var string
	 */
	protected $targetType = 'integer';

	/**
	 * @var int
	 */
	protected $priority = 1;

	/**
	 * Actually convert from $source to $targetType, in fact a noop here.
	 *
	 * @param int|string $source
	 * @param string $targetType
	 * @param array $convertedChildProperties
	 * @param \TYPO3\CMS\Extbase\Property\PropertyMappingConfigurationInterface $configuration
	 * @return int|\TYPO3\CMS\Extbase\Error\Error
	 * @api
	 */
	public function convertFrom($source, $targetType, array $convertedChildProperties = array(), \TYPO3\CMS\Extbase\Property\PropertyMappingConfigurationInterface $configuration = NULL) {
		if ($source === NULL || (string)$source === '') {
			return NULL;
		}
		if (!is_numeric($source)) {
			return new \TYPO3\CMS\Extbase\Error\Error('"%s" is no integer.', 1332933658, array($source));
		}
		return (int)$source;
	}

}
