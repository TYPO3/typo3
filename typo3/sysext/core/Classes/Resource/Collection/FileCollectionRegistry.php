<?php
namespace TYPO3\CMS\Core\Resource\Collection;

/***************************************************************
 * Copyright notice
 *
 * (c) 2013 - Frans Saris <franssaris@gmail.com>
 * All rights reserved
 *
 * This script is part of the TYPO3 project. The TYPO3 project is
 * free software; you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation; either version 2 of the License, or
 * (at your option) any later version.
 *
 * The GNU General Public License can be found at
 * http://www.gnu.org/copyleft/gpl.html.
 * A copy is found in the text file GPL.txt and important notices to the license
 * from the author is found in LICENSE.txt distributed with these scripts.
 *
 *
 * This script is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
 * GNU General Public License for more details.
 *
 * This copyright notice MUST APPEAR in all copies of the script!
 ***************************************************************/

/**
 * Registry for FileCollection classes
 */
class FileCollectionRegistry implements \TYPO3\CMS\Core\SingletonInterface {

	/**
	 * Registered FileCollection types
	 *
	 * @var array
	 */
	protected $types = array();

	/**
	 * Constructor
	 */
	public function __construct() {
		foreach ($GLOBALS['TYPO3_CONF_VARS']['SYS']['fal']['registeredCollections'] as $type => $class) {
			$this->registerFileCollectionClass($class, $type);
		}
	}

	/**
	 * Register a (new) FileCollection type
	 *
	 * @param string $className
	 * @param string $type FileCollection type max length 30 chars (db field restriction)
	 * @param bool $override existing FileCollection type
	 * @return bool TRUE if registration succeeded
	 * @throws \InvalidArgumentException
	 */
	public function registerFileCollectionClass($className, $type, $override = FALSE) {

		if (strlen($type) > 30) {
			throw new \InvalidArgumentException('FileCollection type can have a max string length of 30 bytes', 1391295611);
		}

		if (!class_exists($className)) {
			throw new \InvalidArgumentException('Class ' . $className . ' does not exist.', 1391295613);
		}

		if (!in_array('TYPO3\\CMS\\Core\\Resource\\Collection\\AbstractFileCollection', class_parents($className), TRUE)) {
			throw new \InvalidArgumentException('FileCollection ' . $className . ' needs to extend the AbstractFileCollection.', 1391295633);
		}

		if (isset($this->types[$type])) {
			// Return immediately without changing configuration
			if ($this->types[$type] === $className) {
				return TRUE;
			} elseif (!$override) {
				throw new \InvalidArgumentException('FileCollections ' . $type . ' is already registered.', 1391295643);
			}
		}

		$this->types[$type] = $className;
		return TRUE;
	}

	/**
	 * Add the type to the TCA of sys_file_collection
	 *
	 * @param string $type
	 * @param string $label
	 * @param string $availableFields comma separated list of fields to show
	 * @param array $additionalColumns Additional columns configuration
	 * @return array adjusted TCA for sys_file_collection
	 */
	public function addTypeToTCA($type, $label, $availableFields, array $additionalColumns = array()) {

		$GLOBALS['TCA']['sys_file_collection']['types'][$type] = array(
			'showitem' => 'sys_language_uid;;;;1-1-1, l10n_parent, l10n_diffsource, title;;1, type, ' . $availableFields
		);

		// search for existing type when found override label
		$typeFound = FALSE;
		foreach ($GLOBALS['TCA']['sys_file_collection']['columns']['type']['config']['items'] as $key => $item) {
			if ($item[1] === $type) {
				$typeFound = TRUE;
				$GLOBALS['TCA']['sys_file_collection']['columns']['type']['config']['items'][$key][0] = $label;
			}
		}
		if (!$typeFound) {
			$GLOBALS['TCA']['sys_file_collection']['columns']['type']['config']['items'][] = array(
				0 => $label,
				1 => $type
			);
		}
		if ($additionalColumns !== array()) {
			\TYPO3\CMS\Core\Utility\ArrayUtility::mergeRecursiveWithOverrule($GLOBALS['TCA']['sys_file_collection']['columns'], $additionalColumns);
		}
		return $GLOBALS['TCA']['sys_file_collection'];
	}

	/**
	 * Returns a class name for a given type
	 *
	 * @param string $type
	 * @return string The class name
	 * @throws \InvalidArgumentException
	 */
	public function getFileCollectionClass($type) {
		if (!isset($this->types[$type])) {
			throw new \InvalidArgumentException('Desired FileCollection type "' . $type . '" is not in the list of available FileCollections.', 1391295644);
		}
		return $this->types[$type];
	}

	/**
	 * Checks if the given FileCollection type exists
	 *
	 * @param string $type Type of the FileCollection
	 * @return boolean TRUE if the FileCollection exists, FALSE otherwise
	 */
	public function fileCollectionTypeExists($type) {
		return isset($this->types[$type]);
	}

}