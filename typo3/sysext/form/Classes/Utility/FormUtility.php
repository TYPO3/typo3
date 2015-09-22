<?php
namespace TYPO3\CMS\Form\Utility;

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

use TYPO3\CMS\Core\Utility\GeneralUtility;

/**
 * A session utility
 */
class FormUtility implements \TYPO3\CMS\Core\SingletonInterface {

	/**
	 * Render a content object if allowed
	 *
	 * @param string $key
	 * @param array $configuration
	 * @return string
	 */
	public function renderContentObject($key, array $configuration) {
		return $GLOBALS['TSFE']->cObj->cObjGetSingle(
			$key,
			$configuration
		);
	}

	/**
	 * If the name is not defined it is automatically generated
	 * using the following syntax: id-{element_counter}
	 * The name attribute will be transformed if it contains some
	 * non allowed characters:
	 * - spaces are changed into hyphens
	 * - remove all characters except a-z A-Z 0-9 _ -
	 *
	 * @param string $name
	 * @return string
	 */
	public function sanitizeNameAttribute($name) {
		if (!empty($name)) {
				// Change spaces into hyphens
			$name = preg_replace('/\\s/', '-', $name);
				// Remove non-word characters
			$name = preg_replace('/[^a-zA-Z0-9_\\-]+/', '', $name);
		}
		return $name;
	}

	/**
	 * If the id is not defined it is automatically generated
	 * using the following syntax: field-{element_counter}
	 * The id attribute will be transformed if it contains some
	 * non allowed characters:
	 * - spaces are changed into hyphens
	 * - if the id start with a integer then transform it to field-{integer}
	 * - remove all characters expect a-z A-Z 0-9 _ - : .
	 *
	 * @param string $id
	 * @return string
	 */
	public function sanitizeIdAttribute($id) {
		if (!empty($id)) {
			// Change spaces into hyphens
			$attribute = preg_replace('/\\s/', '-', $id);
			// Change first non-letter to field-
			if (preg_match('/^([^a-zA-Z]{1})/', $attribute)) {
				$id = 'field-' . $attribute;
			}
			// Remove non-word characters
			$id = preg_replace('/([^a-zA-Z0-9_:\\-\\.]*)/', '', $id);
		}
		return $id;
	}

	/**
	 * @return \TYPO3\CMS\Extbase\Object\ObjectManager
	 */
	static public function getObjectManager() {
		return GeneralUtility::makeInstance(\TYPO3\CMS\Extbase\Object\ObjectManager::class);
	}
}
