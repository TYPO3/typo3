<?php
namespace TYPO3\CMS\Dbal\Hooks;

/***************************************************************
 *  Copyright notice
 *
 *  (c) 2010-2013 Xavier Perseguers <xavier@typo3.org>
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
 * Hooks for TYPO3 Extension Manager.
 *
 * @author Xavier Perseguers <xavier@typo3.org>
 */
class ExtensionManagerHooks implements tx_em_Index_CheckDatabaseUpdatesHook {

	/**
	 * Maximal length for an identifier in Oracle.
	 *
	 * @var integer
	 */
	protected $maxIdentifierLength = 30;

	/**
	 * Table names should be short enough in order to let ADOdb generates
	 * the corresponding sequence for the auto-increment field 'uid'.
	 * That is, a sequence of the form {table}_uid
	 *
	 * @var integer
	 */
	protected $tableNameCharacterReservation = 4;

	/**
	 * Mapping of table and field names.
	 *
	 * @var array
	 */
	protected $mapping;

	/**
	 * Initializes internal variables.
	 *
	 * @return void
	 */
	protected function init() {
		$mapping = @$GLOBALS['TYPO3_CONF_VARS']['EXTCONF']['dbal']['mapping'];
		if (!$mapping) {
			$mapping = array();
		}
		$this->mapping = $mapping;
	}

	/**
	 * Hook that allows pre-processing of database structure modifications.
	 * This returns a user form that will temporarily replace the standard
	 * database update form to let user configure mapping.
	 *
	 * @param string $extKey: Extension key
	 * @param array $extInfo: Extension information array
	 * @param array $diff: Database differences
	 * @param t3lib_install $instObj: Instance of the installer
	 * @param tx_em_Install $parent: The calling parent object
	 * @return string Either empty string or a pre-processing user form
	 */
	public function preProcessDatabaseUpdates($extKey, array $extInfo, array $diff, \t3lib_install $instObj, \tx_em_Install $parent) {
		$content = '';
		// Remapping is only mandatory for Oracle:
		if ($GLOBALS['TYPO3_CONF_VARS']['EXTCONF']['dbal']['handlerCfg']['_DEFAULT']['type'] !== 'adodb' || $GLOBALS['TYPO3_CONF_VARS']['EXTCONF']['dbal']['handlerCfg']['_DEFAULT']['config']['driver'] !== 'oci8') {
			// Not using Oracle
			return '';
		}
		$this->init();
		if (\TYPO3\CMS\Core\Utility\GeneralUtility::_GP('dbal')) {
			$this->updateMapping(\TYPO3\CMS\Core\Utility\GeneralUtility::_GP('dbal'), $instObj);
		}
		// Search all table and field names which should be remapped
		$tableCandidates = array();
		$fieldsCandidates = array();
		foreach ($diff['extra'] as $table => $config) {
			if ($this->needsMapping($table)) {
				$tableCandidates[] = $table;
			}
			foreach ($config['fields'] as $field => $type) {
				if ($this->needsMapping($table, $field)) {
					if (!isset($fieldsCandidates[$table])) {
						$fieldsCandidates[$table] = array();
					}
					$fieldsCandidates[$table][$field] = array(
						'fullName' => $field
					);
				}
			}
		}
		if ($tableCandidates || $fieldsCandidates) {
			$mappingSuggestions = $this->getMappingSuggestions($extKey, $extInfo, $tableCandidates, $fieldsCandidates);
			$content .= $this->generateMappingForm($tableCandidates, $fieldsCandidates, $mappingSuggestions);
		}
		return $content;
	}

	/**
	 * Returns TRUE if either the table or the field name should be remapped.
	 *
	 * @param string $table
	 * @param string $field
	 * @param boolean $isKeyField
	 * @return boolean TRUE if mapping is needed, otherwise FALSE
	 */
	protected function needsMapping($table, $field = '', $isKeyField = FALSE) {
		$needsRemapping = FALSE;
		// Take existing DBAL mapping into account
		$origTable = $table;
		if (isset($this->mapping[$origTable])) {
			if (isset($this->mapping[$origTable]['mapTableName'])) {
				$table = $this->mapping[$origTable]['mapTableName'];
			}
			if ($field !== '' && isset($this->mapping[$origTable]['mapFieldNames'])) {
				if (isset($this->mapping[$origTable]['mapFieldNames'][$field])) {
					$field = $this->mapping[$origTable]['mapFieldNames'][$field];
				}
			}
		}
		if ($field === '') {
			if (substr($table, -3) === '_mm') {
				$needsRemapping = strlen($table) > $this->maxIdentifierLength;
			} else {
				$needsRemapping = strlen($table) > $this->maxIdentifierLength - $this->tableNameCharacterReservation;
			}
		} elseif (!$isKeyField) {
			$needsRemapping = strlen($field) > $this->maxIdentifierLength;
		} else {
			$needsRemapping = strlen($table . '_' . $field) > $this->maxIdentifierLength;
		}
		return $needsRemapping;
	}

