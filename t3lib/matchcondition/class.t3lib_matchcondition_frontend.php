<?php
/***************************************************************
*  Copyright notice
*
*  (c) 2009-2010 Oliver Hader <oliver@typo3.org>
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
 * Matching TypoScript conditions for frontend disposal.
 *
 * Used with the TypoScript parser.
 * Matches browserinfo, IPnumbers for use with templates
 *
 * @author	Kasper Skaarhoj <kasperYYYY@typo3.com>
 * @package TYPO3
 * @subpackage t3lib
 */
class t3lib_matchCondition_frontend extends t3lib_matchCondition_abstract {
	/**
	 * @var	array
	 */
	protected $deprecatedHooks = array();

	/**
	 * Constructor for this class
	 *
	 * @return	void
	 */
	public function __construct() {
		$this->initializeDeprecatedHooks();
	}

	/**
	 * Initializes deprectated hooks that existed in t3lib_matchCondition until TYPO3 4.3.
	 *
	 * @return	void
	 */
	protected function initializeDeprecatedHooks() {
			// Hook: $TYPO3_CONF_VARS['SC_OPTIONS']['t3lib/class.t3lib_matchcondition.php']['matchConditionClass']:
		$matchConditionHooks =& $GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS']['t3lib/class.t3lib_matchcondition.php']['matchConditionClass'];
		if (is_array($matchConditionHooks)) {
			t3lib_div::deprecationLog(
				'The hook $TYPO3_CONF_VARS[SC_OPTIONS][t3lib/class.t3lib_matchcondition.php][matchConditionClass] ' .
				'is deprecated since TYPO3 4.3. Use the new hooks getBrowserInfo and getDeviceType in ' .
				't3lib_utility_Client instead.'
			);

			foreach ($matchConditionHooks as $hookClass) {
				$this->deprecatedHooks[] = t3lib_div::getUserObj($hookClass, '');
			}
		}
	}

	/**
	 * Evaluates a TypoScript condition given as input, eg. "[browser=net][...(other conditions)...]"
	 *
	 * @param	string		$string: The condition to match against its criterias.
	 * @return	boolean		Whether the condition matched
	 * @see t3lib_tsparser::parse()
	 */
	protected function evaluateCondition($string) {
		list($key, $value) = t3lib_div::trimExplode('=', $string, false, 2);

		$result = parent::evaluateConditionCommon($key, $value);

		if (is_bool($result)) {
			return $result;
		} else {
			switch ($key) {
				case 'usergroup':
					$groupList = $this->getGroupList();
					if ($groupList != '0,-1') {		// '0,-1' is the default usergroups when not logged in!
						$values = t3lib_div::trimExplode(',', $value, true);
						foreach ($values as $test) {
							if ($test == '*' || t3lib_div::inList($groupList, $test)) {
								return true;
							}
						}
					}
				break;
				case 'treeLevel':
					$values = t3lib_div::trimExplode(',', $value, true);
					$treeLevel = count($this->rootline) - 1;
					foreach ($values as $test) {
						if ($test == $treeLevel) {
							return true;
						}
					}
				break;
				case 'PIDupinRootline':
				case 'PIDinRootline':
					$values = t3lib_div::trimExplode(',', $value, true);
					if (($key=='PIDinRootline') || (!in_array($this->pageId, $values))) {
						foreach ($values as $test) {
							foreach ($this->rootline as $rl_dat) {
								if ($rl_dat['uid'] == $test) {
									return true;
								}
							}
						}
					}
				break;
			}
		}

		return false;
	}

	/**
	 * Generates an array with abstracted browser information
	 *
	 * @param	string		$userAgent: The useragent string, t3lib_div::getIndpEnv('HTTP_USER_AGENT')
	 * @return	array		Contains keys "browser", "version", "system"
	 */
	protected function getBrowserInfo($userAgent) {
			// Exceute deprecated hooks:
			// @deprecated since TYPO3 4.3
		foreach($this->deprecatedHooks as $hookObj) {
			if (method_exists($hookObj, 'browserInfo')) {
				$result = $hookObj->browserInfo($userAgent);
				if (strlen($result)) {
					return $result;
				}
			}
		}

		return parent::getBrowserInfo($userAgent);
	}

