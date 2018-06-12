<?php
namespace TYPO3\CMS\Core\TypoScript;

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

use TYPO3\CMS\Backend\Template\DocumentTemplate;
use TYPO3\CMS\Backend\Utility\BackendUtility;
use TYPO3\CMS\Core\Database\ConnectionPool;
use TYPO3\CMS\Core\Database\Query\QueryBuilder;
use TYPO3\CMS\Core\Database\Query\Restriction\DeletedRestriction;
use TYPO3\CMS\Core\Exception;
use TYPO3\CMS\Core\Imaging\Icon;
use TYPO3\CMS\Core\Imaging\IconFactory;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Core\Utility\MathUtility;
use TYPO3\CMS\Frontend\Configuration\TypoScript\ConditionMatching\ConditionMatcher;
use TYPO3\CMS\Lang\LanguageService;

/**
 * TSParser extension class to TemplateService
 * Contains functions for the TS module in TYPO3 backend
 */
class ExtendedTemplateService extends TemplateService
{
    /**
     * This string is used to indicate the point in a template from where the editable constants are listed.
     * Any vars before this point (if it exists though) is regarded as default values.
     *
     * @var string
     */
    public $edit_divider = '###MOD_TS:EDITABLE_CONSTANTS###';

    /**
     * Disabled in backend context
     *
     * @var bool
     */
    public $tt_track = false;

    /**
     * @var array
     */
    public $categories = [
        'basic' => [],
        // Constants of superior importance for the template-layout. This is dimensions, imagefiles and enabling of various features. The most basic constants, which you would almost always want to configure.
        'menu' => [],
        // Menu setup. This includes fontfiles, sizes, background images. Depending on the menutype.
        'content' => [],
        // All constants related to the display of pagecontent elements
        'page' => [],
        // General configuration like metatags, link targets
        'advanced' => [],
        // Advanced functions, which are used very seldom.
        'all' => []
    ];

    /**
     * Translated categories
     *
     * @var array
     */
    protected $categoryLabels = [];

    /**
     * This will be filled with the available categories of the current template.
     *
     * @var array
     */
    public $subCategories = [
        // Standard categories:
        'enable' => ['Enable features', 'a'],
        'dims' => ['Dimensions, widths, heights, pixels', 'b'],
        'file' => ['Files', 'c'],
        'typo' => ['Typography', 'd'],
        'color' => ['Colors', 'e'],
        'links' => ['Links and targets', 'f'],
        'language' => ['Language specific constants', 'g'],
        // subcategories based on the default content elements
        'cheader' => ['Content: \'Header\'', 'ma'],
        'cheader_g' => ['Content: \'Header\', Graphical', 'ma'],
        'ctext' => ['Content: \'Text\'', 'mb'],
        'cimage' => ['Content: \'Image\'', 'md'],
        'ctextmedia' => ['Content: \'Textmedia\'', 'ml'],
        'cbullets' => ['Content: \'Bullet list\'', 'me'],
        'ctable' => ['Content: \'Table\'', 'mf'],
        'cuploads' => ['Content: \'Filelinks\'', 'mg'],
        'cmultimedia' => ['Content: \'Multimedia\'', 'mh'],
        'cmedia' => ['Content: \'Media\'', 'mr'],
        'cmailform' => ['Content: \'Form\'', 'mi'],
        'csearch' => ['Content: \'Search\'', 'mj'],
        'clogin' => ['Content: \'Login\'', 'mk'],
        'cmenu' => ['Content: \'Menu/Sitemap\'', 'mm'],
        'cshortcut' => ['Content: \'Insert records\'', 'mn'],
        'clist' => ['Content: \'List of records\'', 'mo'],
        'chtml' => ['Content: \'HTML\'', 'mq']
    ];

    /**
     * Tsconstanteditor
     *
     * @var int
     */
    public $ext_inBrace = 0;

    /**
     * Tsbrowser
     *
     * @var array
     */
    public $tsbrowser_searchKeys = [];

    /**
     * @var array
     */
    public $tsbrowser_depthKeys = [];

    /**
     * @var string
     */
    public $constantMode = '';

    /**
     * @var string
     */
    public $regexMode = '';

    /**
     * @var string
     */
    public $fixedLgd = '';

    /**
     * @var int
     */
    public $ext_lineNumberOffset = 0;

    /**
     * @var int
     */
    public $ext_expandAllNotes = 0;

    /**
     * @var int
     */
    public $ext_noPMicons = 0;

    /**
     * @var array
     */
    public $ext_listOfTemplatesArr = [];

    /**
     * @var string
     */
    public $ext_lineNumberOffset_mode = '';

    /**
     * Don't change
     *
     * @var int
     */
    public $ext_dontCheckIssetValues = 0;

    /**
     * @var int
     */
    public $ext_printAll = 0;

    /**
     * @var string
     */
    public $ext_CEformName = 'forms[0]';

    /**
     * @var bool
     */
    public $doNotSortCategoriesBeforeMakingForm = false;

    /**
     * Ts analyzer
     *
     * @var array
     */
    public $templateTitles = [];

    /**
     * @var array|null
     */
    protected $lnToScript = null;

    /**
     * @var array
     */
    public $clearList_const_temp;

    /**
     * @var array
     */
    public $clearList_setup_temp;

    /**
     * @var string
     */
    public $bType = '';

    /**
     * @var bool
     */
    public $linkObjects = false;

    /**
     * @var array
     */
    public $helpConfig = [];

    /**
     * @var bool
     */
    public $changed = false;

    /**
     * @var int[]
     */
    protected $objReg = [];

    /**
     * @var array
     */
    public $raw = [];

    /**
     * @var int
     */
    public $rawP = 0;

    /**
     * @var string
     */
    public $lastComment = '';

    /**
     * @var array
     */
    protected $inlineJavaScript = [];

    /**
     * Gets the inline JavaScript.
     *
     * @return array
     */
    public function getInlineJavaScript()
    {
        return $this->inlineJavaScript;
    }

    /**
     * Substitute constant
     *
     * @param string $all
     * @return string
     */
    public function substituteConstants($all)
    {
        return preg_replace_callback('/\\{\\$(.[^}]+)\\}/', [$this, 'substituteConstantsCallBack'], $all);
    }

    /**
     * Call back method for preg_replace_callback in substituteConstants
     *
     * @param array $matches Regular expression matches
     * @return string Replacement
     * @see substituteConstants()
     */
    public function substituteConstantsCallBack($matches)
    {
        $marker = substr(md5($matches[0]), 0, 6);
        switch ($this->constantMode) {
            case 'const':
                $ret_val = isset($this->flatSetup[$matches[1]]) && !is_array($this->flatSetup[$matches[1]]) ? '##' . $marker . '_B##' . $matches[0] . '##' . $marker . '_E##' : $matches[0];
                break;
            case 'subst':
                $ret_val = isset($this->flatSetup[$matches[1]]) && !is_array($this->flatSetup[$matches[1]]) ? '##' . $marker . '_B##' . $this->flatSetup[$matches[1]] . '##' . $marker . '_E##' : $matches[0];
                break;
            case 'untouched':
                $ret_val = $matches[0];
                break;
            default:
                $ret_val = isset($this->flatSetup[$matches[1]]) && !is_array($this->flatSetup[$matches[1]]) ? $this->flatSetup[$matches[1]] : $matches[0];
        }
        return $ret_val;
    }

