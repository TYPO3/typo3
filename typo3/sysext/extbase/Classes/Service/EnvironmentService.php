<?php
namespace TYPO3\CMS\Extbase\Service;

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
 * Service for determining environment params
 */
class EnvironmentService {

	/**
	 * Detects if TYPO3_MODE is defined and its value is "FE"
	 *
	 * @return boolean
	 */
	public function isEnvironmentInFrontendMode() {
		return (defined('TYPO3_MODE') && TYPO3_MODE === 'FE') ?: FALSE;
	}

	/**
	 * Detects if TYPO3_MODE is defined and its value is "BE"
	 *
	 * @return boolean
	 */
	public function isEnvironmentInBackendMode() {
		return (defined('TYPO3_MODE') && TYPO3_MODE === 'BE') ?: FALSE;
	}

	/**
	 * Detects if we are running a script from the command line.
	 *
	 * @return boolean
	 */
	public function isEnvironmentInCliMode() {
		return $this->isEnvironmentInBackendMode() && defined('TYPO3_cliMode') && TYPO3_cliMode === TRUE;
	}

	/**
	 * @return string
	 */
	public function getServerRequestMethod() {
		return isset($_SERVER['REQUEST_METHOD']) && is_string($_SERVER['REQUEST_METHOD']) ? $_SERVER['REQUEST_METHOD'] : 'GET';
	}
}

?>