	/**
	 * Gets a code for a browsing device based on the input useragent string.
	 *
	 * @param	string		$userAgent: The useragent string, t3lib_div::getIndpEnv('HTTP_USER_AGENT')
	 * @return	string		Code for the specific device type
	 */
	protected function getDeviceType($userAgent) {
			// Exceute deprecated hooks:
			// @deprecated since TYPO3 4.3
		foreach($this->deprecatedHooks as $hookObj) {
			if (method_exists($hookObj, 'whichDevice')) {
				$result = $hookObj->whichDevice($userAgent);
				if (strlen($result)) {
					return $result;
				}
			}
		}

		// deprecated, see above
		if (is_array($GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS']['t3lib/class.t3lib_matchcondition.php']['devices_class'])) {
			foreach($GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS']['t3lib/class.t3lib_matchcondition.php']['devices_class'] as $_classRef) {
				$_procObj = t3lib_div::getUserObj($_classRef);
				return $_procObj->whichDevice_ext($useragent);
			}
		}

		return parent::getDeviceType($userAgent);
	}

	/**
	 * Returns GP / ENV / TSFE vars
	 *
	 * @param	string		Identifier
	 * @return	mixed		The value of the variable pointed to.
	 */
	protected function getVariable($var) {
		$vars = explode(':', $var, 2);

		$val = parent::getVariableCommon($vars);

		if (is_null($val)) {
			$splitAgain=explode('|', $vars[1], 2);
			$k = trim($splitAgain[0]);
			if ($k) {
				switch((string)trim($vars[0])) {
					case 'TSFE':
						$val = $this->getGlobal('TSFE|' . $vars[1]);
					break;
				}
			}
		}

		return $val;
	}

	/**
	 * Get the usergroup list of the current user.
	 *
	 * @return	string		The usergroup list of the current user
	 */
	protected function getGroupList() {
		$groupList = $GLOBALS['TSFE']->gr_list;
		return $groupList;
	}

	/**
	 * Determines the current page Id.
	 *
	 * @return	integer		The current page Id
	 */
	protected function determinePageId() {
		return (int)$GLOBALS['TSFE']->id;
	}

	/**
	 * Determines the rootline for the current page.
	 *
	 * @return	array		The rootline for the current page.
	 */
	protected function determineRootline() {
		$rootline = (array)$GLOBALS['TSFE']->tmpl->rootLine;
		return $rootline;
	}

	/**
	 * Get prefix for user functions (normally 'user_').
	 *
	 * @return	string		The prefix for user functions (normally 'user_').
	 */
	protected function getUserFuncClassPrefix() {
		$userFuncClassPrefix = $GLOBALS['TSFE']->TYPO3_CONF_VARS['FE']['userFuncClassPrefix'];
		return $userFuncClassPrefix;
	}

	/**
	 * Get the id of the current user.
	 *
	 * @return	integer		The id of the current user
	 */
	protected function getUserId() {
		$userId = $GLOBALS['TSFE']->fe_user->user['uid'];
		return $userId;
	}

	/**
	 * Determines if a user is logged in.
	 *
	 * @return	boolean		Determines if a user is logged in
	 */
	protected function isUserLoggedIn() {
		$userLoggedIn = false;
		if ($GLOBALS['TSFE']->loginUser) {
			$userLoggedIn = true;
		}
		return $userLoggedIn;
	}

	/**
	 * Set/write a log message.
	 *
	 * @param	string		$message: The log message to set/write
	 * @return	void
	 */
	protected function log($message) {
		if (is_object($GLOBALS['TT'])) {
			$GLOBALS['TT']->setTSlogMessage($message,3);
		}
	}
}


if (defined('TYPO3_MODE') && $TYPO3_CONF_VARS[TYPO3_MODE]['XCLASS']['t3lib/matchcondition/class.t3lib_matchcondition_frontend.php'])	{
	include_once($TYPO3_CONF_VARS[TYPO3_MODE]['XCLASS']['t3lib/matchcondition/class.t3lib_matchcondition_frontend.php']);
}

?>