    /**
     * Substitute markers
     *
     * @param string $all
     * @return string
     */
    public function substituteCMarkers($all)
    {
        switch ($this->constantMode) {
            case 'const':
            case 'subst':
                $all = preg_replace(
                    '/##[a-z0-9]{6}_B##((?:(?!##[a-z0-9]{6}_E##).)+)?##[a-z0-9]{6}_E##/',
                        '<strong style="color: green;">$1</strong>',
                        $all
                );
                break;
            default:
        }
        return $all;
    }

    /**
     * Parse constants with respect to the constant-editor in this module.
     * In particular comments in the code are registered and the edit_divider is taken into account.
     *
     * @return array
     */
    public function generateConfig_constants()
    {
        // These vars are also set lateron...
        $this->setup['sitetitle'] = $this->sitetitle;
        // Parse constants
        $constants = GeneralUtility::makeInstance(Parser\TypoScriptParser::class);
        // Register comments!
        $constants->regComments = 1;
        $constants->setup = $this->mergeConstantsFromPageTSconfig([]);
        /** @var ConditionMatcher $matchObj */
        $matchObj = GeneralUtility::makeInstance(ConditionMatcher::class);
        // Matches ALL conditions in TypoScript
        $matchObj->setSimulateMatchResult(true);
        $c = 0;
        $cc = count($this->constants);
        $defaultConstants = [];
        foreach ($this->constants as $str) {
            $c++;
            if ($c == $cc) {
                if (strstr($str, $this->edit_divider)) {
                    $parts = explode($this->edit_divider, $str, 2);
                    $str = $parts[1];
                    $constants->parse($parts[0], $matchObj);
                }
                $this->flatSetup = [];
                $this->flattenSetup($constants->setup, '');
                $defaultConstants = $this->flatSetup;
            }
            $constants->parse($str, $matchObj);
        }
        $this->flatSetup = [];
        $this->flattenSetup($constants->setup, '');
        $this->setup['constants'] = $constants->setup;
        return $this->ext_compareFlatSetups($defaultConstants);
    }

    /**
     * @param array $theSetup
     * @param string $theKey
     * @return array
     */
    public function ext_getSetup($theSetup, $theKey)
    {
        $parts = explode('.', $theKey, 2);
        if ((string)$parts[0] !== '' && is_array($theSetup[$parts[0] . '.'])) {
            if (trim($parts[1]) !== '') {
                return $this->ext_getSetup($theSetup[$parts[0] . '.'], trim($parts[1]));
            }
            return [$theSetup[$parts[0] . '.'], $theSetup[$parts[0]]];
        }
        if (trim($theKey) !== '') {
            return [[], $theSetup[$theKey]];
        }
        return [$theSetup, ''];
    }

    /**
     * Get object tree
     *
     * @param array $arr
     * @param string $depth_in
     * @param string $depthData
     * @param string $parentType (unused)
     * @param string $parentValue (unused)
     * @param string $alphaSort sorts the array keys / tree by alphabet when set to 1
     * @return string
     */
    public function ext_getObjTree($arr, $depth_in, $depthData, $parentType = '', $parentValue = '', $alphaSort = '0')
    {
        $HTML = '';
        if ($alphaSort == '1') {
            ksort($arr);
        }
        $keyArr_num = [];
        $keyArr_alpha = [];
        foreach ($arr as $key => $value) {
            // Don't do anything with comments / linenumber registrations...
            if (substr($key, -2) !== '..') {
                $key = preg_replace('/\\.$/', '', $key);
                if (substr($key, -1) !== '.') {
                    if (MathUtility::canBeInterpretedAsInteger($key)) {
                        $keyArr_num[$key] = $arr[$key];
                    } else {
                        $keyArr_alpha[$key] = $arr[$key];
                    }
                }
            }
        }
        ksort($keyArr_num);
        $keyArr = $keyArr_num + $keyArr_alpha;
        if ($depth_in) {
            $depth_in = $depth_in . '.';
        }
        foreach ($keyArr as $key => $value) {
            $depth = $depth_in . $key;
            // This excludes all constants starting with '_' from being shown.
            if ($this->bType !== 'const' || $depth[0] !== '_') {
                $goto = substr(md5($depth), 0, 6);
                $deeper = is_array($arr[$key . '.']) && ($this->tsbrowser_depthKeys[$depth] || $this->ext_expandAllNotes) ? 1 : 0;
                $PM = is_array($arr[$key . '.']) && !$this->ext_noPMicons ? ($deeper ? 'minus' : 'plus') : 'join';
                $HTML .= $depthData . '<li>';
                if ($PM !== 'join') {
                    $urlParameters = [
                        'id' => $GLOBALS['SOBE']->id,
                        'tsbr[' . $depth . ']' => $deeper ? 0 : 1
                    ];
                    if (GeneralUtility::_GP('breakPointLN')) {
                        $urlParameters['breakPointLN'] = GeneralUtility::_GP('breakPointLN');
                    }
                    $aHref = BackendUtility::getModuleUrl('web_ts', $urlParameters) . '#' . $goto;
                    $HTML .= '<a class="list-tree-control' . ($PM === 'minus' ? ' list-tree-control-open' : ' list-tree-control-closed') . '" name="' . $goto . '" href="' . htmlspecialchars($aHref) . '"><i class="fa"></i></a>';
                }
                $label = $key;
                // Read only...
                if (($depth === 'types' || $depth === 'resources' || $depth === 'sitetitle') && $this->bType === 'setup') {
                    $label = '<span style="color: #666666;">' . $label . '</span>';
                } else {
                    if ($this->linkObjects) {
                        $urlParameters = [
                            'id' => $GLOBALS['SOBE']->id,
                            'sObj' => $depth
                        ];
                        if (GeneralUtility::_GP('breakPointLN')) {
                            $urlParameters['breakPointLN'] = GeneralUtility::_GP('breakPointLN');
                        }
                        $aHref = BackendUtility::getModuleUrl('web_ts', $urlParameters);
                        if ($this->bType !== 'const') {
                            $ln = is_array($arr[$key . '.ln..']) ? 'Defined in: ' . $this->lineNumberToScript($arr[$key . '.ln..']) : 'N/A';
                        } else {
                            $ln = '';
                        }
                        if ($this->tsbrowser_searchKeys[$depth] & 4) {
                            $label = '<strong class="text-danger">' . $label . '</strong>';
                        }
                        // The key has matched the search string
                        $label = '<a href="' . htmlspecialchars($aHref) . '" title="' . htmlspecialchars($ln) . '">' . $label . '</a>';
                    }
                }
                $HTML .= '
					<span class="list-tree-group">
						<span class="list-tree-label">[' . $label . ']</span>';
                if (isset($arr[$key])) {
                    $theValue = $arr[$key];
                    if ($this->fixedLgd) {
                        $imgBlocks = ceil(1 + strlen($depthData) / 77);
                        $lgdChars = 68 - ceil(strlen(('[' . $key . ']')) * 0.8) - $imgBlocks * 3;
                        $theValue = $this->ext_fixed_lgd($theValue, $lgdChars);
                    }
                    // The value has matched the search string
                    if ($this->tsbrowser_searchKeys[$depth] & 2) {
                        $HTML .= ' = <span class="list-tree-value text-danger">' . htmlspecialchars($theValue) . '</span>';
                    } else {
                        $HTML .= ' = <span class="list-tree-value">' . htmlspecialchars($theValue) . '</span>';
                    }
                    if ($this->ext_regComments && isset($arr[$key . '..'])) {
                        $comment = $arr[$key . '..'];
                        // Skip INCLUDE_TYPOSCRIPT comments, they are almost useless
                        if (!preg_match('/### <INCLUDE_TYPOSCRIPT:.*/', $comment)) {
                            // Remove linebreaks, replace with ' '
                            $comment = preg_replace('/[\\r\\n]/', ' ', $comment);
                            // Remove # and * if more than twice in a row
                            $comment = preg_replace('/[#\\*]{2,}/', '', $comment);
                            // Replace leading # (just if it exists) and add it again. Result: Every comment should be prefixed by a '#'.
                            $comment = preg_replace('/^[#\\*\\s]+/', '# ', $comment);
                            // Masking HTML Tags: Replace < with &lt; and > with &gt;
                            $comment = htmlspecialchars($comment);
                            $HTML .= ' <i class="text-muted">' . trim($comment) . '</i>';
                        }
                    }
                }
                $HTML .= '</span>';
                if ($deeper) {
                    $HTML .= $this->ext_getObjTree($arr[$key . '.'], $depth, $depthData, '', $arr[$key], $alphaSort);
                }
            }
        }
        if ($HTML !== '') {
            $HTML = '<ul class="list-tree text-monospace">' . $HTML . '</ul>';
        }

        return $HTML;
    }

