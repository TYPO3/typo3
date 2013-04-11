<?php
namespace TYPO3\CMS\Core\TypoScript\Parser;

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
 * The TypoScript parser
 *
 * @author Kasper Skårhøj <kasperYYYY@typo3.com>
 */
class TypoScriptParser {

	// If set, then key names cannot contain characters other than [:alnum:]_\.-
	/**
	 * @todo Define visibility
	 */
	public $strict = 1;

	// Internal
	// TypoScript hierarchy being build during parsing.
	/**
	 * @todo Define visibility
	 */
	public $setup = array();

	// Raw data, the input string exploded by LF
	/**
	 * @todo Define visibility
	 */
	public $raw;

	// Pointer to entry in raw data array
	/**
	 * @todo Define visibility
	 */
	public $rawP;

	// Holding the value of the last comment
	/**
	 * @todo Define visibility
	 */
	public $lastComment = '';

	// Internally set, used as internal flag to create a multi-line comment (one of those like /*... */)
	/**
	 * @todo Define visibility
	 */
	public $commentSet = 0;

	// Internally set, when multiline value is accumulated
	/**
	 * @todo Define visibility
	 */
	public $multiLineEnabled = 0;

	// Internally set, when multiline value is accumulated
	/**
	 * @todo Define visibility
	 */
	public $multiLineObject = '';

	// Internally set, when multiline value is accumulated
	/**
	 * @todo Define visibility
	 */
	public $multiLineValue = array();

	// Internally set, when in brace. Counter.
	/**
	 * @todo Define visibility
	 */
	public $inBrace = 0;

	// For each condition this flag is set, if the condition is TRUE, else it's cleared. Then it's used by the [ELSE] condition to determine if the next part should be parsed.
	/**
	 * @todo Define visibility
	 */
	public $lastConditionTrue = 1;

	// Tracking all conditions found
	/**
	 * @todo Define visibility
	 */
	public $sections = array();

	// Tracking all matching conditions found
	/**
	 * @todo Define visibility
	 */
	public $sectionsMatch = array();

	// If set, then syntax highlight mode is on; Call the function syntaxHighlight() to use this function
	/**
	 * @todo Define visibility
	 */
	public $syntaxHighLight = 0;

	// Syntax highlight data is accumulated in this array. Used by syntaxHighlight_print() to construct the output.
	/**
	 * @todo Define visibility
	 */
	public $highLightData = array();

	// Syntax highlight data keeping track of the curly brace level for each line
	/**
	 * @todo Define visibility
	 */
	public $highLightData_bracelevel = array();

	// Debugging, analysis:
	// DO NOT register the comments. This is default for the ordinary sitetemplate!
	/**
	 * @todo Define visibility
	 */
	public $regComments = 0;

	// DO NOT register the linenumbers. This is default for the ordinary sitetemplate!
	/**
	 * @todo Define visibility
	 */
	public $regLinenumbers = 0;

	// Error accumulation array.
	/**
	 * @todo Define visibility
	 */
	public $errors = array();

	// Used for the error messages line number reporting. Set externally.
	/**
	 * @todo Define visibility
	 */
	public $lineNumberOffset = 0;

	// Line for break point.
	/**
	 * @todo Define visibility
	 */
	public $breakPointLN = 0;

	/**
	 * @todo Define visibility
	 */
	public $highLightStyles = array(
		'prespace' => array('<span class="ts-prespace">', '</span>'),
		// Space before any content on a line
		'objstr_postspace' => array('<span class="ts-objstr_postspace">', '</span>'),
		// Space after the object string on a line
		'operator_postspace' => array('<span class="ts-operator_postspace">', '</span>'),
		// Space after the operator on a line
		'operator' => array('<span class="ts-operator">', '</span>'),
		// The operator char
		'value' => array('<span class="ts-value">', '</span>'),
		// The value of a line
		'objstr' => array('<span class="ts-objstr">', '</span>'),
		// The object string of a line
		'value_copy' => array('<span class="ts-value_copy">', '</span>'),
		// The value when the copy syntax (<) is used; that means the object reference
		'value_unset' => array('<span class="ts-value_unset">', '</span>'),
		// The value when an object is unset. Should not exist.
		'ignored' => array('<span class="ts-ignored">', '</span>'),
		// The "rest" of a line which will be ignored.
		'default' => array('<span class="ts-default">', '</span>'),
		// The default style if none other is applied.
		'comment' => array('<span class="ts-comment">', '</span>'),
		// Comment lines
		'condition' => array('<span class="ts-condition">', '</span>'),
		// Conditions
		'error' => array('<span class="ts-error">', '</span>'),
		// Error messages
		'linenum' => array('<span class="ts-linenum">', '</span>')
	);

	// Additional attributes for the <span> tags for a blockmode line
	/**
	 * @todo Define visibility
	 */
	public $highLightBlockStyles = '';

	// The hex-HTML color for the blockmode
	/**
	 * @todo Define visibility
	 */
	public $highLightBlockStyles_basecolor = '#cccccc';

	//Instance of parentObject, used by \TYPO3\CMS\Core\TypoScript\ExtendedTemplateService
	public $parentObject;

