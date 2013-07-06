<?php
namespace TYPO3\CMS\Extbase\Security\Channel;

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
 *
 * @license http://www.gnu.org/licenses/lgpl.html GNU Lesser General Public License, version 3 or later
 */
class RequestHashService implements \TYPO3\CMS\Core\SingletonInterface {

	/**
	 * @var \TYPO3\CMS\Extbase\Security\Cryptography\HashService
	 */
	protected $hashService;

	/**
	 * @param \TYPO3\CMS\Extbase\Security\Cryptography\HashService $hashService
	 * @return void
	 */
	public function injectHashService(\TYPO3\CMS\Extbase\Security\Cryptography\HashService $hashService) {
		$this->hashService = $hashService;
	}

	/**
	 * Generate a request hash for a list of form fields
	 *
	 * @param array $formFieldNames Array of form fields
	 * @param string $fieldNamePrefix
	 * @throws \TYPO3\CMS\Extbase\Security\Exception\InvalidArgumentForRequestHashGenerationException
	 * @return string request hash
	 * @todo might need to become public API lateron, as we need to call it from Fluid
	 */
	public function generateRequestHash($formFieldNames, $fieldNamePrefix = '') {
		$formFieldArray = array();
		foreach ($formFieldNames as $formField) {
			$formFieldParts = explode('[', $formField);
			$currentPosition = &$formFieldArray;
			for ($i = 0; $i < count($formFieldParts); $i++) {
				$formFieldPart = $formFieldParts[$i];
				if (substr($formFieldPart, -1) == ']') {
					$formFieldPart = substr($formFieldPart, 0, -1);
				}
				// Strip off closing ] if needed
				if (!is_array($currentPosition)) {
					throw new \TYPO3\CMS\Extbase\Security\Exception\InvalidArgumentForRequestHashGenerationException('The form field name "' . $formField . '" collides with a previous form field name which declared the field as string. (String overridden by Array)', 1255072196);
				}
				if ($i == count($formFieldParts) - 1) {
					if (isset($currentPosition[$formFieldPart]) && is_array($currentPosition[$formFieldPart])) {
						throw new \TYPO3\CMS\Extbase\Security\Exception\InvalidArgumentForRequestHashGenerationException('The form field name "' . $formField . '" collides with a previous form field name which declared the field as array. (Array overridden by String)', 1255072587);
					}
					// Last iteration - add a string
					if ($formFieldPart === '') {
						$currentPosition[] = 1;
					} else {
						$currentPosition[$formFieldPart] = 1;
					}
				} else {
					if ($formFieldPart === '') {
						throw new \TYPO3\CMS\Extbase\Security\Exception\InvalidArgumentForRequestHashGenerationException('The form field name "' . $formField . '" is invalid. Reason: "[]" used not as last argument.', 1255072832);
					}
					if (!isset($currentPosition[$formFieldPart])) {
						$currentPosition[$formFieldPart] = array();
					}
					$currentPosition = &$currentPosition[$formFieldPart];
				}
			}
		}
		if ($fieldNamePrefix !== '') {
			$formFieldArray = isset($formFieldArray[$fieldNamePrefix]) ? $formFieldArray[$fieldNamePrefix] : array();
		}
		return $this->serializeAndHashFormFieldArray($formFieldArray);
	}

	/**
	 * Serialize and hash the form field array
	 *
	 * @param array $formFieldArray form field array to be serialized and hashed
	 * @return string Hash
	 */
	protected function serializeAndHashFormFieldArray($formFieldArray) {
		$serializedFormFieldArray = serialize($formFieldArray);
		return $serializedFormFieldArray . $this->hashService->generateHmac($serializedFormFieldArray);
	}

	/**
	 * Verify the request. Checks if there is an __hmac argument, and if yes, tries to validate and verify it.
	 *
	 * In the end, $request->setHmacVerified is set depending on the value.
	 *
	 * @param \TYPO3\CMS\Extbase\Mvc\Web\Request $request The request to verify
	 * @throws \TYPO3\CMS\Extbase\Security\Exception\SyntacticallyWrongRequestHashException
	 * @return void
	 */
	public function verifyRequest(\TYPO3\CMS\Extbase\Mvc\Web\Request $request) {
		if (!$request->getInternalArgument('__hmac')) {
			$request->setHmacVerified(FALSE);
			return;
		}
		$hmac = $request->getInternalArgument('__hmac');
		if (strlen($hmac) < 40) {
			throw new \TYPO3\CMS\Extbase\Security\Exception\SyntacticallyWrongRequestHashException('Request hash too short. This is a probably manipulation attempt!', 1255089361);
		}
		$serializedFieldNames = substr($hmac, 0, -40);
		// TODO: Constant for hash length needs to be introduced
		$hash = substr($hmac, -40);
		if ($this->hashService->validateHmac($serializedFieldNames, $hash)) {
			$requestArguments = $request->getArguments();
			// Unset framework arguments
			unset($requestArguments['__referrer']);
			unset($requestArguments['__hmac']);
			if ($this->checkFieldNameInclusion($requestArguments, unserialize($serializedFieldNames))) {
				$request->setHmacVerified(TRUE);
			} else {
				$request->setHmacVerified(FALSE);
			}
		} else {
			$request->setHmacVerified(FALSE);
		}
	}

	/**
	 * Check if every element in $requestArguments is in $allowedFields as well.
	 *
	 * @param array $requestArguments
	 * @param array $allowedFields
	 * @return boolean TRUE if ALL fields inside requestArguments are in $allowedFields, FALSE otherwise.
	 */
	protected function checkFieldNameInclusion(array $requestArguments, array $allowedFields) {
		foreach ($requestArguments as $argumentName => $argumentValue) {
			if (!isset($allowedFields[$argumentName])) {
				return FALSE;
			}
			if (is_array($requestArguments[$argumentName]) && is_array($allowedFields[$argumentName])) {
				if (!$this->checkFieldNameInclusion($requestArguments[$argumentName], $allowedFields[$argumentName])) {
					return FALSE;
				}
			} elseif (!is_array($requestArguments[$argumentName]) && !is_array($allowedFields[$argumentName])) {
			} elseif (!is_array($requestArguments[$argumentName]) && $requestArguments[$argumentName] === '' && is_array($allowedFields[$argumentName])) {
			} else {
				// different types - error
				return FALSE;
			}
		}
		return TRUE;
	}
}

?>