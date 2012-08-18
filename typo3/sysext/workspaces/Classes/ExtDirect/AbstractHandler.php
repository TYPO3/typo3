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
 * Abstract ExtDirect handler
 *
 * @author Workspaces Team (http://forge.typo3.org/projects/show/typo3v4-workspaces)
 * @package Workspaces
 * @subpackage ExtDirect
 */
abstract class Tx_Workspaces_ExtDirect_AbstractHandler {
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
	 * @return Tx_Workspaces_Service_Workspaces
	 */
	protected function getWorkspaceService() {
		return t3lib_div::makeInstance('Tx_Workspaces_Service_Workspaces');
	}

	/**
	 * Validates whether the submitted language parameter can be
	 * interpreted as integer value.
	 *
	 * @param stdClass $parameters
	 * @return integer|NULL
	 */
	protected function validateLanguageParameter(stdClass $parameters) {
		$language = NULL;

		if (isset($parameters->language) && t3lib_utility_Math::canBeInterpretedAsInteger($parameters->language)) {
			$language = $parameters->language;
		}

		return $language;
	}

	/**
	 * Gets affected elements on publishing/swapping actions.
	 * Affected elements have a dependency, e.g. translation overlay
	 * and the default origin record - thus, the default record would be
	 * affected if the translation overlay shall be published.
	 *
	 * @param stdClass $parameters
	 * @return array
	 */
	protected function getAffectedElements(stdClass $parameters) {
		$affectedElements = array();

		if ($parameters->type === 'selection') {
			foreach ((array) $parameters->selection as $element) {
				$affectedElements[] = Tx_Workspaces_Domain_Model_CombinedRecord::create(
					$element->table,
					$element->liveId,
					$element->versionId
				);
			}
		} elseif ($parameters->type === 'all') {
			$versions = $this->getWorkspaceService()->selectVersionsInWorkspace(
				$this->getCurrentWorkspace(),
				0, -99, -1, 0, 'tables_select',
				$this->validateLanguageParameter($parameters)
			);

			foreach ($versions as $table => $tableElements) {
				foreach ($tableElements as $element) {
					$affectedElement = Tx_Workspaces_Domain_Model_CombinedRecord::create(
						$table,
						$element['t3ver_oid'],
						$element['uid']
					);

					$affectedElement->getVersionRecord()->setRow($element);
					$affectedElements[] = $affectedElement;
				}
			}
		}

		return $affectedElements;
	}

	/**
	 * Creates a new instance of the integrity service for the
	 * given set of affected elements.
	 *
	 * @param Tx_Workspaces_Domain_Model_CombinedRecord[] $affectedElements
	 * @return Tx_Workspaces_Service_Integrity
	 * @see getAffectedElements
	 */
	protected function createIntegrityService(array $affectedElements) {
		/** @var $integrityService Tx_Workspaces_Service_Integrity */
		$integrityService = t3lib_div::makeInstance('Tx_Workspaces_Service_Integrity');
		$integrityService->setAffectedElements($affectedElements);
		return $integrityService;
	}
}
?>