	/**
	 * Start parsing the input TypoScript text piece. The result is stored in $this->setup
	 *
	 * @param string $string The TypoScript text
	 * @param object $matchObj If is object, then this is used to match conditions found in the TypoScript code. If matchObj not specified, then no conditions will work! (Except [GLOBAL])
	 * @return void
	 * @todo Define visibility
	 */
	public function parse($string, $matchObj = '') {
		$this->raw = explode(LF, $string);
		$this->rawP = 0;
		$pre = '[GLOBAL]';
		while ($pre) {
			if ($this->breakPointLN && $pre == '[_BREAK]') {
				$this->error('Breakpoint at ' . ($this->lineNumberOffset + $this->rawP - 2) . ': Line content was "' . $this->raw[($this->rawP - 2)] . '"', 1);
				break;
			}
			if (strtoupper($pre) == '[GLOBAL]' || strtoupper($pre) == '[END]' || !$this->lastConditionTrue && strtoupper($pre) == '[ELSE]') {
				$pre = trim($this->parseSub($this->setup));
				$this->lastConditionTrue = 1;
			} else {
				// We're in a specific section. Therefore we log this section
				if (strtoupper($pre) != '[ELSE]') {
					$this->sections[md5($pre)] = $pre;
				}
				if (is_object($matchObj) && $matchObj->match($pre) || $this->syntaxHighLight) {
					if (strtoupper($pre) != '[ELSE]') {
						$this->sectionsMatch[md5($pre)] = $pre;
					}
					$pre = trim($this->parseSub($this->setup));
					$this->lastConditionTrue = 1;
				} else {
					$pre = trim($this->nextDivider());
					$this->lastConditionTrue = 0;
				}
			}
		}
		if ($this->inBrace) {
			$this->error('Line ' . ($this->lineNumberOffset + $this->rawP - 1) . ': The script is short of ' . $this->inBrace . ' end brace(s)', 1);
		}
		if ($this->multiLineEnabled) {
			$this->error('Line ' . ($this->lineNumberOffset + $this->rawP - 1) . ': A multiline value section is not ended with a parenthesis!', 1);
		}
		$this->lineNumberOffset += count($this->raw) + 1;
	}

	/**
	 * Will search for the next condition. When found it will return the line content (the condition value) and have advanced the internal $this->rawP pointer to point to the next line after the condition.
	 *
	 * @return string The condition value
	 * @see parse()
	 * @todo Define visibility
	 */
	public function nextDivider() {
		while (isset($this->raw[$this->rawP])) {
			$line = ltrim($this->raw[$this->rawP]);
			$this->rawP++;
			if ($line && substr($line, 0, 1) == '[') {
				return $line;
			}
		}
	}

