<?php
namespace TYPO3\CMS\Install\Configuration;

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

use TYPO3\CMS\Core\Utility\GeneralUtility;

/**
 * Abstract custom preset class implements common preset code
 */
abstract class AbstractCustomPreset extends AbstractPreset {

	/**
	 * @var string Name of preset, always set to "Custom"
	 */
	protected $name = 'Custom';

	/**
	 * @var boolean TRUE if custom preset is active
	 */
	protected $isActive = FALSE;

	/**
	 * @var integer Priority of custom prefix is usually the lowest
	 */
	protected $priority = 10;

	/**
	 * Whether custom preset is active is set by feature
	 *
	 * @return boolean TRUE if custom preset is active
	 */
	public function isActive() {
		return $this->isActive;
	}

	/**
	 * Mark preset as active.
	 * The custom features do not know by itself if they are
	 * active or not since the configuration options may overlay
	 * with other presets.
	 * Marking the custom preset as active is therefor taken care
	 * off by the feature itself if no other preset is active.
	 *
	 * @return void
	 */
	public function setActive() {
		$this->isActive = TRUE;
	}

	/**
	 * Custom configuration is always available
	 *
	 * @return boolean TRUE
	 */
	public function isAvailable() {
		return TRUE;
	}

	/**
	 * Get configuration values is used in fluid to show configuration options.
	 * They are fetched from LocalConfiguration / DefaultConfiguration and
	 * merged with given $postValues.
	 *
	 * @return array Configuration values needed to activate prefix
	 */
	public function getConfigurationValues() {
		$configurationValues = array();
		foreach ($this->configurationValues as $configurationKey => $configurationValue) {
			if (isset($this->postValues['enable'])
				&& $this->postValues['enable'] === $this->name
				&& isset($this->postValues[$this->name][$configurationKey])
			) {
				$currentValue = $this->postValues[$this->name][$configurationKey];
			} else {
				$currentValue = $this->configurationManager->getConfigurationValueByPath($configurationKey);
			}
			$configurationValues[$configurationKey] = $currentValue;
		}
		return $configurationValues;
	}
}