    /**
     * Find the originating template name for an array of line numbers (TypoScript setup only!)
     * Given an array of linenumbers the method will try to find the corresponding template where this line originated
     * The linenumber indicates the *last* lineNumber that is part of the template
     *
     * lineNumbers are in sync with the calculated lineNumbers '.ln..' in TypoScriptParser
     *
     * @param array $lnArr Array with linenumbers (might have some extra symbols, for example for unsetting) to be processed
     * @return array The same array where each entry has been prepended by the template title if available
     */
    public function lineNumberToScript(array $lnArr)
    {
        // On the first call, construct the lnToScript array.
        if (!is_array($this->lnToScript)) {
            $this->lnToScript = [];

            // aggregatedTotalLineCount
            $c = 0;
            foreach ($this->hierarchyInfo as $templateNumber => $info) {
                // hierarchyInfo has the number of lines in configLines, but unfortunately this value
                // was calculated *before* processing of any INCLUDE instructions
                // for some yet unknown reason we have to add an extra +2 offset
                $linecountAfterIncludeProcessing = substr_count($this->config[$templateNumber], LF) + 2;
                $c += $linecountAfterIncludeProcessing;
                $this->lnToScript[$c] = $info['title'];
            }
        }

        foreach ($lnArr as $k => $ln) {
            foreach ($this->lnToScript as $endLn => $title) {
                if ($endLn >= (int)$ln) {
                    $lnArr[$k] = '"' . $title . '", ' . $ln;
                    break;
                }
            }
        }

        return implode('; ', $lnArr);
    }

    /**
     * @param array $arr
     * @param string $depth_in
     * @param string $searchString
     * @param array $keyArray
     * @return array
     * @throws Exception
     */
    public function ext_getSearchKeys($arr, $depth_in, $searchString, $keyArray)
    {
        $keyArr = [];
        foreach ($arr as $key => $value) {
            $key = preg_replace('/\\.$/', '', $key);
            if (substr($key, -1) !== '.') {
                $keyArr[$key] = 1;
            }
        }
        if ($depth_in) {
            $depth_in = $depth_in . '.';
        }
        $searchPattern = '';
        if ($this->regexMode) {
            $searchPattern = '/' . addcslashes($searchString, '/') . '/';
            $matchResult = @preg_match($searchPattern, '');
            if ($matchResult === false) {
                throw new Exception(sprintf('Error evaluating regular expression "%s".', $searchPattern), 1446559458);
            }
        }
        foreach ($keyArr as $key => $value) {
            $depth = $depth_in . $key;
            $deeper = is_array($arr[$key . '.']);
            if ($this->regexMode) {
                // The value has matched
                if (preg_match($searchPattern, $arr[$key])) {
                    $this->tsbrowser_searchKeys[$depth] += 2;
                }
                // The key has matched
                if (preg_match($searchPattern, $key)) {
                    $this->tsbrowser_searchKeys[$depth] += 4;
                }
                // Just open this subtree if the parent key has matched the search
                if (preg_match($searchPattern, $depth_in)) {
                    $this->tsbrowser_searchKeys[$depth] = 1;
                }
            } else {
                // The value has matched
                if (stristr($arr[$key], $searchString)) {
                    $this->tsbrowser_searchKeys[$depth] += 2;
                }
                // The key has matches
                if (stristr($key, $searchString)) {
                    $this->tsbrowser_searchKeys[$depth] += 4;
                }
                // Just open this subtree if the parent key has matched the search
                if (stristr($depth_in, $searchString)) {
                    $this->tsbrowser_searchKeys[$depth] = 1;
                }
            }
            if ($deeper) {
                $cS = count($this->tsbrowser_searchKeys);
                $keyArray = $this->ext_getSearchKeys($arr[$key . '.'], $depth, $searchString, $keyArray);
                if ($cS != count($this->tsbrowser_searchKeys)) {
                    $keyArray[$depth] = 1;
                }
            }
        }
        return $keyArray;
    }

    /**
     * @param int $pid
     * @return int
     */
    public function ext_getRootlineNumber($pid)
    {
        if ($pid) {
            foreach ($this->getRootLine() as $key => $val) {
                if ((int)$val['uid'] === (int)$pid) {
                    return (int)$key;
                }
            }
        }
        return -1;
    }

    /**
     * @param array $arr
     * @param string $depthData
     * @param array $keyArray
     * @param int $first
     * @return array
     */
    public function ext_getTemplateHierarchyArr($arr, $depthData, $keyArray, $first = 0)
    {
        $keyArr = [];
        foreach ($arr as $key => $value) {
            $key = preg_replace('/\\.$/', '', $key);
            if (substr($key, -1) !== '.') {
                $keyArr[$key] = 1;
            }
        }
        $a = 0;
        $c = count($keyArr);
        /** @var IconFactory $iconFactory */
        $iconFactory = GeneralUtility::makeInstance(IconFactory::class);
        foreach ($keyArr as $key => $value) {
            $HTML = '';
            $a++;
            $deeper = is_array($arr[$key . '.']);
            $row = $arr[$key];
            $LN = $a == $c ? 'blank' : 'line';
            $BTM = $a == $c ? 'top' : '';
            $HTML .= $depthData;
            $alttext = '[' . $row['templateID'] . ']';
            $alttext .= $row['pid'] ? ' - ' . BackendUtility::getRecordPath($row['pid'], $GLOBALS['SOBE']->perms_clause, 20) : '';
            $icon = substr($row['templateID'], 0, 3) === 'sys'
                ? '<span title="' . htmlspecialchars($alttext) . '">' . $iconFactory->getIconForRecord('sys_template', $row, Icon::SIZE_SMALL)->render() . '</span>'
                : '<span title="' . htmlspecialchars($alttext) . '">' . $iconFactory->getIcon('mimetypes-x-content-template-static', Icon::SIZE_SMALL)->render() . '</span>';
            if (in_array($row['templateID'], $this->clearList_const) || in_array($row['templateID'], $this->clearList_setup)) {
                $urlParameters = [
                    'id' => $GLOBALS['SOBE']->id,
                    'template' => $row['templateID']
                ];
                $aHref = BackendUtility::getModuleUrl('web_ts', $urlParameters);
                $A_B = '<a href="' . htmlspecialchars($aHref) . '">';
                $A_E = '</a>';
                if (GeneralUtility::_GP('template') == $row['templateID']) {
                    $A_B = '<strong>' . $A_B;
                    $A_E .= '</strong>';
                }
            } else {
                $A_B = '';
                $A_E = '';
            }
            $HTML .= ($first ? '' : '<span class="treeline-icon treeline-icon-join' . $BTM . '"></span>') . $icon . ' ' . $A_B
                . htmlspecialchars(GeneralUtility::fixed_lgd_cs($row['title'], $GLOBALS['BE_USER']->uc['titleLen']))
                . $A_E . '&nbsp;&nbsp;';
            $RL = $this->ext_getRootlineNumber($row['pid']);
            $statusCheckedIcon = $iconFactory->getIcon('status-status-checked', Icon::SIZE_SMALL)->render();
            $keyArray[] = '<tr>
							<td class="nowrap">' . $HTML . '</td>
							<td align="center">' . ($row['root'] ? $statusCheckedIcon : '') . '</td>
							<td align="center">' . ($row['clConf'] ? $statusCheckedIcon : '') . '</td>
							<td align="center">' . ($row['clConst'] ? $statusCheckedIcon : '') . '</td>
							<td align="center">' . ($row['pid'] ?: '') . '</td>
							<td align="center">' . ($RL >= 0 ? $RL : '') . '</td>
							<td>' . ($row['next'] ? $row['next'] : '') . '</td>
						</tr>';
            if ($deeper) {
                $keyArray = $this->ext_getTemplateHierarchyArr($arr[$key . '.'], $depthData . ($first ? '' : '<span class="treeline-icon treeline-icon-' . $LN . '"></span>'), $keyArray);
            }
        }
        return $keyArray;
    }

