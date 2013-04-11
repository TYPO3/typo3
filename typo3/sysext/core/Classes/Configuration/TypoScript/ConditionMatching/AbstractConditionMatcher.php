<?php
namespace TYPO3\CMS\Core\Configuration\TypoScript\ConditionMatching;

/***************************************************************
 *  Copyright notice
 *
 *  (c) 2009-2013 Oliver Hader <oliver@typo3.org>
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
 * Matching TypoScript conditions
 *
 * Used with the TypoScript parser.
 * Matches browserinfo, IPnumbers for use with templates
 *
 * @author Oliver Hader <oliver@typo3.org>
 */
abstract class AbstractConditionMatcher {

	/**
	 * Id of the current page.
	 *
	 * @var 	integer
	 */
	protected $pageId;

	/**
	 * The rootline for the current page.
	 *
	 * @var 	array
	 */
	protected $rootline;

	/**
	 * Whether to simulate the behaviour and match all conditions
	 * (used in TypoScript object browser).
	 *
	 * @var 	boolean
	 */
	protected $simulateMatchResult = FALSE;

	/**
	 * Whether to simulat the behaviour and match specific conditions
	 * (used in TypoScript object browser).
	 *
	 * @var 	array
	 */
	protected $simulateMatchConditions = array();

	/**
	 * Sets the id of the page to evaluate conditions for.
	 *
	 * @param integer $pageId Id of the page (must be positive)
	 * @return void
	 */
	public function setPageId($pageId) {
		if (is_integer($pageId) && $pageId > 0) {
			$this->pageId = $pageId;
		}
	}

	/**
	 * Gets the id of the page to evaluate conditions for.
	 *
	 * @return integer Id of the page
	 */
	public function getPageId() {
		return $this->pageId;
	}

	/**
	 * Sets the rootline.
	 *
	 * @param array $rootline The rootline to be used for matching (must have elements)
	 * @return void
	 */
	public function setRootline(array $rootline) {
		if (count($rootline)) {
			$this->rootline = $rootline;
		}
	}

	/**
	 * Gets the rootline.
	 *
	 * @return array The rootline to be used for matching
	 */
	public function getRootline() {
		return $this->rootline;
	}

	/**
	 * Sets whether to simulate the behaviour and match all conditions.
	 *
	 * @param boolean $simulateMatchResult Whether to simulate positive matches
	 * @return void
	 */
	public function setSimulateMatchResult($simulateMatchResult) {
		if (is_bool($simulateMatchResult)) {
			$this->simulateMatchResult = $simulateMatchResult;
		}
	}

	/**
	 * Sets whether to simulate the behaviour and match specific conditions.
	 *
	 * @param array $simulateMatchConditions Conditions to simulate a match for
	 * @return void
	 */
	public function setSimulateMatchConditions(array $simulateMatchConditions) {
		$this->simulateMatchConditions = $simulateMatchConditions;
	}

	/**
	 * Normalizes an expression and removes the first and last square bracket.
	 * + OR normalization: "...]OR[...", "...]||[...", "...][..." --> "...]||[..."
	 * + AND normalization: "...]AND[...", "...]&&[..."		   --> "...]&&[..."
	 *
	 * @param string $expression The expression to be normalized (e.g. "[A] && [B] OR [C]")
	 * @return string The normalized expression (e.g. "[A]&&[B]||[C]")
	 */
	protected function normalizeExpression($expression) {
		$normalizedExpression = preg_replace(array(
			'/\\]\\s*(OR|\\|\\|)?\\s*\\[/i',
			'/\\]\\s*(AND|&&)\\s*\\[/i'
		), array(
			']||[',
			']&&['
		), trim($expression));
		return $normalizedExpression;
	}

