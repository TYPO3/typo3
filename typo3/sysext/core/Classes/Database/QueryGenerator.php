<?php
namespace TYPO3\CMS\Core\Database;

/**
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

use TYPO3\CMS\Backend\Utility\BackendUtility;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Backend\Utility\IconUtility;

/**
 * Class for generating front end for building queries
 *
 * @author Christian Jul Jensen <christian@typo3.com>
 * @author Kasper Skårhøj <kasperYYYY@typo3.com>
 * @coauthor Jo Hasenau <info@cybercraft.de>
 */
class QueryGenerator {

	/**
	 * @var array
	 */
	public $lang = array(
		'OR' => 'or',
		'AND' => 'and',
		'comparison' => array(
			// Type = text	offset = 0
			'0_' => 'contains',
			'1_' => 'does not contain',
			'2_' => 'starts with',
			'3_' => 'does not start with',
			'4_' => 'ends with',
			'5_' => 'does not end with',
			'6_' => 'equals',
			'7_' => 'does not equal',
			// Type = number , offset = 32
			'32_' => 'equals',
			'33_' => 'does not equal',
			'34_' => 'is greater than',
			'35_' => 'is less than',
			'36_' => 'is between',
			'37_' => 'is not between',
			'38_' => 'is in list',
			'39_' => 'is not in list',
			'40_' => 'binary AND equals',
			'41_' => 'binary AND does not equal',
			'42_' => 'binary OR equals',
			'43_' => 'binary OR does not equal',
			// Type = multiple, relation, files , offset = 64
			'64_' => 'equals',
			'65_' => 'does not equal',
			'66_' => 'contains',
			'67_' => 'does not contain',
			'68_' => 'is in list',
			'69_' => 'is not in list',
			'70_' => 'binary AND equals',
			'71_' => 'binary AND does not equal',
			'72_' => 'binary OR equals',
			'73_' => 'binary OR does not equal',
			// Type = date,time  offset = 96
			'96_' => 'equals',
			'97_' => 'does not equal',
			'98_' => 'is greater than',
			'99_' => 'is less than',
			'100_' => 'is between',
			'101_' => 'is not between',
			'102_' => 'binary AND equals',
			'103_' => 'binary AND does not equal',
			'104_' => 'binary OR equals',
			'105_' => 'binary OR does not equal',
			// Type = boolean,  offset = 128
			'128_' => 'is True',
			'129_' => 'is False',
			// Type = binary , offset = 160
			'160_' => 'equals',
			'161_' => 'does not equal',
			'162_' => 'contains',
			'163_' => 'does not contain'
		)
	);

	/**
	 * @var array
	 */
	public $compSQL = array(
		// Type = text	offset = 0
		'0' => '#FIELD# LIKE \'%#VALUE#%\'',
		'1' => '#FIELD# NOT LIKE \'%#VALUE#%\'',
		'2' => '#FIELD# LIKE \'#VALUE#%\'',
		'3' => '#FIELD# NOT LIKE \'#VALUE#%\'',
		'4' => '#FIELD# LIKE \'%#VALUE#\'',
		'5' => '#FIELD# NOT LIKE \'%#VALUE#\'',
		'6' => '#FIELD# = \'#VALUE#\'',
		'7' => '#FIELD# != \'#VALUE#\'',
		// Type = number, offset = 32
		'32' => '#FIELD# = \'#VALUE#\'',
		'33' => '#FIELD# != \'#VALUE#\'',
		'34' => '#FIELD# > #VALUE#',
		'35' => '#FIELD# < #VALUE#',
		'36' => '#FIELD# >= #VALUE# AND #FIELD# <= #VALUE1#',
		'37' => 'NOT (#FIELD# >= #VALUE# AND #FIELD# <= #VALUE1#)',
		'38' => '#FIELD# IN (#VALUE#)',
		'39' => '#FIELD# NOT IN (#VALUE#)',
		'40' => '(#FIELD# & #VALUE#)=#VALUE#',
		'41' => '(#FIELD# & #VALUE#)!=#VALUE#',
		'42' => '(#FIELD# | #VALUE#)=#VALUE#',
		'43' => '(#FIELD# | #VALUE#)!=#VALUE#',
		// Type = multiple, relation, files , offset = 64
		'64' => '#FIELD# = \'#VALUE#\'',
		'65' => '#FIELD# != \'#VALUE#\'',
		'66' => '#FIELD# LIKE \'%#VALUE#%\' AND #FIELD# LIKE \'%#VALUE1#%\'',
		'67' => '(#FIELD# NOT LIKE \'%#VALUE#%\' OR #FIELD# NOT LIKE \'%#VALUE1#%\')',
		'68' => '#FIELD# IN (#VALUE#)',
		'69' => '#FIELD# NOT IN (#VALUE#)',
		'70' => '(#FIELD# & #VALUE#)=#VALUE#',
		'71' => '(#FIELD# & #VALUE#)!=#VALUE#',
		'72' => '(#FIELD# | #VALUE#)=#VALUE#',
		'73' => '(#FIELD# | #VALUE#)!=#VALUE#',
		// Type = date, offset = 32
		'96' => '#FIELD# = \'#VALUE#\'',
		'97' => '#FIELD# != \'#VALUE#\'',
		'98' => '#FIELD# > #VALUE#',
		'99' => '#FIELD# < #VALUE#',
		'100' => '#FIELD# >= #VALUE# AND #FIELD# <= #VALUE1#',
		'101' => 'NOT (#FIELD# >= #VALUE# AND #FIELD# <= #VALUE1#)',
		'102' => '(#FIELD# & #VALUE#)=#VALUE#',
		'103' => '(#FIELD# & #VALUE#)!=#VALUE#',
		'104' => '(#FIELD# | #VALUE#)=#VALUE#',
		'105' => '(#FIELD# | #VALUE#)!=#VALUE#',
		// Type = boolean, offset = 128
		'128' => '#FIELD# = \'1\'',
		'129' => '#FIELD# != \'1\'',
		// Type = binary = 160
		'160' => '#FIELD# = \'#VALUE#\'',
		'161' => '#FIELD# != \'#VALUE#\'',
		'162' => '(#FIELD# & #VALUE#)=#VALUE#',
		'163' => '(#FIELD# & #VALUE#)=0'
	);

	/**
	 * @var array
	 */
	public $comp_offsets = array(
		'text' => 0,
		'number' => 1,
		'multiple' => 2,
		'relation' => 2,
		'files' => 2,
		'date' => 3,
		'time' => 3,
		'boolean' => 4,
		'binary' => 5
	);

	/**
	 * @var string
	 */
	public $noWrap = ' nowrap';

	/**
	 * Form data name prefix
	 *
	 * @var string
	 */
	public $name;

	/**
	 * Table for the query
	 *
	 * @var string
	 */
	public $table;

	/**
	 * Field list
	 *
	 * @var string
	 */
	public $fieldList;

	/**
	 * Array of the fields possible
	 *
	 * @var array
	 */
	public $fields = array();

	/**
	 * @var array
	 */
	public $extFieldLists = array();

	/**
	 * The query config
	 *
	 * @var array
	 */
	public $queryConfig = array();

	/**
	 * @var bool
	 */
	public $enablePrefix = FALSE;

	/**
	 * @var bool
	 */
	public $enableQueryParts = FALSE;

	/**
	 * @var string
	 */
	public $extJSCODE = '';

	/**
	 * @var string
	 */
	protected $formName = '';

	/**
	 * Make a list of fields for current table
	 *
	 * @return string Separated list of fields
	 */
	public function makeFieldList() {
		$fieldListArr = array();
		if (is_array($GLOBALS['TCA'][$this->table])) {
			$fieldListArr = array_keys($GLOBALS['TCA'][$this->table]['columns']);
			$fieldListArr[] = 'uid';
			$fieldListArr[] = 'pid';
			$fieldListArr[] = 'deleted';
			if ($GLOBALS['TCA'][$this->table]['ctrl']['tstamp']) {
				$fieldListArr[] = $GLOBALS['TCA'][$this->table]['ctrl']['tstamp'];
			}
			if ($GLOBALS['TCA'][$this->table]['ctrl']['crdate']) {
				$fieldListArr[] = $GLOBALS['TCA'][$this->table]['ctrl']['crdate'];
			}
			if ($GLOBALS['TCA'][$this->table]['ctrl']['cruser_id']) {
				$fieldListArr[] = $GLOBALS['TCA'][$this->table]['ctrl']['cruser_id'];
			}
			if ($GLOBALS['TCA'][$this->table]['ctrl']['sortby']) {
				$fieldListArr[] = $GLOBALS['TCA'][$this->table]['ctrl']['sortby'];
			}
		}
		return implode(',', $fieldListArr);
	}