    /**
     * Processes the flat array from TemplateService->hierarchyInfo
     * and turns it into a hierarchical array to show dependencies (used by TemplateAnalyzer)
     *
     * @param array $depthDataArr (empty array on external call)
     * @param int &$pointer Element number (1! to count()) of $this->hierarchyInfo that should be processed.
     * @return array Processed hierachyInfo.
     */
    public function ext_process_hierarchyInfo(array $depthDataArr, &$pointer)
    {
        $parent = $this->hierarchyInfo[$pointer - 1]['templateParent'];
        while ($pointer > 0 && $this->hierarchyInfo[$pointer - 1]['templateParent'] == $parent) {
            $pointer--;
            $row = $this->hierarchyInfo[$pointer];
            $depthDataArr[$row['templateID']] = $row;
            unset($this->clearList_setup_temp[$row['templateID']]);
            unset($this->clearList_const_temp[$row['templateID']]);
            $this->templateTitles[$row['templateID']] = $row['title'];
            if ($row['templateID'] == $this->hierarchyInfo[$pointer - 1]['templateParent']) {
                $depthDataArr[$row['templateID'] . '.'] = $this->ext_process_hierarchyInfo([], $pointer);
            }
        }
        return $depthDataArr;
    }

    /**
     * Get formatted HTML output for TypoScript either with Syntaxhiglighting or in plain mode
     *
     * @param array $config Array with simple strings of typoscript code.
     * @param bool $lineNumbers Prepend linNumbers to each line.
     * @param bool $comments Enable including comments in output.
     * @param bool $crop Enable cropping of long lines.
     * @param bool $syntaxHL Enrich output with syntaxhighlighting.
     * @param int $syntaxHLBlockmode
     * @return string
     */
    public function ext_outputTS(
        array $config,
        $lineNumbers = false,
        $comments = false,
        $crop = false,
        $syntaxHL = false,
        $syntaxHLBlockmode = 0
    ) {
        $all = '';
        foreach ($config as $str) {
            $all .= '[GLOBAL]' . LF . $str;
        }
        if ($syntaxHL) {
            $tsparser = GeneralUtility::makeInstance(Parser\TypoScriptParser::class);
            $tsparser->lineNumberOffset = $this->ext_lineNumberOffset + 1;
            $tsparser->parentObject = $this;
            return $tsparser->doSyntaxHighlight($all, $lineNumbers ? [$this->ext_lineNumberOffset + 1] : '', $syntaxHLBlockmode);
        }
        return $this->ext_formatTS($all, $lineNumbers, $comments, $crop);
    }

    /**
     * Returns a new string of max. $chars length
     * If the string is longer, it will be truncated and prepended with '...'
     * $chars must be an integer of at least 4
     *
     * @param string $string
     * @param int $chars
     * @return string
     */
    public function ext_fixed_lgd($string, $chars)
    {
        if ($chars >= 4) {
            if (strlen($string) > $chars) {
                if (strlen($string) > 24 && preg_match('/^##[a-z0-9]{6}_B##$/', substr($string, 0, 12))) {
                    $string = GeneralUtility::fixed_lgd_cs(substr($string, 12, -12), ($chars - 3));
                    $marker = substr(md5($string), 0, 6);
                    return '##' . $marker . '_B##' . $string . '##' . $marker . '_E##';
                }
                return GeneralUtility::fixed_lgd_cs($string, $chars - 3);
            }
        }
        return $string;
    }

    /**
     * @param int $lineNumber Line Number
     * @param array $str
     * @return string
     */
    public function ext_lnBreakPointWrap($lineNumber, $str)
    {
        return '<a href="#" id="line-' . $lineNumber . '" onClick="return brPoint(' . $lineNumber . ','
            . ($this->ext_lineNumberOffset_mode === 'setup' ? 1 : 0) . ');">' . $str . '</a>';
    }

    /**
     * @param string $input
     * @param bool $ln
     * @param bool $comments
     * @param bool $crop
     * @return string
     */
    public function ext_formatTS($input, $ln, $comments = true, $crop = false)
    {
        $cArr = explode(LF, $input);
        $n = ceil(log10(count($cArr) + $this->ext_lineNumberOffset));
        $lineNum = '';
        foreach ($cArr as $k => $v) {
            $lln = $k + $this->ext_lineNumberOffset + 1;
            if ($ln) {
                $lineNum = $this->ext_lnBreakPointWrap($lln, str_replace(' ', '&nbsp;', sprintf(('% ' . $n . 'd'), $lln))) . ':   ';
            }
            $v = htmlspecialchars($v);
            if ($crop) {
                $v = $this->ext_fixed_lgd($v, $ln ? 71 : 77);
            }
            $cArr[$k] = $lineNum . str_replace(' ', '&nbsp;', $v);
            $firstChar = substr(trim($v), 0, 1);
            if ($firstChar === '[') {
                $cArr[$k] = '<strong class="text-success">' . $cArr[$k] . '</strong>';
            } elseif ($firstChar === '/' || $firstChar === '#') {
                if ($comments) {
                    $cArr[$k] = '<span class="text-muted">' . $cArr[$k] . '</span>';
                } else {
                    unset($cArr[$k]);
                }
            }
        }
        $output = implode($cArr, '<br />') . '<br />';
        return $output;
    }

    /**
     * Get a single sys_template record attached to a single page.
     * If multiple template records are on this page, the first (order by sorting)
     * record will be returned, unless a specific template uid is specified via $templateUid
     *
     * @param int $pid The pid to select sys_template records from
     * @param int $templateUid Optional template uid
     * @return array|null Returns the template record or null if none was found
     */
    public function ext_getFirstTemplate($pid, $templateUid = 0)
    {
        if (empty($pid)) {
            return null;
        }

        // Query is taken from the runThroughTemplates($theRootLine) function in the parent class.
        $queryBuilder = $this->getTemplateQueryBuilder($pid)
            ->setMaxResults(1);
        if ($templateUid) {
            $queryBuilder->andWhere(
                $queryBuilder->expr()->eq('uid', $queryBuilder->createNamedParameter($templateUid, \PDO::PARAM_INT))
            );
        }
        $row = $queryBuilder->execute()->fetch();
        BackendUtility::workspaceOL('sys_template', $row);

        return $row;
    }

