<?php
/***************************************************************
 *  Copyright notice
 *
 *  (c) 1999-2011 Kasper Skårhøj (kasperYYYY@typo3.com)
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
 * @author	Kasper Skårhøj <kasperYYYY@typo3.com>
 */


/**
 * Class for displaying an array as a tree
 * See the extension 'lowlevel' /config (Backend module 'Tools > Configuration')
 *
 * @author	Kasper Skårhøj <kasperYYYY@typo3.com>
 * @package TYPO3
 * @subpackage t3lib
 * @see SC_mod_tools_config_index::main()
 */
class t3lib_arrayBrowser {
	var $expAll = FALSE; // If set, will expand all (depthKeys is obsolete then) (and no links are applied)
	var $dontLinkVar = FALSE; // If set, the variable keys are not linked.
	var $depthKeys = array(); // Array defining which keys to expand. Typically set from outside from some session variable - otherwise the array will collapse.
	var $searchKeys = array(); // After calling the getSearchKeys function this array is populated with the key-positions in the array which contains values matching the search.
	var $fixedLgd = 1; // If set, the values are truncated with "..." appended if longer than a certain length.
	var $regexMode = 0; // If set, search for string with regex, otherwise stristr()
	var $searchKeysToo = FALSE; // If set, array keys are subject to the search too.
	var $varName = ''; // Set var name here if you want links to the variable name.

	/**
	 * Make browsable tree
	 * Before calling this function you may want to set some of the internal vars like depthKeys, regexMode and fixedLgd. For examples see SC_mod_tools_config_index::main()
	 *
	 * @param	array		The array to display
	 * @param	string		Key-position id. Build up during recursive calls - [key1].[key2].[key3] - an so on.
	 * @param	string		Depth-data - basically a prefix for the icons. For calling this function from outside, let it stay blank.
	 * @return	string		HTML for the tree
	 * @see SC_mod_tools_config_index::main()
	 */
	function tree($arr, $depth_in, $depthData) {
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

			$deeper = (is_array($arr[$key]) && ($this->depthKeys[$depth] || $this->expAll)) ? 1 : 0;
			$PM = 'join';
			$LN = ($a == $c) ? 'blank' : 'line';
			$BTM = ($a == $c) ? 'bottom' : '';
			$PM = is_array($arr[$key]) ? ($deeper ? 'minus' : 'plus') : 'join';


			$HTML .= $depthData;
			$theIcon = '<img' . t3lib_iconWorks::skinImg($GLOBALS['BACK_PATH'], 'gfx/ol/' . $PM . $BTM . '.gif', 'width="18" height="16"') .
				' align="top" border="0" alt="" />';
			if ($PM == 'join') {
				$HTML .= $theIcon;
			} else {
				$HTML .=
						($this->expAll ? '' : '<a id="' . $goto . '" href="' . htmlspecialchars('index.php?node[' .
								$depth . ']=' . ($deeper ? 0 : 1) . '#' . $goto) . '">') .
								$theIcon .
								($this->expAll ? '' : '</a>');
			}

			$label = $key;
			$HTML .= $this->wrapArrayKey($label, $depth, !is_array($arr[$key]) ? $arr[$key] : '');

			if (!is_array($arr[$key])) {
				$theValue = $arr[$key];
				if ($this->fixedLgd) {
					$imgBlocks = ceil(1 + strlen($depthData) / 77);
						//					debug($imgBlocks);
					$lgdChars = 68 - ceil(strlen('[' . $key . ']') * 0.8) - $imgBlocks * 3;
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
				$HTML .= $this->tree($arr[$key], $depth, $depthData . '<img' .
					t3lib_iconWorks::skinImg($GLOBALS['BACK_PATH'], 'gfx/ol/' . $LN . '.gif', 'width="18" height="16"') .
					' align="top" alt="" />');
			}
		}
		return $HTML;
	}