	/**
	 * Init function
	 *
	 * @param string $name The name
	 * @param string $table The table name
	 * @param string $fieldList The field list
	 * @return void
	 */
	public function init($name, $table, $fieldList = '') {
		// Analysing the fields in the table.
		if (is_array($GLOBALS['TCA'][$table])) {
			$this->name = $name;
			$this->table = $table;
			$this->fieldList = $fieldList ? $fieldList : $this->makeFieldList();
			$fieldArr = GeneralUtility::trimExplode(',', $this->fieldList, TRUE);
			foreach ($fieldArr as $fieldName) {
				$fC = $GLOBALS['TCA'][$this->table]['columns'][$fieldName];
				$this->fields[$fieldName] = $fC['config'];
				$this->fields[$fieldName]['exclude'] = $fC['exclude'];
				if (is_array($fC) && $fC['label']) {
					$this->fields[$fieldName]['label'] = rtrim(trim($GLOBALS['LANG']->sL($fC['label'])), ':');
					switch ($this->fields[$fieldName]['type']) {
						case 'input':
							if (preg_match('/int|year/i', $this->fields[$fieldName]['eval'])) {
								$this->fields[$fieldName]['type'] = 'number';
							} elseif (preg_match('/time/i', $this->fields[$fieldName]['eval'])) {
								$this->fields[$fieldName]['type'] = 'time';
							} elseif (preg_match('/date/i', $this->fields[$fieldName]['eval'])) {
								$this->fields[$fieldName]['type'] = 'date';
							} else {
								$this->fields[$fieldName]['type'] = 'text';
							}
							break;
						case 'check':
							if (!$this->fields[$fieldName]['items']) {
								$this->fields[$fieldName]['type'] = 'boolean';
							} else {
								$this->fields[$fieldName]['type'] = 'binary';
							}
							break;
						case 'radio':
							$this->fields[$fieldName]['type'] = 'multiple';
							break;
						case 'select':
							$this->fields[$fieldName]['type'] = 'multiple';
							if ($this->fields[$fieldName]['foreign_table']) {
								$this->fields[$fieldName]['type'] = 'relation';
							}
							if ($this->fields[$fieldName]['special']) {
								$this->fields[$fieldName]['type'] = 'text';
							}
							break;
						case 'group':
							$this->fields[$fieldName]['type'] = 'files';
							if ($this->fields[$fieldName]['internal_type'] == 'db') {
								$this->fields[$fieldName]['type'] = 'relation';
							}
							break;
						case 'user':
						case 'flex':
						case 'passthrough':
						case 'none':
						case 'text':
						default:
							$this->fields[$fieldName]['type'] = 'text';
					}
				} else {
					$this->fields[$fieldName]['label'] = '[FIELD: ' . $fieldName . ']';
					switch ($fieldName) {
						case 'pid':
							$this->fields[$fieldName]['type'] = 'relation';
							$this->fields[$fieldName]['allowed'] = 'pages';
							break;
						case 'cruser_id':
							$this->fields[$fieldName]['type'] = 'relation';
							$this->fields[$fieldName]['allowed'] = 'be_users';
							break;
						case 'tstamp':
						case 'crdate':
							$this->fields[$fieldName]['type'] = 'time';
							break;
						case 'deleted':
							$this->fields[$fieldName]['type'] = 'boolean';
							break;
						default:
							$this->fields[$fieldName]['type'] = 'number';
					}
				}
			}
		}
		/*	// EXAMPLE:
		$this->queryConfig = array(
		array(
		'operator' => 'AND',
		'type' => 'FIELD_spaceBefore',
		),
		array(
		'operator' => 'AND',
		'type' => 'FIELD_records',
		'negate' => 1,
		'inputValue' => 'foo foo'
		),
		array(
		'type' => 'newlevel',
		'nl' => array(
		array(
		'operator' => 'AND',
		'type' => 'FIELD_spaceBefore',
		'negate' => 1,
		'inputValue' => 'foo foo'
		),
		array(
		'operator' => 'AND',
		'type' => 'FIELD_records',
		'negate' => 1,
		'inputValue' => 'foo foo'
		)
		)
		),
		array(
		'operator' => 'OR',
		'type' => 'FIELD_maillist',
		)
		);
		 */
		$this->initUserDef();
	}

	/**
	 * Set and clean up external lists
	 *
	 * @param string $name The name
	 * @param string $list The list
	 * @param string $force
	 * @return void
	 */
	public function setAndCleanUpExternalLists($name, $list, $force = '') {
		$fields = array_unique(GeneralUtility::trimExplode(',', $list . ',' . $force, TRUE));
		$reList = array();
		foreach ($fields as $fieldName) {
			if ($this->fields[$fieldName]) {
				$reList[] = $fieldName;
			}
		}
		$this->extFieldLists[$name] = implode(',', $reList);
	}

	/**
	 * Process data
	 *
	 * @param string $qC Query config
	 * @return void
	 */
	public function procesData($qC = '') {
		$this->queryConfig = $qC;
		$POST = GeneralUtility::_POST();
		// If delete...
		if ($POST['qG_del']) {
			// Initialize array to work on, save special parameters
			$ssArr = $this->getSubscript($POST['qG_del']);
			$workArr = &$this->queryConfig;
			$ssArrSize = sizeof($ssArr) - 1;
			for ($i = 0; $i < $ssArrSize; $i++) {
				$workArr = &$workArr[$ssArr[$i]];
			}
			// Delete the entry and move the other entries
			unset($workArr[$ssArr[$i]]);
			$workArrSize = sizeof($workArr);
			for ($j = $ssArr[$i]; $j < $workArrSize; $j++) {
				$workArr[$j] = $workArr[$j + 1];
				unset($workArr[$j + 1]);
			}
		}
		// If insert...
		if ($POST['qG_ins']) {
			// Initialize array to work on, save special parameters
			$ssArr = $this->getSubscript($POST['qG_ins']);
			$workArr = &$this->queryConfig;
			$ssArrSize = sizeof($ssArr) - 1;
			for ($i = 0; $i < $ssArrSize; $i++) {
				$workArr = &$workArr[$ssArr[$i]];
			}
			// Move all entries above position where new entry is to be inserted
			$workArrSize = sizeof($workArr);
			for ($j = $workArrSize; $j > $ssArr[$i]; $j--) {
				$workArr[$j] = $workArr[$j - 1];
			}
			// Clear new entry position
			unset($workArr[$ssArr[$i] + 1]);
			$workArr[$ssArr[$i] + 1]['type'] = 'FIELD_';
		}
		// If move up...
		if ($POST['qG_up']) {
			// Initialize array to work on
			$ssArr = $this->getSubscript($POST['qG_up']);
			$workArr = &$this->queryConfig;
			$ssArrSize = sizeof($ssArr) - 1;
			for ($i = 0; $i < $ssArrSize; $i++) {
				$workArr = &$workArr[$ssArr[$i]];
			}
			// Swap entries
			$qG_tmp = $workArr[$ssArr[$i]];
			$workArr[$ssArr[$i]] = $workArr[$ssArr[$i] - 1];
			$workArr[$ssArr[$i] - 1] = $qG_tmp;
		}
		// If new level...
		if ($POST['qG_nl']) {
			// Initialize array to work on
			$ssArr = $this->getSubscript($POST['qG_nl']);
			$workArr = &$this->queryConfig;
			$ssArraySize = sizeof($ssArr) - 1;
			for ($i = 0; $i < $ssArraySize; $i++) {
				$workArr = &$workArr[$ssArr[$i]];
			}
			// Do stuff:
			$tempEl = $workArr[$ssArr[$i]];
			if (is_array($tempEl)) {
				if ($tempEl['type'] != 'newlevel') {
					$workArr[$ssArr[$i]] = array(
						'type' => 'newlevel',
						'operator' => $tempEl['operator'],
						'nl' => array($tempEl)
					);
				}
			}
		}
		// If collapse level...
		if ($POST['qG_remnl']) {
			// Initialize array to work on
			$ssArr = $this->getSubscript($POST['qG_remnl']);
			$workArr = &$this->queryConfig;
			$ssArrSize = sizeof($ssArr) - 1;
			for ($i = 0; $i < $ssArrSize; $i++) {
				$workArr = &$workArr[$ssArr[$i]];
			}
			// Do stuff:
			$tempEl = $workArr[$ssArr[$i]];
			if (is_array($tempEl)) {
				if ($tempEl['type'] == 'newlevel') {
					$a1 = array_slice($workArr, 0, $ssArr[$i]);
					$a2 = array_slice($workArr, $ssArr[$i]);
					array_shift($a2);
					$a3 = $tempEl['nl'];
					$a3[0]['operator'] = $tempEl['operator'];
					$workArr = array_merge($a1, $a3, $a2);
				}
			}
		}
	}