	/**
	 * Parsing the $this->raw TypoScript lines from pointer, $this->rawP
	 *
	 * @param array $setup Reference to the setup array in which to accumulate the values.
	 * @return string Returns the string of the condition found, the exit signal or possible nothing (if it completed parsing with no interruptions)
	 * @todo Define visibility
	 */
	public function parseSub(&$setup) {
		while (isset($this->raw[$this->rawP])) {
			$line = ltrim($this->raw[$this->rawP]);
			$lineP = $this->rawP;
			$this->rawP++;
			if ($this->syntaxHighLight) {
				$this->regHighLight('prespace', $lineP, strlen($line));
			}
			// Breakpoint?
			// By adding 1 we get that line processed
			if ($this->breakPointLN && $this->lineNumberOffset + $this->rawP - 1 == $this->breakPointLN + 1) {
				return '[_BREAK]';
			}
			// Set comment flag?
			if (!$this->multiLineEnabled && substr($line, 0, 2) == '/*') {
				$this->commentSet = 1;
			}
			// If $this->multiLineEnabled we will go and get the line values here because we know, the first if() will be TRUE.
			if (!$this->commentSet && ($line || $this->multiLineEnabled)) {
				// If multiline is enabled. Escape by ')'
				if ($this->multiLineEnabled) {
					// Multiline ends...
					if (substr($line, 0, 1) == ')') {
						if ($this->syntaxHighLight) {
							$this->regHighLight('operator', $lineP, strlen($line) - 1);
						}
						// Disable multiline
						$this->multiLineEnabled = 0;
						$theValue = implode($this->multiLineValue, LF);
						if (strstr($this->multiLineObject, '.')) {
							// Set the value deeper.
							$this->setVal($this->multiLineObject, $setup, array($theValue));
						} else {
							// Set value regularly
							$setup[$this->multiLineObject] = $theValue;
							if ($this->lastComment && $this->regComments) {
								$setup[$this->multiLineObject . '..'] .= $this->lastComment;
							}
							if ($this->regLinenumbers) {
								$setup[$this->multiLineObject . '.ln..'][] = $this->lineNumberOffset + $this->rawP - 1;
							}
						}
					} else {
						if ($this->syntaxHighLight) {
							$this->regHighLight('value', $lineP);
						}
						$this->multiLineValue[] = $this->raw[$this->rawP - 1];
					}
				} elseif ($this->inBrace == 0 && substr($line, 0, 1) == '[') {
					// Beginning of condition (only on level zero compared to brace-levels
					if ($this->syntaxHighLight) {
						$this->regHighLight('condition', $lineP);
					}
					return $line;
				} else {
					// Return if GLOBAL condition is set - no matter what.
					if (substr($line, 0, 1) == '[' && strtoupper(trim($line)) == '[GLOBAL]') {
						if ($this->syntaxHighLight) {
							$this->regHighLight('condition', $lineP);
						}
						$this->error('Line ' . ($this->lineNumberOffset + $this->rawP - 1) . ': On return to [GLOBAL] scope, the script was short of ' . $this->inBrace . ' end brace(s)', 1);
						$this->inBrace = 0;
						return $line;
					} elseif (strcspn($line, '}#/') != 0) {
						// If not brace-end or comment
						// Find object name string until we meet an operator
						$varL = strcspn($line, ' {=<>:(');
						$objStrName = trim(substr($line, 0, $varL));
						if ($this->syntaxHighLight) {
							$this->regHighLight('objstr', $lineP, strlen(substr($line, $varL)));
						}
						if (strlen($objStrName)) {
							$r = array();
							if ($this->strict && preg_match('/[^[:alnum:]_\\\\\\.-]/i', $objStrName, $r)) {
								$this->error('Line ' . ($this->lineNumberOffset + $this->rawP - 1) . ': Object Name String, "' . htmlspecialchars($objStrName) . '" contains invalid character "' . $r[0] . '". Must be alphanumeric or one of: "_-\\."');
							} else {
								$line = ltrim(substr($line, $varL));
								if ($this->syntaxHighLight) {
									$this->regHighLight('objstr_postspace', $lineP, strlen($line));
									if (strlen($line) > 0) {
										$this->regHighLight('operator', $lineP, strlen($line) - 1);
										$this->regHighLight('operator_postspace', $lineP, strlen(ltrim(substr($line, 1))));
									}
								}
								// Checking for special TSparser properties (to change TS values at parsetime)
								$match = array();
								if (preg_match('/^:=([^\\(]+)\\((.*)\\).*/', $line, $match)) {
									$tsFunc = trim($match[1]);
									$tsFuncArg = $match[2];
									list($currentValue) = $this->getVal($objStrName, $setup);
									$tsFuncArg = str_replace(array('\\\\', '\\n', '\\t'), array('\\', LF, TAB), $tsFuncArg);
									$newValue = $this->executeValueModifier($tsFunc, $tsFuncArg, $currentValue);
									if (isset($newValue)) {
										$line = '= ' . $newValue;
									}
								}
								switch (substr($line, 0, 1)) {
								case '=':
									if ($this->syntaxHighLight) {
										$this->regHighLight('value', $lineP, strlen(ltrim(substr($line, 1))) - strlen(trim(substr($line, 1))));
									}
									if (strstr($objStrName, '.')) {
										$value = array();
										$value[0] = trim(substr($line, 1));
										$this->setVal($objStrName, $setup, $value);
									} else {
										$setup[$objStrName] = trim(substr($line, 1));
										if ($this->lastComment && $this->regComments) {
											// Setting comment..
											$setup[$objStrName . '..'] .= $this->lastComment;
										}
										if ($this->regLinenumbers) {
											$setup[$objStrName . '.ln..'][] = $this->lineNumberOffset + $this->rawP - 1;
										}
									}
									break;
								case '{':
									$this->inBrace++;
									if (strstr($objStrName, '.')) {
										$exitSig = $this->rollParseSub($objStrName, $setup);
										if ($exitSig) {
											return $exitSig;
										}
									} else {
										if (!isset($setup[($objStrName . '.')])) {
											$setup[$objStrName . '.'] = array();
										}
										$exitSig = $this->parseSub($setup[$objStrName . '.']);
										if ($exitSig) {
											return $exitSig;
										}
									}
									break;
								case '(':
									$this->multiLineObject = $objStrName;
									$this->multiLineEnabled = 1;
									$this->multiLineValue = array();
									break;
								case '<':
									if ($this->syntaxHighLight) {
										$this->regHighLight('value_copy', $lineP, strlen(ltrim(substr($line, 1))) - strlen(trim(substr($line, 1))));
									}
									$theVal = trim(substr($line, 1));
									if (substr($theVal, 0, 1) == '.') {
										$res = $this->getVal(substr($theVal, 1), $setup);
									} else {
										$res = $this->getVal($theVal, $this->setup);
									}
									$this->setVal($objStrName, $setup, unserialize(serialize($res)), 1);
									// unserialize(serialize(...)) may look stupid but is needed because of some reference issues. See Kaspers reply to "[TYPO3-core] good question" from December 15 2005.
									break;
								case '>':
									if ($this->syntaxHighLight) {
										$this->regHighLight('value_unset', $lineP, strlen(ltrim(substr($line, 1))) - strlen(trim(substr($line, 1))));
									}
									$this->setVal($objStrName, $setup, 'UNSET');
									break;
								default:
									$this->error('Line ' . ($this->lineNumberOffset + $this->rawP - 1) . ': Object Name String, "' . htmlspecialchars($objStrName) . '" was not preceded by any operator, =<>({');
									break;
								}
							}
							$this->lastComment = '';
						}
					} elseif (substr($line, 0, 1) == '}') {
						$this->inBrace--;
						$this->lastComment = '';
						if ($this->syntaxHighLight) {
							$this->regHighLight('operator', $lineP, strlen($line) - 1);
						}
						if ($this->inBrace < 0) {
							$this->error('Line ' . ($this->lineNumberOffset + $this->rawP - 1) . ': An end brace is in excess.', 1);
							$this->inBrace = 0;
						} else {
							break;
						}
					} else {
						if ($this->syntaxHighLight) {
							$this->regHighLight('comment', $lineP);
						}
						// Comment. The comments are concatenated in this temporary string:
						if ($this->regComments) {
							$this->lastComment .= trim($line) . LF;
						}
					}
				}
			}
			// Unset comment
			if ($this->commentSet) {
				if ($this->syntaxHighLight) {
					$this->regHighLight('comment', $lineP);
				}
				if (substr($line, 0, 2) == '*/') {
					$this->commentSet = 0;
				}
			}
		}
	}