	/**
	 * Returns suggestions for the mapping of table/field names.
	 *
	 * @param string $extKey
	 * @param array $extInfo
	 * @param array $tables
	 * @param array $fields
	 * @return array
	 * @api
	 */
	public function getMappingSuggestions($extKey, array $extInfo, array $tables, array $fields) {
		$suggestions = array();
		switch ($extKey) {
		case 'direct_mail':
			$suggestions['sys_dmail_ttaddress_category_mm'] = array(
				'mapTableName' => 'sys_dmail_ttaddress_cat_mm'
			);
			$suggestions['sys_dmail_ttcontent_category_mm'] = array(
				'mapTableName' => 'sys_dmail_ttcontent_cat_mm'
			);
			break;
		case 'extbase':
			$suggestions['tx_extbase_cache_reflection_tags'] = array(
				'mapTableName' => 'tx_extbase_cache_reflect_tags'
			);
			break;
		case 'templavoila':
			$suggestions['tx_templavoila_datastructure'] = array(
				'mapTableName' => 'tx_templavoila_ds'
			);
			$suggestions['tx_templavoila_tmplobj'] = array(
				'mapTableName' => 'tx_templavoila_tmpl'
			);
			break;
		default:
			$dependencies = array_keys($extInfo['EM_CONF']['constraints']['depends']);
			if (\TYPO3\CMS\Core\Utility\GeneralUtility::inArray($dependencies, 'extbase')) {
				$this->storeExtbaseMappingSuggestions($suggestions, $extKey, $extInfo, $tables, $fields);
			}
		}
		// Existing mapping take precedence over suggestions
		$suggestions = \TYPO3\CMS\Core\Utility\GeneralUtility::array_merge_recursive_overrule($suggestions, $this->mapping);
		return $suggestions;
	}

	/**
	 * Stores suggestions for the mapping of table/field names for an Extbase-based extension.
	 *
	 * @param array &$suggestions
	 * @param string $extKey
	 * @param array $extInfo
	 * @param array $tables
	 * @param array $fields
	 * @return void
	 */
	protected function storeExtbaseMappingSuggestions(array &$suggestions, $extKey, array $extInfo, array $tables, array $fields) {
		foreach ($tables as $table) {
			// Remove the "domain_model" part of the table name
			$suggestions[$table] = array(
				'mapTableName' => str_replace('domain_model_', '', $table)
			);
		}
	}