	/**
	 * Clean up query config
	 *
	 * @param array $queryConfig Query config
	 * @return array
	 */
	public function cleanUpQueryConfig($queryConfig) {
		// Since we don't traverse the array using numeric keys in the upcoming while-loop make sure it's fresh and clean before displaying
		if (is_array($queryConfig)) {
			ksort($queryConfig);
		} else {
			// queryConfig should never be empty!
			if (!$queryConfig[0] || !$queryConfig[0]['type']) {
				$queryConfig[0] = array('type' => 'FIELD_');
			}
		}
		// Traverse:
		$c = 0;
		$arrCount = 0;
		foreach ($queryConfig as $key => $conf) {
			if (substr($conf['type'], 0, 6) == 'FIELD_') {
				$fieldName = substr($conf['type'], 6);
				$fieldType = $this->fields[$fieldName]['type'];
			} elseif ($conf['type'] == 'newlevel') {
				$fieldType = $conf['type'];
			} else {
				$fieldType = 'ignore';
			}
			switch ($fieldType) {
				case 'newlevel':
					if (!$queryConfig[$key]['nl']) {
						$queryConfig[$key]['nl'][0]['type'] = 'FIELD_';
					}
					$queryConfig[$key]['nl'] = $this->cleanUpQueryConfig($queryConfig[$key]['nl']);
					break;
				case 'userdef':
					$queryConfig[$key] = $this->userDefCleanUp($queryConfig[$key]);
					break;
				case 'ignore':

				default:
					$verifiedName = $this->verifyType($fieldName);
					$queryConfig[$key]['type'] = 'FIELD_' . $this->verifyType($verifiedName);
					if ($conf['comparison'] >> 5 != $this->comp_offsets[$fieldType]) {
						$conf['comparison'] = $this->comp_offsets[$fieldType] << 5;
					}
					$queryConfig[$key]['comparison'] = $this->verifyComparison($conf['comparison'], $conf['negate'] ? 1 : 0);
					$queryConfig[$key]['inputValue'] = $this->cleanInputVal($queryConfig[$key]);
					$queryConfig[$key]['inputValue1'] = $this->cleanInputVal($queryConfig[$key], 1);
			}
		}
		return $queryConfig;
	}

