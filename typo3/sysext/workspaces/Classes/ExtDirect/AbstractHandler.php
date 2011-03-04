<?php
/***************************************************************
*  Copyright notice
*
*  (c) 2010-2011 Workspaces Team (http://forge.typo3.org/projects/show/typo3v4-workspaces)
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
 * @author Workspaces Team (http://forge.typo3.org/projects/show/typo3v4-workspaces)
 * @package Workspaces
 * @subpackage ExtDirect
 */
abstract class tx_Workspaces_ExtDirect_AbstractHandler {
	/**
	 * Gets the current workspace ID.
	 *
	 * @return integer The current workspace ID
	 */
	protected function getCurrentWorkspace() {
		return $this->getWorkspaceService()->getCurrentWorkspace();
	}

	/**
	 * Gets an error response to be shown in the grid component.
	 *
	 * @param string $errorLabel Name of the label in the locallang.xml file
	 * @param integer $errorCode The error code to be used
	 * @param boolean $successFlagValue Value of the success flag to be delivered back (might be FALSE in most cases)
	 * @return array
	 */
	protected function getErrorResponse($errorLabel, $errorCode = 0, $successFlagValue = FALSE) {
		$localLangFile = 'LLL:EXT:workspaces/Resources/Private/Language/locallang.xml';

		$response = array(
			'error' => array(
				'code' => $errorCode,
				'message' => $GLOBALS['LANG']->sL($localLangFile . ':' . $errorLabel),
			),
			'success' => $successFlagValue,
		);

		return $response;
	}

	/**
	 * Gets an instance of the workspaces service.
	 *
	 * @return tx_Workspaces_Service_Workspaces
	 */
	protected function getWorkspaceService() {
		return t3lib_div::makeInstance('tx_Workspaces_Service_Workspaces');
	}
}


if (defined('TYPO3_MODE') && isset($GLOBALS['TYPO3_CONF_VARS'][TYPO3_MODE]['XCLASS']['ext/workspaces/Classes/ExtDirect/AbstractHandler.php'])) {
	include_once($GLOBALS['TYPO3_CONF_VARS'][TYPO3_MODE]['XCLASS']['ext/workspaces/Classes/ExtDirect/AbstractHandler.php']);
}
?>