	/**
	 * Matches a TypoScript condition expression.
	 *
	 * @param string $expression The expression to match
	 * @return boolean Whether the expression matched
	 */
	public function match($expression) {
		// Return directly if result should be simulated:
		if ($this->simulateMatchResult) {
			return $this->simulateMatchResult;
		}
		// Return directly if matching for specific condition is simulated only:
		if (count($this->simulateMatchConditions)) {
			return in_array($expression, $this->simulateMatchConditions);
		}
		// Sets the current pageId if not defined yet:
		if (!isset($this->pageId)) {
			$this->pageId = $this->determinePageId();
		}
		// Sets the rootline if not defined yet:
		if (!isset($this->rootline)) {
			$this->rootline = $this->determineRootline();
		}
		$result = FALSE;
		$normalizedExpression = $this->normalizeExpression($expression);
		// First and last character must be square brackets (e.g. "[A]&&[B]":
		if (substr($normalizedExpression, 0, 1) === '[' && substr($normalizedExpression, -1) === ']') {
			$innerExpression = substr($normalizedExpression, 1, -1);
			$orParts = explode(']||[', $innerExpression);
			foreach ($orParts as $orPart) {
				$andParts = explode(']&&[', $orPart);
				foreach ($andParts as $andPart) {
					$result = $this->evaluateCondition($andPart);
					// If condition in AND context fails, the whole block is FALSE:
					if ($result === FALSE) {
						break;
					}
				}
				// If condition in OR context succeeds, the whole expression is TRUE:
				if ($result === TRUE) {
					break;
				}
			}
		}
		return $result;
	}

