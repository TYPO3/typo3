<?php
/***************************************************************
*  Copyright notice
*
*  (c) 2009 Jochen Rau <jochen.rau@typoplanet.de>
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

/**
 * A general purpose configuration manager used in frontend mode.
 *
 * Should NOT be singleton, as a new configuration manager is needed per plugin.
 *
 * @package Extbase
 * @subpackage Configuration
 * @version $ID:$
 */
class Tx_Extbase_Configuration_FrontendConfigurationManager extends Tx_Extbase_Configuration_AbstractConfigurationManager {

	/**
	 * @var tslib_cObj
	 */
	protected $contentObject;

	/**
	 * @param tslib_cObj $contentObject
	 * @return void
	 */
	public function setContentObject(tslib_cObj $contentObject) {
		$this->contentObject = $contentObject;
	}

	/**
	 * Returns TypoScript Setup array from current Environment.
	 *
	 * @return array the TypoScript setup
	 */
	public function loadTypoScriptSetup() {
		return $GLOBALS['TSFE']->tmpl->setup;
	}

	/**
	 * The storage PID should be determined by the "Startingpoint" setting
	 * in the Plugin Configuration.
	 *
	 * @return array
	 */
	protected function getContextSpecificFrameworkConfiguration() {
		if (is_string($this->contentObject->data['pages']) && strlen($this->contentObject->data['pages']) > 0) {
			return array(
				'persistence' => array(
					'storagePid' => $this->contentObject->data['pages']
				)
			);
		}
		return array();
	}

}
?>
