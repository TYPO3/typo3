<?php
namespace TYPO3\CMS\Form\Domain\Property\TypeConverter;

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

use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Extbase\Property\TypeConverter\AbstractTypeConverter;
use TYPO3\CMS\Extbase\Property\PropertyMappingConfigurationInterface;
use TYPO3\CMS\Form\Domain\Model\ValidationElement;

/**
 * The form wizard controller
 */
class ArrayToValidationElementConverter extends AbstractTypeConverter {

	/**
	 * @var array<string>
	 */
	protected $sourceTypes = array('array');

	/**
	 * @var string
	 */
	protected $targetType = 'TYPO3\\CMS\\Form\\Domain\\Model\\ValidationElement';

	/**
	 * @var int
	 */
	protected $priority = 1;

	/**
	 * We can only convert empty strings to array or array to array.
	 *
	 * @param mixed $source
	 * @param string $targetType
	 * @return bool
	 */
	public function canConvertFrom($source, $targetType) {
		return is_array($source);
	}

	/**
	 * Convert the incoming array to a ValidationElement
	 *
	 * @param array $source
	 * @param string $targetType
	 * @param array $convertedChildProperties
	 * @param PropertyMappingConfigurationInterface $configuration
	 * @return ValidationElement
	 * @api
	 */
	public function convertFrom($source, $targetType, array $convertedChildProperties = array(), PropertyMappingConfigurationInterface $configuration = NULL) {
		/** @var ValidationElement $validationElement */
		$validationElement = GeneralUtility::makeInstance(ValidationElement::class);

		if (is_array($source)) {
			$validationElement->setIncomingFields($source);
		}

		return $validationElement;
	}
}
