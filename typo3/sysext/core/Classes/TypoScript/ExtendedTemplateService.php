<?php

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

namespace TYPO3\CMS\Core\TypoScript;

use TYPO3\CMS\Backend\Routing\UriBuilder;
use TYPO3\CMS\Core\Context\Context;
use TYPO3\CMS\Core\Exception;
use TYPO3\CMS\Core\TypoScript\Parser\ConstantConfigurationParser;
use TYPO3\CMS\Core\TypoScript\Parser\TypoScriptParser;
use TYPO3\CMS\Core\Utility\ArrayUtility;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Core\Utility\MathUtility;
use TYPO3\CMS\Frontend\Configuration\TypoScript\ConditionMatching\ConditionMatcher;

/**
 * TSParser extension class to TemplateService
 * Contains functions for the TS module in TYPO3 backend
 *
 * @internal this is only used for the TYPO3 TypoScript Template module, which should not be used in Extensions
 */
class ExtendedTemplateService extends TemplateService
{
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
     * @var bool
     */
    public $regexMode = false;

    /**
     * @var int
     */
    public $ext_expandAllNotes = 0;

    /**
     * @var int
     */
    public $ext_noPMicons = 0;

    /**
     * Ts analyzer
     *
     * @var array
     */
    public $templateTitles = [];

    /**
     * @var array|null
     */
    protected $lnToScript;

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
     * @var int[]
     */
    public $objReg = [];

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

    private ConstantConfigurationParser $constantParser;

    public function __construct(Context $context = null, ConstantConfigurationParser $constantParser = null)
    {
        parent::__construct($context);
        $this->constantParser = $constantParser ?? GeneralUtility::makeInstance(ConstantConfigurationParser::class);
        // Disabled in backend context
        $this->tt_track = false;
        $this->verbose = false;
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
                $ret_val = isset($this->flatSetup[$matches[1]]) && !is_array($this->flatSetup[$matches[1]]) ? '##' . $marker . '_B##' . $this->flatSetup[$matches[1]] . '##' . $marker . '_M##' . $matches[0] . '##' . $marker . '_E##' : $matches[0];
                break;
            case 'subst':
                $ret_val = isset($this->flatSetup[$matches[1]]) && !is_array($this->flatSetup[$matches[1]]) ? '##' . $marker . '_B##' . $matches[0] . '##' . $marker . '_M##' . $this->flatSetup[$matches[1]] . '##' . $marker . '_E##' : $matches[0];
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
     * Substitute markers added in substituteConstantsCallBack()
     * with ##6chars_B##value1##6chars_M##value2##6chars_E##
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
                    '/##[a-z0-9]{6}_B##(.*?)##[a-z0-9]{6}_M##(.*?)##[a-z0-9]{6}_E##/',
                    '<strong class="text-success" data-bs-toggle="tooltip" data-bs-placement="top" data-title="$1" title="$1">$2</strong>',
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
        // Parse constants
        $constants = GeneralUtility::makeInstance(TypoScriptParser::class);
        // Register comments!
        $constants->regComments = true;
        $matchObj = GeneralUtility::makeInstance(ConditionMatcher::class);
        // Matches ALL conditions in TypoScript
        $matchObj->setSimulateMatchResult(true);
        $c = 0;
        $cc = count($this->constants);
        $defaultConstants = [];
        foreach ($this->constants as $str) {
            $c++;
            if ($c == $cc) {
                $defaultConstants = ArrayUtility::flatten($constants->setup, '', true);
            }
            $constants->parse($str, $matchObj);
        }
        $this->setup['constants'] = $constants->setup;
        $flatSetup = ArrayUtility::flatten($constants->setup, '', true);
        return $this->constantParser->parseComments(
            $flatSetup,
            $defaultConstants
        );
    }

