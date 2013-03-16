<?php
namespace TYPO3\CMS\Lowlevel\Utility;

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
 * Class for displaying an array as a tree
 *
 * Revised for TYPO3 3.6 July/2003 by Kasper Skårhøj
 * XHTML compliant
 *
 * @author Kasper Skårhøj <kasperYYYY@typo3.com>
 */
/**
 * Class for displaying an array as a tree
 * See the extension 'lowlevel' /config (Backend module 'Tools > Configuration')
 *
 * @author Kasper Skårhøj <kasperYYYY@typo3.com>
 * @see SC_mod_tools_config_index::main()
 */
class ArrayBrowser {

	/**
	 * @todo Define visibility
	 */
	public $expAll = FALSE;

	// If set, will expand all (depthKeys is obsolete then) (and no links are applied)
	/**
	 * @todo Define visibility
	 */
	public $dontLinkVar = FALSE;

	// If set, the variable keys are not linked.
	/**
	 * @todo Define visibility
	 */
	public $depthKeys = array();

	// Array defining which keys to expand. Typically set from outside from some session variable - otherwise the array will collapse.
	/**
	 * @todo Define visibility
	 */
	public $searchKeys = array();

	// After calling the getSearchKeys function this array is populated with the key-positions in the array which contains values matching the search.
	/**
	 * @todo Define visibility
	 */
	public $fixedLgd = 1;

	// If set, the values are truncated with "..." appended if longer than a certain length.
	/**
	 * @todo Define visibility
	 */
	public $regexMode = 0;

	// If set, search for string with regex, otherwise stristr()
	/**
	 * @todo Define visibility
	 */
	public $searchKeysToo = FALSE;

	// If set, array keys are subject to the search too.
	/**
	 * @todo Define visibility
	 */
	public $varName = '';

	// Set var name here if you want links to the variable name.
	/**
	 * Make browsable tree
	 * Before calling this function you may want to set some of the internal vars like depthKeys, regexMode and fixedLgd.
	 * For examples see SC_mod_tools_config_index::main()
	 *
	 * @param array $arr The array to display
	 * @param string $depth_in Key-position id. Build up during recursive calls - [key1].[key2].[key3] - an so on.
	 * @param string $depthData Depth-data - basically a prefix for the icons. For calling this function from outside, let it stay blank.
	 * @return string HTML for the tree
	 * @see SC_mod_tools_config_index::main()
	 * @todo Define visibility
	 */
	public function tree($arr, $depth_in, $depthData) {
		$HTML = '';
		$a = 0;
		if ($depth_in) {
			$depth_in = $depth_in . '.';
		}
		$c = count($arr);
		foreach ($arr as $key => $value) {
			$a++;
			$depth = $depth_in . $key;
			$goto = 'a' . substr(md5($depth), 0, 6);
			if (is_object($arr[$key])) {
				$arr[$key] = (array) $arr[$key];
			}
			$isArray = is_array($arr[$key]);
			$deeper = $isArray && ($this->depthKeys[$depth] || $this->expAll);
			$PM = 'join';
			$LN = $a == $c ? 'blank' : 'line';
			$BTM = $a == $c ? 'bottom' : '';
			$PM = $isArray ? ($deeper ? 'minus' : 'plus') : 'join';
			$HTML .= $depthData;
			$theIcon = '<img' . \TYPO3\CMS\Backend\Utility\IconUtility::skinImg($GLOBALS['BACK_PATH'], ('gfx/ol/' . $PM . $BTM . '.gif'), 'width="18" height="16"') . ' align="top" border="0" alt="" />';
			if ($PM == 'join') {
				$HTML .= $theIcon;
			} else {
				$HTML .= ($this->expAll ? '' : '<a id="' . $goto . '" href="' . htmlspecialchars((\TYPO3\CMS\Backend\Utility\BackendUtility::getModuleUrl(\TYPO3\CMS\Core\Utility\GeneralUtility::_GP('M')) . '&node[' . $depth . ']=' . ($deeper ? 0 : 1) . '#' . $goto)) . '">') . $theIcon . ($this->expAll ? '' : '</a>');
			}
			$label = $key;
			$HTML .= $this->wrapArrayKey($label, $depth, !$isArray ? $arr[$key] : '');
			if (!$isArray) {
				$theValue = $arr[$key];
				if ($this->fixedLgd) {
					$imgBlocks = ceil(1 + strlen($depthData) / 77);
					$lgdChars = 68 - ceil(strlen(('[' . $key . ']')) * 0.8) - $imgBlocks * 3;
					$theValue = $this->fixed_lgd($theValue, $lgdChars);
				}
				if ($this->searchKeys[$depth]) {
					$HTML .= '=<span style="color:red;">' . $this->wrapValue($theValue, $depth) . '</span>';
				} else {
					$HTML .= '=' . $this->wrapValue($theValue, $depth);
				}
			}
			$HTML .= '<br />';
			if ($deeper) {
				$HTML .= $this->tree($arr[$key], $depth, $depthData . '<img' . \TYPO3\CMS\Backend\Utility\IconUtility::skinImg($GLOBALS['BACK_PATH'], ('gfx/ol/' . $LN . '.gif'), 'width="18" height="16"') . ' align="top" alt="" />');
			}
		}
		return $HTML;
	}

