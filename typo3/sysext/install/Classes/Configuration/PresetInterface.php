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
 * Preset interface
 *
 * A preset is a class for handling a specific configuration
 * set of a feature.
 */
interface PresetInterface {

	/**
	 * Set POST values
	 *
	 * @param array $postValues Post values of feature
	 * @return mixed
	 */
	public function setPostValues(array $postValues);

	/**
	 * Check if preset is available on the system
	 *
	 * @return boolean TRUE if preset is available
	 */
	public function isAvailable();

	/**
	 * Wrapper for isAvailable, used in fluid
	 *
	 * @return boolean TRUE if preset is available
	 */
	public function getIsAvailable();

	/**
	 * Check is preset is currently active on the system
	 *
	 * @return boolean TRUE if preset is active
	 */
	public function isActive();

	/**
	 * Wrapper for isActive, used in fluid
	 *
	 * @return boolean TRUE if preset is active
	 */
	public function getIsActive();

	/**
	 * Get name of preset
	 *
	 * @return string Name
	 */
	public function getName();

	/**
	 * Get priority of preset
	 *
	 * @return integer Priority, usually between 0 and 100
	 */
	public function getPriority();

	/**
	 * Get configuration values to activate prefix
	 *
	 * @return array Configuration values needed to activate prefix
	 */
	public function getConfigurationValues();
}
