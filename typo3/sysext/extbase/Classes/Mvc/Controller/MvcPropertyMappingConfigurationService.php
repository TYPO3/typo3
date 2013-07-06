<?php
namespace TYPO3\CMS\Extbase\Mvc\Controller;

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
 * This is a Service which can generate a request hash and check whether the currently given arguments
 * fit to the request hash.
 *
 * It is used when forms are generated and submitted:
 * After a form has been generated, the method "generateRequestHash" is called with the names of all form fields.
 * It cleans up the array of form fields and creates another representation of it, which is then serialized and hashed.
 *
 * Both serialized form field list and the added hash form the request hash, which will be sent over the wire (as an argument __hmac).
 *
 * On the validation side, the validation happens in two steps:
 * 1) Check if the request hash is consistent (the hash value fits to the serialized string)
 * 2) Check that _all_ GET/POST parameters submitted occur inside the form field list of the request hash.
 *
 * Note: It is crucially important that a private key is computed into the hash value! This is done inside the HashService.
 */
class MvcPropertyMappingConfigurationService implements \TYPO3\CMS\Core\SingletonInterface {

	/**
	 * The hash service class to use
	 *
	 * @var \TYPO3\CMS\Extbase\Security\Cryptography\HashService
	 */
	protected $hashService;

	/**
	 * Inject the hash service
	 *
	 * @param \TYPO3\CMS\Extbase\Security\Cryptography\HashService $hashService
	 */
	public function injectHashService(\TYPO3\CMS\Extbase\Security\Cryptography\HashService $hashService) {
		$this->hashService = $hashService;
	}

	/**
	 * Generate a request hash for a list of form fields
	 *
	 * @param array $formFieldNames Array of form fields
	 * @param string $fieldNamePrefix
	 *
	 * @return string trusted properties token
	 * @throws \TYPO3\CMS\EXTBASE\Security\Exception\InvalidArgumentForHashGenerationException
	 */
	public function generateTrustedPropertiesToken($formFieldNames, $fieldNamePrefix = '') {
		$formFieldArray = array();
		foreach ($formFieldNames as $formField) {
			$formFieldParts = explode('[', $formField);
			$currentPosition = &$formFieldArray;
			for ($i = 0; $i < count($formFieldParts); $i++) {
				$formFieldPart = $formFieldParts[$i];
				$formFieldPart = rtrim($formFieldPart, ']');
				if (!is_array($currentPosition)) {
					throw new \TYPO3\CMS\Extbase\Security\Exception\InvalidArgumentForHashGenerationException('The form field "' . $formField . '" is declared as array, but it collides with a previous form field of the same name which declared the field as string. This is an inconsistency you need to fix inside your Fluid form. (String overridden by Array)', 1255072196);
				}
				if ($i === count($formFieldParts) - 1) {
					if (isset($currentPosition[$formFieldPart]) && is_array($currentPosition[$formFieldPart])) {
						throw new \TYPO3\CMS\Extbase\Security\Exception\InvalidArgumentForHashGenerationException('The form field "' . $formField . '" is declared as string, but it collides with a previous form field of the same name which declared the field as array. This is an inconsistency you need to fix inside your Fluid form. (Array overridden by String)', 1255072587);
					}
					// Last iteration - add a string
					if ($formFieldPart === '') {
						$currentPosition[] = 1;
					} else {
						$currentPosition[$formFieldPart] = 1;
					}
				} else {
					if ($formFieldPart === '') {
						throw new \TYPO3\CMS\Extbase\Security\Exception\InvalidArgumentForHashGenerationException('The form field "' . $formField . '" is invalid. Reason: "[]" used not as last argument, but somewhere in the middle (like foo[][bar]).', 1255072832);
					}
					if (!isset($currentPosition[$formFieldPart])) {
						$currentPosition[$formFieldPart] = array();
					}
					$currentPosition = &$currentPosition[$formFieldPart];
				}
			}
		}
		if ($fieldNamePrefix !== '') {
			$formFieldArray = (isset($formFieldArray[$fieldNamePrefix]) ? $formFieldArray[$fieldNamePrefix] : array());
		}
		return $this->serializeAndHashFormFieldArray($formFieldArray);
	}