	/**
	 * Get form elements
	 *
	 * @param int $subLevel
	 * @param string $queryConfig
	 * @param string $parent
	 * @return array
	 */
	public function getFormElements($subLevel = 0, $queryConfig = '', $parent = '') {
		$codeArr = array();
		if (!is_array($queryConfig)) {
			$queryConfig = $this->queryConfig;
		}
		$c = 0;
		$arrCount = 0;
		$loopcount = 0;
		foreach ($queryConfig as $key => $conf) {
			$subscript = $parent . '[' . $key . ']';
			$lineHTML = '';
			$lineHTML .= $this->mkOperatorSelect($this->name . $subscript, $conf['operator'], $c, $conf['type'] != 'FIELD_');
			if (substr($conf['type'], 0, 6) == 'FIELD_') {
				$fieldName = substr($conf['type'], 6);
				$this->fieldName = $fieldName;
				$fieldType = $this->fields[$fieldName]['type'];
				if ($conf['comparison'] >> 5 != $this->comp_offsets[$fieldType]) {
					$conf['comparison'] = $this->comp_offsets[$fieldType] << 5;
				}
				//nasty nasty...
				//make sure queryConfig contains _actual_ comparevalue.
				//mkCompSelect don't care, but getQuery does.
				$queryConfig[$key]['comparison'] += isset($conf['negate']) - $conf['comparison'] % 2;
			} elseif ($conf['type'] == 'newlevel') {
				$fieldType = $conf['type'];
			} else {
				$fieldType = 'ignore';
			}
			switch ($fieldType) {
				case 'ignore':
					break;
				case 'newlevel':
					if (!$queryConfig[$key]['nl']) {
						$queryConfig[$key]['nl'][0]['type'] = 'FIELD_';
					}
					$lineHTML .= '<input type="hidden" name="' . $this->name . $subscript . '[type]" value="newlevel">';
					$codeArr[$arrCount]['sub'] = $this->getFormElements($subLevel + 1, $queryConfig[$key]['nl'], $subscript . '[nl]');
					break;
				case 'userdef':
					$lineHTML .= $this->userDef($this->name . $subscript, $conf, $fieldName, $fieldType);
					break;
				case 'date':
					$lineHTML .= $this->mkTypeSelect($this->name . $subscript . '[type]', $fieldName);
					$lineHTML .= $this->mkCompSelect($this->name . $subscript . '[comparison]', $conf['comparison'], $conf['negate'] ? 1 : 0);
					$lineHTML .= '<input type="checkbox" class="checkbox"' . ($conf['negate'] ? ' checked' : '') . ' name="' . $this->name . $subscript . '[negate]' . '" onClick="submit();">';
					if ($conf['comparison'] == 100 || $conf['comparison'] == 101) {
						// between
						$lineHTML .= '<input type="text" name="' . $this->name . $subscript . '[inputValue]_hr' . '" value="' . strftime('%e-%m-%Y', $conf['inputValue']) . '" ' . $GLOBALS['TBE_TEMPLATE']->formWidth(10) . ' onChange="typo3form.fieldGet(\'' . $this->name . $subscript . '[inputValue]\', \'date\', \'\', 0,0);"><input type="hidden" value="' . htmlspecialchars($conf['inputValue']) . '" name="' . $this->name . $subscript . '[inputValue]' . '">';
						$lineHTML .= '<input type="text" name="' . $this->name . $subscript . '[inputValue1]_hr' . '" value="' . strftime('%e-%m-%Y', $conf['inputValue1']) . '" ' . $GLOBALS['TBE_TEMPLATE']->formWidth(10) . ' onChange="typo3form.fieldGet(\'' . $this->name . $subscript . '[inputValue1]\', \'date\', \'\', 0,0);"><input type="hidden" value="' . htmlspecialchars($conf['inputValue1']) . '" name="' . $this->name . $subscript . '[inputValue1]' . '">';
						$this->extJSCODE .= 'typo3form.fieldSet("' . $this->name . $subscript . '[inputValue]", "date", "", 0,0);';
						$this->extJSCODE .= 'typo3form.fieldSet("' . $this->name . $subscript . '[inputValue1]", "date", "", 0,0);';
					} else {
						$lineHTML .= '<input type="text" name="' . $this->name . $subscript . '[inputValue]_hr' . '" value="' . strftime('%e-%m-%Y', $conf['inputValue']) . '" ' . $GLOBALS['TBE_TEMPLATE']->formWidth(10) . ' onChange="typo3form.fieldGet(\'' . $this->name . $subscript . '[inputValue]\', \'date\', \'\', 0,0);"><input type="hidden" value="' . htmlspecialchars($conf['inputValue']) . '" name="' . $this->name . $subscript . '[inputValue]' . '">';
						$this->extJSCODE .= 'typo3form.fieldSet("' . $this->name . $subscript . '[inputValue]", "date", "", 0,0);';
					}
					break;
				case 'time':
					$lineHTML .= $this->mkTypeSelect($this->name . $subscript . '[type]', $fieldName);
					$lineHTML .= $this->mkCompSelect($this->name . $subscript . '[comparison]', $conf['comparison'], $conf['negate'] ? 1 : 0);
					$lineHTML .= '<input type="checkbox" class="checkbox"' . ($conf['negate'] ? ' checked' : '') . ' name="' . $this->name . $subscript . '[negate]' . '" onClick="submit();">';
					if ($conf['comparison'] == 100 || $conf['comparison'] == 101) {
						// between:
						$lineHTML .= '<input type="text" name="' . $this->name . $subscript . '[inputValue]_hr' . '" value="' . strftime('%H:%M %e-%m-%Y', $conf['inputValue']) . '" ' . $GLOBALS['TBE_TEMPLATE']->formWidth(10) . ' onChange="typo3form.fieldGet(\'' . $this->name . $subscript . '[inputValue]\', \'datetime\', \'\', 0,0);"><input type="hidden" value="' . htmlspecialchars($conf['inputValue']) . '" name="' . $this->name . $subscript . '[inputValue]' . '">';
						$lineHTML .= '<input type="text" name="' . $this->name . $subscript . '[inputValue1]_hr' . '" value="' . strftime('%H:%M %e-%m-%Y', $conf['inputValue1']) . '" ' . $GLOBALS['TBE_TEMPLATE']->formWidth(10) . ' onChange="typo3form.fieldGet(\'' . $this->name . $subscript . '[inputValue1]\', \'datetime\', \'\', 0,0);"><input type="hidden" value="' . htmlspecialchars($conf['inputValue1']) . '" name="' . $this->name . $subscript . '[inputValue1]' . '">';
						$this->extJSCODE .= 'typo3form.fieldSet("' . $this->name . $subscript . '[inputValue]", "datetime", "", 0,0);';
						$this->extJSCODE .= 'typo3form.fieldSet("' . $this->name . $subscript . '[inputValue1]", "datetime", "", 0,0);';
					} else {
						$lineHTML .= '<input type="text" name="' . $this->name . $subscript . '[inputValue]_hr' . '" value="' . strftime('%H:%M %e-%m-%Y', (int)$conf['inputValue']) . '" ' . $GLOBALS['TBE_TEMPLATE']->formWidth(10) . ' onChange="typo3form.fieldGet(\'' . $this->name . $subscript . '[inputValue]\', \'datetime\', \'\', 0,0);"><input type="hidden" value="' . htmlspecialchars($conf['inputValue']) . '" name="' . $this->name . $subscript . '[inputValue]' . '">';
						$this->extJSCODE .= 'typo3form.fieldSet("' . $this->name . $subscript . '[inputValue]", "datetime", "", 0,0);';
					}
					break;
				case 'multiple':
				case 'binary':
				case 'relation':
					$lineHTML .= $this->mkTypeSelect($this->name . $subscript . '[type]', $fieldName);
					$lineHTML .= $this->mkCompSelect($this->name . $subscript . '[comparison]', $conf['comparison'], $conf['negate'] ? 1 : 0);
					$lineHTML .= '<input type="checkbox" class="checkbox"' . ($conf['negate'] ? ' checked' : '') . ' name="' . $this->name . $subscript . '[negate]' . '" onClick="submit();">';
					if ($conf['comparison'] == 68 || $conf['comparison'] == 69 || $conf['comparison'] == 162 || $conf['comparison'] == 163) {
						$lineHTML .= '<select name="' . $this->name . $subscript . '[inputValue]' . '[]" style="vertical-align:top;" size="5" multiple>';
					} elseif ($conf['comparison'] == 66 || $conf['comparison'] == 67) {
						if (is_array($conf['inputValue'])) {
							$conf['inputValue'] = implode(',', $conf['inputValue']);
						}
						$lineHTML .= '<input type="text" value="' . htmlspecialchars($conf['inputValue']) . '" name="' . $this->name . $subscript . '[inputValue]' . '"' . $GLOBALS['TBE_TEMPLATE']->formWidth(10) . '>';
					} else {
						$lineHTML .= '<select name="' . $this->name . $subscript . '[inputValue]' . '" style="vertical-align:top;" onChange="submit();">';
					}
					if ($conf['comparison'] != 66 && $conf['comparison'] != 67) {
						$lineHTML .= $this->makeOptionList($fieldName, $conf, $this->table);
						$lineHTML .= '</select>';
					}
					break;
				case 'files':
					$lineHTML .= $this->mkTypeSelect($this->name . $subscript . '[type]', $fieldName);
					$lineHTML .= $this->mkCompSelect($this->name . $subscript . '[comparison]', $conf['comparison'], $conf['negate'] ? 1 : 0);
					$lineHTML .= '<input type="checkbox" class="checkbox"' . ($conf['negate'] ? ' checked' : '') . ' name="' . $this->name . $subscript . '[negate]' . '" onClick="submit();">';
					if ($conf['comparison'] == 68 || $conf['comparison'] == 69) {
						$lineHTML .= '<select name="' . $this->name . $subscript . '[inputValue]' . '[]" style="vertical-align:top;" size="5" multiple>';
					} else {
						$lineHTML .= '<select name="' . $this->name . $subscript . '[inputValue]' . '" style="vertical-align:top;" onChange="submit();">';
					}
					$lineHTML .= '<option value=""></option>' . $this->makeOptionList($fieldName, $conf, $this->table);
					$lineHTML .= '</select>';
					if ($conf['comparison'] == 66 || $conf['comparison'] == 67) {
						$lineHTML .= ' + <input type="text" value="' . htmlspecialchars($conf['inputValue1']) . '" name="' . $this->name . $subscript . '[inputValue1]' . '"' . $GLOBALS['TBE_TEMPLATE']->formWidth(10) . '>';
					}
					break;
				case 'boolean':
					$lineHTML .= $this->mkTypeSelect($this->name . $subscript . '[type]', $fieldName);
					$lineHTML .= $this->mkCompSelect($this->name . $subscript . '[comparison]', $conf['comparison'], $conf['negate'] ? 1 : 0);
					$lineHTML .= '<input type="checkbox" class="checkbox"' . ($conf['negate'] ? ' checked' : '') . ' name="' . $this->name . $subscript . '[negate]' . '" onClick="submit();">';
					$lineHTML .= '<input type="hidden" value="1" name="' . $this->name . $subscript . '[inputValue]' . '"' . $GLOBALS['TBE_TEMPLATE']->formWidth(10) . '>';
					break;
				default:
					$lineHTML .= $this->mkTypeSelect($this->name . $subscript . '[type]', $fieldName);
					$lineHTML .= $this->mkCompSelect($this->name . $subscript . '[comparison]', $conf['comparison'], $conf['negate'] ? 1 : 0);
					$lineHTML .= '<input type="checkbox" class="checkbox"' . ($conf['negate'] ? ' checked' : '') . ' name="' . $this->name . $subscript . '[negate]' . '" onClick="submit();">';
					if ($conf['comparison'] == 37 || $conf['comparison'] == 36) {
						// between:
						$lineHTML .= '<input type="text" value="' . htmlspecialchars($conf['inputValue']) . '" name="' . $this->name . $subscript . '[inputValue]' . '"' . $GLOBALS['TBE_TEMPLATE']->formWidth(5) . '>
							<input type="text" value="' . htmlspecialchars($conf['inputValue1']) . '" name="' . $this->name . $subscript . '[inputValue1]' . '"' . $GLOBALS['TBE_TEMPLATE']->formWidth(5) . '>';
					} else {
						$lineHTML .= '<input type="text" value="' . htmlspecialchars($conf['inputValue']) . '" name="' . $this->name . $subscript . '[inputValue]' . '"' . $GLOBALS['TBE_TEMPLATE']->formWidth(10) . '>';
					}
			}
			if ($fieldType != 'ignore') {
				$lineHTML .= $this->updateIcon();
				if ($loopcount) {
					$lineHTML .= '<input type="image" border="0" ' . IconUtility::skinImg($GLOBALS['BACK_PATH'], 'gfx/garbage.gif', 'width="11" height="12"') . 'title="Remove condition" name="qG_del' . $subscript . '">';
				}
				$lineHTML .= '<input type="image" border="0" ' . IconUtility::skinImg($GLOBALS['BACK_PATH'], 'gfx/add.gif', 'width="12" height="12"') . ' title="Add condition" name="qG_ins' . $subscript . '">';
				if ($c != 0) {
					$lineHTML .= '<input type="image" border="0" ' . IconUtility::skinImg($GLOBALS['BACK_PATH'], 'gfx/pil2up.gif', 'width="12" height="7"') . ' title="Move up" name="qG_up' . $subscript . '">';
				}
				if ($c != 0 && $fieldType != 'newlevel') {
					$lineHTML .= '<input type="image" border="0" ' . IconUtility::skinImg($GLOBALS['BACK_PATH'], 'gfx/pil2right.gif', 'height="12" width="7"') . ' title="New level" name="qG_nl' . $subscript . '">';
				}
				if ($fieldType == 'newlevel') {
					$lineHTML .= '<input type="image" border="0" ' . IconUtility::skinImg($GLOBALS['BACK_PATH'], 'gfx/pil2left.gif', 'height="12" width="7"') . ' title="Collapse new level" name="qG_remnl' . $subscript . '">';
				}
				$codeArr[$arrCount]['html'] = $lineHTML;
				$codeArr[$arrCount]['query'] = $this->getQuerySingle($conf, $c > 0 ? 0 : 1);
				$arrCount++;
				$c++;
			}
			$loopcount = 1;
		}
		$this->queryConfig = $queryConfig;
		return $codeArr;
	}

