<?php
namespace TYPO3\CMS\Extbase\Property\TypeConverter;

/***************************************************************
 *  Copyright notice
 *
 *  (c) 2010-2013 Extbase Team (http://forge.typo3.org/projects/typo3v4-mvc)
 *  Extbase is a backport of TYPO3 Flow. All credits go to the TYPO3 Flow team.
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
 *  A copy is found in the textfile GPL.txt and important notices to the license
 *  from the author is found in LICENSE.txt distributed with these scripts.
 *
 *
 *  This script is distributed in the hope that it will be useful,
 *  but WITHOUT ANY WARRANTY; without even the implied warranty of
 *  MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 *  GNU General Public License for more details.
 *
 *  This copyright notice MUST APPEAR in all copies of the script!
 ***************************************************************/
/**
 * Converter which transforms simple types to \TYPO3\CMS\Extbase\Domain\Model\File.
 *
 * @api experimental! This class is experimental and subject to change!
 */
abstract class AbstractFileCollectionConverter extends \TYPO3\CMS\Extbase\Property\TypeConverter\AbstractTypeConverter {

	/**
	 * @var integer
	 */
	protected $priority = 1;

	/**
	 * @var string
	 */
	protected $expectedObjectType;

	/**
	 * @var \TYPO3\CMS\Core\Resource\ResourceFactory
	 */
	protected $fileFactory;

	/**
	 * @param \TYPO3\CMS\Core\Resource\ResourceFactory $fileFactory
	 */
	public function injectFileFactory(\TYPO3\CMS\Core\Resource\ResourceFactory $fileFactory) {
		$this->fileFactory = $fileFactory;
	}

	/**
	 * Actually convert from $source to $targetType, taking into account the fully
	 * built $convertedChildProperties and $configuration.
	 *
	 * @param integer $source
	 * @param string $targetType
	 * @param array $convertedChildProperties
	 * @param \TYPO3\CMS\Extbase\Property\PropertyMappingConfigurationInterface $configuration
	 * @throws \TYPO3\CMS\Extbase\Property\Exception
	 * @return \TYPO3\CMS\Extbase\Domain\Model\AbstractFileCollection
	 * @api
	 */
	public function convertFrom($source, $targetType, array $convertedChildProperties = array(), \TYPO3\CMS\Extbase\Property\PropertyMappingConfigurationInterface $configuration = NULL) {
		$object = $this->getObject($source);
		if (empty($this->expectedObjectType) || !$object instanceof $this->expectedObjectType) {
			throw new \TYPO3\CMS\Extbase\Property\Exception('Expected object of type "' . $this->expectedObjectType . '" but got ' . get_class($object), 1342895975);
		}
		/** @var $subject \TYPO3\CMS\Extbase\Domain\Model\AbstractFileCollection */
		$subject = $this->objectManager->get($targetType);
		$subject->setObject($object);
		return $subject;
	}

	/**
	 * @param integer $source
	 * @return \TYPO3\CMS\Core\Resource\Collection\AbstractFileCollection
	 */
	abstract protected function getObject($source);
}

?>