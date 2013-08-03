<?php
namespace TYPO3\CMS\Core\Utility;

/***************************************************************
 *  Copyright notice
 *
 *  (c) 2012-2013 Extbase Team
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
 * Several functions related to naming and conversions of names
 * such as translation between Repository and Model names or
 * exploding an objectControllerName into pieces
 *
 * @api
 */
class ClassNamingUtility {

	/**
	 * Translates a model name to an appropriate repository name
	 * e.g. Tx_Extbase_Domain_Model_Foo to Tx_Extbase_Domain_Repository_FooRepository
	 * or \TYPO3\CMS\Extbase\Domain\Model\Foo to \TYPO3\CMS\Extbase\Domain\Repository\FooRepository
	 *
	 * @param string $modelName Name of the model to translate
	 * @return string Name of the repository
	 */
	static public function translateModelNameToRepositoryName($modelName) {
		return str_replace(
			array('\\Domain\\Model', '_Domain_Model_'),
			array('\\Domain\\Repository', '_Domain_Repository_'),
			$modelName
		) . 'Repository';
	}

	/**
	 * Translates a model name to an appropriate validator name
	 * e.g. Tx_Extbase_Domain_Model_Foo to Tx_Extbase_Domain_Validator_FooValidator
	 * or \TYPO3\CMS\Extbase\Domain\Model\Foo to \TYPO3\CMS\Extbase\Domain\Validator\FooValidator
	 *
	 * @param string $modelName Name of the model to translate
	 * @return string Name of the repository
	 */
	static public function translateModelNameToValidatorName($modelName) {
		return str_replace(
			array('\\Domain\\Model\\', '_Domain_Model_'),
			array('\\Domain\\Validator\\', '_Domain_Validator_'),
			$modelName
		) . 'Validator';
	}

	/**
	 * Translates a repository name to an appropriate model name
	 * e.g. Tx_Extbase_Domain_Repository_FooRepository to Tx_Extbase_Domain_Model_Foo
	 * or \TYPO3\CMS\Extbase\Domain\Repository\FooRepository to \TYPO3\CMS\Extbase\Domain\Model\Foo
	 *
	 * @param string $repositoryName Name of the repository to translate
	 * @return string Name of the model
	 */
	static public function translateRepositoryNameToModelName($repositoryName) {
		return preg_replace(
			array('/\\\\Domain\\\\Repository/', '/_Domain_Repository_/', '/Repository$/'),
			array('\\Domain\\Model', '_Domain_Model_', ''),
			$repositoryName
		);
	}



	/**
	 * Explodes a controllerObjectName like \Vendor\Ext\Controller\FooController
	 * into several pieces like vendorName, extensionName, subpackageKey and controllerName
	 *
	 * @param string $controllerObjectName The controller name to be exploded
	 * @return array An array of controllerObjectName pieces
	 */
	static public function explodeObjectControllerName($controllerObjectName) {
		$matches = array();

		if (strpos($controllerObjectName, '\\') !== FALSE) {
			if (substr($controllerObjectName, 0, 9) === 'TYPO3\\CMS') {
				$extensionName = '^(?P<vendorName>[^\\\\]+\\\[^\\\\]+)\\\(?P<extensionName>[^\\\\]+)';
			} else {
				$extensionName = '^(?P<vendorName>[^\\\\]+)\\\\(?P<extensionName>[^\\\\]+)';
			}

			preg_match(
				'/' . $extensionName . '\\\\(Controller|Command|(?P<subpackageKey>.+)\\\\Controller)\\\\(?P<controllerName>[a-z\\\\]+)Controller$/ix',
				$controllerObjectName,
				$matches
			);
		} else {
			preg_match(
				'/^Tx_(?P<extensionName>[^_]+)_(Controller|Command|(?P<subpackageKey>.+)_Controller)_(?P<controllerName>[a-z_]+)Controller$/ix',
				$controllerObjectName,
				$matches
			);
		}

		return $matches;
	}
}

?>