	/**
	 * Evaluates a TypoScript condition given as input, eg. "[browser=net][...(other conditions)...]"
	 *
	 * @param string $key The condition to match against its criterias.
	 * @param string $value
	 * @return mixed Returns TRUE or FALSE based on the evaluation
	 */
	protected function evaluateConditionCommon($key, $value) {
		if (\TYPO3\CMS\Core\Utility\GeneralUtility::inList('browser,version,system,useragent', strtolower($key))) {
			$browserInfo = $this->getBrowserInfo(\TYPO3\CMS\Core\Utility\GeneralUtility::getIndpEnv('HTTP_USER_AGENT'));
		}
		$keyParts = \TYPO3\CMS\Core\Utility\GeneralUtility::trimExplode('|', $key);
		switch ($keyParts[0]) {
		case 'browser':
			$values = \TYPO3\CMS\Core\Utility\GeneralUtility::trimExplode(',', $value, TRUE);
			// take all identified browsers into account, eg chrome deliver
			// webkit=>532.5, chrome=>4.1, safari=>532.5
			// so comparing string will be
			// "webkit532.5 chrome4.1 safari532.5"
			$all = '';
			foreach ($browserInfo['all'] as $key => $value) {
				$all .= $key . $value . ' ';
			}
			foreach ($values as $test) {
				if (stripos($all, $test) !== FALSE) {
					return TRUE;
				}
			}
			break;
		case 'version':
			$values = \TYPO3\CMS\Core\Utility\GeneralUtility::trimExplode(',', $value, TRUE);
			foreach ($values as $test) {
				if (strcspn($test, '=<>') == 0) {
					switch (substr($test, 0, 1)) {
					case '=':
						if (doubleval(substr($test, 1)) == $browserInfo['version']) {
							return TRUE;
						}
						break;
					case '<':
						if (doubleval(substr($test, 1)) > $browserInfo['version']) {
							return TRUE;
						}
						break;
					case '>':
						if (doubleval(substr($test, 1)) < $browserInfo['version']) {
							return TRUE;
						}
						break;
					}
				} elseif (strpos(' ' . $browserInfo['version'], $test) == 1) {
					return TRUE;
				}
			}
			break;
		case 'system':
			$values = \TYPO3\CMS\Core\Utility\GeneralUtility::trimExplode(',', $value, TRUE);
			// Take all identified systems into account, e.g. mac for iOS, Linux
			// for android and Windows NT for Windows XP
			$allSystems .= ' ' . implode(' ', $browserInfo['all_systems']);
			foreach ($values as $test) {
				if (stripos($allSystems, $test) !== FALSE) {
					return TRUE;
				}
			}
			break;
		case 'device':
			if (!isset($this->deviceInfo)) {
				$this->deviceInfo = $this->getDeviceType(\TYPO3\CMS\Core\Utility\GeneralUtility::getIndpEnv('HTTP_USER_AGENT'));
			}
			$values = \TYPO3\CMS\Core\Utility\GeneralUtility::trimExplode(',', $value, TRUE);
			foreach ($values as $test) {
				if ($this->deviceInfo == $test) {
					return TRUE;
				}
			}
			break;
		case 'useragent':
			$test = trim($value);
			if (strlen($test)) {
				return $this->searchStringWildcard($browserInfo['useragent'], $test);
			}
			break;
		case 'language':
			$values = \TYPO3\CMS\Core\Utility\GeneralUtility::trimExplode(',', $value, TRUE);
			foreach ($values as $test) {
				if (preg_match('/^\\*.+\\*$/', $test)) {
					$allLanguages = preg_split('/[,;]/', \TYPO3\CMS\Core\Utility\GeneralUtility::getIndpEnv('HTTP_ACCEPT_LANGUAGE'));
					if (in_array(substr($test, 1, -1), $allLanguages)) {
						return TRUE;
					}
				} elseif (\TYPO3\CMS\Core\Utility\GeneralUtility::getIndpEnv('HTTP_ACCEPT_LANGUAGE') == $test) {
					return TRUE;
				}
			}
			break;
		case 'IP':
			if (\TYPO3\CMS\Core\Utility\GeneralUtility::cmpIP(\TYPO3\CMS\Core\Utility\GeneralUtility::getIndpEnv('REMOTE_ADDR'), $value)) {
				return TRUE;
			}
			break;
		case 'hostname':
			if (\TYPO3\CMS\Core\Utility\GeneralUtility::cmpFQDN(\TYPO3\CMS\Core\Utility\GeneralUtility::getIndpEnv('REMOTE_ADDR'), $value)) {
				return TRUE;
			}
			break;
		case 'hour':

		case 'minute':

		case 'month':

		case 'year':

		case 'dayofweek':

		case 'dayofmonth':

		case 'dayofyear':
			// In order to simulate time properly in templates.
			$theEvalTime = $GLOBALS['SIM_EXEC_TIME'];
			switch ($key) {
			case 'hour':
				$theTestValue = date('H', $theEvalTime);
				break;
			case 'minute':
				$theTestValue = date('i', $theEvalTime);
				break;
			case 'month':
				$theTestValue = date('m', $theEvalTime);
				break;
			case 'year':
				$theTestValue = date('Y', $theEvalTime);
				break;
			case 'dayofweek':
				$theTestValue = date('w', $theEvalTime);
				break;
			case 'dayofmonth':
				$theTestValue = date('d', $theEvalTime);
				break;
			case 'dayofyear':
				$theTestValue = date('z', $theEvalTime);
				break;
			}
			$theTestValue = intval($theTestValue);
			// comp
			$values = \TYPO3\CMS\Core\Utility\GeneralUtility::trimExplode(',', $value, TRUE);
			foreach ($values as $test) {
				if (\TYPO3\CMS\Core\Utility\MathUtility::canBeInterpretedAsInteger($test)) {
					$test = '=' . $test;
				}
				if ($this->compareNumber($test, $theTestValue)) {
					return TRUE;
				}
			}
			break;
		case 'compatVersion':
			return \TYPO3\CMS\Core\Utility\GeneralUtility::compat_version($value);
			break;
		case 'loginUser':
			if ($this->isUserLoggedIn()) {
				$values = \TYPO3\CMS\Core\Utility\GeneralUtility::trimExplode(',', $value, TRUE);
				foreach ($values as $test) {
					if ($test == '*' || !strcmp($this->getUserId(), $test)) {
						return TRUE;
					}
				}
			} elseif ($value === '') {
				return TRUE;
			}
			break;
		case 'page':
			if ($keyParts[1]) {
				$page = $this->getPage();
				$property = $keyParts[1];
				if (!empty($page) && isset($page[$property])) {
					if (strcmp($page[$property], $value) === 0) {
						return TRUE;
					}
				}
			}
			break;
		case 'globalVar':
			$values = \TYPO3\CMS\Core\Utility\GeneralUtility::trimExplode(',', $value, TRUE);
			foreach ($values as $test) {
				$point = strcspn($test, '!=<>');
				$theVarName = substr($test, 0, $point);
				$nv = $this->getVariable(trim($theVarName));
				$testValue = substr($test, $point);
				if ($this->compareNumber($testValue, $nv)) {
					return TRUE;
				}
			}
			break;
		case 'globalString':
			$values = \TYPO3\CMS\Core\Utility\GeneralUtility::trimExplode(',', $value, TRUE);
			foreach ($values as $test) {
				$point = strcspn($test, '=');
				$theVarName = substr($test, 0, $point);
				$nv = $this->getVariable(trim($theVarName));
				$testValue = substr($test, $point + 1);
				if ($this->searchStringWildcard($nv, trim($testValue))) {
					return TRUE;
				}
			}
			break;
		case 'userFunc':
			$values = preg_split('/\\(|\\)/', $value);
			$funcName = trim($values[0]);
			$funcValue = \TYPO3\CMS\Core\Utility\GeneralUtility::trimExplode(',', $values[1]);
			if (function_exists($funcName) && call_user_func($funcName, $funcValue[0])) {
				return TRUE;
			}
			break;
		}
		return NULL;
	}