	/**
	 * Make option list
	 *
	 * @param string $fieldName
	 * @param array $conf
	 * @param string $table
	 * @return string
	 */
	public function makeOptionList($fieldName, $conf, $table) {
		$out = '';
		$fieldSetup = $this->fields[$fieldName];
		if ($fieldSetup['type'] == 'files') {
			if ($conf['comparison'] == 66 || $conf['comparison'] == 67) {
				$fileExtArray = explode(',', $fieldSetup['allowed']);
				natcasesort($fileExtArray);
				foreach ($fileExtArray as $fileExt) {
					if (GeneralUtility::inList($conf['inputValue'], $fileExt)) {
						$out .= '<option value="' . $fileExt . '" selected>.' . $fileExt . '</option>';
					} else {
						$out .= '<option value="' . $fileExt . '">.' . $fileExt . '</option>';
					}
				}
			}
			$d = dir(PATH_site . $fieldSetup['uploadfolder']);
			while (FALSE !== ($entry = $d->read())) {
				if ($entry == '.' || $entry == '..') {
					continue;
				}
				$fileArray[] = $entry;
			}
			$d->close();
			natcasesort($fileArray);
			foreach ($fileArray as $fileName) {
				if (GeneralUtility::inList($conf['inputValue'], $fileName)) {
					$out .= '<option value="' . $fileName . '" selected>' . $fileName . '</option>';
				} else {
					$out .= '<option value="' . $fileName . '">' . $fileName . '</option>';
				}
			}
		}
		if ($fieldSetup['type'] == 'multiple') {
			foreach ($fieldSetup['items'] as $key => $val) {
				if (substr($val[0], 0, 4) == 'LLL:') {
					$value = $GLOBALS['LANG']->sL($val[0]);
				} else {
					$value = $val[0];
				}
				if (GeneralUtility::inList($conf['inputValue'], $val[1])) {
					$out .= '<option value="' . $val[1] . '" selected>' . $value . '</option>';
				} else {
					$out .= '<option value="' . $val[1] . '">' . $value . '</option>';
				}
			}
		}
		if ($fieldSetup['type'] == 'binary') {
			foreach ($fieldSetup['items'] as $key => $val) {
				if (substr($val[0], 0, 4) == 'LLL:') {
					$value = $GLOBALS['LANG']->sL($val[0]);
				} else {
					$value = $val[0];
				}
				if (GeneralUtility::inList($conf['inputValue'], pow(2, $key))) {
					$out .= '<option value="' . pow(2, $key) . '" selected>' . $value . '</option>';
				} else {
					$out .= '<option value="' . pow(2, $key) . '">' . $value . '</option>';
				}
			}
		}
		if ($fieldSetup['type'] == 'relation') {
			if ($fieldSetup['items']) {
				foreach ($fieldSetup['items'] as $key => $val) {
					if (substr($val[0], 0, 4) == 'LLL:') {
						$value = $GLOBALS['LANG']->sL($val[0]);
					} else {
						$value = $val[0];
					}
					if (GeneralUtility::inList($conf['inputValue'], $val[1])) {
						$out .= '<option value="' . $val[1] . '" selected>' . $value . '</option>';
					} else {
						$out .= '<option value="' . $val[1] . '">' . $value . '</option>';
					}
				}
			}
			if (stristr($fieldSetup['allowed'], ',')) {
				$from_table_Arr = explode(',', $fieldSetup['allowed']);
				$useTablePrefix = 1;
				if (!$fieldSetup['prepend_tname']) {
					$checkres = $GLOBALS['TYPO3_DB']->exec_SELECTquery($fieldName, $table, BackendUtility::deleteClause($table), ($groupBy = ''), ($orderBy = ''), ($limit = ''));
					if ($checkres) {
						while ($row = $GLOBALS['TYPO3_DB']->sql_fetch_assoc($checkres)) {
							if (stristr($row[$fieldName], ',')) {
								$checkContent = explode(',', $row[$fieldName]);
								foreach ($checkContent as $singleValue) {
									if (!stristr($singleValue, '_')) {
										$dontPrefixFirstTable = 1;
									}
								}
							} else {
								$singleValue = $row[$fieldName];
								if (strlen($singleValue) && !stristr($singleValue, '_')) {
									$dontPrefixFirstTable = 1;
								}
							}
						}
						$GLOBALS['TYPO3_DB']->sql_free_result($checkres);
					}
				}
			} else {
				$from_table_Arr[0] = $fieldSetup['allowed'];
			}
			if ($fieldSetup['prepend_tname']) {
				$useTablePrefix = 1;
			}
			if ($fieldSetup['foreign_table']) {
				$from_table_Arr[0] = $fieldSetup['foreign_table'];
			}
			$counter = 0;
			$webMountPageTree = '';
			while (list(, $from_table) = each($from_table_Arr)) {
				if ($useTablePrefix && !$dontPrefixFirstTable && $counter != 1 || $counter == 1) {
					$tablePrefix = $from_table . '_';
				}
				$counter = 1;
				if (is_array($GLOBALS['TCA'][$from_table])) {
					$labelField = $GLOBALS['TCA'][$from_table]['ctrl']['label'];
					$altLabelField = $GLOBALS['TCA'][$from_table]['ctrl']['label_alt'];
					if ($GLOBALS['TCA'][$from_table]['columns'][$labelField]['config']['items']) {
						foreach ($GLOBALS['TCA'][$from_table]['columns'][$labelField]['config']['items'] as $labelArray) {
							if (substr($labelArray[0], 0, 4) == 'LLL:') {
								$labelFieldSelect[$labelArray[1]] = $GLOBALS['LANG']->sL($labelArray[0]);
							} else {
								$labelFieldSelect[$labelArray[1]] = $labelArray[0];
							}
						}
						$useSelectLabels = 1;
					}
					if ($GLOBALS['TCA'][$from_table]['columns'][$altLabelField]['config']['items']) {
						foreach ($GLOBALS['TCA'][$from_table]['columns'][$altLabelField]['config']['items'] as $altLabelArray) {
							if (substr($altLabelArray[0], 0, 4) == 'LLL:') {
								$altLabelFieldSelect[$altLabelArray[1]] = $GLOBALS['LANG']->sL($altLabelArray[0]);
							} else {
								$altLabelFieldSelect[$altLabelArray[1]] = $altLabelArray[0];
							}
						}
						$useAltSelectLabels = 1;
					}
					$altLabelFieldSelect = $altLabelField ? ',' . $altLabelField : '';
					$select_fields = 'uid,' . $labelField . $altLabelFieldSelect;
					if (!$GLOBALS['BE_USER']->isAdmin() && $GLOBALS['TYPO3_CONF_VARS']['BE']['lockBeUserToDBmounts']) {
						$webMounts = $GLOBALS['BE_USER']->returnWebmounts();
						$perms_clause = $GLOBALS['BE_USER']->getPagePermsClause(1);
						foreach ($webMounts as $key => $val) {
							if ($webMountPageTree) {
								$webMountPageTreePrefix = ',';
							}
							$webMountPageTree .= $webMountPageTreePrefix . $this->getTreeList($val, 999, ($begin = 0), $perms_clause);
						}
						if ($from_table == 'pages') {
							$where_clause = 'uid IN (' . $webMountPageTree . ') ';
							if (!$GLOBALS['SOBE']->MOD_SETTINGS['show_deleted']) {
								$where_clause .= BackendUtility::deleteClause($from_table) . ' AND' . $perms_clause;
							}
						} else {
							$where_clause = 'pid IN (' . $webMountPageTree . ') ';
							if (!$GLOBALS['SOBE']->MOD_SETTINGS['show_deleted']) {
								$where_clause .= BackendUtility::deleteClause($from_table);
							}
						}
					} else {
						$where_clause = 'uid';
						if (!$GLOBALS['SOBE']->MOD_SETTINGS['show_deleted']) {
							$where_clause .= BackendUtility::deleteClause($from_table);
						}
					}
					$orderBy = 'uid';
					if (!$this->tableArray[$from_table]) {
						$res = $GLOBALS['TYPO3_DB']->exec_SELECTquery($select_fields, $from_table, $where_clause, ($groupBy = ''), $orderBy, ($limit = ''));
					}
					if ($res) {
						while ($row = $GLOBALS['TYPO3_DB']->sql_fetch_assoc($res)) {
							$this->tableArray[$from_table][] = $row;
						}
						$GLOBALS['TYPO3_DB']->sql_free_result($res);
					}
					foreach ($this->tableArray[$from_table] as $key => $val) {
						if ($useSelectLabels) {
							$outArray[$tablePrefix . $val['uid']] = htmlspecialchars($labelFieldSelect[$val[$labelField]]);
						} elseif ($val[$labelField]) {
							$outArray[$tablePrefix . $val['uid']] = htmlspecialchars($val[$labelField]);
						} elseif ($useAltSelectLabels) {
							$outArray[$tablePrefix . $val['uid']] = htmlspecialchars($altLabelFieldSelect[$val[$altLabelField]]);
						} else {
							$outArray[$tablePrefix . $val['uid']] = htmlspecialchars($val[$altLabelField]);
						}
					}
					if ($GLOBALS['SOBE']->MOD_SETTINGS['options_sortlabel'] && is_array($outArray)) {
						natcasesort($outArray);
					}
				}
			}
			foreach ($outArray as $key2 => $val2) {
				if (GeneralUtility::inList($conf['inputValue'], $key2)) {
					$out .= '<option value="' . $key2 . '" selected>[' . $key2 . '] ' . $val2 . '</option>';
				} else {
					$out .= '<option value="' . $key2 . '">[' . $key2 . '] ' . $val2 . '</option>';
				}
			}
		}
		return $out;
	}

