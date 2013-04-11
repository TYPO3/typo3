<?php
namespace TYPO3\CMS\Core\TypoScript;

/***************************************************************
 *  Copyright notice
 *
 *  (c) 1999-2013 Kasper Skårhøj (kasperYYYY@typo3.com)
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
 * TSParser extension class to TemplateService
 * Contains functions for the TS module in TYPO3 backend
 *
 * @author Kasper Skårhøj <kasperYYYY@typo3.com>
 */
class ExtendedTemplateService extends \TYPO3\CMS\Core\TypoScript\TemplateService {

	// This string is used to indicate the point in a template from where the editable constants are listed. Any vars before this point (if it exists though) is regarded as default values.
	/**
	 * @todo Define visibility
	 */
	public $edit_divider = '###MOD_TS:EDITABLE_CONSTANTS###';

	/**
	 * @todo Define visibility
	 */
	public $HTMLcolorList = 'aqua,beige,black,blue,brown,fuchsia,gold,gray,green,lime,maroon,navy,olive,orange,purple,red,silver,tan,teal,turquoise,yellow,white';

	// internal
	/**
	 * @todo Define visibility
	 */
	public $categories = array(
		'basic' => array(),
		// Constants of superior importance for the template-layout. This is dimensions, imagefiles and enabling of various features. The most basic constants, which you would almost always want to configure.
		'menu' => array(),
		// Menu setup. This includes fontfiles, sizes, background images. Depending on the menutype.
		'content' => array(),
		// All constants related to the display of pagecontent elements
		'page' => array(),
		// General configuration like metatags, link targets
		'advanced' => array(),
		// Advanced functions, which are used very seldomly.
		'all' => array()
	);

	// This will be filled with the available categories of the current template.
	/**
	 * @todo Define visibility
	 */
	public $subCategories = array(
		// Standard categories:
		'enable' => array('Enable features', 'a'),
		'dims' => array('Dimensions, widths, heights, pixels', 'b'),
		'file' => array('Files', 'c'),
		'typo' => array('Typography', 'd'),
		'color' => array('Colors', 'e'),
		'links' => array('Links and targets', 'f'),
		'language' => array('Language specific constants', 'g'),
		// subcategories based on the default content elements
		'cheader' => array('Content: \'Header\'', 'ma'),
		'cheader_g' => array('Content: \'Header\', Graphical', 'ma'),
		'ctext' => array('Content: \'Text\'', 'mb'),
		'cimage' => array('Content: \'Image\'', 'md'),
		'cbullets' => array('Content: \'Bullet list\'', 'me'),
		'ctable' => array('Content: \'Table\'', 'mf'),
		'cuploads' => array('Content: \'Filelinks\'', 'mg'),
		'cmultimedia' => array('Content: \'Multimedia\'', 'mh'),
		'cmedia' => array('Content: \'Media\'', 'mr'),
		'cmailform' => array('Content: \'Form\'', 'mi'),
		'csearch' => array('Content: \'Search\'', 'mj'),
		'clogin' => array('Content: \'Login\'', 'mk'),
		'cmenu' => array('Content: \'Menu/Sitemap\'', 'mm'),
		'cshortcut' => array('Content: \'Insert records\'', 'mn'),
		'clist' => array('Content: \'List of records\'', 'mo'),
		'chtml' => array('Content: \'HTML\'', 'mq')
	);

	/**
	 * @todo Define visibility
	 */
	public $backend_info = 1;

	// Tsconstanteditor
	/**
	 * @todo Define visibility
	 */
	public $ext_inBrace = 0;

	// Tsbrowser
	/**
	 * @todo Define visibility
	 */
	public $tsbrowser_searchKeys = array();

	/**
	 * @todo Define visibility
	 */
	public $tsbrowser_depthKeys = array();

	/**
	 * @todo Define visibility
	 */
	public $constantMode = '';

	/**
	 * @todo Define visibility
	 */
	public $regexMode = '';

	/**
	 * @todo Define visibility
	 */
	public $fixedLgd = '';

	/**
	 * @todo Define visibility
	 */
	public $resourceCheck = 0;

	/**
	 * @todo Define visibility
	 */
	public $ext_lineNumberOffset = 0;

	/**
	 * @todo Define visibility
	 */
	public $ext_localGfxPrefix = '';

	/**
	 * @todo Define visibility
	 */
	public $ext_localWebGfxPrefix = '';

	/**
	 * @todo Define visibility
	 */
	public $ext_expandAllNotes = 0;

	/**
	 * @todo Define visibility
	 */
	public $ext_noPMicons = 0;

	/**
	 * @todo Define visibility
	 */
	public $ext_noSpecialCharsOnLabels = 0;

	/**
	 * @todo Define visibility
	 */
	public $ext_listOfTemplatesArr = array();

	/**
	 * @todo Define visibility
	 */
	public $ext_lineNumberOffset_mode = '';

	// Dont change...
	/**
	 * @todo Define visibility
	 */
	public $ext_dontCheckIssetValues = 0;

	/**
	 * @todo Define visibility
	 */
	public $ext_printAll = 0;

	/**
	 * @todo Define visibility
	 */
	public $ext_CEformName = 'forms[0]';

	/**
	 * @todo Define visibility
	 */
	public $doNotSortCategoriesBeforeMakingForm = FALSE;

	// Ts analyzer
	/**
	 * @todo Define visibility
	 */
	public $templateTitles = array();

	/**
	 * This flattens a hierarchical setuparray to $this->flatSetup
	 * The original function fetched the resource-file if any ('file.'). This functions doesn't.
	 *
	 * @param 	[type]		$setupArray: ...
	 * @param 	[type]		$prefix: ...
	 * @param 	[type]		$resourceFlag: ...
	 * @return 	[type]		...
	 * @todo Define visibility
	 */
	public function flattenSetup($setupArray, $prefix, $resourceFlag) {
		if (is_array($setupArray)) {
			// Setting absolute prefixed path for relative resources.
			$this->getFileName_backPath = PATH_site;
			foreach ($setupArray as $key => $val) {
				// We don't want 'TSConstantEditor' in the flattend setup.
				if ($prefix || substr($key, 0, 16) != 'TSConstantEditor') {
					if (is_array($val)) {
						$this->flattenSetup($val, $prefix . $key, $key == 'file.');
					} elseif ($resourceFlag && $this->resourceCheck) {
						$this->flatSetup[$prefix . $key] = $this->getFileName($val);
						if ($this->removeFromGetFilePath && substr($this->flatSetup[$prefix . $key], 0, strlen($this->removeFromGetFilePath)) == $this->removeFromGetFilePath) {
							$this->flatSetup[$prefix . $key] = substr($this->flatSetup[$prefix . $key], strlen($this->removeFromGetFilePath));
						}
					} else {
						$this->flatSetup[$prefix . $key] = $val;
					}
				}
			}
		}
	}

	/**
	 * [Describe function...]
	 *
	 * @param 	[type]		$all: ...
	 * @return 	[type]		...
	 * @todo Define visibility
	 */
	public function substituteConstants($all) {
		$this->Cmarker = substr(md5(uniqid('')), 0, 6);
		return preg_replace_callback('/\\{\\$(.[^}]+)\\}/', array($this, 'substituteConstantsCallBack'), $all);
	}