	/**
	 * Get variable common
	 *
	 * @param array $vars
	 * @return mixed Whatever value. If none, then NULL.
	 */
	protected function getVariableCommon(array $vars) {
		$value = NULL;
		if (count($vars) == 1) {
			$value = $this->getGlobal($vars[0]);
		} else {
			$splitAgain = explode('|', $vars[1], 2);
			$k = trim($splitAgain[0]);
			if ($k) {
				switch ((string) trim($vars[0])) {
				case 'GP':
					$value = \TYPO3\CMS\Core\Utility\GeneralUtility::_GP($k);
					break;
				case 'ENV':
					$value = getenv($k);
					break;
				case 'IENV':
					$value = \TYPO3\CMS\Core\Utility\GeneralUtility::getIndpEnv($k);
					break;
				case 'LIT':
					return trim($vars[1]);
					break;
				default:
					return NULL;
				}
				// If array:
				if (count($splitAgain) > 1) {
					if (is_array($value) && trim($splitAgain[1])) {
						$value = $this->getGlobal($splitAgain[1], $value);
					} else {
						$value = '';
					}
				}
			}
		}
		return $value;
	}

	/**
	 * Evaluates a $leftValue based on an operator: "<", ">", "<=", ">=", "!=" or "="
	 *
	 * @param string $test The value to compare with on the form [operator][number]. Eg. "< 123
	 * @param integer $leftValue The value on the left side
	 * @return boolean If $value is "50" and $test is "< 123" then it will return TRUE.
	 */
	protected function compareNumber($test, $leftValue) {
		if (preg_match('/^(!?=+|<=?|>=?)\\s*([^\\s]*)\\s*$/', $test, $matches)) {
			$operator = $matches[1];
			$rightValue = $matches[2];
			switch ($operator) {
			case '>=':
				return $leftValue >= doubleval($rightValue);
				break;
			case '<=':
				return $leftValue <= doubleval($rightValue);
				break;
			case '!=':
				// multiple values may be split with '|'
				// see if none matches ("not in list")
				$found = FALSE;
				$rightValueParts = \TYPO3\CMS\Core\Utility\GeneralUtility::trimExplode('|', $rightValue);
				foreach ($rightValueParts as $rightValueSingle) {
					if ($leftValue === doubleval($rightValueSingle)) {
						$found = TRUE;
						break;
					}
				}
				return $found === FALSE;
				break;
			case '<':
				return $leftValue < doubleval($rightValue);
				break;
			case '>':
				return $leftValue > doubleval($rightValue);
				break;
			default:
				// nothing valid found except '=', use '='
				// multiple values may be split with '|'
				// see if one matches ("in list")
				$found = FALSE;
				$rightValueParts = \TYPO3\CMS\Core\Utility\GeneralUtility::trimExplode('|', $rightValue);
				foreach ($rightValueParts as $rightValueSingle) {
					if ($leftValue == $rightValueSingle) {
						$found = TRUE;
						break;
					}
				}
				return $found;
				break;
			}
		}
		return FALSE;
	}

	/**
	 * Matching two strings against each other, supporting a "*" wildcard or (if wrapped in "/") PCRE regular expressions
	 *
	 * @param string $haystack The string in which to find $needle.
	 * @param string $needle The string to find in $haystack
	 * @return boolean Returns TRUE if $needle matches or is found in (according to wildcards) in $haystack. Eg. if $haystack is "Netscape 6.5" and $needle is "Net*" or "Net*ape" then it returns TRUE.
	 */
	protected function searchStringWildcard($haystack, $needle) {
		$result = FALSE;
		if ($needle) {
			if (preg_match('/^\\/.+\\/$/', $needle)) {
				// Regular expression, only "//" is allowed as delimiter
				$regex = $needle;
			} else {
				$needle = str_replace(array('*', '?'), array('###MANY###', '###ONE###'), $needle);
				$regex = '/^' . preg_quote($needle, '/') . '$/';
				// Replace the marker with .* to match anything (wildcard)
				$regex = str_replace(array('###MANY###', '###ONE###'), array('.*', '.'), $regex);
			}
			$result = (bool) preg_match($regex, ((string) $haystack));
		}
		return $result;
	}

