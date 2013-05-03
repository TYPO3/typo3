<?php
namespace TYPO3\CMS\Extbase\Property\TypeConverter;

/*                                                                        *
 * This script belongs to the Extbase framework                           *
 *                                                                        *
 * It is free software; you can redistribute it and/or modify it under    *
 * the terms of the GNU Lesser General Public License as published by the *
 * Free Software Foundation, either version 3 of the License, or (at your *
 * option) any later version.                                             *
 *                                                                        *
 * This script is distributed in the hope that it will be useful, but     *
 * WITHOUT ANY WARRANTY; without even the implied warranty of MERCHAN-    *
 * TABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the GNU Lesser       *
 * General Public License for more details.                               *
 *                                                                        *
 * You should have received a copy of the GNU Lesser General Public       *
 * License along with the script.                                         *
 * If not, see http://www.gnu.org/licenses/lgpl.html                      *
 *                                                                        *
 * The TYPO3 project - inspiring people to share!                         *
 *                                                                        */
/**
 * This converter transforms arrays to simple objects (POPO) by setting properties.
 *
 * @api
 */
class ObjectConverter extends AbstractTypeConverter implements \TYPO3\CMS\Core\SingletonInterface {

	/**
	 * @var integer
	 */
	const CONFIGURATION_TARGET_TYPE = 3;

	/**
	 * @var integer
	 */
	const CONFIGURATION_OVERRIDE_TARGET_TYPE_ALLOWED = 4;

	/**
	 * @var array
	 */
	protected $sourceTypes = array('array');

	/**
	 * @var string
	 */
	protected $targetType = 'object';

	/**
	 * @var integer
	 */
	protected $priority = 0;

	/**
	 * @var \TYPO3\CMS\Extbase\Object\ObjectManagerInterface
	 * @inject
	 */
	protected $objectManager;

	/**
	 * @var \TYPO3\CMS\Extbase\Reflection\ReflectionService
	 * @inject
	 */
	protected $reflectionService;

	/**
	 * Only convert non-persistent types
	 *
	 * @param mixed $source
	 * @param string $targetType
	 * @return boolean
	 */
	public function canConvertFrom($source, $targetType) {
		return !is_subclass_of($targetType, 'TYPO3\\CMS\\Extbase\\DomainObject\\AbstractDomainObject');
	}

	/**
	 * Convert all properties in the source array
	 *
	 * @param mixed $source
	 * @return array
	 */
	public function getSourceChildPropertiesToBeConverted($source) {
		if (isset($source['__type'])) {
			unset($source['__type']);
		}
		return $source;
	}

	/**
	 * The type of a property is determined by the reflection service.
	 *
	 * @param string $targetType
	 * @param string $propertyName
	 * @param \TYPO3\CMS\Extbase\Property\PropertyMappingConfigurationInterface $configuration
	 * @return string
	 * @throws \TYPO3\CMS\Extbase\Property\Exception\InvalidTargetException
	 */
	public function getTypeOfChildProperty($targetType, $propertyName, \TYPO3\CMS\Extbase\Property\PropertyMappingConfigurationInterface $configuration) {
		$configuredTargetType = $configuration->getConfigurationFor($propertyName)->getConfigurationValue('TYPO3\\CMS\\Extbase\\Property\\TypeConverter\\ObjectConverter', self::CONFIGURATION_TARGET_TYPE);
		if ($configuredTargetType !== NULL) {
			return $configuredTargetType;
		}

		if ($this->reflectionService->hasMethod($targetType, \TYPO3\CMS\Extbase\Reflection\ObjectAccess::buildSetterMethodName($propertyName))) {
			$methodParameters = $this->reflectionService->getMethodParameters($targetType, \TYPO3\CMS\Extbase\Reflection\ObjectAccess::buildSetterMethodName($propertyName));
			$methodParameter = current($methodParameters);
			if (!isset($methodParameter['type'])) {
				throw new \TYPO3\CMS\Extbase\Property\Exception\InvalidTargetException('Setter for property "' . $propertyName . '" had no type hint or documentation in target object of type "' . $targetType . '".', 1303379158);
			} else {
				return $methodParameter['type'];
			}
		} else {
			$methodParameters = $this->reflectionService->getMethodParameters($targetType, '__construct');
			if (isset($methodParameters[$propertyName]) && isset($methodParameters[$propertyName]['type'])) {
				return $methodParameters[$propertyName]['type'];
			} else {
				throw new \TYPO3\CMS\Extbase\Property\Exception\InvalidTargetException('Property "' . $propertyName . '" had no setter or constructor argument in target object of type "' . $targetType . '".', 1303379126);
			}
		}
	}

	/**
	 * Convert an object from $source to an object.
	 *
	 * @param mixed $source
	 * @param string $targetType
	 * @param array $convertedChildProperties
	 * @param \TYPO3\CMS\Extbase\Property\PropertyMappingConfigurationInterface $configuration
	 * @return object the target type
	 * @throws \TYPO3\CMS\Extbase\Property\Exception\InvalidTargetException
	 * @throws \TYPO3\CMS\Extbase\Property\Exception\InvalidDataTypeException
	 * @throws \TYPO3\CMS\Extbase\Property\Exception\InvalidPropertyMappingConfigurationException
	 */
	public function convertFrom($source, $targetType, array $convertedChildProperties = array(), \TYPO3\CMS\Extbase\Property\PropertyMappingConfigurationInterface $configuration = NULL) {
		$object = $this->buildObject($convertedChildProperties, $targetType);
		foreach ($convertedChildProperties as $propertyName => $propertyValue) {
			$result = \TYPO3\CMS\Extbase\Reflection\ObjectAccess::setProperty($object, $propertyName, $propertyValue);
			if ($result === FALSE) {
				$exceptionMessage = sprintf(
					'Property "%s" having a value of type "%s" could not be set in target object of type "%s". Make sure that the property is accessible properly, for example via an appropriate setter method.',
					$propertyName,
					(is_object($propertyValue) ? get_class($propertyValue) : gettype($propertyValue)),
					$targetType
				);
				throw new \TYPO3\CMS\Extbase\Property\Exception\InvalidTargetException($exceptionMessage, 1304538165);
			}
		}

		return $object;
	}

