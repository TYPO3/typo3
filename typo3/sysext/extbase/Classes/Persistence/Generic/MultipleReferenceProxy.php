<?php
namespace TYPO3\CMS\Extbase\Persistence\Generic;

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
 * A MultipleReferenceProxy
 *
 * @api
 */
class MultipleReferenceProxy implements \TYPO3\CMS\Core\SingletonInterface {

	/**
	 * @var \TYPO3\CMS\Extbase\Configuration\ConfigurationManager
	 */
	protected $configurationManager;

	/**
	 * @param \TYPO3\CMS\Extbase\Configuration\ConfigurationManager $configurationManager
	 */
	public function injectConfigurationManager(\TYPO3\CMS\Extbase\Configuration\ConfigurationManager $configurationManager) {
		$this->configurationManager = $configurationManager;
	}

	/**
	 * @param string $parentClassName
	 * @param string $tableName
	 *
	 * @throws \TYPO3\CMS\Extbase\Configuration\Exception
	 *
	 * @return string
	 */
	public function resolveClassNameByParentClassNameAndTableName($parentClassName, $tableName) {

		$className = \TYPO3\CMS\Extbase\Reflection\ObjectAccess::getPropertyPath(
			$this->configurationManager->getConfiguration(\TYPO3\CMS\Extbase\Configuration\ConfigurationManagerInterface::CONFIGURATION_TYPE_FRAMEWORK),
			sprintf('persistence.multipleReferenceProxy.%s.%s', $parentClassName, $tableName)
		);

		if ($className === NULL) {
			throw new \TYPO3\CMS\Extbase\Configuration\Exception(sprintf('Missing MultipleReferenceProxy configuration for class "%s" and table "%s"', $parentClassName, $tableName), 1366367253);
		}

		return (string) $className;
	}
}

?>