	/**
	 * Generates an array with abstracted browser information
	 *
	 * @param string $userAgent The useragent string, \TYPO3\CMS\Core\Utility\GeneralUtility::getIndpEnv('HTTP_USER_AGENT')
	 * @return array Contains keys "browser", "version", "system
	 */
	protected function getBrowserInfo($userAgent) {
		return \TYPO3\CMS\Core\Utility\ClientUtility::getBrowserInfo($userAgent);
	}

	/**
	 * Gets a code for a browsing device based on the input useragent string.
	 *
	 * @param string $userAgent The useragent string, \TYPO3\CMS\Core\Utility\GeneralUtility::getIndpEnv('HTTP_USER_AGENT')
	 * @return string Code for the specific device type
	 */
	protected function getDeviceType($userAgent) {
		return \TYPO3\CMS\Core\Utility\ClientUtility::getDeviceType($userAgent);
	}

	/**
	 * Return global variable where the input string $var defines array keys separated by "|"
	 * Example: $var = "HTTP_SERVER_VARS | something" will return the value $GLOBALS['HTTP_SERVER_VARS']['something'] value
	 *
	 * @param string $var Global var key, eg. "HTTP_GET_VAR" or "HTTP_GET_VARS|id" to get the GET parameter "id" back.
	 * @param array $source Alternative array than $GLOBAL to get variables from.
	 * @return mixed Whatever value. If none, then blank string.
	 */
	protected function getGlobal($var, $source = NULL) {
		$vars = explode('|', $var);
		$c = count($vars);
		$k = trim($vars[0]);
		$theVar = isset($source) ? $source[$k] : $GLOBALS[$k];
		for ($a = 1; $a < $c; $a++) {
			if (!isset($theVar)) {
				break;
			}
			$key = trim($vars[$a]);
			if (is_object($theVar)) {
				$theVar = $theVar->{$key};
			} elseif (is_array($theVar)) {
				$theVar = $theVar[$key];
			} else {
				return '';
			}
		}
		if (!is_array($theVar) && !is_object($theVar)) {
			return $theVar;
		} else {
			return '';
		}
	}

	/**
	 * Evaluates a TypoScript condition given as input, eg. "[browser=net][...(other conditions)...]"
	 *
	 * @param string $string The condition to match against its criterias.
	 * @return boolean Whether the condition matched
	 * @see \TYPO3\CMS\Core\TypoScript\Parser\TypoScriptParser::parse()
	 */
	abstract protected function evaluateCondition($string);

	/**
	 * Gets the value of a variable.
	 *
	 * Examples of names:
	 * + TSFE:id
	 * + GP:firstLevel|secondLevel
	 * + _GET|firstLevel|secondLevel
	 * + LIT:someLiteralValue
	 *
	 * @param string $name The name of the variable to fetch the value from
	 * @return mixed The value of the given variable (string) or NULL if variable did not exist
	 */
	abstract protected function getVariable($name);

	/**
	 * Gets the usergroup list of the current user.
	 *
	 * @return string The usergroup list of the current user
	 */
	abstract protected function getGroupList();

	/**
	 * Determines the current page Id.
	 *
	 * @return integer The current page Id
	 */
	abstract protected function determinePageId();

	/**
	 * Gets the properties for the current page.
	 *
	 * @return array The properties for the current page.
	 */
	abstract protected function getPage();

	/**
	 * Determines the rootline for the current page.
	 *
	 * @return array The rootline for the current page.
	 */
	abstract protected function determineRootline();

	/**
	 * Gets the id of the current user.
	 *
	 * @return integer The id of the current user
	 */
	abstract protected function getUserId();

	/**
	 * Determines if a user is logged in.
	 *
	 * @return boolean Determines if a user is logged in
	 */
	abstract protected function isUserLoggedIn();

	/**
	 * Sets a log message.
	 *
	 * @param string $message The log message to set/write
	 * @return void
	 */
	abstract protected function log($message);

}


?>