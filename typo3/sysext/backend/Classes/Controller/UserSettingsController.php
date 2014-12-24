<?php

namespace TYPO3\CMS\Backend\Controller;

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
 * A wrapper class to call BE_USER->uc
 * used for AJAX and TYPO3.Storage JS object
 */
class UserSettingsController {

	/**
	 * Processes all AJAX calls and returns a JSON for the data
	 *
	 * @param array $parameters
	 * @param \TYPO3\CMS\Core\Http\AjaxRequestHandler $ajaxRequestHandler
	 */
	public function processAjaxRequest($parameters, \TYPO3\CMS\Core\Http\AjaxRequestHandler $ajaxRequestHandler) {
		// do the regular / main logic, depending on the action parameter
		$action = GeneralUtility::_GP('action');
		switch ($action) {
			case 'get':
				$key = GeneralUtility::_GP('key');
				$content = $this->get($key);
				break;
			case 'getAll':
				$content = $this->getAll();
				break;
			case 'set':
				$key = GeneralUtility::_GP('key');
				$value = GeneralUtility::_GP('value');
				$this->set($key, $value);
				$content = $this->getAll();
				break;
			case 'unset':
				$key = GeneralUtility::_GP('key');
				$this->unsetOption($key);
				$content = $this->getAll();
				break;
			case 'clear':
				$this->clear();
				$content = array('result' => TRUE);
				break;
			default:
				$content = array('result' => FALSE);
		}

		$ajaxRequestHandler->setContentFormat('json');
		$ajaxRequestHandler->setContent($content);
	}

	/**
	 * Returns a specific user setting
	 *
	 * @param string $key Identifier, allows also dotted notation for subarrays
	 * @return mixed Value associated
	 */
	protected function get($key) {
		return (strpos($key, '.') !== FALSE) ? $this->getFromDottedNotation($key) : $GLOBALS['BE_USER']->uc[$key];
	}

	/**
	 * Get all user settings
	 *
	 * @return mixed all values, usually a multi-dimensional array
	 */
	protected function getAll() {
		return $GLOBALS['BE_USER']->uc;
	}

	/**
	 * Sets user settings by key/value pair
	 *
	 * @param string $key
	 * @param mixed $value
	 * @return void
	 */
	protected function set($key, $value) {
		if (strpos($key, '.') !== FALSE) {
			$this->setFromDottedNotation($key, $value);
		} else {
			$GLOBALS['BE_USER']->uc[$key] = $value;
		}
		$GLOBALS['BE_USER']->writeUC($GLOBALS['BE_USER']->uc);
	}

	/**
	 * Resets the user settings to the default
	 *
	 * @return void
	 */
	protected function clear() {
		$GLOBALS['BE_USER']->resetUC();
	}

	/**
	 * Unsets a key in user settings
	 *
	 * @param string $key
	 * @return void
	 */
	protected function unsetOption($key) {
		if (isset($GLOBALS['BE_USER']->uc[$key])) {
			unset($GLOBALS['BE_USER']->uc[$key]);
			$GLOBALS['BE_USER']->writeUC($GLOBALS['BE_USER']->uc);
		}
	}

	/**
	 * Computes the subarray from dotted notation
	 *
	 * @param $key string Dotted notation of subkeys like moduleData.module1.general.checked
	 * @return mixed value of the settings
	 */
	protected function getFromDottedNotation($key) {
		$subkeys = GeneralUtility::trimExplode('.', $key);
		$array = &$GLOBALS['BE_USER']->uc;
		foreach ($subkeys as $subkey) {
			$array = &$array[$subkey];
		}
		return $array;
	}

	/**
	 * Sets the value of a key written in dotted notation
	 *
	 * @param string $key
	 * @param mixed $value
	 * @return void
	 */
	protected function setFromDottedNotation($key, $value) {
		$subkeys = GeneralUtility::trimExplode('.', $key, TRUE);
		$lastKey = $subkeys[count($subkeys) - 1];
		$array = &$GLOBALS['BE_USER']->uc;
		foreach ($subkeys as $subkey) {
			if ($subkey === $lastKey) {
				$array[$subkey] = $value;
			} else {
				$array = &$array[$subkey];
			}
		}
	}
}
