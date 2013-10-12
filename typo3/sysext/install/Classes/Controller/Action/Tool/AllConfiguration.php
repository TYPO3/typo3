<?php
namespace TYPO3\CMS\Install\Controller\Action\Tool;

/***************************************************************
 *  Copyright notice
 *
 *  (c) 2013 Christian Kuhn <lolli@schwarzbu.ch>
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
 *
 *  This script is distributed in the hope that it will be useful,
 *  but WITHOUT ANY WARRANTY; without even the implied warranty of
 *  MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 *  GNU General Public License for more details.
 *
 *  This copyright notice MUST APPEAR in all copies of the script!
 ***************************************************************/

use TYPO3\CMS\Install\Controller\Action;
use TYPO3\CMS\Core\Utility\GeneralUtility;

/**
 * Show system environment check results
 */
class AllConfiguration extends Action\AbstractAction {

	/**
	 * Error handlers are a bit mask in PHP. This register hints the View to
	 * add a fluid view helper resolving the bit mask to its representation
	 * as constants again for the specified items in ['SYS'].
	 *
	 * @var array
	 */
	protected $phpErrorCodesSettings = array(
		'errorHandlerErrors',
		'exceptionalErrors',
		'syslogErrorReporting',
		'belogErrorReporting',
	);

	/**
	 * Executes the tool
	 *
	 * @return string Rendered content
	 */
	protected function executeAction() {
		if (isset($this->postValues['set']['write'])) {
			$this->view->assign('configurationValuesSaved', TRUE);
			$this->view->assign('savedConfigurationValueMessages', $this->updateLocalConfigurationValues());
		} else {
			$this->view->assign('data', $this->setUpConfigurationData());
		}

		return $this->view->render();
	}

	/**
	 * Set up configuration data
	 *
	 * @return array Configuration data
	 */
	protected function setUpConfigurationData() {
		$data = array();
		$typo3ConfVars = array_keys($GLOBALS['TYPO3_CONF_VARS']);
		sort($typo3ConfVars);
		$commentArray = $this->getDefaultConfigArrayComments();
		foreach ($typo3ConfVars as $sectionName) {
			$data[$sectionName] = array();

			foreach ($GLOBALS['TYPO3_CONF_VARS'][$sectionName] as $key => $value) {
				if (isset($GLOBALS['TYPO3_CONF_VARS_extensionAdded'][$sectionName][$key])) {
					// Don't allow editing stuff which is added by extensions
					// Make sure we fix potentially duplicated entries from older setups
					$potentialValue = str_replace(array('\'.chr(10).\'', '\' . LF . \''), array(LF, LF), $value);
					while (preg_match('/' . preg_quote($GLOBALS['TYPO3_CONF_VARS_extensionAdded'][$sectionName][$key], '/') . '$/', '', $potentialValue)) {
						$potentialValue = preg_replace('/' . preg_quote($GLOBALS['TYPO3_CONF_VARS_extensionAdded'][$sectionName][$key], '/') . '$/', '', $potentialValue);
					}
					$value = $potentialValue;
				}

				$description = trim($commentArray[$sectionName][$key]);
				$isTextarea = preg_match('/^(<.*?>)?string \\(textarea\\)/i', $description) ? TRUE : FALSE;
				$doNotRender = preg_match('/^(<.*?>)?string \\(exclude\\)/i', $description) ? TRUE : FALSE;

				if (!is_array($value) && !$doNotRender && (!preg_match('/[' . LF . CR . ']/', $value) || $isTextarea)) {
					$itemData = array();
					$itemData['key'] = $key;
					$itemData['description'] = $description;
					if ($isTextarea) {
						$itemData['type'] = 'textarea';
						$itemData['value'] = str_replace(array('\'.chr(10).\'', '\' . LF . \''), array(LF, LF), $value);
					} elseif (preg_match('/^(<.*?>)?boolean/i', $description)) {
						$itemData['type'] = 'checkbox';
						$itemData['value'] = $value ? '1' : '0';
						$itemData['checked'] = (boolean)$value;
					} else {
						$itemData['type'] = 'input';
						$itemData['value'] = $value;
					}

					// Check if the setting is a PHP error code, will trigger a view helper in fluid
					if ($sectionName === 'SYS' && in_array($key, $this->phpErrorCodesSettings)) {
						$itemData['phpErrorCode'] = TRUE;
					}

					$data[$sectionName][] = $itemData;
				}
			}
		}
		return $data;
	}