	/**
	 * Determines the target type based on the source's (optional) __type key.
	 *
	 * @param mixed $source
	 * @param string $originalTargetType
	 * @param \TYPO3\CMS\Extbase\Property\PropertyMappingConfigurationInterface $configuration
	 * @return string
	 * @throws \TYPO3\CMS\Extbase\Property\Exception\InvalidDataTypeException
	 * @throws \TYPO3\CMS\Extbase\Property\Exception\InvalidPropertyMappingConfigurationException
	 * @throws \InvalidArgumentException
	 */
	public function getTargetTypeForSource($source, $originalTargetType, \TYPO3\CMS\Extbase\Property\PropertyMappingConfigurationInterface $configuration = NULL) {
		$targetType = $originalTargetType;

		if (is_array($source) && array_key_exists('__type', $source)) {
			$targetType = $source['__type'];

			if ($configuration === NULL) {
				throw new \InvalidArgumentException('A property mapping configuration must be given, not NULL.', 1326277369);
			}
			if ($configuration->getConfigurationValue('TYPO3\CMS\Extbase\Property\TypeConverter\ObjectConverter', self::CONFIGURATION_OVERRIDE_TARGET_TYPE_ALLOWED) !== TRUE) {
				throw new \TYPO3\CMS\Extbase\Property\Exception\InvalidPropertyMappingConfigurationException('Override of target type not allowed. To enable this, you need to set the PropertyMappingConfiguration Value "CONFIGURATION_OVERRIDE_TARGET_TYPE_ALLOWED" to TRUE.', 1317050430);
			}

				// FIXME: The following check and the checkInheritanceChainWithoutIsA() method should be removed if we raise the PHP requirement to 5.3.9 or higher
			if (version_compare(phpversion(), '5.3.8', '>')) {
				if ($targetType !== $originalTargetType && is_a($targetType, $originalTargetType, TRUE) === FALSE) {
					throw new \TYPO3\CMS\Extbase\Property\Exception\InvalidDataTypeException('The given type "' . $targetType . '" is not a subtype of "' . $originalTargetType . '".', 1317048056);
				}
			} else {
				$targetType = $this->checkInheritanceChainWithoutIsA($targetType, $originalTargetType);
			}
		}

		return $targetType;
	}

	/**
	 * Builds a new instance of $objectType with the given $possibleConstructorArgumentValues. If
	 * constructor argument values are missing from the given array the method
	 * looks for a default value in the constructor signature. Furthermore, the constructor arguments are removed from $possibleConstructorArgumentValues
	 *
	 * @param array &$possibleConstructorArgumentValues
	 * @param string $objectType
	 * @return object The created instance
	 * @throws \TYPO3\CMS\Extbase\Property\Exception\InvalidTargetException if a required constructor argument is missing
	 */
	protected function buildObject(array &$possibleConstructorArgumentValues, $objectType) {
		if ($this->reflectionService->hasMethod($objectType, '__construct')) {
			$constructorSignature = $this->reflectionService->getMethodParameters($objectType, '__construct');
			$constructorArguments = array();
			foreach ($constructorSignature as $constructorArgumentName => $constructorArgumentInformation) {
				if (array_key_exists($constructorArgumentName, $possibleConstructorArgumentValues)) {
					$constructorArguments[] = $possibleConstructorArgumentValues[$constructorArgumentName];
					unset($possibleConstructorArgumentValues[$constructorArgumentName]);
				} elseif ($constructorArgumentInformation['optional'] === TRUE) {
					$constructorArguments[] = $constructorArgumentInformation['defaultValue'];
				} else {
					throw new \TYPO3\CMS\Extbase\Property\Exception\InvalidTargetException('Missing constructor argument "' . $constructorArgumentName . '" for object of type "' . $objectType . '".', 1268734872);
				}
			}
			return call_user_func_array(array($this->objectManager, 'get'), array_merge(array($objectType), $constructorArguments));
		} else {
			return $this->objectManager->get($objectType);
		}
	}

	/**
	 * This is a replacement for the functionality provided by is_a() with 3 parameters which is only available from
	 * PHP 5.3.9. It can be removed if the TYPO3 CMS PHP version requirement is raised to 5.3.9 or above.
	 *
	 * @param string $targetType
	 * @param string $originalTargetType
	 * @return string
	 * @throws \TYPO3\CMS\Extbase\Property\Exception\InvalidDataTypeException
	 */
	protected function checkInheritanceChainWithoutIsA($targetType, $originalTargetType) {
		$targetTypeToCompare = $targetType;
		do {
			if ($targetTypeToCompare === $originalTargetType) {
				return $targetType;
			}
		} while ($targetTypeToCompare = get_parent_class($targetTypeToCompare));

		throw new \TYPO3\CMS\Extbase\Property\Exception\InvalidDataTypeException('The given type "' . $targetType . '" is not a subtype of "' . $originalTargetType . '".', 1360928582);
	}

}
?>