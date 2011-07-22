<?php
declare(encoding = 'utf-8');

/***************************************************************
*  Copyright notice
*
*  (c) 2010 Patrick Broens <patrick@patrickbroens.nl>
*
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
 * Repository for tx_form_domain_model_content
 *
 * @category Repository
 * @package TYPO3
 * @subpackage form
 * @author Patrick Broens <patrick@patrickbroens.nl>
 * @license http://www.gnu.org/copyleft/gpl.html
 * @version $Id$
 */
class tx_form_domain_repository_content {
	/**
	 * Get the referenced record from the database
	 *
	 * Using the GET or POST variable 'P'
	 *
	 * @return mixed|tx_form_domain_model_content if found, FALSE if not
	 * @author Patrick Broens <patrick@patrickbroens.nl>
	 */
	public function getRecord() {
		$record = FALSE;

		$getPostVariables = t3lib_div::_GP('P');
		$table = (string) $getPostVariables['table'];
		$recordId = (integer) $getPostVariables['uid'];

		$row = t3lib_BEfunc::getRecord($table, $recordId);
		if (is_array($row)) {
			/** @var $typoScriptParser t3lib_tsparser */
			$typoScriptParser = t3lib_div::makeInstance('t3lib_tsparser');
			$typoScriptParser->parse($row['bodytext']);

			/** @var $record tx_form_domain_model_content */
			$record = t3lib_div::makeInstance('tx_form_domain_model_content');
			$record->setUid($row['uid']);
			$record->setPageId($row['pid']);
			$record->setTyposcript($typoScriptParser->setup);
		}

		return $record;
	}

	/**
	 * Check if the referenced record exists
	 *
	 * @return TRUE if record exists, FALSE if not
	 * @author Patrick Broens <patrick@patrickbroens.nl>
	 */
	public function hasRecord() {
		$hasRecord = FALSE;

		$record = $this->getRecord();

		if ($record) {
			$hasRecord = TRUE;
		}

		return $hasRecord;
	}

	/**
	 * Convert and save the incoming data of the FORM wizard
	 *
	 * @return TRUE if succeeded, FALSE if not
	 * @author Patrick Broens <patrick@patrickbroens.nl>
	 */
	public function save() {
		$json = t3lib_div::_GP('configuration');
		$parameters = t3lib_div::_GP('P');
		$success = FALSE;

		/** @var $converter tx_form_domain_factory_jsontotyposcript */
		$converter = t3lib_div::makeInstance('tx_form_domain_factory_jsontotyposcript');
		$typoscript = $converter->convert($json);

		if ($typoscript) {
				// Make TCEmain object:
			/** @var $tce t3lib_TCEmain */
			$tce = t3lib_div::makeInstance('t3lib_TCEmain');
			$tce->stripslashes_values = 0;

				// Put content into the data array:
			$data = array();
			$data[$parameters['table']][$parameters['uid']][$parameters['field']] = $typoscript;

				// Perform the update:
			$tce->start($data, array());
			$tce->process_datamap();

			$success = TRUE;
		}

		return $success;
	}

	/**
	 * Read and convert the content record to JSON
	 *
	 * @return The JSON object if record exists, FALSE if not
	 * @author Patrick Broens <patrick@patrickbroens.nl>
	 */
	public function getRecordAsJson() {
		$json = FALSE;

		$record = $this->getRecord();

		if ($record) {
			$typoscript = $record->getTyposcript();

			/** @var $converter tx_form_domain_factory_typoscripttojson */
			$converter = t3lib_div::makeInstance('tx_form_domain_factory_typoscripttojson');
			$json = $converter->convert($typoscript);
		}

		return $json;
	}
}
?>