	/**
	 * Executes operator functions, called from TypoScript
	 * example: page.10.value := appendString(!)
	 *
	 * @param string $modifierName TypoScript function called
	 * @param string $modifierArgument Function arguments; In case of multiple arguments, the method must split on its own
	 * @param string $currentValue Current TypoScript value
	 * @return string Modification result
	 */
	protected function executeValueModifier($modifierName, $modifierArgument = NULL, $currentValue = NULL) {
		$newValue = NULL;
		switch ($modifierName) {
			case 'prependString':
				$newValue = $modifierArgument . $currentValue;
				break;
			case 'appendString':
				$newValue = $currentValue . $modifierArgument;
				break;
			case 'removeString':
				$newValue = str_replace($modifierArgument, '', $currentValue);
				break;
			case 'replaceString':
				list($fromStr, $toStr) = explode('|', $modifierArgument, 2);
				$newValue = str_replace($fromStr, $toStr, $currentValue);
				break;
			case 'addToList':
				$newValue = (strcmp('', $currentValue) ? $currentValue . ',' : '') . trim($modifierArgument);
				break;
			case 'removeFromList':
				$existingElements = \TYPO3\CMS\Core\Utility\GeneralUtility::trimExplode(',', $currentValue);
				$removeElements = \TYPO3\CMS\Core\Utility\GeneralUtility::trimExplode(',', $modifierArgument);
				if (count($removeElements)) {
					$newValue = implode(',', array_diff($existingElements, $removeElements));
				}
				break;
			case 'uniqueList':
				$elements = \TYPO3\CMS\Core\Utility\GeneralUtility::trimExplode(',', $currentValue);
				$newValue = implode(',', array_unique($elements));
				break;
			case 'reverseList':
				$elements = \TYPO3\CMS\Core\Utility\GeneralUtility::trimExplode(',', $currentValue);
				$newValue = implode(',', array_reverse($elements));
				break;
			case 'sortList':
				$elements = \TYPO3\CMS\Core\Utility\GeneralUtility::trimExplode(',', $currentValue);
				$arguments = \TYPO3\CMS\Core\Utility\GeneralUtility::trimExplode(',', $modifierArgument);
				$arguments = array_map('strtolower', $arguments);
				$sort_flags = SORT_REGULAR;
				if (in_array('numeric', $arguments)) {
					$sort_flags = SORT_NUMERIC;
				}
				sort($elements, $sort_flags);
				if (in_array('descending', $arguments)) {
					$elements = array_reverse($elements);
				}
				$newValue = implode(',', $elements);
				break;
			default:
				if (isset($GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS']['t3lib/class.t3lib_tsparser.php']['preParseFunc'][$modifierName])) {
					$hookMethod = $GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS']['t3lib/class.t3lib_tsparser.php']['preParseFunc'][$modifierName];
					$params = array('currentValue' => $currentValue, 'functionArgument' => $modifierArgument);
					$fakeThis = FALSE;
					$newValue = \TYPO3\CMS\Core\Utility\GeneralUtility::callUserFunction($hookMethod, $params, $fakeThis);
				} else {
					\TYPO3\CMS\Core\Utility\GeneralUtility::sysLog(
						'Missing function definition for ' . $modifierName . ' on TypoScript',
						'Core',
						\TYPO3\CMS\Core\Utility\GeneralUtility::SYSLOG_SEVERITY_WARNING
					);
				}
		}
		return $newValue;
	}

	/**
	 * Parsing of TypoScript keys inside a curly brace where the key is composite of at least two keys, thus having to recursively call itself to get the value
	 *
	 * @param string $string The object sub-path, eg "thisprop.another_prot
	 * @param array $setup The local setup array from the function calling this function
	 * @return string Returns the exitSignal
	 * @see parseSub()
	 * @todo Define visibility
	 */
	public function rollParseSub($string, &$setup) {
		if ((string) $string != '') {
			$keyLen = strcspn($string, '.');
			if ($keyLen == strlen($string)) {
				$key = $string . '.';
				if (!isset($setup[$key])) {
					$setup[$key] = array();
				}
				$exitSig = $this->parseSub($setup[$key]);
				if ($exitSig) {
					return $exitSig;
				}
			} else {
				$key = substr($string, 0, $keyLen) . '.';
				if (!isset($setup[$key])) {
					$setup[$key] = array();
				}
				$exitSig = $this->rollParseSub(substr($string, $keyLen + 1), $setup[$key]);
				if ($exitSig) {
					return $exitSig;
				}
			}
		}
	}

	/**
	 * Get a value/property pair for an object path in TypoScript, eg. "myobject.myvalue.mysubproperty". Here: Used by the "copy" operator, <
	 *
	 * @param string $string Object path for which to get the value
	 * @param array $setup Global setup code if $string points to a global object path. But if string is prefixed with "." then its the local setup array.
	 * @return array An array with keys 0/1 being value/property respectively
	 * @todo Define visibility
	 */
	public function getVal($string, $setup) {
		if ((string) $string != '') {
			$keyLen = strcspn($string, '.');
			if ($keyLen == strlen($string)) {
				// Added 6/6/03. Shouldn't hurt
				$retArr = array();
				if (isset($setup[$string])) {
					$retArr[0] = $setup[$string];
				}
				if (isset($setup[$string . '.'])) {
					$retArr[1] = $setup[$string . '.'];
				}
				return $retArr;
			} else {
				$key = substr($string, 0, $keyLen) . '.';
				if ($setup[$key]) {
					return $this->getVal(substr($string, $keyLen + 1), $setup[$key]);
				}
			}
		}
	}

	/**
	 * Setting a value/property of an object string in the setup array.
	 *
	 * @param string $string The object sub-path, eg "thisprop.another_prot
	 * @param array $setup The local setup array from the function calling this function.
	 * @param array $value The value/property pair array to set. If only one of them is set, then the other is not touched (unless $wipeOut is set, which it is when copies are made which must include both value and property)
	 * @param boolean $wipeOut If set, then both value and property is wiped out when a copy is made of another value.
	 * @return void
	 * @todo Define visibility
	 */
	public function setVal($string, &$setup, $value, $wipeOut = 0) {
		if ((string) $string != '') {
			$keyLen = strcspn($string, '.');
			if ($keyLen == strlen($string)) {
				if ($value == 'UNSET') {
					unset($setup[$string]);
					unset($setup[$string . '.']);
					if ($this->regLinenumbers) {
						$setup[$string . '.ln..'][] = ($this->lineNumberOffset + $this->rawP - 1) . '>';
					}
				} else {
					$lnRegisDone = 0;
					if ($wipeOut && $this->strict) {
						unset($setup[$string]);
						unset($setup[$string . '.']);
						if ($this->regLinenumbers) {
							$setup[$string . '.ln..'][] = ($this->lineNumberOffset + $this->rawP - 1) . '<';
							$lnRegisDone = 1;
						}
					}
					if (isset($value[0])) {
						$setup[$string] = $value[0];
					}
					if (isset($value[1])) {
						$setup[$string . '.'] = $value[1];
					}
					if ($this->lastComment && $this->regComments) {
						$setup[$string . '..'] .= $this->lastComment;
					}
					if ($this->regLinenumbers && !$lnRegisDone) {
						$setup[$string . '.ln..'][] = $this->lineNumberOffset + $this->rawP - 1;
					}
				}
			} else {
				$key = substr($string, 0, $keyLen) . '.';
				if (!isset($setup[$key])) {
					$setup[$key] = array();
				}
				$this->setVal(substr($string, $keyLen + 1), $setup[$key], $value);
			}
		}
	}

	/**
	 * Stacks errors/messages from the TypoScript parser into an internal array, $this->error
	 * If "TT" is a global object (as it is in the frontend when backend users are logged in) the message will be registered here as well.
	 *
	 * @param string $err The error message string
	 * @param integer $num The error severity (in the scale of $GLOBALS['TT']->setTSlogMessage: Approx: 2=warning, 1=info, 0=nothing, 3=fatal.)
	 * @return void
	 * @todo Define visibility
	 */
	public function error($err, $num = 2) {
		if (is_object($GLOBALS['TT'])) {
			$GLOBALS['TT']->setTSlogMessage($err, $num);
		}
		$this->errors[] = array($err, $num, $this->rawP - 1, $this->lineNumberOffset);
	}

	/**
	 * Checks the input string (un-parsed TypoScript) for include-commands ("<INCLUDE_TYPOSCRIPT: ....")
	 * Use: \TYPO3\CMS\Core\TypoScript\Parser\TypoScriptParser::checkIncludeLines()
	 *
	 * @param string $string Unparsed TypoScript
	 * @param integer $cycle_counter Counter for detecting endless loops
	 * @param boolean $returnFiles When set an array containing the resulting typoscript and all included files will get returned
	 * @return string Complete TypoScript with includes added.
	 * @static
	 */
	static public function checkIncludeLines($string, $cycle_counter = 1, $returnFiles = FALSE) {
		$includedFiles = array();
		if ($cycle_counter > 100) {
			\TYPO3\CMS\Core\Utility\GeneralUtility::sysLog('It appears like TypoScript code is looping over itself. Check your templates for "&lt;INCLUDE_TYPOSCRIPT: ..." tags', 'Core', \TYPO3\CMS\Core\Utility\GeneralUtility::SYSLOG_SEVERITY_WARNING);
			if ($returnFiles) {
				return array(
					'typoscript' => '',
					'files' => $includedFiles
				);
			}
			return '
###
### ERROR: Recursion!
###
';
		}
		$splitStr = '<INCLUDE_TYPOSCRIPT:';
		if (strstr($string, $splitStr)) {
			$newString = '';
			// Adds line break char before/after
			$allParts = explode($splitStr, LF . $string . LF);
			foreach ($allParts as $c => $v) {
				// First goes through
				if (!$c) {
					$newString .= $v;
				} elseif (preg_match('/\\r?\\n\\s*$/', $allParts[$c - 1])) {
					$subparts = explode('>', $v, 2);
					// There must be a line-break char after
					if (preg_match('/^\\s*\\r?\\n/', $subparts[1])) {
						// SO, the include was positively recognized:
						$newString .= '### ' . $splitStr . $subparts[0] . '> BEGIN:' . LF;
						$params = \TYPO3\CMS\Core\Utility\GeneralUtility::get_tag_attributes($subparts[0]);
						if ($params['source']) {
							$sourceParts = explode(':', $params['source'], 2);
							switch (strtolower(trim($sourceParts[0]))) {
							case 'file':
								$filename = \TYPO3\CMS\Core\Utility\GeneralUtility::getFileAbsFileName(trim($sourceParts[1]));
								// Must exist and must not contain '..' and must be relative
								if (strcmp($filename, '')) {
									// Check for allowed files
									if (\TYPO3\CMS\Core\Utility\GeneralUtility::verifyFilenameAgainstDenyPattern($filename)) {
										if (@is_file($filename)) {
											// Check for includes in included text
											$includedFiles[] = $filename;
											$included_text = self::checkIncludeLines(\TYPO3\CMS\Core\Utility\GeneralUtility::getUrl($filename), $cycle_counter + 1, $returnFiles);
											// If the method also has to return all included files, merge currently included
											// files with files included by recursively calling itself
											if ($returnFiles && is_array($included_text)) {
												$includedFiles = array_merge($includedFiles, $included_text['files']);
												$included_text = $included_text['typoscript'];
											}
											$newString .= $included_text . LF;
										} else {
											$newString .= '
###
### ERROR: File "' . $filename . '" was not was not found.
###

';
											\TYPO3\CMS\Core\Utility\GeneralUtility::sysLog('File "' . $filename . '" was not found.', 'Core', \TYPO3\CMS\Core\Utility\GeneralUtility::SYSLOG_SEVERITY_WARNING);
										}
									} else {
										$newString .= '
###
### ERROR: File "' . $filename . '" was not included since it is not allowed due to fileDenyPattern
###

';
										\TYPO3\CMS\Core\Utility\GeneralUtility::sysLog('File "' . $filename . '" was not included since it is not allowed due to fileDenyPattern', 'Core', \TYPO3\CMS\Core\Utility\GeneralUtility::SYSLOG_SEVERITY_WARNING);
									}
								}
								break;
							}
						}
						$newString .= '### ' . $splitStr . $subparts[0] . '> END:' . LF;
						$newString .= $subparts[1];
					} else {
						$newString .= $splitStr . $v;
					}
				} else {
					$newString .= $splitStr . $v;
				}
			}
			// Not the first/last linebreak char.
			$string = substr($newString, 1, -1);
		}
		// When all included files should get returned, simply return an compound array containing
		// the TypoScript with all "includes" processed and the files which got included
		if ($returnFiles) {
			return array(
				'typoscript' => $string,
				'files' => $includedFiles
			);
		}
		return $string;
	}

	/**
	 * Parses the string in each value of the input array for include-commands
	 *
	 * @param array $array Array with TypoScript in each value
	 * @return array Same array but where the values has been parsed for include-commands
	 */
	static public function checkIncludeLines_array($array) {
		foreach ($array as $k => $v) {
			$array[$k] = self::checkIncludeLines($array[$k]);
		}
		return $array;
	}

	/**
	 * Search for commented INCLUDE_TYPOSCRIPT statements
	 * and save the content between the BEGIN and the END line to the specified file
	 *
	 * @param string $string Template content
	 * @param integer $cycle_counter Counter for detecting endless loops
	 * @param array $extractedFileNames
	 * @return string Template content with uncommented include statements
	 */
	static public function extractIncludes($string, $cycle_counter = 1, $extractedFileNames = array()) {
		if ($cycle_counter > 10) {
			\TYPO3\CMS\Core\Utility\GeneralUtility::sysLog('It appears like TypoScript code is looping over itself. Check your templates for "&lt;INCLUDE_TYPOSCRIPT: ..." tags', 'Core', \TYPO3\CMS\Core\Utility\GeneralUtility::SYSLOG_SEVERITY_WARNING);
			return '
###
### ERROR: Recursion!
###
';
		}
		$fileContent = array();
		$restContent = array();
		$fileName = NULL;
		$inIncludePart = FALSE;
		$lines = explode('
', $string);
		$skipNextLineIfEmpty = FALSE;
		$openingCommentedIncludeStatement = NULL;
		foreach ($lines as $line) {
			// \TYPO3\CMS\Core\TypoScript\Parser\TypoScriptParser::checkIncludeLines inserts
			// an additional empty line, remove this again
			if ($skipNextLineIfEmpty) {
				if (trim($line) == '') {
					continue;
				}
				$skipNextLineIfEmpty = FALSE;
			}
			// Outside commented include statements
			if (!$inIncludePart) {
				// Search for beginning commented include statements
				$matches = array();
				if (preg_match('/###\\s*<INCLUDE_TYPOSCRIPT:\\s*source\\s*=\\s*"\\s*FILE\\s*:\\s*(.*)\\s*">\\s*BEGIN/i', $line, $matches)) {
					// Save this line in case there is no ending tag
					$openingCommentedIncludeStatement = trim($line);
					$openingCommentedIncludeStatement = trim(preg_replace('/### Warning: .*###/', '', $openingCommentedIncludeStatement));
					// Found a commented include statement
					$fileName = trim($matches[1]);
					$inIncludePart = TRUE;
					$expectedEndTag = '### <INCLUDE_TYPOSCRIPT: source="FILE:' . $fileName . '"> END';
					// Strip all whitespace characters to make comparision safer
					$expectedEndTag = strtolower(preg_replace('/\\s/', '', $expectedEndTag));
				} else {
					// If this is not a beginning commented include statement this line goes into the rest content
					$restContent[] = $line;
				}
			} else {
				// Inside commented include statements
				// Search for the matching ending commented include statement
				$strippedLine = strtolower(preg_replace('/\\s/', '', $line));
				if (strpos($strippedLine, $expectedEndTag) !== FALSE) {
					// Found the matching ending include statement
					$fileContentString = implode('
', $fileContent);
					// Write the content to the file
					$realFileName = \TYPO3\CMS\Core\Utility\GeneralUtility::getFileAbsFileName($fileName);
					// Some file checks
					if (empty($realFileName)) {
						throw new \UnexpectedValueException(sprintf('"%s" is not a valid file location.', $fileName), 1294586441);
					}
					if (!is_writable($realFileName)) {
						throw new \RuntimeException(sprintf('"%s" is not writable.', $fileName), 1294586442);
					}
					if (in_array($realFileName, $extractedFileNames)) {
						throw new \RuntimeException(sprintf('Recursive/multiple inclusion of file "%s"', $realFileName), 1294586443);
					}
					$extractedFileNames[] = $realFileName;
					// Recursive call to detected nested commented include statements
					$fileContentString = self::extractIncludes($fileContentString, $cycle_counter + 1, $extractedFileNames);
					if (!\TYPO3\CMS\Core\Utility\GeneralUtility::writeFile($realFileName, $fileContentString)) {
						throw new \RuntimeException(sprintf('Could not write file "%s"', $realFileName), 1294586444);
					}
					// Insert reference to the file in the rest content
					$restContent[] = '<INCLUDE_TYPOSCRIPT: source="FILE: ' . $fileName . '">';
					// Reset variables (preparing for the next commented include statement)
					$fileContent = array();
					$fileName = NULL;
					$inIncludePart = FALSE;
					$openingCommentedIncludeStatement = NULL;
					// \TYPO3\CMS\Core\TypoScript\Parser\TypoScriptParser::checkIncludeLines inserts
					// an additional empty line, remove this again
					$skipNextLineIfEmpty = TRUE;
				} else {
					// If this is not a ending commented include statement this line goes into the file content
					$fileContent[] = $line;
				}
			}
		}
		// If we're still inside commented include statements copy the lines back to the rest content
		if ($inIncludePart) {
			$restContent[] = $openingCommentedIncludeStatement . ' ### Warning: Corresponding end line missing! ###';
			$restContent = array_merge($restContent, $fileContent);
		}
		$restContentString = implode('
', $restContent);
		return $restContentString;
	}

	/**
	 * Processes the string in each value of the input array with extractIncludes
	 *
	 * @param array $array Array with TypoScript in each value
	 * @return array Same array but where the values has been processed with extractIncludes
	 */
	static public function extractIncludes_array($array) {
		foreach ($array as $k => $v) {
			$array[$k] = self::extractIncludes($array[$k]);
		}
		return $array;
	}

	/**********************************
	 *
	 * Syntax highlighting
	 *
	 *********************************/
	/**
	 * Syntax highlight a TypoScript text
	 * Will parse the content. Remember, the internal setup array may contain invalid parsed content since conditions are ignored!
	 *
	 * @param string $string The TypoScript text
	 * @param mixed $lineNum If blank, linenumbers are NOT printed. If array then the first key is the linenumber offset to add to the internal counter.
	 * @param boolean $highlightBlockMode If set, then the highlighted output will be formatted in blocks based on the brace levels. prespace will be ignored and empty lines represented with a single no-break-space.
	 * @return string HTML code for the syntax highlighted string
	 * @todo Define visibility
	 */
	public function doSyntaxHighlight($string, $lineNum = '', $highlightBlockMode = 0) {
		$this->syntaxHighLight = 1;
		$this->highLightData = array();
		$this->error = array();
		// This is done in order to prevent empty <span>..</span> sections around CR content. Should not do anything but help lessen the amount of HTML code.
		$string = str_replace(CR, '', $string);
		$this->parse($string);
		return $this->syntaxHighlight_print($lineNum, $highlightBlockMode);
	}

	/**
	 * Registers a part of a TypoScript line for syntax highlighting.
	 *
	 * @param string $code Key from the internal array $this->highLightStyles
	 * @param integer $pointer Pointer to the line in $this->raw which this is about
	 * @param integer $strlen The number of chars LEFT on this line before the end is reached.
	 * @return void
	 * @access private
	 * @see 	parse()
	 * @todo Define visibility
	 */
	public function regHighLight($code, $pointer, $strlen = -1) {
		if ($strlen == -1) {
			$this->highLightData[$pointer] = array(array($code, 0));
		} else {
			$this->highLightData[$pointer][] = array($code, $strlen);
		}
		$this->highLightData_bracelevel[$pointer] = $this->inBrace;
	}

	/**
	 * Formatting the TypoScript code in $this->raw based on the data collected by $this->regHighLight in $this->highLightData
	 *
	 * @param mixed $lineNumDat If blank, linenumbers are NOT printed. If array then the first key is the linenumber offset to add to the internal counter.
	 * @param boolean $highlightBlockMode If set, then the highlighted output will be formatted in blocks based on the brace levels. prespace will be ignored and empty lines represented with a single no-break-space.
	 * @return string HTML content
	 * @access private
	 * @see doSyntaxHighlight()
	 * @todo Define visibility
	 */
	public function syntaxHighlight_print($lineNumDat, $highlightBlockMode) {
		// Registers all error messages in relation to their linenumber
		$errA = array();
		foreach ($this->errors as $err) {
			$errA[$err[2]][] = $err[0];
		}
		// Generates the syntax highlighted output:
		$lines = array();
		foreach ($this->raw as $rawP => $value) {
			$start = 0;
			$strlen = strlen($value);
			$lineC = '';
			if (is_array($this->highLightData[$rawP])) {
				foreach ($this->highLightData[$rawP] as $set) {
					$len = $strlen - $start - $set[1];
					if ($len > 0) {
						$part = substr($value, $start, $len);
						$start += $len;
						$st = $this->highLightStyles[isset($this->highLightStyles[$set[0]]) ? $set[0] : 'default'];
						if (!$highlightBlockMode || $set[0] != 'prespace') {
							$lineC .= $st[0] . htmlspecialchars($part) . $st[1];
						}
					} elseif ($len < 0) {
						debug(array($len, $value, $rawP));
					}
				}
			} else {
				debug(array($value));
			}
			if (strlen(substr($value, $start))) {
				$lineC .= $this->highLightStyles['ignored'][0] . htmlspecialchars(substr($value, $start)) . $this->highLightStyles['ignored'][1];
			}
			if ($errA[$rawP]) {
				$lineC .= $this->highLightStyles['error'][0] . '<strong> - ERROR:</strong> ' . htmlspecialchars(implode(';', $errA[$rawP])) . $this->highLightStyles['error'][1];
			}
			if ($highlightBlockMode && $this->highLightData_bracelevel[$rawP]) {
				$lineC = str_pad('', $this->highLightData_bracelevel[$rawP] * 2, ' ', STR_PAD_LEFT) . '<span style="' . $this->highLightBlockStyles . ($this->highLightBlockStyles_basecolor ? 'background-color: ' . \TYPO3\CMS\Core\Utility\GeneralUtility::modifyHTMLColorAll($this->highLightBlockStyles_basecolor, -$this->highLightData_bracelevel[$rawP] * 16) : '') . '">' . (strcmp($lineC, '') ? $lineC : '&nbsp;') . '</span>';
			}
			if (is_array($lineNumDat)) {
				$lineNum = $rawP + $lineNumDat[0];
				if ($this->parentObject instanceof \TYPO3\CMS\Core\TypoScript\ExtendedTemplateService) {
					$lineNum = $this->parentObject->ext_lnBreakPointWrap($lineNum, $lineNum);
				}
				$lineC = $this->highLightStyles['linenum'][0] . str_pad($lineNum, 4, ' ', STR_PAD_LEFT) . ':' . $this->highLightStyles['linenum'][1] . ' ' . $lineC;
			}
			$lines[] = $lineC;
		}
		return '<pre class="ts-hl">' . implode(LF, $lines) . '</pre>';
	}

}


?>