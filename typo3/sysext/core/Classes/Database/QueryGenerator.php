<?php
namespace TYPO3\CMS\Core\Database;

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

use TYPO3\CMS\Backend\Module\BaseScriptClass;
use TYPO3\CMS\Backend\Utility\BackendUtility;
use TYPO3\CMS\Core\Authentication\BackendUserAuthentication;
use TYPO3\CMS\Core\Core\Environment;
use TYPO3\CMS\Core\Database\Query\QueryHelper;
use TYPO3\CMS\Core\Database\Query\Restriction\DeletedRestriction;
use TYPO3\CMS\Core\Localization\LanguageService;
use TYPO3\CMS\Core\Type\Bitmask\Permission;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Core\Utility\MathUtility;
use TYPO3\CMS\Core\Utility\StringUtility;

/**
 * Class for generating front end for building queries
 */
class QueryGenerator
{
    /**
     * @var array
     */
    public $lang = [
        'OR' => 'or',
        'AND' => 'and',
        'comparison' => [
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
        ]
    ];

    /**
     * @var array
     */
    public $compSQL = [
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
    ];

    /**
     * @var array
     */
    public $comp_offsets = [
        'text' => 0,
        'number' => 1,
        'multiple' => 2,
        'relation' => 2,
        'files' => 2,
        'date' => 3,
        'time' => 3,
        'boolean' => 4,
        'binary' => 5
    ];

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
     * @var array
     */
    public $tableArray;

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
    public $fields = [];

    /**
     * @var array
     */
    public $extFieldLists = [];

    /**
     * The query config
     *
     * @var array
     */
    public $queryConfig = [];

    /**
     * @var bool
     */
    public $enablePrefix = false;

    /**
     * @var bool
     */
    public $enableQueryParts = false;

    /**
     * @var string
     */
    protected $formName = '';

    /**
     * @var int
     */
    protected $limitBegin;

    /**
     * @var int
     */
    protected $limitLength;

    /**
     * @var string
     */
    protected $fieldName;