    /**
     * Get an array of all template records on a page.
     *
     * @param int $pid Pid to fetch sys_template records for
     * @return array[] Array of template records
     */
    public function ext_getAllTemplates($pid): array
    {
        if (empty($pid)) {
            return [];
        }
        $result = $this->getTemplateQueryBuilder($pid)->execute();
        $outRes = [];
        while ($row = $result->fetch()) {
            BackendUtility::workspaceOL('sys_template', $row);
            if (is_array($row)) {
                $outRes[] = $row;
            }
        }
        return $outRes;
    }

    /**
     * Internal helper method to prepare the query builder for
     * getting sys_template records from a given pid
     *
     * @param int $pid The pid to select sys_template records from
     * @return QueryBuilder Returns a QueryBuilder
     */
    protected function getTemplateQueryBuilder(int $pid): QueryBuilder
    {
        $queryBuilder = GeneralUtility::makeInstance(ConnectionPool::class)
            ->getQueryBuilderForTable('sys_template');
        $queryBuilder->getRestrictions()
            ->removeAll()
            ->add(GeneralUtility::makeInstance(DeletedRestriction::class));
        $queryBuilder->select('*')
            ->from('sys_template')
            ->where(
                $queryBuilder->expr()->eq('pid', $queryBuilder->createNamedParameter($pid, \PDO::PARAM_INT))
            );
        if (!empty($GLOBALS['TCA']['sys_template']['ctrl']['sortby'])) {
            $queryBuilder->orderBy($GLOBALS['TCA']['sys_template']['ctrl']['sortby']);
        }

        return $queryBuilder;
    }

    /**
     * This function compares the flattened constants (default and all).
     * Returns an array with the constants from the whole template which may be edited by the module.
     *
     * @param array $default
     * @return array
     */
    public function ext_compareFlatSetups($default)
    {
        $editableComments = [];
        $counter = 0;
        foreach ($this->flatSetup as $const => $value) {
            if (substr($const, -2) === '..' || !isset($this->flatSetup[$const . '..'])) {
                continue;
            }
            $counter++;
            $comment = trim($this->flatSetup[$const . '..']);
            $c_arr = explode(LF, $comment);
            foreach ($c_arr as $k => $v) {
                $line = trim(preg_replace('/^[#\\/]*/', '', $v));
                if (!$line) {
                    continue;
                }
                $parts = explode(';', $line);
                foreach ($parts as $par) {
                    if (strstr($par, '=')) {
                        $keyValPair = explode('=', $par, 2);
                        switch (trim(strtolower($keyValPair[0]))) {
                            case 'type':
                                // Type:
                                $editableComments[$const]['type'] = trim($keyValPair[1]);
                                break;
                            case 'cat':
                                // List of categories.
                                $catSplit = explode('/', strtolower($keyValPair[1]));
                                $catSplit[0] = trim($catSplit[0]);
                                if (isset($this->categoryLabels[$catSplit[0]])) {
                                    $catSplit[0] = $this->categoryLabels[$catSplit[0]];
                                }
                                $editableComments[$const]['cat'] = $catSplit[0];
                                // This is the subcategory. Must be a key in $this->subCategories[].
                                // catSplit[2] represents the search-order within the subcat.
                                $catSplit[1] = trim($catSplit[1]);
                                if ($catSplit[1] && isset($this->subCategories[$catSplit[1]])) {
                                    $editableComments[$const]['subcat_name'] = $catSplit[1];
                                    $orderIdentifier = isset($catSplit[2]) ? trim($catSplit[2]) : $counter;
                                    $editableComments[$const]['subcat'] = $this->subCategories[$catSplit[1]][1]
                                        . '/' . $catSplit[1] . '/' . $orderIdentifier . 'z';
                                } elseif (isset($catSplit[2])) {
                                    $editableComments[$const]['subcat'] = 'x' . '/' . trim($catSplit[2]) . 'z';
                                } else {
                                    $editableComments[$const]['subcat'] = 'x' . '/' . $counter . 'z';
                                }
                                break;
                            case 'label':
                                // Label
                                $editableComments[$const]['label'] = trim($keyValPair[1]);
                                break;
                            case 'customcategory':
                                // Custom category label
                                $customCategory = explode('=', $keyValPair[1], 2);
                                if (trim($customCategory[0])) {
                                    $categoryKey = strtolower($customCategory[0]);
                                    $this->categoryLabels[$categoryKey] = $this->getLanguageService()->sL($customCategory[1]);
                                }
                                break;
                            case 'customsubcategory':
                                // Custom subCategory label
                                $customSubcategory = explode('=', $keyValPair[1], 2);
                                if (trim($customSubcategory[0])) {
                                    $subCategoryKey = strtolower($customSubcategory[0]);
                                    $this->subCategories[$subCategoryKey][0] = $this->getLanguageService()->sL($customSubcategory[1]);
                                }
                                break;
                        }
                    }
                }
            }
            if (isset($editableComments[$const])) {
                $editableComments[$const]['name'] = $const;
                $editableComments[$const]['value'] = trim($value);
                if (isset($default[$const])) {
                    $editableComments[$const]['default_value'] = trim($default[$const]);
                }
            }
        }
        return $editableComments;
    }

    /**
     * @param array $editConstArray
     */
    public function ext_categorizeEditableConstants($editConstArray)
    {
        // Runs through the available constants and fills the $this->categories array with pointers and priority-info
        foreach ($editConstArray as $constName => $constData) {
            if (!$constData['type']) {
                $constData['type'] = 'string';
            }
            $cats = explode(',', $constData['cat']);
            // if = only one category, while allows for many. We have agreed on only one category is the most basic way...
            foreach ($cats as $theCat) {
                $theCat = trim($theCat);
                if ($theCat) {
                    $this->categories[$theCat][$constName] = $constData['subcat'];
                }
            }
        }
    }

    /**
     * @return array
     */
    public function ext_getCategoryLabelArray()
    {
        // Returns array used for labels in the menu.
        $retArr = [];
        foreach ($this->categories as $k => $v) {
            if (!empty($v)) {
                $retArr[$k] = strtoupper($k) . ' (' . count($v) . ')';
            }
        }
        return $retArr;
    }

    /**
     * @param string $type
     * @return array
     */
    public function ext_getTypeData($type)
    {
        $retArr = [];
        $type = trim($type);
        if (!$type) {
            $retArr['type'] = 'string';
        } else {
            $m = strcspn($type, ' [');
            $retArr['type'] = strtolower(substr($type, 0, $m));
            $types = ['int' => 1, 'options' => 1, 'file' => 1, 'boolean' => 1, 'offset' => 1, 'user' => 1];
            if (isset($types[$retArr['type']])) {
                $p = trim(substr($type, $m));
                $reg = [];
                preg_match('/\\[(.*)\\]/', $p, $reg);
                $p = trim($reg[1]);
                if ($p) {
                    $retArr['paramstr'] = $p;
                    switch ($retArr['type']) {
                        case 'int':
                            if ($retArr['paramstr'][0] === '-') {
                                $retArr['params'] = GeneralUtility::intExplode('-', substr($retArr['paramstr'], 1));
                                $retArr['params'][0] = (int)('-' . $retArr['params'][0]);
                            } else {
                                $retArr['params'] = GeneralUtility::intExplode('-', $retArr['paramstr']);
                            }
                            $retArr['min'] = $retArr['params'][0];
                            $retArr['max'] = $retArr['params'][1];
                            $retArr['paramstr'] = $retArr['params'][0] . ' - ' . $retArr['params'][1];
                            break;
                        case 'options':
                            $retArr['params'] = explode(',', $retArr['paramstr']);
                            break;
                    }
                }
            }
        }
        return $retArr;
    }