	/**
	 * Store changed values in LocalConfiguration
	 *
	 * @return string Status messages of changed values
	 */
	protected function updateLocalConfigurationValues() {
		$statusObjects = array();
		if (isset($this->postValues['values']) && is_array($this->postValues['values'])) {
			$configurationPathValuePairs = array();
			$commentArray = $this->getDefaultConfigArrayComments();
			$formValues = $this->postValues['values'];
			foreach ($formValues as $section => $valueArray) {
				if (is_array($GLOBALS['TYPO3_CONF_VARS'][$section])) {
					foreach ($valueArray as $valueKey => $value) {
						if (isset($GLOBALS['TYPO3_CONF_VARS'][$section][$valueKey])) {
							$description = trim($commentArray[$section][$valueKey]);
							if (preg_match('/^string \\(textarea\\)/i', $description)) {
								// Force Unix linebreaks in textareas
								$value = str_replace(CR, '', $value);
								// Preserve linebreaks
								$value = str_replace(LF, '\' . LF . \'', $value);
							}
							if (preg_match('/^boolean/i', $description)) {
								// When submitting settings in the Install Tool, values that default to "FALSE" or "TRUE"
								// in EXT:core/Configuration/DefaultConfiguration.php will be sent as "0" resp. "1".
								// Therefore, reset the values to their boolean equivalent.
								if ($GLOBALS['TYPO3_CONF_VARS'][$section][$valueKey] === FALSE && $value === '0') {
									$value = FALSE;
								} elseif ($GLOBALS['TYPO3_CONF_VARS'][$section][$valueKey] === TRUE && $value === '1') {
									$value = TRUE;
								}
							}
							// Save if value changed
							if ((string)$GLOBALS['TYPO3_CONF_VARS'][$section][$valueKey] !== (string)$value) {
								$configurationPathValuePairs[$section . '/' . $valueKey] = $value;
								/** @var $message \TYPO3\CMS\Install\Status\StatusInterface */
								$status = $this->objectManager->get('TYPO3\\CMS\\Install\\Status\\OkStatus');
								$status->setTitle('$TYPO3_CONF_VARS[\'' . $section . '\'][\'' . $valueKey . '\']');
								$status->setMessage('New value = ' . $value);
								$statusObjects[] = $status;
							}
						}
					}
				}
			}
			if (count($statusObjects)) {
				/** @var \TYPO3\CMS\Core\Configuration\ConfigurationManager $configurationManager */
				$configurationManager = $this->objectManager->get('TYPO3\\CMS\\Core\\Configuration\\ConfigurationManager');
				$configurationManager->setLocalConfigurationValuesByPathValuePairs($configurationPathValuePairs);
			}
		}
		return $statusObjects;
	}

	/**
	 * Make an array of the comments in the EXT:core/Configuration/DefaultConfiguration.php file
	 *
	 * @return array
	 */
	protected function getDefaultConfigArrayComments() {
		/** @var \TYPO3\CMS\Core\Configuration\ConfigurationManager $configurationManager */
		$configurationManager = $this->objectManager->get('TYPO3\\CMS\\Core\\Configuration\\ConfigurationManager');
		$string = GeneralUtility::getUrl($configurationManager->getDefaultConfigurationFileLocation());

		$commentArray = array();
		$lines = explode(LF, $string);
		$in = 0;
		$mainKey = '';
		foreach ($lines as $lc) {
			$lc = trim($lc);
			if ($in) {
				if ($lc === ');') {
					$in = 0;
				} else {
					if (preg_match('/["\']([[:alnum:]_-]*)["\'][[:space:]]*=>(.*)/i', $lc, $reg)) {
						preg_match('/,[\\t\\s]*\\/\\/(.*)/i', $reg[2], $creg);
						$theComment = trim($creg[1]);
						if (substr(strtolower(trim($reg[2])), 0, 5) == 'array' && $reg[1] === strtoupper($reg[1])) {
							$mainKey = trim($reg[1]);
						} elseif ($mainKey) {
							$commentArray[$mainKey][$reg[1]] = $theComment;
						}
					}
				}
			}
			if ($lc === 'return array(') {
				$in = 1;
			}
		}
		return $commentArray;
	}
}
