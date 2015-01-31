<?php
namespace TYPO3\CMS\Extbase\Mvc\Controller;

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
 * The default property mapping configuration is available
 * inside the Argument-object.
 *
 * @license http://www.gnu.org/licenses/lgpl.html GNU Lesser General Public License, version 3 or later
 * @api
 */
class MvcPropertyMappingConfiguration extends \TYPO3\CMS\Extbase\Property\PropertyMappingConfiguration {

	/**
	 * Allow creation of a certain sub property
	 *
	 * @param string $propertyPath
	 * @return void
	 * @api
	 */
	public function allowCreationForSubProperty($propertyPath) {
		$this->forProperty($propertyPath)->setTypeConverterOption('TYPO3\\CMS\\Extbase\\Property\\TypeConverter\\PersistentObjectConverter', \TYPO3\CMS\Extbase\Property\TypeConverter\PersistentObjectConverter::CONFIGURATION_CREATION_ALLOWED, TRUE);
	}

	/**
	 * Allow modification for a given property path
	 *
	 * @param string $propertyPath
	 * @return void
	 * @api
	 */
	public function allowModificationForSubProperty($propertyPath) {
		$this->forProperty($propertyPath)->setTypeConverterOption('TYPO3\\CMS\\Extbase\\Property\\TypeConverter\\PersistentObjectConverter', \TYPO3\CMS\Extbase\Property\TypeConverter\PersistentObjectConverter::CONFIGURATION_MODIFICATION_ALLOWED, TRUE);
	}

	/**
	 * Set the target type for a certain property. Especially useful
	 * if there is an object which has a nested object which is abstract,
	 * and you want to instanciate a concrete object instead.
	 *
	 * @param string $propertyPath
	 * @param string $targetType
	 * @return void
	 * @api
	 */
	public function setTargetTypeForSubProperty($propertyPath, $targetType) {
		$this->forProperty($propertyPath)->setTypeConverterOption('TYPO3\\CMS\\Extbase\\Property\\TypeConverter\\PersistentObjectConverter', \TYPO3\CMS\Extbase\Property\TypeConverter\PersistentObjectConverter::CONFIGURATION_TARGET_TYPE, $targetType);
	}
}
