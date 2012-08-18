<?php
/***************************************************************
 *  Copyright notice
 *
 *  (c) 2012 Oliver Hader <oliver.hader@typo3.org>
 *  All rights reserved
 *
 *  This script is part of the TYPO3 project. The TYPO3 project is
 *  free software; you can redistribute it and/or modify
 *  it under the terms of the GNU General Public License as published by
 *  the Free Software Foundation; either version 2 of the License, or
 *  (at your option) any later version.
 *
 *  The GNU General Public License can be found at
 *  http://www.gnu.org/copyleft/gpl.html.
 *
 *  This script is distributed in the hope that it will be useful,
 *  but WITHOUT ANY WARRANTY; without even the implied warranty of
 *  MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
 *  GNU General Public License for more details.
 *
 *  This copyright notice MUST APPEAR in all copies of the script!
 ***************************************************************/

/**
 * Converter which transforms simple types to Tx_Extbase_Domain_Model_File.
 *
 * @api experimental! This class is experimental and subject to change!
 */
abstract class Tx_Extbase_Property_TypeConverter_AbstractFileCollectionConverter extends Tx_Extbase_Property_TypeConverter_AbstractTypeConverter {
	/**
	 * @var integer
	 */
	protected $priority = 1;

	/**
	 * @var string
	 */
	protected $expectedObjectType;

	/**
	 * @var t3lib_file_Factory
	 */
	protected $fileFactory;

	/**
	 * @param t3lib_file_Factory $fileFactory
	 */
	public function injectFileFactory(t3lib_file_Factory $fileFactory) {
		$this->fileFactory = $fileFactory;
	}

	/**
	 * Actually convert from $source to $targetType, taking into account the fully
	 * built $convertedChildProperties and $configuration.
	 *
	 * @param integer $source
	 * @param string $targetType
	 * @param array $convertedChildProperties
	 * @param Tx_Extbase_Property_PropertyMappingConfigurationInterface $configuration
	 * @return Tx_Extbase_Domain_Model_AbstractFileCollection
	 * @api
	 */
	public function convertFrom($source, $targetType, array $convertedChildProperties = array(), Tx_Extbase_Property_PropertyMappingConfigurationInterface $configuration = NULL) {
		$object = $this->getObject($source);

		if (empty($this->expectedObjectType) || !$object instanceof $this->expectedObjectType) {
			throw new Tx_Extbase_Property_Exception(
				'Expected object of type "' . $this->expectedObjectType . '" but got ' . get_class($object),
				1342895975
			);
		}

		/** @var $subject Tx_Extbase_Domain_Model_AbstractFileCollection */
		$subject = $this->objectManager->create($targetType);
		$subject->setObject($object);

		return $subject;
	}

	/**
	 * @param integer $source
	 * @return t3lib_file_Collection_AbstractFileCollection
	 */
	abstract protected function getObject($source);
}
?>