    /**
     * Make a list of fields for current table
     *
     * @return string Separated list of fields
     */
    public function makeFieldList()
    {
        $fieldListArr = [];
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
     */
    public function init($name, $table, $fieldList = '')
    {
        // Analysing the fields in the table.
        if (is_array($GLOBALS['TCA'][$table])) {
            $this->name = $name;
            $this->table = $table;
            $this->fieldList = $fieldList ? $fieldList : $this->makeFieldList();
            $fieldArr = GeneralUtility::trimExplode(',', $this->fieldList, true);
            foreach ($fieldArr as $fieldName) {
                $fC = $GLOBALS['TCA'][$this->table]['columns'][$fieldName];
                $this->fields[$fieldName] = $fC['config'];
                $this->fields[$fieldName]['exclude'] = $fC['exclude'];
                if ($this->fields[$fieldName]['type'] === 'user' && !isset($this->fields[$fieldName]['type']['userFunc'])
                    || $this->fields[$fieldName]['type'] === 'none'
                ) {
                    // Do not list type=none "virtual" fields or query them from db,
                    // and if type is user without defined userFunc
                    unset($this->fields[$fieldName]);
                    continue;
                }
                if (is_array($fC) && $fC['label']) {
                    $this->fields[$fieldName]['label'] = rtrim(trim($this->getLanguageService()->sL($fC['label'])), ':');
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
                            if (!$this->fields[$fieldName]['items'] || count($this->fields[$fieldName]['items']) <= 1) {
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
                            if ($this->fields[$fieldName]['internal_type'] === 'db') {
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
     */
    public function setAndCleanUpExternalLists($name, $list, $force = '')
    {
        $fields = array_unique(GeneralUtility::trimExplode(',', $list . ',' . $force, true));
        $reList = [];
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
     */
    public function procesData($qC = '')
    {
        $this->queryConfig = $qC;
        $POST = GeneralUtility::_POST();
        // If delete...
        if ($POST['qG_del']) {
            // Initialize array to work on, save special parameters
            $ssArr = $this->getSubscript($POST['qG_del']);
            $workArr = &$this->queryConfig;
            $ssArrSize = count($ssArr) - 1;
            $i = 0;
            for (; $i < $ssArrSize; $i++) {
                $workArr = &$workArr[$ssArr[$i]];
            }
            // Delete the entry and move the other entries
            unset($workArr[$ssArr[$i]]);
            $workArrSize = count($workArr);
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
            $ssArrSize = count($ssArr) - 1;
            $i = 0;
            for (; $i < $ssArrSize; $i++) {
                $workArr = &$workArr[$ssArr[$i]];
            }
            // Move all entries above position where new entry is to be inserted
            $workArrSize = count($workArr);
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
            $ssArrSize = count($ssArr) - 1;
            $i = 0;
            for (; $i < $ssArrSize; $i++) {
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
            $ssArraySize = count($ssArr) - 1;
            $i = 0;
            for (; $i < $ssArraySize; $i++) {
                $workArr = &$workArr[$ssArr[$i]];
            }
            // Do stuff:
            $tempEl = $workArr[$ssArr[$i]];
            if (is_array($tempEl)) {
                if ($tempEl['type'] !== 'newlevel') {
                    $workArr[$ssArr[$i]] = [
                        'type' => 'newlevel',
                        'operator' => $tempEl['operator'],
                        'nl' => [$tempEl]
                    ];
                }
            }
        }
        // If collapse level...
        if ($POST['qG_remnl']) {
            // Initialize array to work on
            $ssArr = $this->getSubscript($POST['qG_remnl']);
            $workArr = &$this->queryConfig;
            $ssArrSize = count($ssArr) - 1;
            $i = 0;
            for (; $i < $ssArrSize; $i++) {
                $workArr = &$workArr[$ssArr[$i]];
            }
            // Do stuff:
            $tempEl = $workArr[$ssArr[$i]];
            if (is_array($tempEl)) {
                if ($tempEl['type'] === 'newlevel') {
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
    public function cleanUpQueryConfig($queryConfig)
    {
        // Since we don't traverse the array using numeric keys in the upcoming while-loop make sure it's fresh and clean before displaying
        if (is_array($queryConfig)) {
            ksort($queryConfig);
        } else {
            // queryConfig should never be empty!
            if (!isset($queryConfig[0]) || empty($queryConfig[0]['type'])) {
                // Make sure queryConfig is an array
                $queryConfig = [];
                $queryConfig[0] = ['type' => 'FIELD_'];
            }
        }
        // Traverse:
        foreach ($queryConfig as $key => $conf) {
            $fieldName = '';
            if (strpos($conf['type'], 'FIELD_') === 0) {
                $fieldName = substr($conf['type'], 6);
                $fieldType = $this->fields[$fieldName]['type'];
            } elseif ($conf['type'] === 'newlevel') {
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
    public function getFormElements($subLevel = 0, $queryConfig = '', $parent = '')
    {
        $codeArr = [];
        if (!is_array($queryConfig)) {
            $queryConfig = $this->queryConfig;
        }
        $c = 0;
        $arrCount = 0;
        $loopCount = 0;
        foreach ($queryConfig as $key => $conf) {
            $fieldName = '';
            $subscript = $parent . '[' . $key . ']';
            $lineHTML = [];
            $lineHTML[] = $this->mkOperatorSelect($this->name . $subscript, $conf['operator'], $c, $conf['type'] !== 'FIELD_');
            if (strpos($conf['type'], 'FIELD_') === 0) {
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
            } elseif ($conf['type'] === 'newlevel') {
                $fieldType = $conf['type'];
            } else {
                $fieldType = 'ignore';
            }
            $fieldPrefix = htmlspecialchars($this->name . $subscript);
            switch ($fieldType) {
                case 'ignore':
                    break;
                case 'newlevel':
                    if (!$queryConfig[$key]['nl']) {
                        $queryConfig[$key]['nl'][0]['type'] = 'FIELD_';
                    }
                    $lineHTML[] = '<input type="hidden" name="' . $fieldPrefix . '[type]" value="newlevel">';
                    $codeArr[$arrCount]['sub'] = $this->getFormElements($subLevel + 1, $queryConfig[$key]['nl'], $subscript . '[nl]');
                    break;
                case 'userdef':
                    $lineHTML[] = $this->userDef($fieldPrefix, $conf, $fieldName, $fieldType);
                    break;
                case 'date':
                    $lineHTML[] = '<div class="form-inline">';
                    $lineHTML[] = $this->makeComparisonSelector($subscript, $fieldName, $conf);
                    if ($conf['comparison'] === 100 || $conf['comparison'] === 101) {
                        // between
                        $lineHTML[] = $this->getDateTimePickerField($fieldPrefix . '[inputValue]', $conf['inputValue'], 'date');
                        $lineHTML[] = $this->getDateTimePickerField($fieldPrefix . '[inputValue1]', $conf['inputValue1'], 'date');
                    } else {
                        $lineHTML[] = $this->getDateTimePickerField($fieldPrefix . '[inputValue]', $conf['inputValue'], 'date');
                    }
                    $lineHTML[] = '</div>';
                    break;
                case 'time':
                    $lineHTML[] = '<div class="form-inline">';
                    $lineHTML[] = $this->makeComparisonSelector($subscript, $fieldName, $conf);
                    if ($conf['comparison'] === 100 || $conf['comparison'] === 101) {
                        // between:
                        $lineHTML[] = $this->getDateTimePickerField($fieldPrefix . '[inputValue]', $conf['inputValue'], 'datetime');
                        $lineHTML[] = $this->getDateTimePickerField($fieldPrefix . '[inputValue1]', $conf['inputValue1'], 'datetime');
                    } else {
                        $lineHTML[] = $this->getDateTimePickerField($fieldPrefix . '[inputValue]', $conf['inputValue'], 'datetime');
                    }
                    $lineHTML[] = '</div>';
                    break;
                case 'multiple':
                case 'binary':
                case 'relation':
                    $lineHTML[] = '<div class="form-inline">';
                    $lineHTML[] = $this->makeComparisonSelector($subscript, $fieldName, $conf);
                    if ($conf['comparison'] === 68 || $conf['comparison'] === 69 || $conf['comparison'] === 162 || $conf['comparison'] === 163) {
                        $lineHTML[] = '<select class="form-control" name="' . $fieldPrefix . '[inputValue]' . '[]" multiple="multiple">';
                    } elseif ($conf['comparison'] === 66 || $conf['comparison'] === 67) {
                        if (is_array($conf['inputValue'])) {
                            $conf['inputValue'] = implode(',', $conf['inputValue']);
                        }
                        $lineHTML[] = '<input class="form-control t3js-clearable" type="text" value="' . htmlspecialchars($conf['inputValue']) . '" name="' . $fieldPrefix . '[inputValue]">';
                    } elseif ($conf['comparison'] === 64) {
                        if (is_array($conf['inputValue'])) {
                            $conf['inputValue'] = $conf['inputValue'][0];
                        }
                        $lineHTML[] = '<select class="form-control t3js-submit-change" name="' . $fieldPrefix . '[inputValue]">';
                    } else {
                        $lineHTML[] = '<select class="form-control t3js-submit-change" name="' . $fieldPrefix . '[inputValue]' . '">';
                    }
                    if ($conf['comparison'] != 66 && $conf['comparison'] != 67) {
                        $lineHTML[] = $this->makeOptionList($fieldName, $conf, $this->table);
                        $lineHTML[] = '</select>';
                    }
                    $lineHTML[] = '</div>';
                    break;
                case 'files':
                    $lineHTML[] = '<div class="form-inline">';
                    $lineHTML[] = $this->makeComparisonSelector($subscript, $fieldName, $conf);
                    if ($conf['comparison'] === 68 || $conf['comparison'] === 69) {
                        $lineHTML[] = '<select class="form-control" name="' . $fieldPrefix . '[inputValue]' . '[]" multiple="multiple">';
                    } else {
                        $lineHTML[] = '<select class="form-control t3js-submit-change" name="' . $fieldPrefix . '[inputValue]' . '">';
                    }
                    $lineHTML[] = '<option value=""></option>' . $this->makeOptionList($fieldName, $conf, $this->table);
                    $lineHTML[] = '</select>';
                    if ($conf['comparison'] === 66 || $conf['comparison'] === 67) {
                        $lineHTML[] = ' + <input class="form-control t3js-clearable" type="text" value="' . htmlspecialchars($conf['inputValue1']) . '" name="' . $fieldPrefix . '[inputValue1]' . '">';
                    }
                    $lineHTML[] = '</div>';
                    break;
                case 'boolean':
                    $lineHTML[] = '<div class="form-inline">';
                    $lineHTML[] = $this->makeComparisonSelector($subscript, $fieldName, $conf);
                    $lineHTML[] = '<input type="hidden" value="1" name="' . $fieldPrefix . '[inputValue]' . '">';
                    $lineHTML[] = '</div>';
                    break;
                default:
                    $lineHTML[] = '<div class="form-inline">';
                    $lineHTML[] = $this->makeComparisonSelector($subscript, $fieldName, $conf);
                    if ($conf['comparison'] === 37 || $conf['comparison'] === 36) {
                        // between:
                        $lineHTML[] = '<input class="form-control t3js-clearable" type="text" value="' . htmlspecialchars($conf['inputValue']) . '" name="' . $fieldPrefix . '[inputValue]' . '">';
                        $lineHTML[] = '<input class="form-control t3js-clearable" type="text" value="' . htmlspecialchars($conf['inputValue1']) . '" name="' . $fieldPrefix . '[inputValue1]' . '">';
                    } else {
                        $lineHTML[] = '<input class="form-control t3js-clearable" type="text" value="' . htmlspecialchars($conf['inputValue']) . '" name="' . $fieldPrefix . '[inputValue]' . '">';
                    }
                    $lineHTML[] = '</div>';
            }
            if ($fieldType !== 'ignore') {
                $lineHTML[] = '<div class="btn-group action-button-group">';
                $lineHTML[] = $this->updateIcon();
                if ($loopCount) {
                    $lineHTML[] = '<button class="btn btn-default" title="Remove condition" name="qG_del' . htmlspecialchars($subscript) . '"><i class="fa fa-trash fa-fw"></i></button>';
                }
                $lineHTML[] = '<button class="btn btn-default" title="Add condition" name="qG_ins' . htmlspecialchars($subscript) . '"><i class="fa fa-plus fa-fw"></i></button>';
                if ($c != 0) {
                    $lineHTML[] = '<button class="btn btn-default" title="Move up" name="qG_up' . htmlspecialchars($subscript) . '"><i class="fa fa-chevron-up fa-fw"></i></button>';
                }
                if ($c != 0 && $fieldType !== 'newlevel') {
                    $lineHTML[] = '<button class="btn btn-default" title="New level" name="qG_nl' . htmlspecialchars($subscript) . '"><i class="fa fa-chevron-right fa-fw"></i></button>';
                }
                if ($fieldType === 'newlevel') {
                    $lineHTML[] = '<button class="btn btn-default" title="Collapse new level" name="qG_remnl' . htmlspecialchars($subscript) . '"><i class="fa fa-chevron-left fa-fw"></i></button>';
                }
                $lineHTML[] = '</div>';
                $codeArr[$arrCount]['html'] = implode(LF, $lineHTML);
                $codeArr[$arrCount]['query'] = $this->getQuerySingle($conf, $c > 0 ? 0 : 1);
                $arrCount++;
                $c++;
            }
            $loopCount = 1;
        }
        $this->queryConfig = $queryConfig;
        return $codeArr;
    }

    /**
     * @param string $subscript
     * @param string $fieldName
     * @param array $conf
     *
     * @return string
     */
    protected function makeComparisonSelector($subscript, $fieldName, $conf)
    {
        $fieldPrefix = $this->name . $subscript;
        $lineHTML = [];
        $lineHTML[] = $this->mkTypeSelect($fieldPrefix . '[type]', $fieldName);
        $lineHTML[] = '	<div class="input-group">';
        $lineHTML[] = $this->mkCompSelect($fieldPrefix . '[comparison]', $conf['comparison'], $conf['negate'] ? 1 : 0);
        $lineHTML[] = '	<div class="input-group-addon">';
        $lineHTML[] = '		<input type="checkbox" class="checkbox t3js-submit-click"' . ($conf['negate'] ? ' checked' : '') . ' name="' . htmlspecialchars($fieldPrefix) . '[negate]' . '">';
        $lineHTML[] = '	</div>';
        $lineHTML[] = '	</div>';
        return implode(LF, $lineHTML);
    }

    /**
     * Make option list
     *
     * @param string $fieldName
     * @param array $conf
     * @param string $table
     * @return string
     */
    public function makeOptionList($fieldName, $conf, $table)
    {
        $out = [];
        $fieldSetup = $this->fields[$fieldName];
        $languageService = $this->getLanguageService();
        if ($fieldSetup['type'] === 'files') {
            if ($conf['comparison'] === 66 || $conf['comparison'] === 67) {
                $fileExtArray = explode(',', $fieldSetup['allowed']);
                natcasesort($fileExtArray);
                foreach ($fileExtArray as $fileExt) {
                    if (GeneralUtility::inList($conf['inputValue'], $fileExt)) {
                        $out[] = '<option value="' . htmlspecialchars($fileExt) . '" selected>.' . htmlspecialchars($fileExt) . '</option>';
                    } else {
                        $out[] = '<option value="' . htmlspecialchars($fileExt) . '">.' . htmlspecialchars($fileExt) . '</option>';
                    }
                }
            }
            $d = dir(Environment::getPublicPath() . '/' . $fieldSetup['uploadfolder']);
            while (false !== ($entry = $d->read())) {
                if ($entry === '.' || $entry === '..') {
                    continue;
                }
                $fileArray[] = $entry;
            }
            $d->close();
            natcasesort($fileArray);
            foreach ($fileArray as $fileName) {
                if (GeneralUtility::inList($conf['inputValue'], $fileName)) {
                    $out[] = '<option value="' . htmlspecialchars($fileName) . '" selected>' . htmlspecialchars($fileName) . '</option>';
                } else {
                    $out[] = '<option value="' . htmlspecialchars($fileName) . '">' . htmlspecialchars($fileName) . '</option>';
                }
            }
        }
        if ($fieldSetup['type'] === 'multiple') {
            $optGroupOpen = false;
            foreach ($fieldSetup['items'] as $key => $val) {
                if (strpos($val[0], 'LLL:') === 0) {
                    $value = $languageService->sL($val[0]);
                } else {
                    $value = $val[0];
                }
                if ($val[1] === '--div--') {
                    if ($optGroupOpen) {
                        $out[] = '</optgroup>';
                    }
                    $optGroupOpen = true;
                    $out[] = '<optgroup label="' . htmlspecialchars($value) . '">';
                } elseif (GeneralUtility::inList($conf['inputValue'], $val[1])) {
                    $out[] = '<option value="' . htmlspecialchars($val[1]) . '" selected>' . htmlspecialchars($value) . '</option>';
                } else {
                    $out[] = '<option value="' . htmlspecialchars($val[1]) . '">' . htmlspecialchars($value) . '</option>';
                }
            }
            if ($optGroupOpen) {
                $out[] = '</optgroup>';
            }
        }
        if ($fieldSetup['type'] === 'binary') {
            foreach ($fieldSetup['items'] as $key => $val) {
                if (strpos($val[0], 'LLL:') === 0) {
                    $value = $languageService->sL($val[0]);
                } else {
                    $value = $val[0];
                }
                if (GeneralUtility::inList($conf['inputValue'], pow(2, $key))) {
                    $out[] = '<option value="' . pow(2, $key) . '" selected>' . htmlspecialchars($value) . '</option>';
                } else {
                    $out[] = '<option value="' . pow(2, $key) . '">' . htmlspecialchars($value) . '</option>';
                }
            }
        }
        if ($fieldSetup['type'] === 'relation') {
            $useTablePrefix = 0;
            $dontPrefixFirstTable = 0;
            if ($fieldSetup['items']) {
                foreach ($fieldSetup['items'] as $key => $val) {
                    if (strpos($val[0], 'LLL:') === 0) {
                        $value = $languageService->sL($val[0]);
                    } else {
                        $value = $val[0];
                    }
                    if (GeneralUtility::inList($conf['inputValue'], $val[1])) {
                        $out[] = '<option value="' . htmlspecialchars($val[1]) . '" selected>' . htmlspecialchars($value) . '</option>';
                    } else {
                        $out[] = '<option value="' . htmlspecialchars($val[1]) . '">' . htmlspecialchars($value) . '</option>';
                    }
                }
            }
            if (stristr($fieldSetup['allowed'], ',')) {
                $from_table_Arr = explode(',', $fieldSetup['allowed']);
                $useTablePrefix = 1;
                if (!$fieldSetup['prepend_tname']) {
                    $queryBuilder = GeneralUtility::makeInstance(ConnectionPool::class)->getQueryBuilderForTable($table);
                    $queryBuilder->getRestrictions()->removeAll()->add(GeneralUtility::makeInstance(DeletedRestriction::class));
                    $statement = $queryBuilder->select($fieldName)
                        ->from($table)
                        ->execute();
                    while ($row = $statement->fetch()) {
                        if (stristr($row[$fieldName], ',')) {
                            $checkContent = explode(',', $row[$fieldName]);
                            foreach ($checkContent as $singleValue) {
                                if (!stristr($singleValue, '_')) {
                                    $dontPrefixFirstTable = 1;
                                }
                            }
                        } else {
                            $singleValue = $row[$fieldName];
                            if ($singleValue !== '' && !stristr($singleValue, '_')) {
                                $dontPrefixFirstTable = 1;
                            }
                        }
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
            $tablePrefix = '';
            $backendUserAuthentication = $this->getBackendUserAuthentication();
            $module = $this->getModule();
            $outArray = [];
            $labelFieldSelect = [];
            foreach ($from_table_Arr as $from_table) {
                $useSelectLabels = false;
                $useAltSelectLabels = false;
                if ($useTablePrefix && !$dontPrefixFirstTable && $counter != 1 || $counter === 1) {
                    $tablePrefix = $from_table . '_';
                }
                $counter = 1;
                if (is_array($GLOBALS['TCA'][$from_table])) {
                    $labelField = $GLOBALS['TCA'][$from_table]['ctrl']['label'];
                    $altLabelField = $GLOBALS['TCA'][$from_table]['ctrl']['label_alt'];
                    if ($GLOBALS['TCA'][$from_table]['columns'][$labelField]['config']['items']) {
                        foreach ($GLOBALS['TCA'][$from_table]['columns'][$labelField]['config']['items'] as $labelArray) {
                            if (strpos($labelArray[0], 'LLL:') === 0) {
                                $labelFieldSelect[$labelArray[1]] = $languageService->sL($labelArray[0]);
                            } else {
                                $labelFieldSelect[$labelArray[1]] = $labelArray[0];
                            }
                        }
                        $useSelectLabels = true;
                    }
                    $altLabelFieldSelect = [];
                    if ($GLOBALS['TCA'][$from_table]['columns'][$altLabelField]['config']['items']) {
                        foreach ($GLOBALS['TCA'][$from_table]['columns'][$altLabelField]['config']['items'] as $altLabelArray) {
                            if (strpos($altLabelArray[0], 'LLL:') === 0) {
                                $altLabelFieldSelect[$altLabelArray[1]] = $languageService->sL($altLabelArray[0]);
                            } else {
                                $altLabelFieldSelect[$altLabelArray[1]] = $altLabelArray[0];
                            }
                        }
                        $useAltSelectLabels = true;
                    }

                    if (!$this->tableArray[$from_table]) {
                        $queryBuilder = GeneralUtility::makeInstance(ConnectionPool::class)->getQueryBuilderForTable($from_table);
                        if ($module->MOD_SETTINGS['show_deleted']) {
                            $queryBuilder->getRestrictions()->removeAll();
                        } else {
                            $queryBuilder->getRestrictions()->removeAll()->add(GeneralUtility::makeInstance(DeletedRestriction::class));
                        }
                        $selectFields = ['uid', $labelField];
                        if ($altLabelField) {
                            $selectFields[] = $altLabelField;
                        }
                        $queryBuilder->select(...$selectFields)
                            ->from($from_table)
                            ->orderBy('uid');
                        if (!$backendUserAuthentication->isAdmin() && $GLOBALS['TYPO3_CONF_VARS']['BE']['lockBeUserToDBmounts']) {
                            $webMounts = $backendUserAuthentication->returnWebmounts();
                            $perms_clause = $backendUserAuthentication->getPagePermsClause(Permission::PAGE_SHOW);
                            $webMountPageTree = '';
                            $webMountPageTreePrefix = '';
                            foreach ($webMounts as $webMount) {
                                if ($webMountPageTree) {
                                    $webMountPageTreePrefix = ',';
                                }
                                $webMountPageTree .= $webMountPageTreePrefix
                                    . $this->getTreeList($webMount, 999, 0, $perms_clause);
                            }
                            if ($from_table === 'pages') {
                                $queryBuilder->where(
                                    QueryHelper::stripLogicalOperatorPrefix($perms_clause),
                                    $queryBuilder->expr()->in(
                                        'uid',
                                        $queryBuilder->createNamedParameter(
                                            GeneralUtility::intExplode(',', $webMountPageTree),
                                            Connection::PARAM_INT_ARRAY
                                        )
                                    )
                                );
                            } else {
                                $queryBuilder->where(
                                    $queryBuilder->expr()->in(
                                        'pid',
                                        $queryBuilder->createNamedParameter(
                                            GeneralUtility::intExplode(',', $webMountPageTree),
                                            Connection::PARAM_INT_ARRAY
                                        )
                                    )
                                );
                            }
                        }
                        $statement = $queryBuilder->execute();
                        $this->tableArray[$from_table] = [];
                        while ($row = $statement->fetch()) {
                            $this->tableArray[$from_table][] = $row;
                        }
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
                    if ($module->MOD_SETTINGS['options_sortlabel'] && is_array($outArray)) {
                        natcasesort($outArray);
                    }
                }
            }
            foreach ($outArray as $key2 => $val2) {
                if (GeneralUtility::inList($conf['inputValue'], $key2)) {
                    $out[] = '<option value="' . htmlspecialchars($key2) . '" selected>[' . htmlspecialchars($key2) . '] ' . htmlspecialchars($val2) . '</option>';
                } else {
                    $out[] = '<option value="' . htmlspecialchars($key2) . '">[' . htmlspecialchars($key2) . '] ' . htmlspecialchars($val2) . '</option>';
                }
            }
        }
        return implode(LF, $out);
    }

    /**
     * Print code array
     *
     * @param array $codeArr
     * @param int $recursionLevel
     * @return string
     */
    public function printCodeArray($codeArr, $recursionLevel = 0)
    {
        $indent = 'row-group';
        if ($recursionLevel) {
            $indent = 'row-group indent indent-' . (int)$recursionLevel;
        }
        $out = [];
        foreach ($codeArr as $k => $v) {
            $out[] = '<div class="' . $indent . '">';
            $out[] = $v['html'];

            if ($this->enableQueryParts) {
                $out[] = '<pre>';
                $out[] = htmlspecialchars($v['query']);
                $out[] = '</pre>';
            }
            if (is_array($v['sub'])) {
                $out[] = '<div class="' . $indent . '">';
                $out[] = $this->printCodeArray($v['sub'], $recursionLevel + 1);
                $out[] = '</div>';
            }

            $out[] = '</div>';
        }
        return implode(LF, $out);
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
    public function mkOperatorSelect($name, $op, $draw, $submit)
    {
        $out = [];
        if ($draw) {
            $out[] = '<select class="form-control from-control-operator' . ($submit ? ' t3js-submit-change' : '') . '" name="' . htmlspecialchars($name) . '[operator]">';
            $out[] = '	<option value="AND"' . (!$op || $op === 'AND' ? ' selected' : '') . '>' . htmlspecialchars($this->lang['AND']) . '</option>';
            $out[] = '	<option value="OR"' . ($op === 'OR' ? ' selected' : '') . '>' . htmlspecialchars($this->lang['OR']) . '</option>';
            $out[] = '</select>';
        } else {
            $out[] = '<input type="hidden" value="' . htmlspecialchars($op) . '" name="' . htmlspecialchars($name) . '[operator]">';
        }
        return implode(LF, $out);
    }

    /**
     * Make type select
     *
     * @param string $name
     * @param string $fieldName
     * @param string $prepend
     * @return string
     */
    public function mkTypeSelect($name, $fieldName, $prepend = 'FIELD_')
    {
        $out = [];
        $out[] = '<select class="form-control t3js-submit-change" name="' . htmlspecialchars($name) . '">';
        $out[] = '<option value=""></option>';
        foreach ($this->fields as $key => $value) {
            if (!$value['exclude'] || $this->getBackendUserAuthentication()->check('non_exclude_fields', $this->table . ':' . $key)) {
                $label = $this->fields[$key]['label'];
                $out[] = '<option value="' . htmlspecialchars($prepend . $key) . '"' . ($key === $fieldName ? ' selected' : '') . '>' . htmlspecialchars($label) . '</option>';
            }
        }
        $out[] = '</select>';
        return implode(LF, $out);
    }

    /**
     * Verify type
     *
     * @param string $fieldName
     * @return string
     */
    public function verifyType($fieldName)
    {
        $first = '';
        foreach ($this->fields as $key => $value) {
            if (!$first) {
                $first = $key;
            }
            if ($key === $fieldName) {
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
    public function verifyComparison($comparison, $neg)
    {
        $compOffSet = $comparison >> 5;
        $first = -1;
        for ($i = 32 * $compOffSet + $neg; $i < 32 * ($compOffSet + 1); $i += 2) {
            if ($first === -1) {
                $first = $i;
            }
            if ($i >> 1 === $comparison >> 1) {
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
    public function mkFieldToInputSelect($name, $fieldName)
    {
        $out = [];
        $out[] = '<div class="input-group">';
        $out[] = '	<div class="input-group-addon">';
        $out[] = '		<span class="input-group-btn">';
        $out[] = $this->updateIcon();
        $out[] = ' 		</span>';
        $out[] = ' 	</div>';
        $out[] = '	<input type="text" class="form-control t3js-clearable" value="' . htmlspecialchars($fieldName) . '" name="' . htmlspecialchars($name) . '">';
        $out[] = '</div>';

        $out[] = '<select class="form-control t3js-addfield" name="_fieldListDummy" size="5" data-field="' . htmlspecialchars($name) . '">';
        foreach ($this->fields as $key => $value) {
            if (!$value['exclude'] || $this->getBackendUserAuthentication()->check('non_exclude_fields', $this->table . ':' . $key)) {
                $label = $this->fields[$key]['label'];
                $out[] = '<option value="' . htmlspecialchars($key) . '"' . ($key === $fieldName ? ' selected' : '') . '>' . htmlspecialchars($label) . '</option>';
            }
        }
        $out[] = '</select>';
        return implode(LF, $out);
    }

    /**
     * Make table select
     *
     * @param string $name
     * @param string $cur
     * @return string
     */
    public function mkTableSelect($name, $cur)
    {
        $out = [];
        $out[] = '<select class="form-control t3js-submit-change" name="' . $name . '">';
        $out[] = '<option value=""></option>';
        foreach ($GLOBALS['TCA'] as $tN => $value) {
            if ($this->getBackendUserAuthentication()->check('tables_select', $tN)) {
                $out[] = '<option value="' . htmlspecialchars($tN) . '"' . ($tN === $cur ? ' selected' : '') . '>' . htmlspecialchars($this->getLanguageService()->sL($GLOBALS['TCA'][$tN]['ctrl']['title'])) . '</option>';
            }
        }
        $out[] = '</select>';
        return implode(LF, $out);
    }

    /**
     * Make comparison select
     *
     * @param string $name
     * @param string $comparison
     * @param int $neg
     * @return string
     */
    public function mkCompSelect($name, $comparison, $neg)
    {
        $compOffSet = $comparison >> 5;
        $out = [];
        $out[] = '<select class="form-control t3js-submit-change" name="' . $name . '">';
        for ($i = 32 * $compOffSet + $neg; $i < 32 * ($compOffSet + 1); $i += 2) {
            if ($this->lang['comparison'][$i . '_']) {
                $out[] = '<option value="' . $i . '"' . ($i >> 1 === $comparison >> 1 ? ' selected' : '') . '>' . htmlspecialchars($this->lang['comparison'][$i . '_']) . '</option>';
            }
        }
        $out[] = '</select>';
        return implode(LF, $out);
    }

    /**
     * Get subscript
     *
     * @param array $arr
     * @return array
     */
    public function getSubscript($arr): array
    {
        $retArr = [];
        while (\is_array($arr)) {
            reset($arr);
            $key = key($arr);
            $retArr[] = $key;
            if (isset($arr[$key])) {
                $arr = $arr[$key];
            } else {
                break;
            }
        }
        return $retArr;
    }

    /**
     * Init user definition
     */
    public function initUserDef()
    {
    }

    /**
     * User definition
     *
     * @param string $fieldPrefix
     * @param array $conf
     * @param string $fieldName
     * @param string $fieldType
     *
     * @return string
     */
    public function userDef($fieldPrefix, $conf, $fieldName, $fieldType)
    {
        return '';
    }

    /**
     * User definition clean up
     *
     * @param array $queryConfig
     * @return array
     */
    public function userDefCleanUp($queryConfig)
    {
        return $queryConfig;
    }

    /**
     * Get query
     *
     * @param array $queryConfig
     * @param string $pad
     * @return string
     */
    public function getQuery($queryConfig, $pad = '')
    {
        $qs = '';
        // Since we don't traverse the array using numeric keys in the upcoming whileloop make sure it's fresh and clean
        ksort($queryConfig);
        $first = 1;
        foreach ($queryConfig as $key => $conf) {
            $conf = $this->convertIso8601DatetimeStringToUnixTimestamp($conf);
            switch ($conf['type']) {
                case 'newlevel':
                    $qs .= LF . $pad . trim($conf['operator']) . ' (' . $this->getQuery(
                        $queryConfig[$key]['nl'],
                        $pad . '   '
                    ) . LF . $pad . ')';
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
     * Convert ISO-8601 timestamp (string) into unix timestamp (int)
     *
     * @param array $conf
     * @return array
     */
    protected function convertIso8601DatetimeStringToUnixTimestamp(array $conf): array
    {
        if ($this->isDateOfIso8601Format($conf['inputValue'])) {
            $conf['inputValue'] = strtotime($conf['inputValue']);
            if ($this->isDateOfIso8601Format($conf['inputValue1'])) {
                $conf['inputValue1'] = strtotime($conf['inputValue1']);
            }
        }

        return $conf;
    }

    /**
     * Checks if the given value is of the ISO 8601 format.
     *
     * @param mixed $date
     * @return bool
     */
    protected function isDateOfIso8601Format($date): bool
    {
        if (!is_int($date) && !is_string($date)) {
            return false;
        }
        $format = 'Y-m-d\\TH:i:s\\Z';
        $formattedDate = \DateTime::createFromFormat($format, $date);
        return $formattedDate && $formattedDate->format($format) === $date;
    }

    /**
     * Get single query
     *
     * @param array $conf
     * @param bool $first
     * @return string
     */
    public function getQuerySingle($conf, $first)
    {
        $qs = '';
        $queryBuilder = GeneralUtility::makeInstance(ConnectionPool::class)->getConnectionForTable($this->table);
        $prefix = $this->enablePrefix ? $this->table . '.' : '';
        if (!$first) {
            // Is it OK to insert the AND operator if none is set?
            $operator = strtoupper(trim($conf['operator']));
            if (!in_array($operator, ['AND', 'OR'], true)) {
                $operator = 'AND';
            }
            $qs .= $operator . ' ';
        }
        $qsTmp = str_replace('#FIELD#', $prefix . trim(substr($conf['type'], 6)), $this->compSQL[$conf['comparison']]);
        $inputVal = $this->cleanInputVal($conf);
        if ($conf['comparison'] === 68 || $conf['comparison'] === 69) {
            $inputVal = explode(',', $inputVal);
            foreach ($inputVal as $key => $fileName) {
                $inputVal[$key] = $queryBuilder->quote($fileName);
            }
            $inputVal = implode(',', $inputVal);
            $qsTmp = str_replace('#VALUE#', $inputVal, $qsTmp);
        } elseif ($conf['comparison'] === 162 || $conf['comparison'] === 163) {
            $inputValArray = explode(',', $inputVal);
            $inputVal = 0;
            foreach ($inputValArray as $fileName) {
                $inputVal += (int)$fileName;
            }
            $qsTmp = str_replace('#VALUE#', $inputVal, $qsTmp);
        } else {
            if (is_array($inputVal)) {
                $inputVal = $inputVal[0];
            }
            $qsTmp = str_replace('#VALUE#', trim($queryBuilder->quote($inputVal), '\''), $qsTmp);
        }
        if ($conf['comparison'] === 37 || $conf['comparison'] === 36 || $conf['comparison'] === 66 || $conf['comparison'] === 67 || $conf['comparison'] === 100 || $conf['comparison'] === 101) {
            // between:
            $inputVal = $this->cleanInputVal($conf, '1');
            $qsTmp = str_replace('#VALUE1#', trim($queryBuilder->quote($inputVal), '\''), $qsTmp);
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
    public function cleanInputVal($conf, $suffix = '')
    {
        if ($conf['comparison'] >> 5 === 0 || ($conf['comparison'] === 32 || $conf['comparison'] === 33 || $conf['comparison'] === 64 || $conf['comparison'] === 65 || $conf['comparison'] === 66 || $conf['comparison'] === 67 || $conf['comparison'] === 96 || $conf['comparison'] === 97)) {
            $inputVal = $conf['inputValue' . $suffix];
        } elseif ($conf['comparison'] === 39 || $conf['comparison'] === 38) {
            // in list:
            $inputVal = implode(',', GeneralUtility::intExplode(',', $conf['inputValue' . $suffix]));
        } elseif ($conf['comparison'] === 68 || $conf['comparison'] === 69 || $conf['comparison'] === 162 || $conf['comparison'] === 163) {
            // in list:
            if (is_array($conf['inputValue' . $suffix])) {
                $inputVal = implode(',', $conf['inputValue' . $suffix]);
            } elseif ($conf['inputValue' . $suffix]) {
                $inputVal = $conf['inputValue' . $suffix];
            } else {
                $inputVal = 0;
            }
        } elseif (!is_array($conf['inputValue' . $suffix]) && strtotime($conf['inputValue' . $suffix])) {
            $inputVal = $conf['inputValue' . $suffix];
        } elseif (!is_array($conf['inputValue' . $suffix]) && MathUtility::canBeInterpretedAsInteger($conf['inputValue' . $suffix])) {
            $inputVal = (int)$conf['inputValue' . $suffix];
        } else {
            // TODO: Six eyes looked at this code and nobody understood completely what is going on here and why we
            // fallback to float casting, the whole class smells like it needs a refactoring.
            $inputVal = (float)$conf['inputValue' . $suffix];
        }
        return $inputVal;
    }

    /**
     * Get user definition query
     *
     * @param array $qcArr
     * @param bool $first
     */
    public function getUserDefQuery($qcArr, $first)
    {
    }

    /**
     * Update icon
     *
     * @return string
     */
    public function updateIcon()
    {
        return '<button class="btn btn-default" title="Update" name="just_update"><i class="fa fa-refresh fa-fw"></i></button>';
    }

    /**
     * Get label column
     *
     * @return string
     */
    public function getLabelCol()
    {
        return $GLOBALS['TCA'][$this->table]['ctrl']['label'];
    }

    /**
     * Make selector table
     *
     * @param array $modSettings
     * @param string $enableList
     * @return string
     */
    public function makeSelectorTable($modSettings, $enableList = 'table,fields,query,group,order,limit')
    {
        $out = [];
        $enableArr = explode(',', $enableList);
        $userTsConfig = $this->getBackendUserAuthentication()->getTSConfig();

        // Make output
        if (in_array('table', $enableArr) && !$userTsConfig['mod.']['dbint.']['disableSelectATable']) {
            $out[] = '<div class="form-group">';
            $out[] = '	<label for="SET[queryTable]">Select a table:</label>';
            $out[] =    $this->mkTableSelect('SET[queryTable]', $this->table);
            $out[] = '</div>';
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
                $reList = [];
                foreach ($orderParts as $kk => $vv) {
                    $reList[] = $vv . ($descParts[$kk] ? ' DESC' : '');
                }
                $this->extFieldLists['queryOrder_SQL'] = implode(',', $reList);
            }
            // Query Generator:
            $this->procesData($modSettings['queryConfig'] ? unserialize($modSettings['queryConfig'], ['allowed_classes' => false]) : '');
            $this->queryConfig = $this->cleanUpQueryConfig($this->queryConfig);
            $this->enableQueryParts = (bool)$modSettings['search_query_smallparts'];
            $codeArr = $this->getFormElements();
            $queryCode = $this->printCodeArray($codeArr);
            if (in_array('fields', $enableArr) && !$userTsConfig['mod.']['dbint.']['disableSelectFields']) {
                $out[] = '<div class="form-group form-group-with-button-addon">';
                $out[] = '	<label for="SET[queryFields]">Select fields:</label>';
                $out[] =    $this->mkFieldToInputSelect('SET[queryFields]', $this->extFieldLists['queryFields']);
                $out[] = '</div>';
            }
            if (in_array('query', $enableArr) && !$userTsConfig['mod.']['dbint.']['disableMakeQuery']) {
                $out[] = '<div class="form-group">';
                $out[] = '	<label>Make Query:</label>';
                $out[] =    $queryCode;
                $out[] = '</div>';
            }
            if (in_array('group', $enableArr) && !$userTsConfig['mod.']['dbint.']['disableGroupBy']) {
                $out[] = '<div class="form-group form-inline">';
                $out[] = '	<label for="SET[queryGroup]">Group By:</label>';
                $out[] =     $this->mkTypeSelect('SET[queryGroup]', $this->extFieldLists['queryGroup'], '');
                $out[] = '</div>';
            }
            if (in_array('order', $enableArr) && !$userTsConfig['mod.']['dbint.']['disableOrderBy']) {
                $module = $this->getModule();
                $orderByArr = explode(',', $this->extFieldLists['queryOrder']);
                $orderBy = [];
                $orderBy[] = $this->mkTypeSelect('SET[queryOrder]', $orderByArr[0], '');
                $orderBy[] = '<div class="checkbox">';
                $orderBy[] = '	<label for="checkQueryOrderDesc">';
                $orderBy[] =        BackendUtility::getFuncCheck($module->id, 'SET[queryOrderDesc]', $modSettings['queryOrderDesc'], '', '', 'id="checkQueryOrderDesc"') . ' Descending';
                $orderBy[] = '	</label>';
                $orderBy[] = '</div>';

                if ($orderByArr[0]) {
                    $orderBy[] = $this->mkTypeSelect('SET[queryOrder2]', $orderByArr[1], '');
                    $orderBy[] = '<div class="checkbox">';
                    $orderBy[] = '	<label for="checkQueryOrder2Desc">';
                    $orderBy[] =        BackendUtility::getFuncCheck($module->id, 'SET[queryOrder2Desc]', $modSettings['queryOrder2Desc'], '', '', 'id="checkQueryOrder2Desc"') . ' Descending';
                    $orderBy[] = '	</label>';
                    $orderBy[] = '</div>';
                }
                $out[] = '<div class="form-group form-inline">';
                $out[] = '	<label>Order By:</label>';
                $out[] =     implode(LF, $orderBy);
                $out[] = '</div>';
            }
            if (in_array('limit', $enableArr) && !$userTsConfig['mod.']['dbint.']['disableLimit']) {
                $limit = [];
                $limit[] = '<div class="input-group">';
                $limit[] = '	<div class="input-group-addon">';
                $limit[] = '		<span class="input-group-btn">';
                $limit[] = $this->updateIcon();
                $limit[] = '		</span>';
                $limit[] = '	</div>';
                $limit[] = '	<input type="text" class="form-control" value="' . htmlspecialchars($this->extFieldLists['queryLimit']) . '" name="SET[queryLimit]" id="queryLimit">';
                $limit[] = '</div>';

                $prevLimit = $this->limitBegin - $this->limitLength < 0 ? 0 : $this->limitBegin - $this->limitLength;
                $prevButton = '';
                $nextButton = '';

                if ($this->limitBegin) {
                    $prevButton = '<input type="button" class="btn btn-default" value="previous ' . htmlspecialchars($this->limitLength) . '" data-value="' . htmlspecialchars($prevLimit . ',' . $this->limitLength) . '">';
                }
                if (!$this->limitLength) {
                    $this->limitLength = 100;
                }

                $nextLimit = $this->limitBegin + $this->limitLength;
                if ($nextLimit < 0) {
                    $nextLimit = 0;
                }
                if ($nextLimit) {
                    $nextButton = '<input type="button" class="btn btn-default" value="next ' . htmlspecialchars($this->limitLength) . '" data-value="' . htmlspecialchars($nextLimit . ',' . $this->limitLength) . '">';
                }

                $out[] = '<div class="form-group form-group-with-button-addon">';
                $out[] = '	<label>Limit:</label>';
                $out[] = '	<div class="form-inline">';
                $out[] =        implode(LF, $limit);
                $out[] = '		<div class="input-group">';
                $out[] = '			<div class="btn-group t3js-limit-submit">';
                $out[] =                $prevButton;
                $out[] =                $nextButton;
                $out[] = '			</div>';
                $out[] = '			<div class="btn-group t3js-limit-submit">';
                $out[] = '				<input type="button" class="btn btn-default" data-value="10" value="10">';
                $out[] = '				<input type="button" class="btn btn-default" data-value="20" value="20">';
                $out[] = '				<input type="button" class="btn btn-default" data-value="50" value="50">';
                $out[] = '				<input type="button" class="btn btn-default" data-value="100" value="100">';
                $out[] = '			</div>';
                $out[] = '		</div>';
                $out[] = '	</div>';
                $out[] = '</div>';
            }
        }
        return implode(LF, $out);
    }

    /**
     * Recursively fetch all descendants of a given page
     *
     * @param int $id uid of the page
     * @param int $depth
     * @param int $begin
     * @param string $permClause
     * @return string comma separated list of descendant pages
     */
    public function getTreeList($id, $depth, $begin = 0, $permClause = '')
    {
        $depth = (int)$depth;
        $begin = (int)$begin;
        $id = (int)$id;
        if ($id < 0) {
            $id = abs($id);
        }
        if ($begin === 0) {
            $theList = $id;
        } else {
            $theList = '';
        }
        if ($id && $depth > 0) {
            $queryBuilder = GeneralUtility::makeInstance(ConnectionPool::class)->getQueryBuilderForTable('pages');
            $queryBuilder->getRestrictions()->removeAll()->add(GeneralUtility::makeInstance(DeletedRestriction::class));
            $queryBuilder->select('uid')
                ->from('pages')
                ->where(
                    $queryBuilder->expr()->eq('pid', $queryBuilder->createNamedParameter($id, \PDO::PARAM_INT)),
                    $queryBuilder->expr()->eq('sys_language_uid', 0)
                )
                ->orderBy('uid');
            if ($permClause !== '') {
                $queryBuilder->andWhere(QueryHelper::stripLogicalOperatorPrefix($permClause));
            }
            $statement = $queryBuilder->execute();
            while ($row = $statement->fetch()) {
                if ($begin <= 0) {
                    $theList .= ',' . $row['uid'];
                }
                if ($depth > 1) {
                    $theSubList = $this->getTreeList($row['uid'], $depth - 1, $begin - 1, $permClause);
                    if (!empty($theList) && !empty($theSubList) && ($theSubList[0] !== ',')) {
                        $theList .= ',';
                    }
                    $theList .= $theSubList;
                }
            }
        }
        return $theList;
    }

    /**
     * Get select query
     *
     * @param string $qString
     * @return string
     */
    public function getSelectQuery($qString = ''): string
    {
        $backendUserAuthentication = $this->getBackendUserAuthentication();
        $queryBuilder = GeneralUtility::makeInstance(ConnectionPool::class)->getQueryBuilderForTable($this->table);
        if ($this->getModule()->MOD_SETTINGS['show_deleted']) {
            $queryBuilder->getRestrictions()->removeAll();
        } else {
            $queryBuilder->getRestrictions()->removeAll()->add(GeneralUtility::makeInstance(DeletedRestriction::class));
        }
        $fieldList = GeneralUtility::trimExplode(
            ',',
            $this->extFieldLists['queryFields']
            . ',pid'
            . ($GLOBALS['TCA'][$this->table]['ctrl']['delete'] ? ',' . $GLOBALS['TCA'][$this->table]['ctrl']['delete'] : '')
        );
        $queryBuilder->select(...$fieldList)
            ->from($this->table);

        if ($this->extFieldLists['queryGroup']) {
            $queryBuilder->groupBy(...QueryHelper::parseGroupBy($this->extFieldLists['queryGroup']));
        }
        if ($this->extFieldLists['queryOrder']) {
            foreach (QueryHelper::parseOrderBy($this->extFieldLists['queryOrder_SQL']) as $orderPair) {
                list($fieldName, $order) = $orderPair;
                $queryBuilder->addOrderBy($fieldName, $order);
            }
        }
        if ($this->extFieldLists['queryLimit']) {
            $queryBuilder->setMaxResults((int)$this->extFieldLists['queryLimit']);
        }

        if (!$backendUserAuthentication->isAdmin() && $GLOBALS['TYPO3_CONF_VARS']['BE']['lockBeUserToDBmounts']) {
            $webMounts = $backendUserAuthentication->returnWebmounts();
            $perms_clause = $backendUserAuthentication->getPagePermsClause(Permission::PAGE_SHOW);
            $webMountPageTree = '';
            $webMountPageTreePrefix = '';
            foreach ($webMounts as $webMount) {
                if ($webMountPageTree) {
                    $webMountPageTreePrefix = ',';
                }
                $webMountPageTree .= $webMountPageTreePrefix
                    . $this->getTreeList($webMount, 999, $begin = 0, $perms_clause);
            }
            // createNamedParameter() is not used here because the SQL fragment will only include
            // the :dcValueX placeholder when the query is returned as a string. The value for the
            // placeholder would be lost in the process.
            if ($this->table === 'pages') {
                $queryBuilder->where(
                    QueryHelper::stripLogicalOperatorPrefix($perms_clause),
                    $queryBuilder->expr()->in(
                        'uid',
                        GeneralUtility::intExplode(',', $webMountPageTree)
                    )
                );
            } else {
                $queryBuilder->where(
                    $queryBuilder->expr()->in(
                        'pid',
                        GeneralUtility::intExplode(',', $webMountPageTree)
                    )
                );
            }
        }
        if (!$qString) {
            $qString = $this->getQuery($this->queryConfig);
        }
        $queryBuilder->andWhere(QueryHelper::stripLogicalOperatorPrefix($qString));

        return $queryBuilder->getSQL();
    }

    /**
     * @param string $name the field name
     * @param string $timestamp ISO-8601 timestamp
     * @param string $type [datetime, date, time, timesec, year]
     *
     * @return string
     */
    protected function getDateTimePickerField($name, $timestamp, $type)
    {
        $value = strtotime($timestamp) ? date($GLOBALS['TYPO3_CONF_VARS']['SYS']['hhmm'] . ' ' . $GLOBALS['TYPO3_CONF_VARS']['SYS']['ddmmyy'], strtotime($timestamp)) : '';
        $id = StringUtility::getUniqueId('dt_');
        $html = [];
        $html[] = '<div class="input-group" id="' . $id . '-wrapper">';
        $html[] = '		<input data-formengine-input-name="' . htmlspecialchars($name) . '" value="' . $value . '" class="form-control t3js-datetimepicker t3js-clearable" data-date-type="' . htmlspecialchars($type) . '" type="text" id="' . $id . '">';
        $html[] = '		<input name="' . htmlspecialchars($name) . '" value="' . htmlspecialchars($timestamp) . '" type="hidden">';
        $html[] = '		<span class="input-group-btn">';
        $html[] = '			<label class="btn btn-default" for="' . $id . '">';
        $html[] = '				<span class="fa fa-calendar"></span>';
        $html[] = '			</label>';
        $html[] = ' 	</span>';
        $html[] = '</div>';
        return implode(LF, $html);
    }

    /**
     * Sets the current name of the input form.
     *
     * @param string $formName The name of the form.
     */
    public function setFormName($formName)
    {
        $this->formName = trim($formName);
    }

    /**
     * @return BackendUserAuthentication
     */
    protected function getBackendUserAuthentication()
    {
        return $GLOBALS['BE_USER'];
    }

    /**
     * @return BaseScriptClass
     */
    protected function getModule()
    {
        return $GLOBALS['SOBE'];
    }

    /**
     * @return LanguageService
     */
    protected function getLanguageService()
    {
        return $GLOBALS['LANG'];
    }
}