    /**
     * @param string $category
     */
    public function ext_getTSCE_config($category)
    {
        $catConf = $this->setup['constants']['TSConstantEditor.'][$category . '.'];
        $out = [];
        if (is_array($catConf)) {
            foreach ($catConf as $key => $val) {
                switch ($key) {
                    case 'description':
                    case 'bulletlist':
                    case 'header':
                        $out[$key] = $val;
                        break;
                    default:
                        if (MathUtility::canBeInterpretedAsInteger($key)) {
                            $constRefs = explode(',', $val);
                            foreach ($constRefs as $const) {
                                $const = trim($const);
                                if ($const) {
                                    $out['constants'][$const] .= '<span class="label label-danger">' . $key . '</span>';
                                }
                            }
                        }
                }
            }
        }
        $this->helpConfig = $out;
    }

    /**
     * @param array $params
     * @return array
     */
    public function ext_fNandV($params)
    {
        $fN = 'data[' . $params['name'] . ']';
        $idName = str_replace('.', '-', $params['name']);
        $fV = $params['value'];
        // Values entered from the constantsedit cannot be constants!	230502; removed \{ and set {
        if (preg_match('/^{[\\$][a-zA-Z0-9\\.]*}$/', trim($fV), $reg)) {
            $fV = '';
        }
        $fV = htmlspecialchars($fV);
        return [$fN, $fV, $params, $idName];
    }

