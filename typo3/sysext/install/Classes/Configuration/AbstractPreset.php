<?php
namespace TYPO3\CMS\Install\Configuration;

/**
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
 * Abstract preset class implements common preset code
 */
abstract class AbstractPreset implements PresetInterface {

	/**
	 * @var \TYPO3\CMS\Core\Configuration\ConfigurationManager
	 * @inject
	 */
	protected $configurationManager = NULL;

	/**
	 * @var string Name of preset, must be set by extending classes
	 */
	protected $name = '';

	/**
	 * @var integer Default priority of preset
	 */
	protected $priority = 50;

	/**
	 * @var array Configuration values handled by this preset
	 */
	protected $configurationValues = array();

	/**
	 * @var array List of $POST values
	 */
	protected $postValues = array();

	/**
	 * Set POST values
	 *
	 * @param array $postValues Post values of feature
	 * @return mixed
	 */
	public function setPostValues(array $postValues) {
		$this->postValues = $postValues;
	}

	/**
	 * Wrapper for isAvailable, used in fluid
	 *
	 * @return boolean TRUE if preset is available
	 */
	public function getIsAvailable() {
		return $this->isAvailable();
	}

	/**
	 * Check is preset is currently active on the system
	 *
	 * @return boolean TRUE if preset is active
	 */
	public function isActive() {
		$isActive = TRUE;
		foreach ($this->configurationValues as $configurationKey => $configurationValue) {
			try {
				$currentValue = $this->configurationManager->getConfigurationValueByPath($configurationKey);
			} catch (\RuntimeException $e) {
				$currentValue = NULL;
			}
			if ($currentValue !== $configurationValue) {
				$isActive = FALSE;
				break;
			}
		}
		return $isActive;
	}

	/**
	 * Wrapper for isActive, used in fluid
	 *
	 * @return boolean TRUE if preset is active
	 */
	public function getIsActive() {
		return $this->isActive();
	}

	/**
	 * Get name of preset
	 *
	 * @return string Name
	 */
	public function getName() {
		return $this->name;
	}

	/**
	 * Get priority of preset
	 *
	 * @return integer Priority, usually between 0 and 100
	 */
	public function getPriority() {
		return $this->priority;
	}

	/**
	 * Get configuration values to activate prefix
	 *
	 * @return array Configuration values needed to activate prefix
	 */
	public function getConfigurationValues() {
		return $this->configurationValues;
	}
}
