<?php
namespace TYPO3\CMS\Rtehtmlarea\Form\Element;

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

use TYPO3\CMS\Backend\Form\Element\AbstractFormElement;
use TYPO3\CMS\Backend\Utility\BackendUtility;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Core\Utility\MathUtility;
use TYPO3\CMS\Backend\Form\FormEngine;
use TYPO3\CMS\Core\Authentication\BackendUserAuthentication;
use TYPO3\CMS\Backend\Form\NodeFactory;

/**
 * Render rich text editor in FormEngine
 */
class RichTextElement extends AbstractFormElement {

	/**
	 * This will render a <textarea> OR RTE area form field,
	 * possibly with various control/validation features
	 *
	 * @return array As defined in initializeResultArray() of AbstractNode
	 */
	public function render() {
		$table = $this->globalOptions['table'];
		$fieldName = $this->globalOptions['fieldName'];
		$row = $this->globalOptions['databaseRow'];
		$parameterArray = $this->globalOptions['parameterArray'];
		$resultArray = $this->initializeResultArray();
		$backendUser = $this->getBackendUserAuthentication();

		$validationConfig = array();

		// "Extra" configuration; Returns configuration for the field based on settings found in the "types" fieldlist. Traditionally, this is where RTE configuration has been found.
		$specialConfiguration = BackendUtility::getSpecConfParts($parameterArray['fieldConf']['defaultExtras']);
		// Setting up the altItem form field, which is a hidden field containing the value
		$altItem = '<input type="hidden" name="' . htmlspecialchars($parameterArray['itemFormElName']) . '" value="' . htmlspecialchars($parameterArray['itemFormElValue']) . '" />';

		BackendUtility::fixVersioningPid($table, $row);
		list($recordPid, $tsConfigPid) = BackendUtility::getTSCpidCached($table, $row['uid'], $row['pid']);

		// If the pid-value is not negative (that is, a pid could NOT be fetched)
		$rteSetup = $backendUser->getTSConfig('RTE', BackendUtility::getPagesTSconfig($recordPid));
		$rteTcaTypeValue = BackendUtility::getTCAtypeValue($table, $row);
		$rteSetupConfiguration = BackendUtility::RTEsetup($rteSetup['properties'], $table, $fieldName, $rteTcaTypeValue);

		// Get RTE object, draw form and set flag:
		$rteObject = BackendUtility::RTEgetObj();
		$dummyFormEngine = new FormEngine();
		$rteResult = $rteObject->drawRTE(
			$dummyFormEngine,
			$table,
			$fieldName,
			$row,
			$parameterArray,
			$specialConfiguration,
			$rteSetupConfiguration,
			$rteTcaTypeValue,
			'',
			$tsConfigPid,
			$this->globalOptions,
			$this->initializeResultArray(),
			$this->getValidationDataAsDataAttribute($validationConfig)
		);
		// This is a compat layer for "other" RTE's: If the result is not an array, it is the html string,
		// otherwise it is a structure similar to our casual return array
		// @todo: This interface needs a full re-definition, RTE should probably be its own type in the
		// @todo: end, and other RTE implementations could then just override this.
		if (is_array($rteResult)) {
			$html = $rteResult['html'];
			$rteResult['html'] = '';
			$resultArray = $this->mergeChildReturnIntoExistingResult($resultArray, $rteResult);
		} else {
			$html = $rteResult;
		}

		// Wizard
		$html = $this->renderWizards(
			array($html, $altItem),
			$parameterArray['fieldConf']['config']['wizards'],
			$table,
			$row,
			$fieldName,
			$parameterArray,
			$parameterArray['itemFormElName'],
			$specialConfiguration,
			TRUE
		);

		$resultArray['html'] = $html;
		return $resultArray;
	}

	/**
	 * @return BackendUserAuthentication
	 */
	protected function getBackendUserAuthentication() {
		return $GLOBALS['BE_USER'];
	}

}