    /**
     * This functions returns the HTML-code that creates the editor-layout of the module.
     *
     * @param array $theConstants
     * @param string $category
     * @return string
     */
    public function ext_printFields($theConstants, $category)
    {
        reset($theConstants);
        $output = '';
        $subcat = '';
        if (is_array($this->categories[$category])) {
            $help = $this->helpConfig;
            if (!$this->doNotSortCategoriesBeforeMakingForm) {
                asort($this->categories[$category]);
            }
            /** @var IconFactory $iconFactory */
            $iconFactory = GeneralUtility::makeInstance(IconFactory::class);
            foreach ($this->categories[$category] as $name => $type) {
                $params = $theConstants[$name];
                if (is_array($params)) {
                    if ($subcat != $params['subcat_name']) {
                        $subcat = $params['subcat_name'];
                        $subcat_name = $params['subcat_name'] ? $this->subCategories[$params['subcat_name']][0] : 'Others';
                        $output .= '<h3>' . $subcat_name . '</h3>';
                    }
                    $label = $this->getLanguageService()->sL($params['label']);
                    $label_parts = explode(':', $label, 2);
                    if (count($label_parts) === 2) {
                        $head = trim($label_parts[0]);
                        $body = trim($label_parts[1]);
                    } else {
                        $head = trim($label_parts[0]);
                        $body = '';
                    }
                    $typeDat = $this->ext_getTypeData($params['type']);
                    $p_field = '';
                    $raname = substr(md5($params['name']), 0, 10);
                    $aname = '\'' . $raname . '\'';
                    list($fN, $fV, $params, $idName) = $this->ext_fNandV($params);
                    $idName = htmlspecialchars($idName);
                    $hint = '';
                    switch ($typeDat['type']) {
                        case 'int':
                        case 'int+':
                            $additionalAttributes = '';
                            if ($typeDat['paramstr']) {
                                $hint = ' Range: ' . $typeDat['paramstr'];
                            } elseif ($typeDat['type'] === 'int+') {
                                $hint = ' Range: 0 - ';
                                $typeDat['min'] = 0;
                            } else {
                                $hint = ' (Integer)';
                            }

                            if (isset($typeDat['min'])) {
                                $additionalAttributes .= ' min="' . (int)$typeDat['min'] . '" ';
                            }
                            if (isset($typeDat['max'])) {
                                $additionalAttributes .= ' max="' . (int)$typeDat['max'] . '" ';
                            }

                            $p_field =
                                '<input class="form-control" id="' . $idName . '" type="number"'
                                . ' name="' . $fN . '" value="' . $fV . '"' . ' onChange="uFormUrl(' . $aname . ')"' . $additionalAttributes . ' />';
                            break;
                        case 'color':
                            $p_field = '
                                <input class="form-control formengine-colorpickerelement t3js-color-picker" type="text" id="input-' . $idName . '" rel="' . $idName .
                                '" name="' . $fN . '" value="' . $fV . '"' . $this->getDocumentTemplate()->formWidth(7) . ' onChange="uFormUrl(' . $aname . ')" />';

                            if (empty($this->inlineJavaScript[$typeDat['type']])) {
                                $this->inlineJavaScript[$typeDat['type']] = 'require([\'TYPO3/CMS/Backend/ColorPicker\'], function(ColorPicker){ColorPicker.initialize()});';
                            }
                            break;
                        case 'wrap':
                            $wArr = explode('|', $fV);
                            $p_field = '<input class="form-control" type="text" id="' . $idName . '" name="' . $fN . '" value="' . $wArr[0] . '"' . $this->getDocumentTemplate()->formWidth(29) . ' onChange="uFormUrl(' . $aname . ')" />';
                            $p_field .= ' | ';
                            $p_field .= '<input class="form-control" type="text" name="W' . $fN . '" value="' . $wArr[1] . '"' . $this->getDocumentTemplate()->formWidth(15) . ' onChange="uFormUrl(' . $aname . ')" />';
                            break;
                        case 'offset':
                            $wArr = explode(',', $fV);
                            $labels = GeneralUtility::trimExplode(',', $typeDat['paramstr']);
                            $p_field = ($labels[0] ? $labels[0] : 'x') . ':<input type="text" name="' . $fN . '" value="' . $wArr[0] . '"' . $this->getDocumentTemplate()->formWidth(4) . ' onChange="uFormUrl(' . $aname . ')" />';
                            $p_field .= ' , ';
                            $p_field .= ($labels[1] ? $labels[1] : 'y') . ':<input type="text" name="W' . $fN . '" value="' . $wArr[1] . '"' . $this->getDocumentTemplate()->formWidth(4) . ' onChange="uFormUrl(' . $aname . ')" />';
                            $labelsCount = count($labels);
                            for ($aa = 2; $aa < $labelsCount; $aa++) {
                                if ($labels[$aa]) {
                                    $p_field .= ' , ' . $labels[$aa] . ':<input type="text" name="W' . $aa . $fN . '" value="' . $wArr[$aa] . '"' . $this->getDocumentTemplate()->formWidth(4) . ' onChange="uFormUrl(' . $aname . ')" />';
                                } else {
                                    $p_field .= '<input type="hidden" name="W' . $aa . $fN . '" value="' . $wArr[$aa] . '" />';
                                }
                            }
                            break;
                        case 'options':
                            if (is_array($typeDat['params'])) {
                                $p_field = '';
                                foreach ($typeDat['params'] as $val) {
                                    $vParts = explode('=', $val, 2);
                                    $label = $vParts[0];
                                    $val = isset($vParts[1]) ? $vParts[1] : $vParts[0];
                                    // option tag:
                                    $sel = '';
                                    if ($val === $params['value']) {
                                        $sel = ' selected';
                                    }
                                    $p_field .= '<option value="' . htmlspecialchars($val) . '"' . $sel . '>' . $this->getLanguageService()->sL($label) . '</option>';
                                }
                                $p_field = '<select class="form-control" id="' . $idName . '" name="' . $fN . '" onChange="uFormUrl(' . $aname . ')">' . $p_field . '</select>';
                            }
                            break;
                        case 'boolean':
                            $sel = $fV ? 'checked' : '';
                            $p_field =
                                '<input type="hidden" name="' . $fN . '" value="0" />'
                                . '<label class="btn btn-default btn-checkbox">'
                                . '<input id="' . $idName . '" type="checkbox" name="' . $fN . '" value="' . ($typeDat['paramstr'] ? $typeDat['paramstr'] : 1) . '" ' . $sel . ' onClick="uFormUrl(' . $aname . ')" />'
                                . '<span class="t3-icon fa"></span>'
                                . '</label>';
                            break;
                        case 'comment':
                            $sel = $fV ? 'checked' : '';
                            $p_field =
                                '<input type="hidden" name="' . $fN . '" value="#" />'
                                . '<label class="btn btn-default btn-checkbox">'
                                . '<input id="' . $idName . '" type="checkbox" name="' . $fN . '" value="" ' . $sel . ' onClick="uFormUrl(' . $aname . ')" />'
                                . '<span class="t3-icon fa"></span>'
                                . '</label>';
                            break;
                        case 'file':
                            // extensionlist
                            $extList = $typeDat['paramstr'];
                            if ($extList === 'IMAGE_EXT') {
                                $extList = $GLOBALS['TYPO3_CONF_VARS']['GFX']['imagefile_ext'];
                            }
                            $p_field = '<option value="">(' . $extList . ')</option>';
                            if (trim($params['value'])) {
                                $val = $params['value'];
                                $p_field .= '<option value=""></option>';
                                $p_field .= '<option value="' . htmlspecialchars($val) . '" selected>' . $val . '</option>';
                            }
                            $p_field = '<select class="form-select" id="' . $idName . '" name="' . $fN . '" onChange="uFormUrl(' . $aname . ')">' . $p_field . '</select>';
                            break;
                        case 'user':
                            $userFunction = $typeDat['paramstr'];
                            $userFunctionParams = ['fieldName' => $fN, 'fieldValue' => $fV];
                            $p_field = GeneralUtility::callUserFunction($userFunction, $userFunctionParams, $this);
                            break;
                        default:
                            $p_field = '<input class="form-control" id="' . $idName . '" type="text" name="' . $fN . '" value="' . $fV . '"'
                                . ' onChange="uFormUrl(' . $aname . ')" />';
                    }
                    // Define default names and IDs
                    $userTyposcriptID = 'userTS-' . $idName;
                    $defaultTyposcriptID = 'defaultTS-' . $idName;
                    $checkboxName = 'check[' . $params['name'] . ']';
                    $checkboxID = 'check-' . $idName;
                    $userTyposcriptStyle = '';
                    $deleteIconHTML = '';
                    $constantCheckbox = '';
                    $constantDefaultRow = '';
                    if (!$this->ext_dontCheckIssetValues) {
                        // Set the default styling options
                        if (isset($this->objReg[$params['name']])) {
                            $checkboxValue = 'checked';
                            $defaultTyposcriptStyle = 'style="display:none;"';
                        } else {
                            $checkboxValue = '';
                            $userTyposcriptStyle = 'style="display:none;"';
                            $defaultTyposcriptStyle = '';
                        }
                        $deleteTitle = htmlspecialchars($this->getLanguageService()->sL('LLL:EXT:lang/Resources/Private/Language/locallang_core.xlf:labels.deleteTitle'));
                        $deleteIcon = $iconFactory->getIcon('actions-edit-undo', Icon::SIZE_SMALL)->render();
                        $deleteIconHTML =
                            '<button type="button" class="btn btn-default t3js-toggle" data-toggle="undo" rel="' . $idName . '">'
                                . '<span title="' . $deleteTitle . '" alt="' . $deleteTitle . '">'
                                    . $deleteIcon
                                . '</span>'
                            . '</button>';
                        $editTitle = htmlspecialchars($this->getLanguageService()->sL('LLL:EXT:lang/Resources/Private/Language/locallang_core.xlf:labels.editTitle'));
                        $editIcon = $iconFactory->getIcon('actions-open', Icon::SIZE_SMALL)->render();
                        $editIconHTML =
                            '<button type="button" class="btn btn-default t3js-toggle" data-toggle="edit"  rel="' . $idName . '">'
                                . '<span title="' . $editTitle . '" alt="' . $editTitle . '">'
                                    . $editIcon
                                . '</span>'
                            . '</button>';
                        $constantCheckbox = '<input type="hidden" name="' . $checkboxName . '" id="' . $checkboxID . '" value="' . $checkboxValue . '"/>';
                        // If there's no default value for the field, use a static label.
                        if (!$params['default_value']) {
                            $params['default_value'] = '[Empty]';
                        }
                        $constantDefaultRow =
                            '<div class="input-group defaultTS" id="' . $defaultTyposcriptID . '" ' . $defaultTyposcriptStyle . '>'
                                . '<span class="input-group-btn">' . $editIconHTML . '</span>'
                                . '<input class="form-control" type="text" placeholder="' . htmlspecialchars($params['default_value']) . '" readonly>'
                            . '</div>';
                    }
                    $constantEditRow =
                        '<div class="input-group userTS" id="' . $userTyposcriptID . '" ' . $userTyposcriptStyle . '>'
                            . '<span class="input-group-btn">' . $deleteIconHTML . '</span>'
                            . $p_field
                        . '</div>';
                    $constantLabel = '<label class="t3js-formengine-label"><span>' . htmlspecialchars($head) . '</span></label>';
                    $constantName = '<span class="help-block">[' . $params['name'] . ']</span>';
                    $constantDescription = $body ? '<p class="help-block">' . htmlspecialchars($body) . '</p>' : '';
                    $constantData = '';
                    if ($hint !== '') {
                        $constantData .= '<span class="help-block">' . $hint . '</span>';
                    }
                    $constantData .=
                        $constantCheckbox
                        . $constantEditRow
                        . $constantDefaultRow;

                    $output .=
                        '<fieldset class="form-section">'
                            . '<a name="' . $raname . '"></a>' . $help['constants'][$params['name']]
                            . '<div class="form-group">'
                                . $constantLabel . $constantName . $constantDescription . $constantData
                            . '</div>'
                        . '</fieldset>';
                } else {
                    debug('Error. Constant did not exist. Should not happen.');
                }
            }
        }
        return '<div class="tstemplate-constanteditor">' . $output . '</div>';
    }

    /***************************
     *
     * Processing input values
     *
     ***************************/
    /**
     * @param string $constants
     */
    public function ext_regObjectPositions($constants)
    {
        // This runs through the lines of the constants-field of the active template and registers the constants-names
        // and line positions in an array, $this->objReg
        $this->raw = explode(LF, $constants);
        $this->rawP = 0;
        // Resetting the objReg if the divider is found!!
        $this->objReg = [];
        $this->ext_regObjects('');
    }

    /**
     * @param string $pre
     */
    public function ext_regObjects($pre)
    {
        // Works with regObjectPositions. "expands" the names of the TypoScript objects
        while (isset($this->raw[$this->rawP])) {
            $line = ltrim($this->raw[$this->rawP]);
            if (strstr($line, $this->edit_divider)) {
                // Resetting the objReg if the divider is found!!
                $this->objReg = [];
            }
            $this->rawP++;
            if ($line) {
                if ($line[0] === '[') {
                } elseif (strcspn($line, '}#/') != 0) {
                    $varL = strcspn($line, ' {=<');
                    $var = substr($line, 0, $varL);
                    $line = ltrim(substr($line, $varL));
                    switch ($line[0]) {
                        case '=':
                            $this->objReg[$pre . $var] = $this->rawP - 1;
                            break;
                        case '{':
                            $this->ext_inBrace++;
                            $this->ext_regObjects($pre . $var . '.');
                            break;
                    }
                    $this->lastComment = '';
                } elseif ($line[0] === '}') {
                    $this->lastComment = '';
                    $this->ext_inBrace--;
                    if ($this->ext_inBrace < 0) {
                        $this->ext_inBrace = 0;
                    } else {
                        break;
                    }
                }
            }
        }
    }