	/**
	 * Generates a mapping form.
	 *
	 * @param array $tables
	 * @param array $fields
	 * @param array $suggestions
	 * @return string
	 */
	protected function generateMappingForm(array $tables, array $fields, array $suggestions) {
		$out = array();
		$tableId = uniqid('table');
		$label = 'DBAL Mapping';
		$description = sprintf('Some table names are longer than %s characters and/or some field names are longer than %s characters.' . ' This is incompatible with your database:' . ' <ul style="list-style: square; margin: 3px 1em; padding: 3px 1em;">' . '		<li>Table names should be short enough to let ADOdb generates a sequence of the form {table}_uid for the' . '			auto-increment "uid" field within %s characters;</li>' . '		<li>Field names may not contain more than %s characters.</li>' . ' </ul>', $this->maxIdentifierLength - $this->tableNameCharacterReservation, $this->maxIdentifierLength, $this->maxIdentifierLength, $this->maxIdentifierLength);
		$tables = array_unique(array_merge($tables, array_keys($fields)));
		foreach ($tables as $table) {
			$newTableName = $table;
			if (isset($suggestions[$table]) && isset($suggestions[$table]['mapTableName'])) {
				$newTableName = $suggestions[$table]['mapTableName'];
			}
			$out[] = '
				<tr>
					<td style="padding-top: 1em;"><label for="table-' . $table . '">' . $table . '</label></td>
					<td style="padding-top: 1em;">=&gt;</td>
					<td style="padding-top: 1em;"><input type="text" size="35" id="table-' . $table . '" name="dbal[tables][' . $table . ']" value="' . $newTableName . '" /> ' . strlen($newTableName) . ' characters' . '</td>
				</tr>';
			if (isset($fields[$table])) {
				foreach ($fields[$table] as $field => $info) {
					$newFieldName = $field;
					if (isset($suggestions[$table]) && isset($suggestions[$table]['mapFieldNames'])) {
						if (isset($suggestions[$table]['mapFieldNames'][$field])) {
							$newFieldName = $suggestions[$table]['mapFieldNames'][$field];
						}
					}
					$newFieldFullName = preg_replace('/^' . $table . '/', $newTableName, $info['fullName']);
					$newFieldFullName = preg_replace('/' . $field . '$/', $newFieldName, $newFieldFullName);
					$out[] = '
						<tr>
							<td>&nbsp;&nbsp;&nbsp;&nbsp;<label for="field-' . $table . '_' . $field . '">' . $field . '</label></td>
							<td>=&gt;</td>
							<td><input type="text" size="35" id="field-' . $table . '_' . $field . '" name="dbal[fields][' . $table . '][' . $field . ']" value="' . $newFieldName . '" /> ' . ($info['fullname'] !== $field ? strlen($newFieldFullName) . ' characters: ' . $newFieldFullName : '') . '</td>
						</tr>';
				}
			}
		}
		// Compile rows:
		$content = '
			<!-- Remapping database fields / tables -->
			<h3>' . $label . '</h3>
			<p>' . $description . '</p>
			<table border="0" cellpadding="2" cellspacing="2" id="' . $tableId . '" class="remap-db-table-fields">' . implode('', $out) . '
			</table>';
		return $content;
	}

	/**
	 * Updates the mapping in localconf.php according to form input values.
	 *
	 * @param array $data
	 * @param t3lib_install $instObj
	 * @return void
	 * @api
	 */
	public function updateMapping(array $data, \t3lib_install $instObj) {
		$newMapping = $this->mapping;
		foreach ($data['tables'] as $table => $newName) {
			$newName = trim($newName);
			if ($newName && $newName !== $table) {
				if (!isset($newMapping[$table])) {
					$newMapping[$table] = array();
				}
				$newMapping[$table]['mapTableName'] = $newName;
			}
			if (isset($data['fields'][$table])) {
				foreach ($data['fields'][$table] as $field => $newName) {
					$newName = trim($newName);
					if ($newName && $newName !== $field) {
						if (!isset($newMapping[$table])) {
							$newMapping[$table] = array();
						}
						if (!isset($newMapping[$table]['mapFieldNames'])) {
							$newMapping[$table]['mapFieldNames'] = array();
						}
						$newMapping[$table]['mapFieldNames'][$field] = $newName;
					}
				}
			}
		}
		// Sort table and field names
		foreach ($newMapping as $table => &$config) {
			if (isset($config['mapFieldNames'])) {
				ksort($config['mapFieldNames']);
			}
		}
		ksort($newMapping);
		// Write new mapping to localconf.php
		$key = '$TYPO3_CONF_VARS[\'EXTCONF\'][\'dbal\'][\'mapping\']';
		$instObj->allowUpdateLocalConf = 1;
		$instObj->updateIdentity = 'TYPO3 Extension Manager';
		$lines = $instObj->writeToLocalconf_control();
		$instObj->setArrayValueInLocalconfFile($lines, $key, $newMapping);
		if ($instObj->writeToLocalconf($lines)) {
			$this->mapping = $newMapping;
		}
	}

	/**
	 * Hook that allows to dynamically extend the table definitions for e.g. custom caches.
	 * The hook implementation may return table create strings that will be respected by
	 * the extension manager during installation of an extension.
	 *
	 * @param string $extKey: Extension key
	 * @param array $extInfo: Extension information array
	 * @param string $fileContent: Content of the current extension sql file
	 * @param t3lib_install $instObj: Instance of the installer
	 * @param \TYPO3\CMS\Install\Sql\SchemaMigrator $instSqlObj: Instance of the installer sql object
	 * @param tx_em_Install $parent: The calling parent object
	 * @return string Either empty string or table create strings
	 */
	public function appendTableDefinitions($extKey, array $extInfo, $fileContent, \t3lib_install $instObj, \TYPO3\CMS\Install\Sql\SchemaMigrator $instSqlObj, \tx_em_Install $parent) {

	}

}


?>