	/**
	 * Serialize and hash the form field array
	 *
	 * @param array $formFieldArray form field array to be serialized and hashed
	 *
	 * @return string Hash
	 */
	protected function serializeAndHashFormFieldArray(array $formFieldArray) {
		$serializedFormFieldArray = serialize($formFieldArray);
		return $this->hashService->appendHmac($serializedFormFieldArray);
	}

	/**
	 * Initialize the property mapping configuration in $controllerArguments if
	 * the trusted properties are set inside the request.
	 *
	 * @param \TYPO3\CMS\Extbase\Mvc\Request $request
	 * @param \TYPO3\CMS\Extbase\Mvc\Controller\Arguments $controllerArguments
	 *
	 * @return void
	 */
	public function initializePropertyMappingConfigurationFromRequest(\TYPO3\CMS\Extbase\Mvc\Request $request, \TYPO3\CMS\Extbase\Mvc\Controller\Arguments $controllerArguments) {
		$trustedPropertiesToken = $request->getInternalArgument('__trustedProperties');
		if (!is_string($trustedPropertiesToken)) {
			return;
		}

		$serializedTrustedProperties = $this->hashService->validateAndStripHmac($trustedPropertiesToken);
		$trustedProperties = unserialize($serializedTrustedProperties);
		foreach ($trustedProperties as $propertyName => $propertyConfiguration) {
			if (!$controllerArguments->hasArgument($propertyName)) {
				continue;
			}
			$propertyMappingConfiguration = $controllerArguments->getArgument($propertyName)->getPropertyMappingConfiguration();
			$this->modifyPropertyMappingConfiguration($propertyConfiguration, $propertyMappingConfiguration);
		}
	}

	/**
	 * Modify the passed $propertyMappingConfiguration according to the $propertyConfiguration which
	 * has been generated by Fluid. In detail, if the $propertyConfiguration contains
	 * an __identity field, we allow modification of objects; else we allow creation.
	 *
	 * All other properties are specified as allowed properties.
	 *
	 * @param array $propertyConfiguration
	 * @param \TYPO3\CMS\Extbase\Property\PropertyMappingConfiguration $propertyMappingConfiguration
	 *
	 * @return void
	 */
	protected function modifyPropertyMappingConfiguration($propertyConfiguration, \TYPO3\CMS\Extbase\Property\PropertyMappingConfiguration $propertyMappingConfiguration) {
		if (!is_array($propertyConfiguration)) {
			return;
		}

		if (isset($propertyConfiguration['__identity'])) {
			$propertyMappingConfiguration->setTypeConverterOption('TYPO3\CMS\Extbase\Property\TypeConverter\PersistentObjectConverter', \TYPO3\CMS\Extbase\Property\TypeConverter\PersistentObjectConverter::CONFIGURATION_MODIFICATION_ALLOWED, TRUE);
			unset($propertyConfiguration['__identity']);
		} else {
			$propertyMappingConfiguration->setTypeConverterOption('TYPO3\CMS\Extbase\Property\TypeConverter\PersistentObjectConverter', \TYPO3\CMS\Extbase\Property\TypeConverter\PersistentObjectConverter::CONFIGURATION_CREATION_ALLOWED, TRUE);
		}

		foreach ($propertyConfiguration as $innerKey => $innerValue) {
			if (is_array($innerValue)) {
				$this->modifyPropertyMappingConfiguration($innerValue, $propertyMappingConfiguration->forProperty($innerKey));
			}
			$propertyMappingConfiguration->allowProperties($innerKey);
		}
	}
}

?>