    /**
     * @param string $key
     * @param string $var
     */
    public function ext_putValueInConf($key, $var)
    {
        // Puts the value $var to the TypoScript value $key in the current lines of the templates.
        // If the $key is not found in the template constants field, a new line is inserted in the bottom.
        $theValue = ' ' . trim($var);
        if (isset($this->objReg[$key])) {
            $lineNum = $this->objReg[$key];
            $parts = explode('=', $this->raw[$lineNum], 2);
            if (count($parts) === 2) {
                $parts[1] = $theValue;
            }
            $this->raw[$lineNum] = implode($parts, '=');
        } else {
            $this->raw[] = $key . ' =' . $theValue;
        }
        $this->changed = true;
    }

    /**
     * @param string $key
     */
    public function ext_removeValueInConf($key)
    {
        // Removes the value in the configuration
        if (isset($this->objReg[$key])) {
            $lineNum = $this->objReg[$key];
            unset($this->raw[$lineNum]);
        }
        $this->changed = true;
    }

    /**
     * @param array $arr
     * @param array $settings
     * @return array
     */
    public function ext_depthKeys($arr, $settings)
    {
        $tsbrArray = [];
        foreach ($arr as $theK => $theV) {
            $theKeyParts = explode('.', $theK);
            $depth = '';
            $c = count($theKeyParts);
            $a = 0;
            foreach ($theKeyParts as $p) {
                $a++;
                $depth .= ($depth ? '.' : '') . $p;
                $tsbrArray[$depth] = $c == $a ? $theV : 1;
            }
        }
        // Modify settings
        foreach ($tsbrArray as $theK => $theV) {
            if ($theV) {
                $settings[$theK] = 1;
            } else {
                unset($settings[$theK]);
            }
        }
        return $settings;
    }

    /**
     * Process input
     *
     * @param array $http_post_vars
     * @param array $http_post_files (not used anymore)
     * @param array $theConstants
     * @param array $tplRow Not used
     */
    public function ext_procesInput($http_post_vars, $http_post_files, $theConstants, $tplRow)
    {
        $data = $http_post_vars['data'];
        $check = $http_post_vars['check'];
        $Wdata = $http_post_vars['Wdata'];
        $W2data = $http_post_vars['W2data'];
        $W3data = $http_post_vars['W3data'];
        $W4data = $http_post_vars['W4data'];
        $W5data = $http_post_vars['W5data'];
        if (is_array($data)) {
            foreach ($data as $key => $var) {
                if (isset($theConstants[$key])) {
                    // If checkbox is set, update the value
                    if ($this->ext_dontCheckIssetValues || isset($check[$key])) {
                        // Exploding with linebreak, just to make sure that no multiline input is given!
                        list($var) = explode(LF, $var);
                        $typeDat = $this->ext_getTypeData($theConstants[$key]['type']);
                        switch ($typeDat['type']) {
                            case 'int':
                                if ($typeDat['paramstr']) {
                                    $var = MathUtility::forceIntegerInRange($var, $typeDat['params'][0], $typeDat['params'][1]);
                                } else {
                                    $var = (int)$var;
                                }
                                break;
                            case 'int+':
                                $var = max(0, (int)$var);
                                break;
                            case 'color':
                                $col = [];
                                if ($var) {
                                    $var = preg_replace('/[^A-Fa-f0-9]*/', '', $var);
                                    $useFulHex = strlen($var) > 3;
                                    $col[] = hexdec($var[0]);
                                    $col[] = hexdec($var[1]);
                                    $col[] = hexdec($var[2]);
                                    if ($useFulHex) {
                                        $col[] = hexdec($var[3]);
                                        $col[] = hexdec($var[4]);
                                        $col[] = hexdec($var[5]);
                                    }
                                    $var = substr(('0' . dechex($col[0])), -1) . substr(('0' . dechex($col[1])), -1) . substr(('0' . dechex($col[2])), -1);
                                    if ($useFulHex) {
                                        $var .= substr(('0' . dechex($col[3])), -1) . substr(('0' . dechex($col[4])), -1) . substr(('0' . dechex($col[5])), -1);
                                    }
                                    $var = '#' . strtoupper($var);
                                }
                                break;
                            case 'comment':
                                if ($var) {
                                    $var = '#';
                                } else {
                                    $var = '';
                                }
                                break;
                            case 'wrap':
                                if (isset($Wdata[$key])) {
                                    $var .= '|' . $Wdata[$key];
                                }
                                break;
                            case 'offset':
                                if (isset($Wdata[$key])) {
                                    $var = (int)$var . ',' . (int)$Wdata[$key];
                                    if (isset($W2data[$key])) {
                                        $var .= ',' . (int)$W2data[$key];
                                        if (isset($W3data[$key])) {
                                            $var .= ',' . (int)$W3data[$key];
                                            if (isset($W4data[$key])) {
                                                $var .= ',' . (int)$W4data[$key];
                                                if (isset($W5data[$key])) {
                                                    $var .= ',' . (int)$W5data[$key];
                                                }
                                            }
                                        }
                                    }
                                }
                                break;
                            case 'boolean':
                                if ($var) {
                                    $var = $typeDat['paramstr'] ? $typeDat['paramstr'] : 1;
                                }
                                break;
                        }
                        if ($this->ext_printAll || (string)$theConstants[$key]['value'] !== (string)$var) {
                            // Put value in, if changed.
                            $this->ext_putValueInConf($key, $var);
                        }
                        // Remove the entry because it has been "used"
                        unset($check[$key]);
                    } else {
                        $this->ext_removeValueInConf($key);
                    }
                }
            }
        }
        // Remaining keys in $check indicates fields that are just clicked "on" to be edited.
        // Therefore we get the default value and puts that in the template as a start...
        if (!$this->ext_dontCheckIssetValues && is_array($check)) {
            foreach ($check as $key => $var) {
                if (isset($theConstants[$key])) {
                    $dValue = $theConstants[$key]['default_value'];
                    $this->ext_putValueInConf($key, $dValue);
                }
            }
        }
    }

    /**
     * @param int $id
     * @param string $perms_clause
     * @return array
     */
    public function ext_prevPageWithTemplate($id, $perms_clause)
    {
        $rootLine = BackendUtility::BEgetRootLine($id, $perms_clause ? ' AND ' . $perms_clause : '');
        foreach ($rootLine as $p) {
            if ($this->ext_getFirstTemplate($p['uid'])) {
                return $p;
            }
        }
        return [];
    }

    /**
     * Is set by runThroughTemplates(), previously set via TemplateAnalyzerModuleFunctionController from the outside
     *
     * @return array
     */
    protected function getRootLine()
    {
        return is_array($this->absoluteRootLine) ? $this->absoluteRootLine : [];
    }

    /**
     * @return LanguageService
     */
    protected function getLanguageService()
    {
        return $GLOBALS['LANG'];
    }

    /**
     * @return DocumentTemplate
     */
    protected function getDocumentTemplate()
    {
        return $GLOBALS['TBE_TEMPLATE'];
    }
}