	/**
	 * Print code array
	 *
	 * @param array $codeArr
	 * @param int $recursionLevel
	 * @return string
	 */
	public function printCodeArray($codeArr, $recursionLevel = 0) {
		$line = '';
		$indent = '';
		if ($recursionLevel) {
			$indent = '<td style="vertical-align:top;"><img height="1" width="50"></td>';
		}
		foreach ($codeArr as $k => $v) {
			$line .= '<tr>' . $indent . '<td class="bgColor2" ' . $this->noWrap . '>' . $v['html'] . '</td></tr>';
			if ($this->enableQueryParts) {
				$line .= '<tr>' . $indent . '<td>' . $this->formatQ($v['query']) . '</td></tr>';
			}
			if (is_array($v['sub'])) {
				$line .= '<tr>' . $indent . '<td' . $this->noWrap . '>' . $this->printCodeArray($v['sub'], ($recursionLevel + 1)) . '</td></tr>';
			}
		}
		$out = '<table border="0" cellpadding="0" cellspacing="1">' . $line . '</table>';
		return $out;
	}

	/**
	 * Format query-string (output as HTML)
	 *
	 * @param string $str
	 * @return string
	 */
	public function formatQ($str) {
		return '<font size="1" face="verdana" color="maroon"><i>' . htmlspecialchars($str) . '</i></font>';
	}

	/**
	 * Make operator select
	 *
	 * @param string $name
	 * @param string $op
	 * @param bool $draw
	 * @param bool $submit
	 * @return string
	 */
	public function mkOperatorSelect($name, $op, $draw, $submit) {
		if ($draw) {
			$out = '<select name="' . $name . '[operator]"' . ($submit ? ' onChange="submit();"' : '') . '>';
			//
			$out .= '<option value="AND"' . (!$op || $op == 'AND' ? ' selected' : '') . '>' . $this->lang['AND'] . '</option>';
			$out .= '<option value="OR"' . ($op == 'OR' ? ' selected' : '') . '>' . $this->lang['OR'] . '</option>';
			$out .= '</select>';
		} else {
			$out = '<input type="hidden" value="' . $op . '" name="' . $name . '[operator]">';
			$out .= '<img src="clear.gif" height="1" width="47">';
		}
		return $out;
	}

	/**
	 * Make type select
	 *
	 * @param string $name
	 * @param string $fieldName
	 * @param string $prepend
	 * @return string
	 */
	public function mkTypeSelect($name, $fieldName, $prepend = 'FIELD_') {
		$out = '<select name="' . $name . '" onChange="submit();">';
		$out .= '<option value=""></option>';
		foreach ($this->fields as $key => $value) {
			if (!$value['exclude'] || $GLOBALS['BE_USER']->check('non_exclude_fields', $this->table . ':' . $key)) {
				$label = $this->fields[$key]['label'];
				$label_alt = $this->fields[$key]['label_alt'];
				$out .= '<option value="' . $prepend . $key . '"' . ($key == $fieldName ? ' selected' : '') . '>' . $label . '</option>';
			}
		}
		$out .= '</select>';
		return $out;
	}

	/**
	 * Verify type
	 *
	 * @param string $fieldName
	 * @return string
	 */
	public function verifyType($fieldName) {
		$first = '';
		foreach ($this->fields as $key => $value) {
			if (!$first) {
				$first = $key;
			}
			if ($key == $fieldName) {
				return $key;
			}
		}
		return $first;
	}

	/**
	 * Verify comparison
	 *
	 * @param string $comparison
	 * @param int $neg
	 * @return int
	 */
	public function verifyComparison($comparison, $neg) {
		$compOffSet = $comparison >> 5;
		$first = -1;
		for ($i = 32 * $compOffSet + $neg; $i < 32 * ($compOffSet + 1); $i += 2) {
			if ($first == -1) {
				$first = $i;
			}
			if ($i >> 1 == $comparison >> 1) {
				return $i;
			}
		}
		return $first;
	}

	/**
	 * Make field to input select
	 *
	 * @param string $name
	 * @param string $fieldName
	 * @return string
	 */
	public function mkFieldToInputSelect($name, $fieldName) {
		$out = '<input type="Text" value="' . htmlspecialchars($fieldName) . '" name="' . $name . '"' . $GLOBALS['TBE_TEMPLATE']->formWidth() . '>' . $this->updateIcon();
		$out .= '<a href="#" onClick="document.forms[0][\'' . $name . '\'].value=\'\';return false;">' . IconUtility::getSpriteIcon('actions-edit-delete', array('title' => 'Clear list')) . '</a>';
		$out .= '<BR><select name="_fieldListDummy" size="5" onChange="document.forms[0][\'' . $name . '\'].value+=\',\'+this.value">';
		foreach ($this->fields as $key => $value) {
			if (!$value['exclude'] || $GLOBALS['BE_USER']->check('non_exclude_fields', $this->table . ':' . $key)) {
				$label = $this->fields[$key]['label'];
				$label_alt = $this->fields[$key]['label_alt'];
				$out .= '<option value="' . $key . '"' . ($key == $fieldName ? ' selected' : '') . '>' . $label . '</option>';
			}
		}
		$out .= '</select>';
		return $out;
	}

	/**
	 * Make table select
	 *
	 * @param string $name
	 * @param string $cur
	 * @return string
	 */
	public function mkTableSelect($name, $cur) {
		$out = '<select name="' . $name . '" onChange="submit();">';
		$out .= '<option value=""></option>';
		foreach ($GLOBALS['TCA'] as $tN => $value) {
			if ($GLOBALS['BE_USER']->check('tables_select', $tN)) {
				$out .= '<option value="' . $tN . '"' . ($tN == $cur ? ' selected' : '') . '>' . $GLOBALS['LANG']->sl($GLOBALS['TCA'][$tN]['ctrl']['title']) . '</option>';
			}
		}
		$out .= '</select>';
		return $out;
	}

	/**
	 * Make comparison select
	 *
	 * @param string $name
	 * @param string $comparison
	 * @param int $neg
	 * @return string
	 */
	public function mkCompSelect($name, $comparison, $neg) {
		$compOffSet = $comparison >> 5;
		$out = '<select name="' . $name . '" onChange="submit();">';
		for ($i = 32 * $compOffSet + $neg; $i < 32 * ($compOffSet + 1); $i += 2) {
			if ($this->lang['comparison'][$i . '_']) {
				$out .= '<option value="' . $i . '"' . ($i >> 1 == $comparison >> 1 ? ' selected' : '') . '>' . $this->lang['comparison'][($i . '_')] . '</option>';
			}
		}
		$out .= '</select>';
		return $out;
	}

	/**
	 * Get subscript
	 *
	 * @param array $arr
	 * @return array
	 */
	public function getSubscript($arr) {
		$retArr = array();
		while (is_array($arr)) {
			reset($arr);
			list($key, ) = each($arr);
			$retArr[] = $key;
			$arr = $arr[$key];
		}
		return $retArr;
	}

	/**
	 * Init user definition
	 *
	 * @return void
	 */
	public function initUserDef() {

	}

	/**
	 * User definition
	 *
	 * @return void
	 */
	public function userDef() {

	}