	/**
	 * Call back method for preg_replace_callback in substituteConstants
	 *
	 * @param array $matches Regular expression matches
	 * @return string Replacement
	 * @see substituteConstants()
	 * @todo Define visibility
	 */
	public function substituteConstantsCallBack($matches) {
		switch ($this->constantMode) {
		case 'const':
			$ret_val = isset($this->flatSetup[$matches[1]]) && !is_array($this->flatSetup[$matches[1]]) ? '##' . $this->Cmarker . '_B##' . $matches[0] . '##' . $this->Cmarker . '_E##' : $matches[0];
			break;
		case 'subst':
			$ret_val = isset($this->flatSetup[$matches[1]]) && !is_array($this->flatSetup[$matches[1]]) ? '##' . $this->Cmarker . '_B##' . $this->flatSetup[$matches[1]] . '##' . $this->Cmarker . '_E##' : $matches[0];
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
	 * [Describe function...]
	 *
	 * @param 	[type]		$all: ...
	 * @return 	[type]		...
	 * @todo Define visibility
	 */
	public function substituteCMarkers($all) {
		switch ($this->constantMode) {
		case 'const':
			$all = str_replace('##' . $this->Cmarker . '_B##', '<font color="green"><strong>', $all);
			$all = str_replace('##' . $this->Cmarker . '_E##', '</strong></font>', $all);
			break;
		case 'subst':
			$all = str_replace('##' . $this->Cmarker . '_B##', '<font color="green"><strong>', $all);
			$all = str_replace('##' . $this->Cmarker . '_E##', '</strong></font>', $all);
			break;
		default:
			$all = $all;
			break;
		}
		return $all;
	}

	/**
	 * Parses the constants in $this->const with respect to the constant-editor in this module.
	 * In particular comments in the code are registered and the edit_divider is taken into account.
	 *
	 * @return 	[type]		...
	 * @todo Define visibility
	 */
	public function generateConfig_constants() {
		// These vars are also set lateron...
		$this->setup['resources'] = $this->resources;
		$this->setup['sitetitle'] = $this->sitetitle;
		// Parse constants
		$constants = \TYPO3\CMS\Core\Utility\GeneralUtility::makeInstance('TYPO3\\CMS\\Core\\TypoScript\\Parser\\TypoScriptParser');
		// Register comments!
		$constants->regComments = 1;
		$constants->setup = $this->const;
		$constants->setup = $this->mergeConstantsFromPageTSconfig($constants->setup);
		/** @var $matchObj \TYPO3\CMS\Frontend\Configuration\TypoScript\ConditionMatching\ConditionMatcher */
		$matchObj = \TYPO3\CMS\Core\Utility\GeneralUtility::makeInstance('TYPO3\\CMS\\Frontend\\Configuration\\TypoScript\\ConditionMatching\\ConditionMatcher');
		// Matches ALL conditions in TypoScript
		$matchObj->setSimulateMatchResult(TRUE);
		$c = 0;
		$cc = count($this->constants);
		foreach ($this->constants as $str) {
			$c++;
			if ($c == $cc) {
				if (strstr($str, $this->edit_divider)) {
					$parts = explode($this->edit_divider, $str, 2);
					$str = $parts[1];
					$constants->parse($parts[0], $matchObj);
				}
				$this->flatSetup = array();
				$this->flattenSetup($constants->setup, '', '');
				$defaultConstants = $this->flatSetup;
			}
			$constants->parse($str, $matchObj);
		}
		$this->flatSetup = array();
		$this->flattenSetup($constants->setup, '', '');
		$this->setup['constants'] = $constants->setup;
		return $this->ext_compareFlatSetups($defaultConstants);
	}

	/**
	 * [Describe function...]
	 *
	 * @param 	[type]		$theSetup: ...
	 * @param 	[type]		$theKey: ...
	 * @return 	[type]		...
	 * @todo Define visibility
	 */
	public function ext_getSetup($theSetup, $theKey) {
		$parts = explode('.', $theKey, 2);
		if (strcmp($parts[0], '') && is_array($theSetup[$parts[0] . '.'])) {
			if (strcmp(trim($parts[1]), '')) {
				return $this->ext_getSetup($theSetup[$parts[0] . '.'], trim($parts[1]));
			} else {
				return array($theSetup[$parts[0] . '.'], $theSetup[$parts[0]]);
			}
		} else {
			if (strcmp(trim($theKey), '')) {
				return array(array(), $theSetup[$theKey]);
			} else {
				return array($theSetup, '');
			}
		}
	}

	/**
	 * [Describe function...]
	 *
	 * @param 	[type]		$arr: ...
	 * @param 	[type]		$depth_in: ...
	 * @param 	[type]		$depthData: ...
	 * @param 	[type]		$parentType: ...
	 * @param 	[type]		$parentValue: ...
	 * @param boolean $alphaSort sorts the array keys / tree by alphabet when set to 1
	 * @return 	[type]		...
	 * @todo Define visibility
	 */
	public function ext_getObjTree($arr, $depth_in, $depthData, $parentType = '', $parentValue = '', $alphaSort = '0') {
		$HTML = '';
		$a = 0;
		if ($alphaSort == '1') {
			ksort($arr);
		}
		$keyArr_num = array();
		$keyArr_alpha = array();
		foreach ($arr as $key => $value) {
			// Don't do anything with comments / linenumber registrations...
			if (substr($key, -2) != '..') {
				$key = preg_replace('/\\.$/', '', $key);
				if (substr($key, -1) != '.') {
					if (\TYPO3\CMS\Core\Utility\MathUtility::canBeInterpretedAsInteger($key)) {
						$keyArr_num[$key] = $arr[$key];
					} else {
						$keyArr_alpha[$key] = $arr[$key];
					}
				}
			}
		}
		ksort($keyArr_num);
		$keyArr = $keyArr_num + $keyArr_alpha;
		$c = count($keyArr);
		if ($depth_in) {
			$depth_in = $depth_in . '.';
		}
		foreach ($keyArr as $key => $value) {
			$a++;
			$depth = $depth_in . $key;
			// This excludes all constants starting with '_' from being shown.
			if ($this->bType != 'const' || substr($depth, 0, 1) != '_') {
				$goto = substr(md5($depth), 0, 6);
				$deeper = is_array($arr[$key . '.']) && ($this->tsbrowser_depthKeys[$depth] || $this->ext_expandAllNotes) ? 1 : 0;
				$PM = 'join';
				$LN = $a == $c ? 'blank' : 'line';
				$BTM = $a == $c ? 'bottom' : '';
				$PM = is_array($arr[$key . '.']) && !$this->ext_noPMicons ? ($deeper ? 'minus' : 'plus') : 'join';
				$HTML .= $depthData;
				$theIcon = '<img src="' . $GLOBALS['BACK_PATH'] . 'gfx/ol/' . $PM . $BTM . '.gif" width="18" height="16" align="top" border="0" alt="" />';
				if ($PM == 'join') {
					$HTML .= $theIcon;
				} else {
					$urlParameters = array(
						'id' => $GLOBALS['SOBE']->id,
						'tsbr[' . $depth . ']' => $deeper ? 0 : 1
					);
					if (\TYPO3\CMS\Core\Utility\GeneralUtility::_GP('breakPointLN')) {
						$urlParameters['breakPointLN'] = \TYPO3\CMS\Core\Utility\GeneralUtility::_GP('breakPointLN');
					}
					$aHref = \TYPO3\CMS\Backend\Utility\BackendUtility::getModuleUrl('web_ts', $urlParameters) . '#' . $goto;
					$HTML .= '<a name="' . $goto . '" href="' . htmlspecialchars($aHref) . '">' . $theIcon . '</a>';
				}
				$label = $key;
				// Read only...
				if (\TYPO3\CMS\Core\Utility\GeneralUtility::inList('types,resources,sitetitle', $depth) && $this->bType == 'setup') {
					$label = '<font color="#666666">' . $label . '</font>';
				} else {
					if ($this->linkObjects) {
						$urlParameters = array(
							'id' => $GLOBALS['SOBE']->id,
							'sObj' => $depth
						);
						if (\TYPO3\CMS\Core\Utility\GeneralUtility::_GP('breakPointLN')) {
							$urlParameters['breakPointLN'] = \TYPO3\CMS\Core\Utility\GeneralUtility::_GP('breakPointLN');
						}
						$aHref = \TYPO3\CMS\Backend\Utility\BackendUtility::getModuleUrl('web_ts', $urlParameters);
						if ($this->bType != 'const') {
							$ln = is_array($arr[$key . '.ln..']) ? 'Defined in: ' . $this->lineNumberToScript($arr[($key . '.ln..')]) : 'N/A';
						} else {
							$ln = '';
						}
						if ($this->tsbrowser_searchKeys[$depth] & 4) {
							$label = '<strong><font color="red">' . $label . '</font></strong>';
						}
						// The key has matched the search string
						$label = '<a href="' . htmlspecialchars($aHref) . '" title="' . htmlspecialchars($ln) . '">' . $label . '</a>';
					}
				}
				$HTML .= '[' . $label . ']';
				if (isset($arr[$key])) {
					$theValue = $arr[$key];
					if ($this->fixedLgd) {
						$imgBlocks = ceil(1 + strlen($depthData) / 77);
						$lgdChars = 68 - ceil(strlen(('[' . $key . ']')) * 0.8) - $imgBlocks * 3;
						$theValue = $this->ext_fixed_lgd($theValue, $lgdChars);
					}
					// The value has matched the search string
					if ($this->tsbrowser_searchKeys[$depth] & 2) {
						$HTML .= '&nbsp;=&nbsp;<strong><font color="red">' . $this->makeHtmlspecialchars($theValue) . '</font></strong>';
					} else {
						$HTML .= '&nbsp;=&nbsp;<strong>' . $this->makeHtmlspecialchars($theValue) . '</strong>';
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
							$comment = $this->makeHtmlspecialchars($comment);
							$HTML .= ' <span class="comment">' . trim($comment) . '</span>';
						}
					}
				}
				$HTML .= '<br />';
				if ($deeper) {
					$HTML .= $this->ext_getObjTree($arr[$key . '.'], $depth, $depthData . '<img src="' . $GLOBALS['BACK_PATH'] . 'gfx/ol/' . $LN . '.gif" width="18" height="16" align="top" alt="" />', '', $arr[$key], $alphaSort);
				}
			}
		}
		return $HTML;
	}

	/**
	 * [Describe function...]
	 *
	 * @param 	[type]		$lnArr: ...
	 * @return 	[type]		...
	 * @todo Define visibility
	 */
	public function lineNumberToScript($lnArr) {
		if (!is_array($this->lnToScript)) {
			$this->lnToScript = array();
			$c = 1;
			$c += substr_count($GLOBALS['TYPO3_CONF_VARS']['FE']['defaultTypoScript_setup'], LF) + 2;
			$this->lnToScript[$c] = '[Default]';
			foreach ($this->hierarchyInfoToRoot as $info) {
				$c += $info['configLines'] + 1;
				$this->lnToScript[$c] = $info['title'];
			}
		}
		foreach ($lnArr as $k => $ln) {
			foreach ($this->lnToScript as $endLn => $title) {
				if ($endLn >= intval($ln)) {
					$lnArr[$k] = '"' . $title . '", ' . $ln;
					break;
				}
			}
		}
		return implode('; ', $lnArr);
	}

	/**
	 * [Describe function...]
	 *
	 * @param 	[type]		$theValue: ...
	 * @return 	[type]		...
	 * @todo Define visibility
	 */
	public function makeHtmlspecialchars($theValue) {
		return $this->ext_noSpecialCharsOnLabels ? $theValue : htmlspecialchars($theValue);
	}

	/**
	 * [Describe function...]
	 *
	 * @param 	[type]		$arr: ...
	 * @param 	[type]		$depth_in: ...
	 * @param 	[type]		$searchString: ...
	 * @param 	[type]		$keyArray: ...
	 * @return 	[type]		...
	 * @todo Define visibility
	 */
	public function ext_getSearchKeys($arr, $depth_in, $searchString, $keyArray) {
		$keyArr = array();
		foreach ($arr as $key => $value) {
			$key = preg_replace('/\\.$/', '', $key);
			if (substr($key, -1) != '.') {
				$keyArr[$key] = 1;
			}
		}
		$c = count($keyArr);
		if ($depth_in) {
			$depth_in = $depth_in . '.';
		}
		foreach ($keyArr as $key => $value) {
			$depth = $depth_in . $key;
			$deeper = is_array($arr[$key . '.']);
			if ($this->regexMode) {
				// The value has matched
				if (preg_match('/' . $searchString . '/', $arr[$key])) {
					$this->tsbrowser_searchKeys[$depth] += 2;
				}
				// The key has matched
				if (preg_match('/' . $searchString . '/', $key)) {
					$this->tsbrowser_searchKeys[$depth] += 4;
				}
				// Just open this subtree if the parent key has matched the search
				if (preg_match('/' . $searchString . '/', $depth_in)) {
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
	 * [Describe function...]
	 *
	 * @param 	[type]		$pid: ...
	 * @return 	[type]		...
	 * @todo Define visibility
	 */
	public function ext_getRootlineNumber($pid) {
		if ($pid && is_array($GLOBALS['rootLine'])) {
			foreach ($GLOBALS['rootLine'] as $key => $val) {
				if ($val['uid'] == $pid) {
					return $key;
				}
			}
		}
	}

	/**
	 * [Describe function...]
	 *
	 * @param 	[type]		$arr: ...
	 * @param 	[type]		$depthData: ...
	 * @param 	[type]		$keyArray: ...
	 * @param 	[type]		$first: ...
	 * @return 	[type]		...
	 * @todo Define visibility
	 */
	public function ext_getTemplateHierarchyArr($arr, $depthData, $keyArray, $first = 0) {
		$keyArr = array();
		foreach ($arr as $key => $value) {
			$key = preg_replace('/\\.$/', '', $key);
			if (substr($key, -1) != '.') {
				$keyArr[$key] = 1;
			}
		}
		$a = 0;
		$c = count($keyArr);
		static $i = 0;
		foreach ($keyArr as $key => $value) {
			$HTML = '';
			$a++;
			$deeper = is_array($arr[$key . '.']);
			$row = $arr[$key];
			$PM = 'join';
			$LN = $a == $c ? 'blank' : 'line';
			$BTM = $a == $c ? 'top' : '';
			$PM = 'join';
			$HTML .= $depthData;
			$alttext = '[' . $row['templateID'] . ']';
			$alttext .= $row['pid'] ? ' - ' . \TYPO3\CMS\Backend\Utility\BackendUtility::getRecordPath($row['pid'], $GLOBALS['SOBE']->perms_clause, 20) : '';
			$icon = substr($row['templateID'], 0, 3) == 'sys' ? \TYPO3\CMS\Backend\Utility\IconUtility::getSpriteIconForRecord('sys_template', $row, array('title' => $alttext)) : \TYPO3\CMS\Backend\Utility\IconUtility::getSpriteIcon('mimetypes-x-content-template-static', array('title' => $alttext));
			if (in_array($row['templateID'], $this->clearList_const) || in_array($row['templateID'], $this->clearList_setup)) {
				$urlParameters = array(
					'id' => $GLOBALS['SOBE']->id,
					'template' => $row['templateID']
				);
				$aHref = \TYPO3\CMS\Backend\Utility\BackendUtility::getModuleUrl('web_ts', $urlParameters);
				$A_B = '<a href="' . htmlspecialchars($aHref) . '">';
				$A_E = '</a>';
				if (\TYPO3\CMS\Core\Utility\GeneralUtility::_GP('template') == $row['templateID']) {
					$A_B = '<strong>' . $A_B;
					$A_E .= '</strong>';
				}
			} else {
				$A_B = '';
				$A_E = '';
			}
			$HTML .= ($first ? '' : '<img src="' . $GLOBALS['BACK_PATH'] . 'gfx/ol/' . $PM . $BTM . '.gif" width="18" height="16" align="top" border="0" alt="" />') . $icon . $A_B . htmlspecialchars(\TYPO3\CMS\Core\Utility\GeneralUtility::fixed_lgd_cs($row['title'], $GLOBALS['BE_USER']->uc['titleLen'])) . $A_E . '&nbsp;&nbsp;';
			$RL = $this->ext_getRootlineNumber($row['pid']);
			$keyArray[] = '<tr class="' . ($i++ % 2 == 0 ? 'bgColor4' : 'bgColor6') . '">
							<td nowrap="nowrap">' . $HTML . '</td>
							<td align="center">' . ($row['root'] ? \TYPO3\CMS\Backend\Utility\IconUtility::getSpriteIcon('status-status-checked') : '') . '&nbsp;&nbsp;</td>
							<td align="center">' . ($row['clConf'] ? \TYPO3\CMS\Backend\Utility\IconUtility::getSpriteIcon('status-status-checked') : '') . '&nbsp;&nbsp;' . '</td>
							<td align="center">' . ($row['clConst'] ? \TYPO3\CMS\Backend\Utility\IconUtility::getSpriteIcon('status-status-checked') : '') . '&nbsp;&nbsp;' . '</td>
							<td align="center">' . ($row['pid'] ? $row['pid'] : '') . '</td>
							<td align="center">' . (strcmp($RL, '') ? $RL : '') . '</td>
							<td>' . ($row['next'] ? '&nbsp;' . $row['next'] . '&nbsp;&nbsp;' : '') . '</td>
						</tr>';
			if ($deeper) {
				$keyArray = $this->ext_getTemplateHierarchyArr($arr[$key . '.'], $depthData . ($first ? '' : '<img src="' . $GLOBALS['BACK_PATH'] . 'gfx/ol/' . $LN . '.gif" width="18" height="16" align="top" />'), $keyArray);
			}
		}
		return $keyArray;
	}

	/**
	 * [Describe function...]
	 *
	 * @param 	[type]		$depthDataArr: ...
	 * @param 	[type]		$pointer: ...
	 * @return 	[type]		...
	 * @todo Define visibility
	 */
	public function ext_process_hierarchyInfo($depthDataArr, &$pointer) {
		$parent = $this->hierarchyInfo[$pointer - 1]['templateParent'];
		while ($pointer > 0 && $this->hierarchyInfo[$pointer - 1]['templateParent'] == $parent) {
			$pointer--;
			$row = $this->hierarchyInfo[$pointer];
			$depthDataArr[$row['templateID']] = $row;
			$depthDataArr[$row['templateID']]['bgcolor_setup'] = isset($this->clearList_setup_temp[$row['templateID']]) ? ' class="bgColor5"' : '';
			$depthDataArr[$row['templateID']]['bgcolor_const'] = isset($this->clearList_const_temp[$row['templateID']]) ? ' class="bgColor5"' : '';
			unset($this->clearList_setup_temp[$row['templateID']]);
			unset($this->clearList_const_temp[$row['templateID']]);
			$this->templateTitles[$row['templateID']] = $row['title'];
			if ($row['templateID'] == $this->hierarchyInfo[$pointer - 1]['templateParent']) {
				$depthDataArr[$row['templateID'] . '.'] = $this->ext_process_hierarchyInfo(array(), $pointer);
			}
		}
		return $depthDataArr;
	}

	/**
	 * [Describe function...]
	 *
	 * @param 	[type]		$config: ...
	 * @param 	[type]		$lineNumbers: ...
	 * @param 	[type]		$comments: ...
	 * @param 	[type]		$crop: ...
	 * @param 	[type]		$syntaxHL: ...
	 * @param 	[type]		$syntaxHLBlockmode: ...
	 * @return 	[type]		...
	 * @todo Define visibility
	 */
	public function ext_outputTS($config, $lineNumbers = 0, $comments = 0, $crop = 0, $syntaxHL = 0, $syntaxHLBlockmode = 0) {
		$all = '';
		foreach ($config as $str) {
			$all .= LF . '[GLOBAL]' . LF . $str;
		}
		if ($syntaxHL) {
			$all = preg_replace('/^[^' . LF . ']*./', '', $all);
			$all = chop($all);
			$tsparser = \TYPO3\CMS\Core\Utility\GeneralUtility::makeInstance('TYPO3\\CMS\\Core\\TypoScript\\Parser\\TypoScriptParser');
			$tsparser->lineNumberOffset = $this->ext_lineNumberOffset + 1;
			$tsparser->parentObject = $this;
			return $tsparser->doSyntaxHighlight($all, $lineNumbers ? array($this->ext_lineNumberOffset + 1) : '', $syntaxHLBlockmode);
		} else {
			return $this->ext_formatTS($all, $lineNumbers, $comments, $crop);
		}
	}

	/**
	 * Returns a new string of max. $chars length
	 * If the string is longer, it will be truncated and prepended with '...'
	 * $chars must be an integer of at least 4
	 *
	 * @param 	[type]		$string: ...
	 * @param 	[type]		$chars: ...
	 * @return 	[type]		...
	 * @todo Define visibility
	 */
	public function ext_fixed_lgd($string, $chars) {
		if ($chars >= 4) {
			if (strlen($string) > $chars) {
				if (strlen($string) > 24 && substr($string, 0, 12) == '##' . $this->Cmarker . '_B##') {
					return '##' . $this->Cmarker . '_B##' . \TYPO3\CMS\Core\Utility\GeneralUtility::fixed_lgd_cs(substr($string, 12, -12), ($chars - 3)) . '##' . $this->Cmarker . '_E##';
				} else {
					return \TYPO3\CMS\Core\Utility\GeneralUtility::fixed_lgd_cs($string, $chars - 3);
				}
			}
		}
		return $string;
	}

	/**
	 * [Describe function...]
	 *
	 * @param integer $lineNumber Line Number
	 * @param 	[type]		$str: ...
	 * @return string
	 * @todo Define visibility
	 */
	public function ext_lnBreakPointWrap($lineNumber, $str) {
		return '<a href="#" id="line-' . $lineNumber . '" onClick="return brPoint(' . $lineNumber . ',' . ($this->ext_lineNumberOffset_mode == 'setup' ? 1 : 0) . ');">' . $str . '</a>';
	}

	/**
	 * [Describe function...]
	 *
	 * @param 	[type]		$input: ...
	 * @param 	[type]		$ln: ...
	 * @param 	[type]		$comments: ...
	 * @param 	[type]		$crop: ...
	 * @return 	[type]		...
	 * @todo Define visibility
	 */
	public function ext_formatTS($input, $ln, $comments = 1, $crop = 0) {
		$input = preg_replace('/^[^' . LF . ']*./', '', $input);
		$input = chop($input);
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
			if ($firstChar == '[') {
				$cArr[$k] = '<font color="green"><strong>' . $cArr[$k] . '</strong></font>';
			} elseif ($firstChar == '/' || $firstChar == '#') {
				if ($comments) {
					$cArr[$k] = '<span class="typo3-dimmed">' . $cArr[$k] . '</span>';
				} else {
					unset($cArr[$k]);
				}
			}
		}
		$output = implode($cArr, '<br />') . '<br />';
		return $output;
	}

	/**
	 * [Describe function...]
	 *
	 * @param 	[type]		$id: ...
	 * @param 	[type]		$template_uid: ...
	 * @return 	[type]		...
	 * @todo Define visibility
	 */
	public function ext_getFirstTemplate($id, $template_uid = 0) {
		// Query is taken from the runThroughTemplates($theRootLine) function in the parent class.
		if (intval($id)) {
			if ($template_uid) {
				$addC = ' AND uid=' . $template_uid;
			}
			$res = $GLOBALS['TYPO3_DB']->exec_SELECTquery('*', 'sys_template', 'pid=' . intval($id) . $addC . ' ' . $this->whereClause, '', 'sorting', '1');
			$row = $GLOBALS['TYPO3_DB']->sql_fetch_assoc($res);
			\TYPO3\CMS\Backend\Utility\BackendUtility::workspaceOL('sys_template', $row);
			$GLOBALS['TYPO3_DB']->sql_free_result($res);
			// Returns the template row if found.
			return $row;
		}
	}

	/**
	 * [Describe function...]
	 *
	 * @param 	[type]		$id: ...
	 * @return 	[type]		...
	 * @todo Define visibility
	 */
	public function ext_getAllTemplates($id) {
		// Query is taken from the runThroughTemplates($theRootLine) function in the parent class.
		if (intval($id)) {
			$outRes = array();
			$res = $GLOBALS['TYPO3_DB']->exec_SELECTquery('*', 'sys_template', 'pid=' . intval($id) . ' ' . $this->whereClause, '', 'sorting');
			while ($row = $GLOBALS['TYPO3_DB']->sql_fetch_assoc($res)) {
				\TYPO3\CMS\Backend\Utility\BackendUtility::workspaceOL('sys_template', $row);
				if (is_array($row)) {
					$outRes[] = $row;
				}
			}
			$GLOBALS['TYPO3_DB']->sql_free_result($res);
			// Returns the template rows in an array.
			return $outRes;
		}
	}

	/**
	 * This function compares the flattened constants (default and all).
	 * Returns an array with the constants from the whole template which may be edited by the module.
	 *
	 * @param 	[type]		$default: ...
	 * @return 	[type]		...
	 * @todo Define visibility
	 */
	public function ext_compareFlatSetups($default) {
		$editableComments = array();
		foreach ($this->flatSetup as $const => $value) {
			if (substr($const, -2) != '..' && isset($this->flatSetup[$const . '..'])) {
				$comment = trim($this->flatSetup[$const . '..']);
				$c_arr = explode(LF, $comment);
				foreach ($c_arr as $k => $v) {
					$line = trim(preg_replace('/^[#\\/]*/', '', $v));
					if ($line) {
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
									$editableComments[$const]['cat'] = trim($catSplit[0]);
									// This is the subcategory. Must be a key in $this->subCategories[]. catSplit[2] represents the search-order within the subcat.
									$catSplit[1] = trim($catSplit[1]);
									if ($catSplit[1] && isset($this->subCategories[$catSplit[1]])) {
										$editableComments[$const]['subcat_name'] = $catSplit[1];
										$editableComments[$const]['subcat'] = $this->subCategories[$catSplit[1]][1] . '/' . $catSplit[1] . '/' . trim($catSplit[2]) . 'z';
									} else {
										$editableComments[$const]['subcat'] = 'x' . '/' . trim($catSplit[2]) . 'z';
									}
									break;
								case 'label':
									// Label
									$editableComments[$const]['label'] = trim($keyValPair[1]);
									break;
								case 'customsubcategory':
									// Custom subCategory label
									$customSubcategory = explode('=', $keyValPair[1], 2);
									if (trim($customSubcategory[0])) {
										$subCategoryKey = strtolower($customSubcategory[0]);
										$this->subCategories[$subCategoryKey][0] = $GLOBALS['LANG']->sL($customSubcategory[1]);
									}
									break;
								}
							}
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
	 * [Describe function...]
	 *
	 * @param 	[type]		$editConstArray: ...
	 * @return 	[type]		...
	 * @todo Define visibility
	 */
	public function ext_categorizeEditableConstants($editConstArray) {
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
	 * [Describe function...]
	 *
	 * @return 	[type]		...
	 * @todo Define visibility
	 */
	public function ext_getCategoryLabelArray() {
		// Returns array used for labels in the menu.
		$retArr = array();
		foreach ($this->categories as $k => $v) {
			if (count($v)) {
				$retArr[$k] = strtoupper($k) . ' (' . count($v) . ')';
			}
		}
		return $retArr;
	}

	/**
	 * [Describe function...]
	 *
	 * @param 	[type]		$type: ...
	 * @return 	[type]		...
	 * @todo Define visibility
	 */
	public function ext_getTypeData($type) {
		$retArr = array();
		$type = trim($type);
		if (!$type) {
			$retArr['type'] = 'string';
		} else {
			$m = strcspn($type, ' [');
			$retArr['type'] = strtolower(substr($type, 0, $m));
			if (\TYPO3\CMS\Core\Utility\GeneralUtility::inList('int,options,file,boolean,offset,user', $retArr['type'])) {
				$p = trim(substr($type, $m));
				$reg = array();
				preg_match('/\\[(.*)\\]/', $p, $reg);
				$p = trim($reg[1]);
				if ($p) {
					$retArr['paramstr'] = $p;
					switch ($retArr['type']) {
					case 'int':
						if (substr($retArr['paramstr'], 0, 1) == '-') {
							$retArr['params'] = \TYPO3\CMS\Core\Utility\GeneralUtility::intExplode('-', substr($retArr['paramstr'], 1));
							$retArr['params'][0] = intval('-' . $retArr['params'][0]);
						} else {
							$retArr['params'] = \TYPO3\CMS\Core\Utility\GeneralUtility::intExplode('-', $retArr['paramstr']);
						}
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
	 * [Describe function...]
	 *
	 * @param 	[type]		$category: ...
	 * @return 	[type]		...
	 * @todo Define visibility
	 */
	public function ext_getTSCE_config($category) {
		$catConf = $this->setup['constants']['TSConstantEditor.'][$category . '.'];
		$out = array();
		if (is_array($catConf)) {
			foreach ($catConf as $key => $val) {
				switch ($key) {
				case 'image':
					$out['imagetag'] = $this->ext_getTSCE_config_image($catConf['image']);
					break;
				case 'description':

				case 'bulletlist':

				case 'header':
					$out[$key] = $val;
					break;
				default:
					if (\TYPO3\CMS\Core\Utility\MathUtility::canBeInterpretedAsInteger($key)) {
						$constRefs = explode(',', $val);
						foreach ($constRefs as $const) {
							$const = trim($const);
							if ($const && $const <= 20) {
								$out['constants'][$const] .= $this->ext_getKeyImage($key);
							}
						}
					}
					break;
				}
			}
		}
		$this->helpConfig = $out;
	}

	/**
	 * [Describe function...]
	 *
	 * @param 	[type]		$key: ...
	 * @return 	[type]		...
	 * @todo Define visibility
	 */
	public function ext_getKeyImage($key) {
		return '<img src="' . $this->ext_localWebGfxPrefix . 'gfx/' . $key . '.gif" align="top" hspace=2>';
	}

	/**
	 * [Describe function...]
	 *
	 * @param 	[type]		$imgConf: ...
	 * @return 	[type]		...
	 * @todo Define visibility
	 */
	public function ext_getTSCE_config_image($imgConf) {
		if (substr($imgConf, 0, 4) == 'gfx/') {
			$iFile = $this->ext_localGfxPrefix . $imgConf;
			$tFile = $this->ext_localWebGfxPrefix . $imgConf;
		} elseif (substr($imgConf, 0, 4) == 'EXT:') {
			$iFile = \TYPO3\CMS\Core\Utility\GeneralUtility::getFileAbsFileName($imgConf);
			if ($iFile) {
				$f = substr($iFile, strlen(PATH_site));
				$tFile = $GLOBALS['BACK_PATH'] . '../' . $f;
			}
		}
		$imageInfo = @getImagesize($iFile);
		return '<img src="' . $tFile . '" ' . $imageInfo[3] . '>';
	}

	/**
	 * [Describe function...]
	 *
	 * @param 	[type]		$params: ...
	 * @return 	[type]		...
	 * @todo Define visibility
	 */
	public function ext_fNandV($params) {
		$fN = 'data[' . $params['name'] . ']';
		$fV = $params['value'];
		// Values entered from the constantsedit cannot be constants!	230502; removed \{ and set {
		if (preg_match('/^{[\\$][a-zA-Z0-9\\.]*}$/', trim($fV), $reg)) {
			$fV = '';
		}
		$fV = htmlspecialchars($fV);
		return array($fN, $fV, $params);
	}

	/**
	 * This functions returns the HTML-code that creates the editor-layout of the module.
	 *
	 * @param 	[type]		$theConstants: ...
	 * @param 	[type]		$category: ...
	 * @return 	[type]		...
	 * @todo Define visibility
	 */
	public function ext_printFields($theConstants, $category) {
		reset($theConstants);
		$output = '<script type="text/javascript" src="' . $GLOBALS['BACK_PATH'] . 'js/constantEditor.js"></script>
		';
		$subcat = '';
		if (is_array($this->categories[$category])) {
			$help = $this->helpConfig;
			if (!$this->doNotSortCategoriesBeforeMakingForm) {
				asort($this->categories[$category]);
			}
			foreach ($this->categories[$category] as $name => $type) {
				$params = $theConstants[$name];
				if (is_array($params)) {
					if ($subcat != $params['subcat_name']) {
						$subcat = $params['subcat_name'];
						$subcat_name = $params['subcat_name'] ? $this->subCategories[$params['subcat_name']][0] : 'Others';
						$output .= '<h3 class="typo3-tstemplate-ceditor-subcat">' . $subcat_name . '</h3>';
					}
					$label = $GLOBALS['LANG']->sL($params['label']);
					$label_parts = explode(':', $label, 2);
					if (count($label_parts) == 2) {
						$head = trim($label_parts[0]);
						$body = trim($label_parts[1]);
					} else {
						$head = trim($label_parts[0]);
						$body = '';
					}
					if (strlen($head) > 35) {
						if (!$body) {
							$body = $head;
						}
						$head = \TYPO3\CMS\Core\Utility\GeneralUtility::fixed_lgd_cs($head, 35);
					}
					$typeDat = $this->ext_getTypeData($params['type']);
					$checked = '';
					$p_field = '';
					$raname = substr(md5($params['name']), 0, 10);
					$aname = '\'' . $raname . '\'';
					list($fN, $fV, $params) = $this->ext_fNandV($params);
					switch ($typeDat['type']) {
					case 'int':

					case 'int+':
						$p_field = '<input id="' . $fN . '" type="text" name="' . $fN . '" value="' . $fV . '"' . $GLOBALS['TBE_TEMPLATE']->formWidth(5) . ' onChange="uFormUrl(' . $aname . ')" />';
						if ($typeDat['paramstr']) {
							$p_field .= ' Range: ' . $typeDat['paramstr'];
						} elseif ($typeDat['type'] == 'int+') {
							$p_field .= ' Range: 0 - ';
						} else {
							$p_field .= ' (Integer)';
						}
						break;
					case 'color':
						$colorNames = explode(',', ',' . $this->HTMLcolorList);
						$p_field = '';
						foreach ($colorNames as $val) {
							$sel = '';
							if ($val == strtolower($params['value'])) {
								$sel = ' selected';
							}
							$p_field .= '<option value="' . htmlspecialchars($val) . '"' . $sel . '>' . $val . '</option>';
						}
						$p_field = '<select id="select-' . $fN . '" rel="' . $fN . '" name="C' . $fN . '" class="typo3-tstemplate-ceditor-color-select" onChange="uFormUrl(' . $aname . ');">' . $p_field . '</select>';
						$p_field .= '<input type="text" id="input-' . $fN . '" rel="' . $fN . '" name="' . $fN . '" class="typo3-tstemplate-ceditor-color-input" value="' . $fV . '"' . $GLOBALS['TBE_TEMPLATE']->formWidth(7) . ' onChange="uFormUrl(' . $aname . ')" />';
						break;
					case 'wrap':
						$wArr = explode('|', $fV);
						$p_field = '<input type="text" id="' . $fN . '" name="' . $fN . '" value="' . $wArr[0] . '"' . $GLOBALS['TBE_TEMPLATE']->formWidth(29) . ' onChange="uFormUrl(' . $aname . ')" />';
						$p_field .= ' | ';
						$p_field .= '<input type="text" name="W' . $fN . '" value="' . $wArr[1] . '"' . $GLOBALS['TBE_TEMPLATE']->formWidth(15) . ' onChange="uFormUrl(' . $aname . ')" />';
						break;
					case 'offset':
						$wArr = explode(',', $fV);
						$labels = \TYPO3\CMS\Core\Utility\GeneralUtility::trimExplode(',', $typeDat['paramstr']);
						$p_field = ($labels[0] ? $labels[0] : 'x') . ':<input type="text" name="' . $fN . '" value="' . $wArr[0] . '"' . $GLOBALS['TBE_TEMPLATE']->formWidth(4) . ' onChange="uFormUrl(' . $aname . ')" />';
						$p_field .= ' , ';
						$p_field .= ($labels[1] ? $labels[1] : 'y') . ':<input type="text" name="W' . $fN . '" value="' . $wArr[1] . '"' . $GLOBALS['TBE_TEMPLATE']->formWidth(4) . ' onChange="uFormUrl(' . $aname . ')" />';
						$labelsCount = count($labels);
						for ($aa = 2; $aa < $labelsCount; $aa++) {
							if ($labels[$aa]) {
								$p_field .= ' , ' . $labels[$aa] . ':<input type="text" name="W' . $aa . $fN . '" value="' . $wArr[$aa] . '"' . $GLOBALS['TBE_TEMPLATE']->formWidth(4) . ' onChange="uFormUrl(' . $aname . ')" />';
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
								if ($val == $params['value']) {
									$sel = ' selected';
								}
								$p_field .= '<option value="' . htmlspecialchars($val) . '"' . $sel . '>' . $GLOBALS['LANG']->sL($label) . '</option>';
							}
							$p_field = '<select id="' . $fN . '" name="' . $fN . '" onChange="uFormUrl(' . $aname . ')">' . $p_field . '</select>';
						}
						break;
					case 'boolean':
						$p_field = '<input type="hidden" name="' . $fN . '" value="0" />';
						$sel = '';
						if ($fV) {
							$sel = ' checked';
						}
						$p_field .= '<input id="' . $fN . '" type="checkbox" name="' . $fN . '" value="' . ($typeDat['paramstr'] ? $typeDat['paramstr'] : 1) . '"' . $sel . ' onClick="uFormUrl(' . $aname . ')" />';
						break;
					case 'comment':
						$p_field = '<input type="hidden" name="' . $fN . '" value="#" />';
						$sel = '';
						if (!$fV) {
							$sel = ' checked';
						}
						$p_field .= '<input id="' . $fN . '" type="checkbox" name="' . $fN . '" value=""' . $sel . ' onClick="uFormUrl(' . $aname . ')" />';
						break;
					case 'file':
						$p_field = '<option value=""></option>';
						$theImage = '';
						// extensionlist
						$extList = $typeDat['paramstr'];
						$p_field = '<option value="">(' . $extList . ')</option>';
						if ($extList == 'IMAGE_EXT') {
							$extList = $GLOBALS['TYPO3_CONF_VARS']['GFX']['imagefile_ext'];
						}
						if (trim($params['value'])) {
							$val = $params['value'];
							$p_field .= '<option value=""></option>';
							$p_field .= '<option value="' . htmlspecialchars($val) . '" selected>' . $val . '</option>';
						}
						$p_field = '<select id="' . $fN . '" name="' . $fN . '" onChange="uFormUrl(' . $aname . ')">' . $p_field . '</select>';
						$p_field .= $theImage;
						break;
					case 'user':
						$userFunction = $typeDat['paramstr'];
						$userFunctionParams = array('fieldName' => $fN, 'fieldValue' => $fV);
						$p_field = \TYPO3\CMS\Core\Utility\GeneralUtility::callUserFunction($userFunction, $userFunctionParams, $this, '');
						break;
					case 'small':

					default:
						$fwidth = $typeDat['type'] == 'small' ? 10 : 46;
						$p_field = '<input id="' . $fN . '" type="text" name="' . $fN . '" value="' . $fV . '"' . $GLOBALS['TBE_TEMPLATE']->formWidth($fwidth) . ' onChange="uFormUrl(' . $aname . ')" />';
						break;
					}
					// Define default names and IDs
					$userTyposcriptID = 'userTS-' . $params['name'];
					$defaultTyposcriptID = 'defaultTS-' . $params['name'];
					$checkboxName = 'check[' . $params['name'] . ']';
					$checkboxID = $checkboxName;
					// Handle type=color specially
					if ($typeDat['type'] == 'color' && substr($params['value'], 0, 2) != '{$') {
						$color = '<div id="colorbox-' . $fN . '" class="typo3-tstemplate-ceditor-colorblock" style="background-color:' . $params['value'] . ';">&nbsp;</div>';
					} else {
						$color = '';
					}
					if (!$this->ext_dontCheckIssetValues) {
						// Set the default styling options
						if (isset($this->objReg[$params['name']])) {
							$checkboxValue = 'checked';
							$userTyposcriptStyle = '';
							$defaultTyposcriptStyle = 'style="display:none;"';
						} else {
							$checkboxValue = '';
							$userTyposcriptStyle = 'style="display:none;"';
							$defaultTyposcriptStyle = '';
						}
						$deleteIconHTML = \TYPO3\CMS\Backend\Utility\IconUtility::getSpriteIcon('actions-edit-undo', array(
							'class' => 'typo3-tstemplate-ceditor-control undoIcon',
							'alt' => 'Revert to default Constant',
							'title' => 'Revert to default Constant',
							'rel' => $params['name']
						));
						$editIconHTML = \TYPO3\CMS\Backend\Utility\IconUtility::getSpriteIcon('actions-document-open', array(
							'class' => 'typo3-tstemplate-ceditor-control editIcon',
							'alt' => 'Edit this Constant',
							'title' => 'Edit this Constant',
							'rel' => $params['name']
						));
						$constantCheckbox = '<input type="hidden" name="' . $checkboxName . '" id="' . $checkboxID . '" value="' . $checkboxValue . '"/>';
						// If there's no default value for the field, use a static label.
						if (!$params['default_value']) {
							$params['default_value'] = '[Empty]';
						}
						$constantDefaultRow = '<div class="typo3-tstemplate-ceditor-row" id="' . $defaultTyposcriptID . '" ' . $defaultTyposcriptStyle . '>' . $editIconHTML . htmlspecialchars($params['default_value']) . $color . '</div>';
					}
					$constantEditRow = '<div class="typo3-tstemplate-ceditor-row" id="' . $userTyposcriptID . '" ' . $userTyposcriptStyle . '>' . $deleteIconHTML . $p_field . $color . '</div>';
					$constantLabel = '<dt class="typo3-tstemplate-ceditor-label">' . htmlspecialchars($head) . '</dt>';
					$constantName = '<dt class="typo3-dimmed">[' . $params['name'] . ']</dt>';
					$constantDescription = $body ? '<dd>' . htmlspecialchars($body) . '</dd>' : '';
					$constantData = '<dd>' . $constantCheckbox . $constantEditRow . $constantDefaultRow . '</dd>';
					$output .= '<a name="' . $raname . '"></a>' . $help['constants'][$params['name']];
					$output .= '<dl class="typo3-tstemplate-ceditor-constant">' . $constantLabel . $constantName . $constantDescription . $constantData . '</dl>';
				} else {
					debug('Error. Constant did not exist. Should not happen.');
				}
			}
		}
		return $output;
	}

	/***************************
	 *
	 * Processing input values
	 *
	 ***************************/
	/**
	 * @param 	[type]		$constants: ...
	 * @return 	[type]		...
	 * @todo Define visibility
	 */
	public function ext_regObjectPositions($constants) {
		// This runs through the lines of the constants-field of the active template and registers the constants-names
		// and linepositions in an array, $this->objReg
		$this->raw = explode(LF, $constants);
		$this->rawP = 0;
		// Resetting the objReg if the divider is found!!
		$this->objReg = array();
		$this->ext_regObjects('');
	}

	/**
	 * [Describe function...]
	 *
	 * @param 	[type]		$pre: ...
	 * @return 	[type]		...
	 * @todo Define visibility
	 */
	public function ext_regObjects($pre) {
		// Works with regObjectPositions. "expands" the names of the TypoScript objects
		while (isset($this->raw[$this->rawP])) {
			$line = ltrim($this->raw[$this->rawP]);
			if (strstr($line, $this->edit_divider)) {
				// Resetting the objReg if the divider is found!!
				$this->objReg = array();
			}
			$this->rawP++;
			if ($line) {
				if (substr($line, 0, 1) == '[') {

				} elseif (strcspn($line, '}#/') != 0) {
					$varL = strcspn($line, ' {=<');
					$var = substr($line, 0, $varL);
					$line = ltrim(substr($line, $varL));
					switch (substr($line, 0, 1)) {
					case '=':
						$this->objReg[$pre . $var] = $this->rawP - 1;
						break;
					case '{':
						$this->ext_inBrace++;
						$this->ext_regObjects($pre . $var . '.');
						break;
					}
					$this->lastComment = '';
				} elseif (substr($line, 0, 1) == '}') {
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
	 * [Describe function...]
	 *
	 * @param 	[type]		$key: ...
	 * @param 	[type]		$var: ...
	 * @return 	[type]		...
	 * @todo Define visibility
	 */
	public function ext_putValueInConf($key, $var) {
		// Puts the value $var to the TypoScript value $key in the current lines of the templates.
		// If the $key is not found in the template constants field, a new line is inserted in the bottom.
		$theValue = ' ' . trim($var);
		if (isset($this->objReg[$key])) {
			$lineNum = $this->objReg[$key];
			$parts = explode('=', $this->raw[$lineNum], 2);
			if (count($parts) == 2) {
				$parts[1] = $theValue;
			}
			$this->raw[$lineNum] = implode($parts, '=');
		} else {
			$this->raw[] = $key . ' =' . $theValue;
		}
		$this->changed = 1;
	}

	/**
	 * [Describe function...]
	 *
	 * @param 	[type]		$key: ...
	 * @return 	[type]		...
	 * @todo Define visibility
	 */
	public function ext_removeValueInConf($key) {
		// Removes the value in the configuration
		if (isset($this->objReg[$key])) {
			$lineNum = $this->objReg[$key];
			unset($this->raw[$lineNum]);
		}
		$this->changed = 1;
	}

	/**
	 * [Describe function...]
	 *
	 * @param 	[type]		$arr: ...
	 * @param 	[type]		$settings: ...
	 * @return 	[type]		...
	 * @todo Define visibility
	 */
	public function ext_depthKeys($arr, $settings) {
		$tsbrArray = array();
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
	 * [Describe function...]
	 *
	 * @param 	[type]		$http_post_vars: ...
	 * @param 	array (not used anymore)
	 * @param 	[type]		$theConstants: ...
	 * @param 	[type]		$tplRow: ...
	 * @return 	[type]		...
	 * @todo Define visibility
	 */
	public function ext_procesInput($http_post_vars, $http_post_files, $theConstants, $tplRow) {
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
								$var = \TYPO3\CMS\Core\Utility\MathUtility::forceIntegerInRange($var, $typeDat['params'][0], $typeDat['params'][1]);
							} else {
								$var = intval($var);
							}
							break;
						case 'int+':
							$var = max(0, intval($var));
							break;
						case 'color':
							$col = array();
							if ($var && !\TYPO3\CMS\Core\Utility\GeneralUtility::inList($this->HTMLcolorList, strtolower($var))) {
								$var = preg_replace('/[^A-Fa-f0-9]*/', '', $var);
								$useFulHex = strlen($var) > 3;
								$col[] = HexDec(substr($var, 0, 1));
								$col[] = HexDec(substr($var, 1, 1));
								$col[] = HexDec(substr($var, 2, 1));
								if ($useFulHex) {
									$col[] = HexDec(substr($var, 3, 1));
									$col[] = HexDec(substr($var, 4, 1));
									$col[] = HexDec(substr($var, 5, 1));
								}
								$var = substr(('0' . DecHex($col[0])), -1) . substr(('0' . DecHex($col[1])), -1) . substr(('0' . DecHex($col[2])), -1);
								if ($useFulHex) {
									$var .= substr(('0' . DecHex($col[3])), -1) . substr(('0' . DecHex($col[4])), -1) . substr(('0' . DecHex($col[5])), -1);
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
								$var = intval($var) . ',' . intval($Wdata[$key]);
								if (isset($W2data[$key])) {
									$var .= ',' . intval($W2data[$key]);
									if (isset($W3data[$key])) {
										$var .= ',' . intval($W3data[$key]);
										if (isset($W4data[$key])) {
											$var .= ',' . intval($W4data[$key]);
											if (isset($W5data[$key])) {
												$var .= ',' . intval($W5data[$key]);
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
						if ($this->ext_printAll || strcmp($theConstants[$key]['value'], $var)) {
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
		// Remaining keys in $check indicates fields that are just clicked "on" to be edited. Therefore we get the default value and puts that in the template as a start...
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
	 * [Describe function...]
	 *
	 * @param 	[type]		$id: ...
	 * @param 	[type]		$perms_clause: ...
	 * @return 	[type]		...
	 * @todo Define visibility
	 */
	public function ext_prevPageWithTemplate($id, $perms_clause) {
		$rootLine = \TYPO3\CMS\Backend\Utility\BackendUtility::BEgetRootLine($id, $perms_clause ? ' AND ' . $perms_clause : '');
		foreach ($rootLine as $p) {
			if ($this->ext_getFirstTemplate($p['uid'])) {
				return $p;
			}
		}
	}

}


?>