    /**
     * Get object tree
     *
     * @param array $arr
     * @param string $depth_in
     * @param string $depthData
     * @param bool $alphaSort sorts the array keys / tree by alphabet when set
     * @return string
     */
    public function ext_getObjTree($arr, $depth_in, $depthData, bool $alphaSort = false, string $targetRoute = 'web_ts')
    {
        $HTML = '';
        if ($alphaSort) {
            ksort($arr);
        }
        $keyArr_num = [];
        $keyArr_alpha = [];
        $uriBuilder = GeneralUtility::makeInstance(UriBuilder::class);
        foreach ($arr as $key => $value) {
            // Don't do anything with comments / linenumber registrations...
            if (substr($key, -2) !== '..') {
                $key = preg_replace('/\\.$/', '', $key) ?? '';
                if (substr($key, -1) !== '.') {
                    if (MathUtility::canBeInterpretedAsInteger($key)) {
                        $keyArr_num[$key] = $arr[$key] ?? '';
                    } else {
                        $keyArr_alpha[$key] = $arr[$key] ?? '';
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
                $deeper = is_array($arr[$key . '.'] ?? null) && (($this->tsbrowser_depthKeys[$depth] ?? false) || $this->ext_expandAllNotes);
                $PM = is_array($arr[$key . '.'] ?? null) && !$this->ext_noPMicons ? ($deeper ? 'minus' : 'plus') : 'join';
                $HTML .= $depthData . '<li><span class="list-tree-group">';
                if ($PM !== 'join') {
                    $urlParameters = [
                        'id' => (int)GeneralUtility::_GP('id'),
                        'tsbr[' . $depth . ']' => $deeper ? 0 : 1,
                    ];
                    $aHref = $uriBuilder->buildUriFromRoute($targetRoute, $urlParameters) . '#' . $goto;
                    $HTML .= '<a class="list-tree-control' . ($PM === 'minus' ? ' list-tree-control-open' : ' list-tree-control-closed') . '" name="' . $goto . '" href="' . htmlspecialchars($aHref) . '"><i class="fa"></i></a>';
                }
                $label = $key;
                // Read only...
                if (($depth === 'types') && $this->bType === 'setup') {
                    $label = '<span style="color: #666666;">' . $label . '</span>';
                } else {
                    if ($this->linkObjects) {
                        $urlParameters = [
                            'id' => (int)GeneralUtility::_GP('id'),
                            'sObj' => $depth,
                        ];
                        $aHref = (string)$uriBuilder->buildUriFromRoute($targetRoute, $urlParameters);
                        if ($this->bType !== 'const') {
                            $ln = is_array($arr[$key . '.ln..'] ?? null) ? 'Defined in: ' . $this->lineNumberToScript($arr[$key . '.ln..']) : 'N/A';
                        } else {
                            $ln = '';
                        }
                        if (($this->tsbrowser_searchKeys[$depth] ?? 0) & 4) {
                            // The key has matched the search string
                            $label = '<strong class="text-danger">' . $label . '</strong>';
                        }
                        $label = '<a href="' . htmlspecialchars($aHref) . '" title="' . htmlspecialchars($depth_in . $key . ' ' . $ln) . '">' . $label . '</a>';
                    }
                }
                $HTML .= '<span class="list-tree-label" title="' . htmlspecialchars($depth_in . $key) . '">[' . $label . ']</span>';
                if (isset($arr[$key])) {
                    $theValue = $arr[$key];
                    // The value has matched the search string
                    if (($this->tsbrowser_searchKeys[$depth] ?? 0) & 2) {
                        $HTML .= ' = <span class="list-tree-value text-danger">' . htmlspecialchars($theValue) . '</span>';
                    } else {
                        $HTML .= ' = <span class="list-tree-value">' . htmlspecialchars($theValue) . '</span>';
                    }
                    if ($this->ext_regComments && isset($arr[$key . '..'])) {
                        $comment = (string)$arr[$key . '..'];
                        // Skip INCLUDE_TYPOSCRIPT comments, they are almost useless
                        if (!preg_match('/### <INCLUDE_TYPOSCRIPT:.*/', $comment)) {
                            // Remove linebreaks, replace with ' '
                            $comment = preg_replace('/[\\r\\n]/', ' ', $comment) ?? '';
                            // Remove # and * if more than twice in a row
                            $comment = preg_replace('/[#\\*]{2,}/', '', $comment) ?? '';
                            // Replace leading # (just if it exists) and add it again. Result: Every comment should be prefixed by a '#'.
                            $comment = preg_replace('/^[#\\*\\s]+/', '# ', $comment) ?? '';
                            // Masking HTML Tags: Replace < with &lt; and > with &gt;
                            $comment = htmlspecialchars($comment);
                            $HTML .= ' <i class="text-muted">' . trim($comment) . '</i>';
                        }
                    }
                }
                $HTML .= '</span>';
                if ($deeper) {
                    $HTML .= $this->ext_getObjTree($arr[$key . '.'] ?? [], $depth, $depthData, $alphaSort, $targetRoute);
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
     * @return string Imploded array of line number and template title
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
            $key = preg_replace('/\\.$/', '', $key) ?? '';
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
            if ($this->regexMode) {
                // The value has matched
                if (($arr[$key] ?? false) && preg_match($searchPattern, $arr[$key])) {
                    $this->tsbrowser_searchKeys[$depth] = ($this->tsbrowser_searchKeys[$depth] ?? 0) + 2;
                }
                // The key has matched
                if (preg_match($searchPattern, $key)) {
                    $this->tsbrowser_searchKeys[$depth] = ($this->tsbrowser_searchKeys[$depth] ?? 0) + 4;
                }
                // Just open this subtree if the parent key has matched the search
                if (preg_match($searchPattern, $depth_in)) {
                    $this->tsbrowser_searchKeys[$depth] = 1;
                }
            } else {
                // The value has matched
                if (($arr[$key] ?? false) && stripos($arr[$key], $searchString) !== false) {
                    $this->tsbrowser_searchKeys[$depth] = ($this->tsbrowser_searchKeys[$depth] ?? 0) + 2;
                }
                // The key has matches
                if (stripos($key, $searchString) !== false) {
                    $this->tsbrowser_searchKeys[$depth] = ($this->tsbrowser_searchKeys[$depth] ?? 0) + 4;
                }
                // Just open this subtree if the parent key has matched the search
                if (stripos($depth_in, $searchString) !== false) {
                    $this->tsbrowser_searchKeys[$depth] = 1;
                }
            }
            if (is_array($arr[$key . '.'] ?? null)) {
                $cS = count($this->tsbrowser_searchKeys);
                $keyArray = $this->ext_getSearchKeys($arr[$key . '.'], $depth, $searchString, $keyArray);
                if ($cS !== count($this->tsbrowser_searchKeys)) {
                    $keyArray[$depth] = 1;
                }
            }
        }
        return $keyArray;
    }

    /**
     * Processes the flat array from TemplateService->hierarchyInfo
     * and turns it into a hierarchical array to show dependencies (used by TemplateAnalyzer)
     *
     * @param array $depthDataArr (empty array on external call)
     * @param int $pointer Element number (1! to count()) of $this->hierarchyInfo that should be processed.
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
            if ($row['templateID'] == ($this->hierarchyInfo[$pointer - 1]['templateParent'] ?? '')) {
                $depthDataArr[$row['templateID'] . '.'] = $this->ext_process_hierarchyInfo([], $pointer);
            }
        }
        return $depthDataArr;
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
                $p = trim($reg[1] ?? '');
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

    /***************************
     *
     * Processing input values
     *
     ***************************/
    /**
     * @param string $constants
     */
    public function ext_regObjectPositions(string $constants): void
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
            $this->raw[$lineNum] = implode('=', $parts);
        } else {
            $this->raw[] = $key . ' =' . $theValue;
        }
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
    }

    public function ext_procesInput(array $http_post_vars, array $theConstants)
    {
        $valuesHaveChanged = false;
        $data = $http_post_vars['data'] ?? null;
        $check = $http_post_vars['check'] ?? [];
        $Wdata = $http_post_vars['Wdata'] ?? [];
        $W2data = $http_post_vars['W2data'] ?? [];
        $W3data = $http_post_vars['W3data'] ?? [];
        $W4data = $http_post_vars['W4data'] ?? [];
        $W5data = $http_post_vars['W5data'] ?? [];
        if (is_array($data)) {
            foreach ($data as $key => $var) {
                if (isset($theConstants[$key])) {
                    // If checkbox is set, update the value
                    if (isset($check[$key])) {
                        // Exploding with linebreak, just to make sure that no multiline input is given!
                        [$var] = explode(LF, $var);
                        $typeDat = $this->ext_getTypeData($theConstants[$key]['type']);
                        switch ($typeDat['type']) {
                            case 'int':
                                if ($typeDat['paramstr'] ?? false) {
                                    $var = MathUtility::forceIntegerInRange((int)$var, $typeDat['params'][0], $typeDat['params'][1]);
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
                                    $var = preg_replace('/[^A-Fa-f0-9]*/', '', $var) ?? '';
                                    $useFulHex = strlen($var) > 3;
                                    $col[] = (int)hexdec($var[0]);
                                    $col[] = (int)hexdec($var[1]);
                                    $col[] = (int)hexdec($var[2]);
                                    if ($useFulHex) {
                                        $col[] = (int)hexdec($var[3]);
                                        $col[] = (int)hexdec($var[4]);
                                        $col[] = (int)hexdec($var[5]);
                                    }
                                    $var = substr('0' . dechex($col[0]), -1) . substr('0' . dechex($col[1]), -1) . substr('0' . dechex($col[2]), -1);
                                    if ($useFulHex) {
                                        $var .= substr('0' . dechex($col[3]), -1) . substr('0' . dechex($col[4]), -1) . substr('0' . dechex($col[5]), -1);
                                    }
                                    $var = '#' . strtoupper($var);
                                }
                                break;
                            case 'comment':
                                if ($var) {
                                    $var = '';
                                } else {
                                    $var = '#';
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
                                    $var = ($typeDat['paramstr'] ?? false) ?: 1;
                                }
                                break;
                        }
                        if ((string)($theConstants[$key]['value'] ?? '') !== (string)$var) {
                            // Put value in, if changed.
                            $this->ext_putValueInConf($key, $var);
                            $valuesHaveChanged = true;
                        }
                        // Remove the entry because it has been "used"
                        unset($check[$key]);
                    } else {
                        $this->ext_removeValueInConf($key);
                        $valuesHaveChanged = true;
                    }
                }
            }
        }
        // Remaining keys in $check indicates fields that are just clicked "on" to be edited.
        // Therefore we get the default value and puts that in the template as a start...
        foreach ($check ?? [] as $key => $var) {
            if (isset($theConstants[$key])) {
                $dValue = $theConstants[$key]['default_value'];
                $this->ext_putValueInConf($key, $dValue);
                $valuesHaveChanged = true;
            }
        }
        return $valuesHaveChanged;
    }

    /**
     * Is set by runThroughTemplates(), previously set via TemplateAnalyzerModuleFunctionController from the outside
     */
    public function getRootLine(): array
    {
        return $this->absoluteRootLine;
    }
}