	/**
	 * User definition clean up
	 *
	 * @param array $queryConfig
	 * @return array
	 */
	public function userDefCleanUp($queryConfig) {
		return $queryConfig;
	}

	/**
	 * Get query
	 *
	 * @param array $queryConfig
	 * @param string $pad
	 * @return string
	 */
	public function getQuery($queryConfig, $pad = '') {
		$qs = '';
		// Since we don't traverse the array using numeric keys in the upcoming whileloop make sure it's fresh and clean
		ksort($queryConfig);
		$first = 1;
		foreach ($queryConfig as $key => $conf) {
			switch ($conf['type']) {
				case 'newlevel':
					$qs .= LF . $pad . trim($conf['operator']) . ' (' . $this->getQuery($queryConfig[$key]['nl'], ($pad . '   ')) . LF . $pad . ')';
					break;
				case 'userdef':
					$qs .= LF . $pad . $this->getUserDefQuery($conf, $first);
					break;
				default:
					$qs .= LF . $pad . $this->getQuerySingle($conf, $first);
			}
			$first = 0;
		}
		return $qs;
	}

	/**
	 * Get single query
	 *
	 * @param array $conf
	 * @param bool $first
	 * @return string
	 */
	public function getQuerySingle($conf, $first) {
		$qs = '';
		$prefix = $this->enablePrefix ? $this->table . '.' : '';
		if (!$first) {
			// Is it OK to insert the AND operator if none is set?
			$qs .= trim(($conf['operator'] ?: 'AND')) . ' ';
		}
		$qsTmp = str_replace('#FIELD#', $prefix . trim(substr($conf['type'], 6)), $this->compSQL[$conf['comparison']]);
		$inputVal = $this->cleanInputVal($conf);
		if ($conf['comparison'] == 68 || $conf['comparison'] == 69) {
			$inputVal = explode(',', $inputVal);
			foreach ($inputVal as $key => $fileName) {
				$inputVal[$key] = '\'' . $fileName . '\'';
			}
			$inputVal = implode(',', $inputVal);
			$qsTmp = str_replace('#VALUE#', $inputVal, $qsTmp);
		} elseif ($conf['comparison'] == 162 || $conf['comparison'] == 163) {
			$inputValArray = explode(',', $inputVal);
			$inputVal = 0;
			foreach ($inputValArray as $fileName) {
				$inputVal += (int)$fileName;
			}
			$qsTmp = str_replace('#VALUE#', $inputVal, $qsTmp);
		} else {
			$qsTmp = str_replace('#VALUE#', $GLOBALS['TYPO3_DB']->quoteStr($inputVal, $this->table), $qsTmp);
		}
		if ($conf['comparison'] == 37 || $conf['comparison'] == 36 || $conf['comparison'] == 66 || $conf['comparison'] == 67 || $conf['comparison'] == 100 || $conf['comparison'] == 101) {
			// between:
			$inputVal = $this->cleanInputVal($conf, '1');
			$qsTmp = str_replace('#VALUE1#', $GLOBALS['TYPO3_DB']->quoteStr($inputVal, $this->table), $qsTmp);
		}
		$qs .= trim($qsTmp);
		return $qs;
	}

	/**
	 * Clear input value
	 *
	 * @param array $conf
	 * @param string $suffix
	 * @return string
	 */
	public function cleanInputVal($conf, $suffix = '') {
		if ($conf['comparison'] >> 5 == 0 || ($conf['comparison'] == 32 || $conf['comparison'] == 33 || $conf['comparison'] == 64 || $conf['comparison'] == 65 || $conf['comparison'] == 66 || $conf['comparison'] == 67 || $conf['comparison'] == 96 || $conf['comparison'] == 97)) {
			$inputVal = $conf['inputValue' . $suffix];
		} elseif ($conf['comparison'] == 39 || $conf['comparison'] == 38) {
			// in list:
			$inputVal = implode(',', GeneralUtility::intExplode(',', $conf['inputValue' . $suffix]));
		} elseif ($conf['comparison'] == 68 || $conf['comparison'] == 69 || $conf['comparison'] == 162 || $conf['comparison'] == 163) {
			// in list:
			if (is_array($conf['inputValue' . $suffix])) {
				$inputVal = implode(',', $conf['inputValue' . $suffix]);
			} elseif ($conf['inputValue' . $suffix]) {
				$inputVal = $conf['inputValue' . $suffix];
			} else {
				$inputVal = 0;
			}
		} else {
			$inputVal = doubleval($conf['inputValue' . $suffix]);
		}
		return $inputVal;
	}

	/**
	 * Get user definition query
	 *
	 * @param array $qcArr
	 * @param bool $first
	 * @return void
	 */
	public function getUserDefQuery($qcArr, $first) {

	}

	/**
	 * Update icon
	 *
	 * @return string
	 */
	public function updateIcon() {
		return '<input type="image" border="0" ' . IconUtility::skinImg($GLOBALS['BACK_PATH'], 'gfx/refresh_n.gif', 'width="14" height="14"') . ' title="Update" name="just_update">';
	}

	/**
	 * Get label column
	 *
	 * @return string
	 */
	public function getLabelCol() {
		return $GLOBALS['TCA'][$this->table]['ctrl']['label'];
	}