	/**
	 * Wrapping the value in bold tags etc.
	 *
	 * @param string $theValue The title string
	 * @param string $depth Depth path
	 * @return string Title string, htmlspecialchars()'ed
	 * @todo Define visibility
	 */
	public function wrapValue($theValue, $depth) {
		$wrappedValue = '';
		if (strlen($theValue) > 0) {
			$wrappedValue = '<strong>' . htmlspecialchars($theValue) . '</strong>';
		}
		return $wrappedValue;
	}

	/**
	 * Wrapping the value in bold tags etc.
	 *
	 * @param string $label The title string
	 * @param string $depth Depth path
	 * @param string $theValue The value for the array entry.
	 * @return string Title string, htmlspecialchars()'ed
	 * @todo Define visibility
	 */
	public function wrapArrayKey($label, $depth, $theValue) {
		// Protect label:
		$label = htmlspecialchars($label);
		// If varname is set:
		if ($this->varName && !$this->dontLinkVar) {
			$variableName = $this->varName . '[\'' . str_replace('.', '\'][\'', $depth) . '\'] = ' . (!\TYPO3\CMS\Core\Utility\MathUtility::canBeInterpretedAsInteger($theValue) ? '\'' . addslashes($theValue) . '\'' : $theValue) . '; ';
			$label = '<a href="' . htmlspecialchars((\TYPO3\CMS\Backend\Utility\BackendUtility::getModuleUrl(\TYPO3\CMS\Core\Utility\GeneralUtility::_GP('M')) . '&varname=' . urlencode($variableName))) . '#varname">' . $label . '</a>';
		}
		// Return:
		return '[' . $label . ']';
	}

	/**
	 * Creates an array with "depthKeys" which will expand the array to show the search results
	 *
	 * @param array $keyArr The array to search for the value
	 * @param string $depth_in Depth string - blank for first call (will build up during recursive calling creating an id of the position: [key1].[key2].[key3]
	 * @param string $searchString The string to search for
	 * @param array $keyArray Key array, for first call pass empty array
	 * @return array
	 * @todo Define visibility
	 */
	public function getSearchKeys($keyArr, $depth_in, $searchString, $keyArray) {
		$c = count($keyArr);
		if ($depth_in) {
			$depth_in = $depth_in . '.';
		}
		foreach ($keyArr as $key => $value) {
			$depth = $depth_in . $key;
			$deeper = is_array($keyArr[$key]);
			if ($this->regexMode) {
				if (preg_match('/' . $searchString . '/', $keyArr[$key]) || $this->searchKeysToo && preg_match('/' . $searchString . '/', $key)) {
					$this->searchKeys[$depth] = 1;
				}
			} else {
				if (!$deeper && stristr($keyArr[$key], $searchString) || $this->searchKeysToo && stristr($key, $searchString)) {
					$this->searchKeys[$depth] = 1;
				}
			}
			if ($deeper) {
				$cS = count($this->searchKeys);
				$keyArray = $this->getSearchKeys($keyArr[$key], $depth, $searchString, $keyArray);
				if ($cS != count($this->searchKeys)) {
					$keyArray[$depth] = 1;
				}
			}
		}
		return $keyArray;
	}

	/**
	 * Fixed length function
	 *
	 * @param string $string String to process
	 * @param integer $chars Max number of chars
	 * @return string Processed string
	 * @todo Define visibility
	 */
	public function fixed_lgd($string, $chars) {
		if ($chars >= 4) {
			if (strlen($string) > $chars) {
				return substr($string, 0, ($chars - 3)) . '...';
			}
		}
		return $string;
	}

	/**
	 * Function modifying the depthKey array
	 *
	 * @param array $arr Array with instructions to open/close nodes.
	 * @param array $settings Input depth_key array
	 * @return array Output depth_key array with entries added/removed based on $arr
	 * @see SC_mod_tools_config_index::main()
	 * @todo Define visibility
	 */
	public function depthKeys($arr, $settings) {
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

}


?>