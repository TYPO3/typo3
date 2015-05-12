<?php
namespace TYPO3\CMS\Backend\Form\Container;

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
use TYPO3\CMS\Backend\Utility\BackendUtility;
use TYPO3\CMS\Lang\LanguageService;
use TYPO3\CMS\Backend\Form\FlexFormsHelper;
use TYPO3\CMS\Backend\Form\NodeFactory;

/**
 * Entry container to a flex form element. This container is created by
 * SingleFieldContainer if a type='flexform' field is rendered.
 * The container prepares the flex form data and structure and hands
 * over to FlexFormLanguageContainer for further processing.
 */
class FlexFormContainer extends AbstractContainer {

	/**
	 * Entry method
	 *
	 * @return array As defined in initializeResultArray() of AbstractNode
	 */
	public function render() {
		$languageService = $this->getLanguageService();

		$table = $this->globalOptions['table'];
		$row = $this->globalOptions['databaseRow'];
		$field = $this->globalOptions['field'];
		$parameterArray = $this->globalOptions['parameterArray'];

		// Data Structure
		$flexFormDataStructureArray = BackendUtility::getFlexFormDS($parameterArray['fieldConf']['config'], $row, $table, $field);

		// Early return if no data structure was found at all
		if (!is_array($flexFormDataStructureArray)) {
			$resultArray = $this->initializeResultArray();
			$resultArray['html'] = 'Data Structure ERROR: ' . $flexFormDataStructureArray;
			return $resultArray;
		}

		// Manipulate Flex form DS via TSConfig and group access lists
		if (is_array($flexFormDataStructureArray)) {
			$flexFormHelper = GeneralUtility::makeInstance(FlexFormsHelper::class);
			$flexFormDataStructureArray = $flexFormHelper->modifyFlexFormDS($flexFormDataStructureArray, $table, $field, $row, $parameterArray['fieldConf']);
		}

		// Get data
		$xmlData = $parameterArray['itemFormElValue'];
		$xmlHeaderAttributes = GeneralUtility::xmlGetHeaderAttribs($xmlData);
		$storeInCharset = strtolower($xmlHeaderAttributes['encoding']);
		if ($storeInCharset) {
			$currentCharset = $languageService->charSet;
			$xmlData = $languageService->csConvObj->conv($xmlData, $storeInCharset, $currentCharset, 1);
		}
		$flexFormRowData = GeneralUtility::xml2array($xmlData);

		// Must be XML parsing error...
		if (!is_array($flexFormRowData)) {
			$flexFormRowData = array();
		} elseif (!isset($flexFormRowData['meta']) || !is_array($flexFormRowData['meta'])) {
			$flexFormRowData['meta'] = array();
		}

		$options = $this->globalOptions;
		$options['flexFormDataStructureArray'] = $flexFormDataStructureArray;
		$options['flexFormRowData'] = $flexFormRowData;
		$options['type'] = 'flexFormLanguageContainer';
		/** @var NodeFactory $nodeFactory */
		$nodeFactory = $this->globalOptions['nodeFactory'];
		return $nodeFactory->create($options)->render();
	}

	/**
	 * @return LanguageService
	 */
	protected function getLanguageService() {
		return $GLOBALS['LANG'];
	}

}