	/**
	 * Make selector table
	 *
	 * @param array $modSettings
	 * @param string $enableList
	 * @return string
	 */
	public function makeSelectorTable($modSettings, $enableList = 'table,fields,query,group,order,limit') {
		$enableArr = explode(',', $enableList);
		// Make output
		$TDparams = ' class="bgColor5" nowrap';
		if (in_array('table', $enableArr) && !$GLOBALS['BE_USER']->userTS['mod.']['dbint.']['disableSelectATable']) {
			$out = '
			<tr>
				<td' . $TDparams . '><strong>Select a table:</strong></td>
				<td' . $TDparams . '>' . $this->mkTableSelect('SET[queryTable]', $this->table) . '</td>
			</tr>';
		}
		if ($this->table) {
			// Init fields:
			$this->setAndCleanUpExternalLists('queryFields', $modSettings['queryFields'], 'uid,' . $this->getLabelCol());
			$this->setAndCleanUpExternalLists('queryGroup', $modSettings['queryGroup']);
			$this->setAndCleanUpExternalLists('queryOrder', $modSettings['queryOrder'] . ',' . $modSettings['queryOrder2']);
			// Limit:
			$this->extFieldLists['queryLimit'] = $modSettings['queryLimit'];
			if (!$this->extFieldLists['queryLimit']) {
				$this->extFieldLists['queryLimit'] = 100;
			}
			$parts = GeneralUtility::intExplode(',', $this->extFieldLists['queryLimit']);
			if ($parts[1]) {
				$this->limitBegin = $parts[0];
				$this->limitLength = $parts[1];
			} else {
				$this->limitLength = $this->extFieldLists['queryLimit'];
			}
			$this->extFieldLists['queryLimit'] = implode(',', array_slice($parts, 0, 2));
			// Insert Descending parts
			if ($this->extFieldLists['queryOrder']) {
				$descParts = explode(',', $modSettings['queryOrderDesc'] . ',' . $modSettings['queryOrder2Desc']);
				$orderParts = explode(',', $this->extFieldLists['queryOrder']);
				$reList = array();
				foreach ($orderParts as $kk => $vv) {
					$reList[] = $vv . ($descParts[$kk] ? ' DESC' : '');
				}
				$this->extFieldLists['queryOrder_SQL'] = implode(',', $reList);
			}
			// Query Generator:
			$this->procesData($modSettings['queryConfig'] ? unserialize($modSettings['queryConfig']) : '');
			$this->queryConfig = $this->cleanUpQueryConfig($this->queryConfig);
			$this->enableQueryParts = (bool)$modSettings['search_query_smallparts'];
			$codeArr = $this->getFormElements();
			$queryCode = $this->printCodeArray($codeArr);
			if (in_array('fields', $enableArr) && !$GLOBALS['BE_USER']->userTS['mod.']['dbint.']['disableSelectFields']) {
				$out .= '
				<tr>
					<td' . $TDparams . '><strong>Select fields:</strong></td>
					<td' . $TDparams . '>' . $this->mkFieldToInputSelect('SET[queryFields]', $this->extFieldLists['queryFields']) . '</td>
				</tr>';
			}
			if (in_array('query', $enableArr) && !$GLOBALS['BE_USER']->userTS['mod.']['dbint.']['disableMakeQuery']) {
				$out .= '<tr>
					<td colspan="2"' . $TDparams . '><strong>Make Query:</strong></td>
				</tr>
				<tr>
					<td colspan="2">' . $queryCode . '</td>
				</tr>
				';
			}
			if (in_array('group', $enableArr) && !$GLOBALS['BE_USER']->userTS['mod.']['dbint.']['disableGroupBy']) {
				$out .= '<tr>
					<td' . $TDparams . '><strong>Group By:</strong></td>
					<td' . $TDparams . '>' . $this->mkTypeSelect('SET[queryGroup]', $this->extFieldLists['queryGroup'], '') . '</td>
				</tr>';
			}
			if (in_array('order', $enableArr) && !$GLOBALS['BE_USER']->userTS['mod.']['dbint.']['disableOrderBy']) {
				$orderByArr = explode(',', $this->extFieldLists['queryOrder']);
				$orderBy = '';
				$orderBy .= $this->mkTypeSelect('SET[queryOrder]', $orderByArr[0], '') . '&nbsp;'
					. '<div class="checkbox"><label for="checkQueryOrderDesc">' . BackendUtility::getFuncCheck($GLOBALS['SOBE']->id, 'SET[queryOrderDesc]', $modSettings['queryOrderDesc'], '', '', 'id="checkQueryOrderDesc"') . 'Descending</label></div>';
				if ($orderByArr[0]) {
					$orderBy .= '<BR>' . $this->mkTypeSelect('SET[queryOrder2]', $orderByArr[1], '') . '&nbsp;'
					. '<div class="checkbox"><label for="checkQueryOrder2Desc">' . BackendUtility::getFuncCheck($GLOBALS['SOBE']->id, 'SET[queryOrder2Desc]', $modSettings['queryOrder2Desc'], '', '', 'id="checkQueryOrder2Desc"') . 'Descending</label></div>';
				}
				$out .= '<tr>
					<td' . $TDparams . '><strong>Order By:</strong></td>
					<td' . $TDparams . '>' . $orderBy . '</td>
				</tr>';
			}
			if (in_array('limit', $enableArr) && !$GLOBALS['BE_USER']->userTS['mod.']['dbint.']['disableLimit']) {
				$limit = '<input type="Text" value="' . htmlspecialchars($this->extFieldLists['queryLimit']) . '" name="SET[queryLimit]" id="queryLimit"' . $GLOBALS['TBE_TEMPLATE']->formWidth(10) . '>' . $this->updateIcon();
				$prevLimit = $this->limitBegin - $this->limitLength < 0 ? 0 : $this->limitBegin - $this->limitLength;
				if ($this->limitBegin) {
					$prevButton = '<input type="button" value="previous ' . $this->limitLength . '" onclick=\'document.getElementById("queryLimit").value="' . $prevLimit . ',' . $this->limitLength . '";document.forms[0].submit();\'>';
				}
				if (!$this->limitLength) {
					$this->limitLength = 100;
				}
				$nextLimit = $this->limitBegin + $this->limitLength;
				if ($nextLimit < 0) {
					$nextLimit = 0;
				}
				if ($nextLimit) {
					$nextButton = '<input type="button" value="next ' . $this->limitLength . '" onclick=\'document.getElementById("queryLimit").value="' . $nextLimit . ',' . $this->limitLength . '";document.forms[0].submit();\'>';
				}
				$numberButtons = '<input type="button" value="10" onclick=\'document.getElementById("queryLimit").value="10";document.forms[0].submit();\'>';
				$numberButtons .= '<input type="button" value="20" onclick=\'document.getElementById("queryLimit").value="20";document.forms[0].submit();\'>';
				$numberButtons .= '<input type="button" value="50" onclick=\'document.getElementById("queryLimit").value="50";document.forms[0].submit();\'>';
				$numberButtons .= '<input type="button" value="100" onclick=\'document.getElementById("queryLimit").value="100";document.forms[0].submit();\'>';
				$out .= '<tr>
					<td' . $TDparams . '><strong>Limit:</strong></td>
					<td' . $TDparams . '>' . $limit . $prevButton . $nextButton . '&nbsp;' . $numberButtons . '</td>
				</tr>
				';
			}
		}
		$out = '<table border="0" cellpadding="3" cellspacing="1" class="qg-make-query">' . $out . '</table>';
		$out .= $this->JSbottom($this->formName);
		return $out;
	}

	/**
	 * Get tree list
	 *
	 * @param int $id
	 * @param int $depth
	 * @param int $begin
	 * @param string $perms_clause
	 * @return string
	 */
	public function getTreeList($id, $depth, $begin = 0, $perms_clause) {
		$depth = (int)$depth;
		$begin = (int)$begin;
		$id = (int)$id;
		if ($id < 0) {
			$id = abs($id);
		}
		if ($begin == 0) {
			$theList = $id;
		} else {
			$theList = '';
		}
		if ($id && $depth > 0) {
			$res = $GLOBALS['TYPO3_DB']->exec_SELECTquery('uid', 'pages', 'pid=' . $id . ' ' . BackendUtility::deleteClause('pages') . ' AND ' . $perms_clause);
			while ($row = $GLOBALS['TYPO3_DB']->sql_fetch_assoc($res)) {
				if ($begin <= 0) {
					$theList .= ',' . $row['uid'];
				}
				if ($depth > 1) {
					$theList .= $this->getTreeList($row['uid'], $depth - 1, $begin - 1, $perms_clause);
				}
			}
			$GLOBALS['TYPO3_DB']->sql_free_result($res);
		}
		return $theList;
	}

	/**
	 * Get select query
	 *
	 * @param string $qString
	 * @param string $fieldName
	 * @return bool|\mysqli_result|object
	 */
	public function getSelectQuery($qString = '', $fieldName = '') {
		if (!$qString) {
			$qString = $this->getQuery($this->queryConfig);
		}
		$qString = '(' . $qString . ')';
		if (!$GLOBALS['BE_USER']->isAdmin() && $GLOBALS['TYPO3_CONF_VARS']['BE']['lockBeUserToDBmounts']) {
			$webMounts = $GLOBALS['BE_USER']->returnWebmounts();
			$perms_clause = $GLOBALS['BE_USER']->getPagePermsClause(1);
			$webMountPageTree = '';
			foreach ($webMounts as $key => $val) {
				if ($webMountPageTree) {
					$webMountPageTreePrefix = ',';
				}
				$webMountPageTree .= $webMountPageTreePrefix . $this->getTreeList($val, 999, ($begin = 0), $perms_clause);
			}
			if ($this->table == 'pages') {
				$qString .= ' AND uid IN (' . $webMountPageTree . ')';
			} else {
				$qString .= ' AND pid IN (' . $webMountPageTree . ')';
			}
		}
		$fieldlist = $this->extFieldLists['queryFields'] . ',pid' . ($GLOBALS['TCA'][$this->table]['ctrl']['delete'] ? ',' . $GLOBALS['TCA'][$this->table]['ctrl']['delete'] : '');
		if (!$GLOBALS['SOBE']->MOD_SETTINGS['show_deleted']) {
			$qString .= BackendUtility::deleteClause($this->table);
		}
		$query = $GLOBALS['TYPO3_DB']->SELECTquery($fieldlist, $this->table, $qString, trim($this->extFieldLists['queryGroup']), $this->extFieldLists['queryOrder'] ? trim($this->extFieldLists['queryOrder_SQL']) : '', $this->extFieldLists['queryLimit']);
		return $query;
	}

	/**
	 * JavaScript bottom
	 *
	 * @param string $formname
	 * @return string
	 */
	public function JSbottom($formname) {
		$out = '';
		if ($this->extJSCODE) {
			$out .= '
			<script language="javascript" type="text/javascript" src="' . $GLOBALS['BACK_PATH'] . 'sysext/backend/Resources/Public/JavaScript/jsfunc.evalfield.js"></script>
			<script language="javascript" type="text/javascript" src="' . $GLOBALS['BACK_PATH'] . 'sysext/backend/Resources/Public/JavaScript/jsfunc.tbe_editor.js"></script>
			<script language="javascript" type="text/javascript">
				TBE_EDITOR.formname = "' . $formname . '";
				TBE_EDITOR.formnameUENC = "' . rawurlencode($formname) . '";
				TBE_EDITOR.backend_interface = "' . $GLOBALS['BE_USER']->uc['interfaceSetup'] . '";
				' . $this->extJSCODE . '
			</script>';
		}
		return $out;
	}

	/**
	 * Sets the current name of the input form.
	 *
	 * @param string $formName The name of the form.
	 * @return void
	 */
	public function setFormName($formName) {
		$this->formName = trim($formName);
	}

}
