<?php
/***************************************************************
 *  Copyright notice
 *
 *  (c) 1999-2010 Kasper Skårhøj (kasperYYYY@typo3.com)
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
 * Contains class for Matching TypoScript conditions
 *
 * $Id$
 * Revised for TYPO3 3.6 July/2003 by Kasper Skårhøj
 *
 * @author	Kasper Skårhøj <kasperYYYY@typo3.com>
 */
/**
 * [CLASS/FUNCTION INDEX of SCRIPT]
 *
 *
 *
 *   80: class t3lib_matchCondition
 *   87:	 function __construct()
 *  105:	 function t3lib_matchCondition()
 *  115:	 function match($condition_line)
 *  160:	 function evalConditionStr($string)
 *  381:	 function testNumber($test,$value)
 *  405:	 function matchWild($haystack,$needle)
 *  429:	 function whichDevice($useragent)
 *  498:	 function browserInfo($useragent)
 *  611:	 function browserInfo_version($tmp)
 *  624:	 function getGlobal($var, $source=NULL)
 *  658:	 function getGP_ENV_TSFE($var)
 *
 * TOTAL FUNCTIONS: 11
 * (This index is automatically created/updated by the extension "extdeveval")
 *
 */


/**
 * Matching TypoScript conditions
 *
 * Used with the TypoScript parser.
 * Matches browserinfo, IPnumbers for use with templates
 *
 * @author	Kasper Skårhøj <kasperYYYY@typo3.com>
 * @package TYPO3
 * @subpackage t3lib
 * @see t3lib_TStemplate::matching(), t3lib_TStemplate::generateConfig()
 */
class t3lib_matchCondition extends t3lib_matchCondition_frontend {
	var $matchAlternative = array(); // If this array has elements, the matching returns true if a whole "matchline" is found in the array!
	var $matchAll = 0; // If set all is matched!

	var $altRootLine = array();
	var $hookObjectsArr = array();

	/**
	 * Constructor for this class
	 *
	 * @return	void
	 * @deprecated	since TYPO3 4.3, will be removed in TYPO3 4.6 - The functionality was moved to t3lib_matchCondition_frontend
	 */
	function __construct() {
		t3lib_div::logDeprecatedFunction();

		parent::__construct();
	}

	/**
	 * Matching TS condition
	 *
	 * @param	string		Line to match
	 * @return	boolean		True if matched
	 */
	function match($condition_line) {
		if ($this->matchAll) {
			parent::setSimulateMatchResult(TRUE);
		}
		if (count($this->matchAlternative)) {
			parent::setSimulateMatchConditions($this->matchAlternative);
		}

		return parent::match($condition_line);
	}


	/**
	 * Evaluates a TypoScript condition given as input, eg. "[browser=net][...(other conditions)...]"
	 *
	 * @param	string		The condition to match against its criterias.
	 * @return	boolean		Returns true or false based on the evaluation.
	 * @see t3lib_tsparser::parse()
	 * @link http://typo3.org/doc.0.html?&tx_extrepmgm_pi1[extUid]=270&tx_extrepmgm_pi1[tocEl]=292&cHash=c6c7d43d2f
	 */
	function evalConditionStr($string) {
		return parent::evaluateCondition($string);
	}

	/**
	 * Evaluates a $leftValue based on an operator: "<", ">", "<=", ">=", "!=" or "="
	 *
	 * @param	string		$test: The value to compare with on the form [operator][number]. Eg. "< 123"
	 * @param	integer		$leftValue: The value on the left side
	 * @return	boolean		If $value is "50" and $test is "< 123" then it will return true.
	 */
	function testNumber($test, $leftValue) {
		return parent::compareNumber($test, $leftValue);
	}

	/**
	 * Matching two strings against each other, supporting a "*" wildcard or (if wrapped in "/") PCRE regular expressions
	 *
	 * @param	string		The string in which to find $needle.
	 * @param	string		The string to find in $haystack
	 * @return	boolean		Returns true if $needle matches or is found in (according to wildcards) in $haystack. Eg. if $haystack is "Netscape 6.5" and $needle is "Net*" or "Net*ape" then it returns true.
	 */
	function matchWild($haystack, $needle) {
		return parent::searchStringWildcard($haystack, $needle);
	}

	/**
	 * Returns a code for a browsing device based on the input useragent string
	 *
	 * @param	string		User agent string from browser, t3lib_div::getIndpEnv('HTTP_USER_AGENT')
	 * @return	string		A code. See link.
	 * @access private
	 * @link http://typo3.org/doc.0.html?&tx_extrepmgm_pi1[extUid]=270&tx_extrepmgm_pi1[tocEl]=296&cHash=a8ae66c7d6
	 */
	function whichDevice($useragent) {
		return parent::getDeviceType($useragent);
	}

	/**
	 * Generates an array with abstracted browser information
	 * This method is used in the function match() in this class
	 *
	 * @param	string		The useragent string, t3lib_div::getIndpEnv('HTTP_USER_AGENT')
	 * @return	array		Contains keys "browser", "version", "system"
	 * @access private
	 * @see match()
	 */
	function browserInfo($useragent) {
		return parent::getBrowserInfo($useragent);
	}

	/**
	 * Returns the version of a browser; Basically getting doubleval() of the input string, stripping of any non-numeric values in the beginning of the string first.
	 *
	 * @param	string		A string with version number, eg. "/7.32 blablabla"
	 * @return	double		Returns double value, eg. "7.32"
	 * @deprecated	since TYPO3 4.3, will be removed in TYPO3 4.6 - use t3lib_utility_Client::getVersion() instead
	 */
	function browserInfo_version($tmp) {
		t3lib_div::logDeprecatedFunction();
		return t3lib_utility_Client::getVersion($tmp);
	}

	/**
	 * Return global variable where the input string $var defines array keys separated by "|"
	 * Example: $var = "HTTP_SERVER_VARS | something" will return the value $GLOBALS['HTTP_SERVER_VARS']['something'] value
	 *
	 * @param	string		Global var key, eg. "HTTP_GET_VAR" or "HTTP_GET_VARS|id" to get the GET parameter "id" back.
	 * @param	array		Alternative array than $GLOBAL to get variables from.
	 * @return	mixed		Whatever value. If none, then blank string.
	 * @access private
	 */
	function getGlobal($var, $source = NULL) {
		return parent::getGlobal($var, $source);
	}

	/**
	 * Returns GP / ENV / TSFE vars
	 *
	 * @param	string		Identifier
	 * @return	mixed		The value of the variable pointed to.
	 * @access private
	 * @link http://typo3.org/doc.0.html?&tx_extrepmgm_pi1[extUid]=270&tx_extrepmgm_pi1[tocEl]=311&cHash=487cbd5cdf
	 */
	function getGP_ENV_TSFE($var) {
		return parent::getVariable($var);
	}
}


if (defined('TYPO3_MODE') && isset($GLOBALS['TYPO3_CONF_VARS'][TYPO3_MODE]['XCLASS']['t3lib/class.t3lib_matchcondition.php'])) {
	include_once($GLOBALS['TYPO3_CONF_VARS'][TYPO3_MODE]['XCLASS']['t3lib/class.t3lib_matchcondition.php']);
}

?>