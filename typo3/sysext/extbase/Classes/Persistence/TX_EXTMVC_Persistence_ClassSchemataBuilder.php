<?php
declare(ENCODING = 'utf-8');

/*                                                                        *
 * This script belongs to the FLOW3 framework.                            *
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
 * The Class Schemata Builder is used by the Persistence Manager to build class
 * schemata for all classes tagged as ValueObject or Entity.
 *
 * @version $Id:$
 * @license http://www.gnu.org/licenses/lgpl.html GNU Lesser General Public License, version 3 or later
 */
class ClassSchemataBuilder {

	/**
	 * The reflection service
	 *
	 * @var F3_FLOW3_Reflection_Service
	 */
	protected $reflectionService;

	/**
	 * Constructor
	 *
	 * @param F3_FLOW3_Reflection_Service $reflectionService The reflection service
	 * @author Robert Lemke <robert@typo3.org>
	 */
	public function __construct(F3_FLOW3_Reflection_Service $reflectionService) {
		$this->reflectionService = $reflectionService;
	}

	/**
	 * Builds class schemata from the specified classes
	 *
	 * @param array $classNames Names of the classes to build schemata from
	 * @return array of TX_EXTMVC_Persistence_ClassSchema
	 * @author Robert Lemke <robert@typo3.org>
	 * @throws TX_EXTMVC_Persistence_Exception_InvalidClass if one of the specified classes does not exist
	 */
	public function build(array $classNames) {
		$classSchemata = array();
		foreach ($classNames as $className) {
			if (!class_exists($className)) throw new TX_EXTMVC_Persistence_Exception_InvalidClass('Unknown class "' . $className . '".', 1214495364);

			$modelType = NULL;
			if ($this->reflectionService->isClassTaggedWith($className, 'entity')) {
				$modelType = TX_EXTMVC_Persistence_ClassSchema::MODELTYPE_ENTITY;
			} elseif ($this->reflectionService->isClassImplementationOf($className, 'TX_EXTMVC_Persistence_RepositoryInterface')) {
				$modelType = TX_EXTMVC_Persistence_ClassSchema::MODELTYPE_REPOSITORY;
			} elseif ($this->reflectionService->isClassTaggedWith($className, 'valueobject')) {
				$modelType = TX_EXTMVC_Persistence_ClassSchema::MODELTYPE_VALUEOBJECT;
			} else {
				continue;
			}

			$classSchema = new TX_EXTMVC_Persistence_ClassSchema($className);
			$classSchema->setModelType($modelType);
			foreach ($this->reflectionService->getClassPropertyNames($className) as $propertyName) {
				if ($this->reflectionService->isPropertyTaggedWith($className, $propertyName, 'uid')) {
					$classSchema->setUidProperty($propertyName);
				}
				if (!$this->reflectionService->isPropertyTaggedWith($className, $propertyName, 'transient') && $this->reflectionService->isPropertyTaggedWith($className, $propertyName, 'var')) {
					$classSchema->setProperty($propertyName, implode(' ', $this->reflectionService->getPropertyTagValues($className, $propertyName, 'var')));
				}
			}
			$classSchemata[$className] = $classSchema;
		}
		return $classSchemata;
	}

}
?>