	/**
	 * Wrapping the value in bold tags etc.
	 *
	 * @param	string		The title string
	 * @param	string		Depth path
	 * @return	string		Title string, htmlspecialchars()'ed
	 */
	function wrapValue($theValue, $depth) {
		$wrappedValue = '';
		if (strlen($theValue) > 0) {
			$wrappedValue = '<strong>' . htmlspecialchars($theValue) . '</strong>';
		}
		return $wrappedValue;
	}

	/**
	 * Wrapping the value in bold tags etc.
	 *
	 * @param	string		The title string
	 * @param	string		Depth path
	 * @param	string		The value for the array entry.
	 * @return	string		Title string, htmlspecialchars()'ed
	 */
	function wrapArrayKey($label, $depth, $theValue) {

			// Protect label:
		$label = htmlspecialchars($label);

			// If varname is set:
		if ($this->varName && !$this->dontLinkVar) {
			$variableName = $this->varName . '[\'' . str_replace('.', '\'][\'', $depth) . '\'] = ' .
				(!t3lib_div::testInt($theValue) ? '\'' . addslashes($theValue) . '\'' : $theValue) . '; ';
			$label = '<a href="index.php?varname=' . urlencode($variableName) . '#varname">' . $label . '</a>';
		}

			// Return:
		return '[' . $label . ']';
	}

	/**
	 * Creates an array with "depthKeys" which will expand the array to show the search results
	 *
	 * @param	array		The array to search for the value
	 * @param	string		Depth string - blank for first call (will build up during recursive calling creating an id of the position: [key1].[key2].[key3]
	 * @param	string		The string to search for
	 * @param	array		Key array, for first call pass empty array
	 * @return	array
	 */
	function getSearchKeys($keyArr, $depth_in, $searchString, $keyArray) {
		$c = count($keyArr);
		if ($depth_in) {
			$depth_in = $depth_in . '.';
		}
		foreach ($keyArr as $key => $value) {
			$depth = $depth_in . $key;
			$deeper = is_array($keyArr[$key]);

			if ($this->regexMode) {
				if (preg_match('/' . $searchString . '/', $keyArr[$key]) || ($this->searchKeysToo && preg_match('/' . $searchString . '/', $key))) {
					$this->searchKeys[$depth] = 1;
				}
			} else {
				if ((!$deeper && stristr($keyArr[$key], $searchString)) || ($this->searchKeysToo && stristr($key, $searchString))) {
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
	 * @param	string		String to process
	 * @param	integer		Max number of chars
	 * @return	string		Processed string
	 */
	function fixed_lgd($string, $chars) {
		if ($chars >= 4) {
			if (strlen($string) > $chars) {
				return substr($string, 0, $chars - 3) . '...';
			}
		}
		return $string;
	}

	/**
	 * Function modifying the depthKey array
	 *
	 * @param	array		Array with instructions to open/close nodes.
	 * @param	array		Input depth_key array
	 * @return	array		Output depth_key array with entries added/removed based on $arr
	 * @see SC_mod_tools_config_index::main()
	 */
	function depthKeys($arr, $settings) {
		$tsbrArray = array();
		foreach ($arr as $theK => $theV) {
			$theKeyParts = explode('.', $theK);
			$depth = '';
			$c = count($theKeyParts);
			$a = 0;
			foreach ($theKeyParts as $p) {
				$a++;
				$depth .= ($depth ? '.' : '') . $p;
				$tsbrArray[$depth] = ($c == $a) ? $theV : 1;
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

if (defined('TYPO3_MODE') && isset($GLOBALS['TYPO3_CONF_VARS'][TYPO3_MODE]['XCLASS']['t3lib/class.t3lib_arraybrowser.php'])) {
	include_once($GLOBALS['TYPO3_CONF_VARS'][TYPO3_MODE]['XCLASS']['t3lib/class.t3lib_arraybrowser.php']);
}
?>