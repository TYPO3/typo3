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

namespace TYPO3\CMS\Core\TypoScript\Parser;

use Psr\Http\Message\ServerRequestInterface;
use Psr\Log\LoggerInterface;
use Psr\Log\LogLevel;
use Symfony\Component\Finder\Finder;
use TYPO3\CMS\Backend\Configuration\TypoScript\ConditionMatching\ConditionMatcher as BackendConditionMatcher;
use TYPO3\CMS\Core\Http\ApplicationType;
use TYPO3\CMS\Core\Log\LogManager;
use TYPO3\CMS\Core\Resource\Security\FileNameValidator;
use TYPO3\CMS\Core\TimeTracker\TimeTracker;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Core\Utility\PathUtility;
use TYPO3\CMS\Core\Utility\StringUtility;
use TYPO3\CMS\Frontend\Configuration\TypoScript\ConditionMatching\ConditionMatcher as FrontendConditionMatcher;

/**
 * The TypoScript parser
 */
class TypoScriptParser
{
    /**
     * TypoScript hierarchy being build during parsing.
     *
     * @var array
     */
    public $setup = [];

    /**
     * Raw data, the input string exploded by LF
     *
     * @var string[]
     */
    protected $raw;

    /**
     * Pointer to entry in raw data array
     *
     * @var int
     */
    protected $rawP;

    /**
     * Holding the value of the last comment
     *
     * @var string
     */
    protected $lastComment = '';

    /**
     * Internally set, used as internal flag to create a multi-line comment (one of those like /* ... * /
     *
     * @var bool
     */
    protected $commentSet = false;

    /**
     * Internally set, when multiline value is accumulated
     *
     * @var bool
     */
    protected $multiLineEnabled = false;

    /**
     * Internally set, when multiline value is accumulated
     *
     * @var string
     */
    protected $multiLineObject = '';

    /**
     * Internally set, when multiline value is accumulated
     *
     * @var array
     */
    protected $multiLineValue = [];

    /**
     * Internally set, when in brace. Counter.
     *
     * @var int
     */
    protected $inBrace = 0;

    /**
     * For each condition this flag is set, if the condition is TRUE,
     * else it's cleared. Then it's used by the [ELSE] condition to determine if the next part should be parsed.
     *
     * @var bool
     */
    protected $lastConditionTrue = true;

    /**
     * Tracking all conditions found
     *
     * @var array
     */
    public $sections = [];

    /**
     * Tracking all matching conditions found
     *
     * @var array
     */
    public $sectionsMatch = [];

    /**
     * DO NOT register the comments. This is default for the ordinary sitetemplate!
     *
     * @var bool
     */
    public $regComments = false;

    /**
     * DO NOT register the linenumbers. This is default for the ordinary sitetemplate!
     *
     * @var bool
     */
    public $regLinenumbers = false;

    /**
     * Error accumulation array.
     *
     * @var array
     */
    public $errors = [];

    /**
     * Used for the error messages line number reporting. Set externally.
     *
     * @var int
     */
    public $lineNumberOffset = 0;

    /**
     * @deprecated Unused since v11, will be removed in v12
     */
    public $breakPointLN = 0;

    /**
     * @deprecated Unused since v11, will be removed in v12
     */
    public $parentObject;

    /**
     * Start parsing the input TypoScript text piece. The result is stored in $this->setup
     *
     * @param string $string The TypoScript text
     * @param object|string $matchObj If is object, then this is used to match conditions found in the TypoScript code. If matchObj not specified, then no conditions will work! (Except [GLOBAL])
     */
    public function parse($string, $matchObj = '')
    {
        $this->raw = explode(LF, $string);
        $this->rawP = 0;
        $pre = '[GLOBAL]';
        while ($pre) {
            if ($pre === '[]') {
                $this->error('Empty condition is always false, this does not make sense. At line ' . ($this->lineNumberOffset + $this->rawP - 1), LogLevel::WARNING);
                break;
            }
            $preUppercase = strtoupper($pre);
            if ($pre[0] === '[' &&
                ($preUppercase === '[GLOBAL]' ||
                    $preUppercase === '[END]' ||
                    !$this->lastConditionTrue && $preUppercase === '[ELSE]')
            ) {
                $pre = trim($this->parseSub($this->setup));
                $this->lastConditionTrue = true;
            } else {
                // We're in a specific section. Therefore we log this section
                $specificSection = $preUppercase !== '[ELSE]';
                if ($specificSection) {
                    $this->sections[md5($pre)] = $pre;
                }
                if (is_object($matchObj) && $matchObj->match($pre)) {
                    if ($specificSection) {
                        $this->sectionsMatch[md5($pre)] = $pre;
                    }
                    $pre = trim($this->parseSub($this->setup));
                    $this->lastConditionTrue = true;
                } else {
                    $pre = $this->nextDivider();
                    $this->lastConditionTrue = false;
                }
            }
        }
        if ($this->inBrace) {
            $this->error('Line ' . ($this->lineNumberOffset + $this->rawP - 1) . ': The script is short of ' . $this->inBrace . ' end brace(s)', LogLevel::INFO);
        }
        if ($this->multiLineEnabled) {
            $this->error('Line ' . ($this->lineNumberOffset + $this->rawP - 1) . ': A multiline value section is not ended with a parenthesis!', LogLevel::INFO);
        }
        $this->lineNumberOffset += count($this->raw) + 1;
    }

    /**
     * Will search for the next condition. When found it will return the line content (the condition value) and have advanced the internal $this->rawP pointer to point to the next line after the condition.
     *
     * @return string The condition value
     * @see parse()
     */
    protected function nextDivider()
    {
        while (isset($this->raw[$this->rawP])) {
            $line = trim($this->raw[$this->rawP]);
            $this->rawP++;
            if ($line && $line[0] === '[') {
                return $line;
            }
        }
        return '';
    }

    /**
     * Parsing the $this->raw TypoScript lines from pointer, $this->rawP
     *
     * @param array $setup Reference to the setup array in which to accumulate the values.
     * @return string Returns the string of the condition found, the exit signal or possible nothing (if it completed parsing with no interruptions)
     */
    protected function parseSub(array &$setup)
    {
        while (isset($this->raw[$this->rawP])) {
            $line = ltrim($this->raw[$this->rawP]);
            $this->rawP++;
            // Set comment flag?
            if (!$this->multiLineEnabled && strpos($line, '/*') === 0) {
                $this->commentSet = true;
            }
            // If $this->multiLineEnabled we will go and get the line values here because we know, the first if() will be TRUE.
            if (!$this->commentSet && ($line || $this->multiLineEnabled)) {
                // If multiline is enabled. Escape by ')'
                if ($this->multiLineEnabled) {
                    // Multiline ends...
                    if (!empty($line[0]) && $line[0] === ')') {
                        // Disable multiline
                        $this->multiLineEnabled = false;
                        $theValue = implode(LF, $this->multiLineValue);
                        if (str_contains($this->multiLineObject, '.')) {
                            // Set the value deeper.
                            $this->setVal($this->multiLineObject, $setup, [$theValue]);
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
                        $this->multiLineValue[] = $this->raw[$this->rawP - 1];
                    }
                } elseif ($this->inBrace === 0 && $line[0] === '[') {
                    if (substr(trim($line), -1, 1) !== ']') {
                        $this->error('Line ' . ($this->lineNumberOffset + $this->rawP - 1) . ': Invalid condition found, any condition must end with "]": ' . $line);
                        return $line;
                    }
                    return $line;
                } else {
                    // Return if GLOBAL condition is set - no matter what.
                    if ($line[0] === '[' && stripos($line, '[GLOBAL]') !== false) {
                        $this->error('Line ' . ($this->lineNumberOffset + $this->rawP - 1) . ': On return to [GLOBAL] scope, the script was short of ' . $this->inBrace . ' end brace(s)', 1);
                        $this->inBrace = 0;
                        return $line;
                    }
                    if ($line[0] !== '}' && $line[0] !== '#' && $line[0] !== '/') {
                        // If not brace-end or comment
                        // Find object name string until we meet an operator
                        $varL = strcspn($line, "\t" . ' {=<>(');
                        // check for special ":=" operator
                        if ($varL > 0 && substr($line, $varL - 1, 2) === ':=') {
                            --$varL;
                        }
                        // also remove tabs after the object string name
                        $objStrName = substr($line, 0, $varL);
                        if ($objStrName !== '') {
                            $r = [];
                            if (preg_match('/[^[:alnum:]\\/_\\\\\\.:-]/i', $objStrName, $r)) {
                                $this->error('Line ' . ($this->lineNumberOffset + $this->rawP - 1) . ': Object Name String, "' . htmlspecialchars($objStrName) . '" contains invalid character "' . $r[0] . '". Must be alphanumeric or one of: "_:-/\\."');
                            } else {
                                $line = ltrim(substr($line, $varL));
                                if ($line === '') {
                                    $this->error('Line ' . ($this->lineNumberOffset + $this->rawP - 1) . ': Object Name String, "' . htmlspecialchars($objStrName) . '" was not followed by any operator, =<>({');
                                } else {
                                    // Checking for special TSparser properties (to change TS values at parsetime)
                                    $match = [];
                                    if ($line[0] === ':' && preg_match('/^:=\\s*([[:alpha:]]+)\\s*\\((.*)\\).*/', $line, $match)) {
                                        $tsFunc = $match[1];
                                        $tsFuncArg = $match[2];
                                        $val = $this->getVal($objStrName, $setup);
                                        $tsFuncArg = str_replace(['\\\\', '\\n', '\\t'], ['\\', LF, "\t"], $tsFuncArg);
                                        $newValue = $this->executeValueModifier($tsFunc, $tsFuncArg, $val[0]);
                                        if (isset($newValue)) {
                                            $line = '= ' . $newValue;
                                        } else {
                                            continue;
                                        }
                                    }
                                    switch ($line[0]) {
                                        case '=':
                                            if (str_contains($objStrName, '.')) {
                                                $value = [];
                                                $value[0] = trim(substr($line, 1));
                                                $this->setVal($objStrName, $setup, $value);
                                            } else {
                                                $setup[$objStrName] = trim(substr($line, 1));
                                                if ($this->lastComment && $this->regComments) {
                                                    // Setting comment..
                                                    $matchingCommentKey = $objStrName . '..';
                                                    if (isset($setup[$matchingCommentKey])) {
                                                        $setup[$matchingCommentKey] .= $this->lastComment;
                                                    } else {
                                                        $setup[$matchingCommentKey] = $this->lastComment;
                                                    }
                                                }
                                                if ($this->regLinenumbers) {
                                                    $setup[$objStrName . '.ln..'][] = $this->lineNumberOffset + $this->rawP - 1;
                                                }
                                            }
                                            break;
                                        case '{':
                                            $this->inBrace++;
                                            if (str_contains($objStrName, '.')) {
                                                $exitSig = $this->rollParseSub($objStrName, $setup);
                                                if ($exitSig) {
                                                    return $exitSig;
                                                }
                                            } else {
                                                if (!isset($setup[$objStrName . '.'])) {
                                                    $setup[$objStrName . '.'] = [];
                                                }
                                                $exitSig = $this->parseSub($setup[$objStrName . '.']);
                                                if ($exitSig) {
                                                    return $exitSig;
                                                }
                                            }
                                            break;
                                        case '(':
                                            $this->multiLineObject = $objStrName;
                                            $this->multiLineEnabled = true;
                                            $this->multiLineValue = [];
                                            break;
                                        case '<':
                                            $theVal = trim(substr($line, 1));
                                            if (str_starts_with($theVal, '.')) {
                                                $res = $this->getVal(substr($theVal, 1), $setup);
                                            } else {
                                                $res = $this->getVal($theVal, $this->setup);
                                            }
                                            if ($res[0] === '') {
                                                unset($res[0]);
                                            }
                                            if ($res[1] === []) {
                                                unset($res[1]);
                                            }
                                            // unserialize(serialize(...)) may look stupid but is needed because of some reference issues.
                                            // See forge issue #76919 and functional test hasFlakyReferences()
                                            $this->setVal($objStrName, $setup, unserialize(serialize($res), ['allowed_classes' => false]), true);
                                            break;
                                        case '>':
                                            $this->setVal($objStrName, $setup, 'UNSET');
                                            break;
                                        default:
                                            $this->error('Line ' . ($this->lineNumberOffset + $this->rawP - 1) . ': Object Name String, "' . htmlspecialchars($objStrName) . '" was not followed by any operator, =<>({');
                                    }
                                }
                            }
                            $this->lastComment = '';
                        }
                    } elseif ($line[0] === '}') {
                        $this->inBrace--;
                        $this->lastComment = '';
                        if ($this->inBrace < 0) {
                            $this->error('Line ' . ($this->lineNumberOffset + $this->rawP - 1) . ': An end brace is in excess.', LogLevel::INFO);
                            $this->inBrace = 0;
                        } else {
                            break;
                        }
                    } else {
                        // Comment. The comments are concatenated in this temporary string:
                        if ($this->regComments) {
                            $this->lastComment .= rtrim($line) . LF;
                        }
                    }
                    if (strpos($line, '### ERROR') === 0) {
                        $this->error(substr($line, 11));
                    }
                }
            }
            // Unset comment
            if ($this->commentSet) {
                if (str_contains($line, '*/')) {
                    $this->commentSet = false;
                }
            }
        }
        return '';
    }

    /**
     * Executes operator functions, called from TypoScript
     * example: page.10.value := appendString(!)
     *
     * @param string $modifierName TypoScript function called
     * @param string $modifierArgument Function arguments; In case of multiple arguments, the method must split on its own
     * @param string $currentValue Current TypoScript value
     * @return string|null Modified result or null for no modification
     */
    protected function executeValueModifier($modifierName, $modifierArgument = null, $currentValue = null)
    {
        $modifierArgumentAsString = (string)$modifierArgument;
        $currentValueAsString = (string)$currentValue;
        $newValue = null;
        switch ($modifierName) {
            case 'prependString':
                $newValue = $modifierArgumentAsString . $currentValueAsString;
                break;
            case 'appendString':
                $newValue = $currentValueAsString . $modifierArgumentAsString;
                break;
            case 'removeString':
                $newValue = str_replace($modifierArgumentAsString, '', $currentValueAsString);
                break;
            case 'replaceString':
                $modifierArgumentArray = explode('|', $modifierArgumentAsString, 2);
                $fromStr = $modifierArgumentArray[0] ?? '';
                $toStr = $modifierArgumentArray[1] ?? '';
                $newValue = str_replace($fromStr, $toStr, $currentValueAsString);
                break;
            case 'addToList':
                $newValue = ($currentValueAsString !== '' ? $currentValueAsString . ',' : '') . $modifierArgumentAsString;
                break;
            case 'removeFromList':
                $existingElements = GeneralUtility::trimExplode(',', $currentValueAsString);
                $removeElements = GeneralUtility::trimExplode(',', $modifierArgumentAsString);
                if (!empty($removeElements)) {
                    $newValue = implode(',', array_diff($existingElements, $removeElements));
                }
                break;
            case 'uniqueList':
                $elements = GeneralUtility::trimExplode(',', $currentValueAsString);
                $newValue = implode(',', array_unique($elements));
                break;
            case 'reverseList':
                $elements = GeneralUtility::trimExplode(',', $currentValueAsString);
                $newValue = implode(',', array_reverse($elements));
                break;
            case 'sortList':
                $elements = GeneralUtility::trimExplode(',', $currentValueAsString);
                $arguments = GeneralUtility::trimExplode(',', $modifierArgumentAsString);
                $arguments = array_map('strtolower', $arguments);
                $sort_flags = SORT_REGULAR;
                if (in_array('numeric', $arguments)) {
                    $sort_flags = SORT_NUMERIC;
                    // If the sorting modifier "numeric" is given, all values
                    // are checked and an exception is thrown if a non-numeric value is given
                    // otherwise there is a different behaviour between PHP7 and PHP 5.x
                    // See also the warning on http://us.php.net/manual/en/function.sort.php
                    foreach ($elements as $element) {
                        if (!is_numeric($element)) {
                            throw new \InvalidArgumentException('The list "' . $currentValueAsString . '" should be sorted numerically but contains a non-numeric value', 1438191758);
                        }
                    }
                }
                sort($elements, $sort_flags);
                if (in_array('descending', $arguments)) {
                    $elements = array_reverse($elements);
                }
                $newValue = implode(',', $elements);
                break;
            case 'getEnv':
                $environmentValue = getenv(trim($modifierArgumentAsString));
                if ($environmentValue !== false) {
                    $newValue = $environmentValue;
                }
                break;
            default:
                if (isset($GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS']['t3lib/class.t3lib_tsparser.php']['preParseFunc'][$modifierName])) {
                    $hookMethod = $GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS']['t3lib/class.t3lib_tsparser.php']['preParseFunc'][$modifierName];
                    $params = ['currentValue' => $currentValue, 'functionArgument' => $modifierArgument];
                    $fakeThis = null;
                    $newValue = GeneralUtility::callUserFunction($hookMethod, $params, $fakeThis);
                } else {
                    self::getLogger()->warning('Missing function definition for {modifier_name} on TypoScript', [
                        'modifier_name' => $modifierName,
                    ]);
                }
        }
        return $newValue;
    }

    /**
     * Parsing of TypoScript keys inside a curly brace where the key is composite of at least two keys,
     * thus having to recursively call itself to get the value
     *
     * @param string $string The object sub-path, eg "thisprop.another_prot
     * @param array $setup The local setup array from the function calling this function
     * @return string Returns the exitSignal
     * @see parseSub()
     */
    protected function rollParseSub($string, array &$setup)
    {
        if ((string)$string === '') {
            return '';
        }

        [$key, $remainingKey] = $this->parseNextKeySegment($string);
        $key .= '.';
        if (!isset($setup[$key])) {
            $setup[$key] = [];
        }
        $exitSig = $remainingKey === ''
            ? $this->parseSub($setup[$key])
            : $this->rollParseSub($remainingKey, $setup[$key]);
        return $exitSig ?: '';
    }

    /**
     * Get a value/property pair for an object path in TypoScript, eg. "myobject.myvalue.mysubproperty".
     * Here: Used by the "copy" operator, <
     *
     * @param string $string Object path for which to get the value
     * @param array $setup Global setup code if $string points to a global object path. But if string is prefixed with "." then its the local setup array.
     * @return array An array with keys 0/1 being value/property respectively
     */
    public function getVal($string, $setup): array
    {
        $retArr = [
            0 => '',
            1 => [],
        ];
        if ((string)$string === '') {
            return $retArr;
        }

        [$key, $remainingKey] = $this->parseNextKeySegment($string);
        $subKey = $key . '.';
        if ($remainingKey === '') {
            $retArr[0] = $setup[$key] ?? $retArr[0];
            $retArr[1] = $setup[$subKey] ?? $retArr[1];
            return $retArr;
        }
        if (isset($setup[$subKey])) {
            return $this->getVal($remainingKey, $setup[$subKey]);
        }

        return $retArr;
    }

    /**
     * Setting a value/property of an object string in the setup array.
     *
     * @param string $string The object sub-path, eg "thisprop.another_prot
     * @param array $setup The local setup array from the function calling this function.
     * @param array|string $value The value/property pair array to set. If only one of them is set, then the other is not touched (unless $wipeOut is set, which it is when copies are made which must include both value and property)
     * @param bool $wipeOut If set, then both value and property is wiped out when a copy is made of another value.
     */
    protected function setVal($string, array &$setup, $value, $wipeOut = false)
    {
        if ((string)$string === '') {
            return;
        }

        [$key, $remainingKey] = $this->parseNextKeySegment($string);
        $subKey = $key . '.';
        if ($remainingKey === '') {
            if ($value === 'UNSET') {
                unset($setup[$key]);
                unset($setup[$subKey]);
                if ($this->regLinenumbers) {
                    $setup[$key . '.ln..'][] = ($this->lineNumberOffset + $this->rawP - 1) . '>';
                }
            } else {
                $lnRegisDone = 0;
                if ($wipeOut) {
                    unset($setup[$key]);
                    unset($setup[$subKey]);
                    if ($this->regLinenumbers) {
                        $setup[$key . '.ln..'][] = ($this->lineNumberOffset + $this->rawP - 1) . '<';
                        $lnRegisDone = 1;
                    }
                }
                if (isset($value[0])) {
                    $setup[$key] = $value[0];
                }
                if (isset($value[1])) {
                    $setup[$subKey] = $value[1];
                }
                if ($this->lastComment && $this->regComments) {
                    $setup[$key . '..'] = $setup[$key . '..'] ?? '' . $this->lastComment;
                }
                if ($this->regLinenumbers && !$lnRegisDone) {
                    $setup[$key . '.ln..'][] = $this->lineNumberOffset + $this->rawP - 1;
                }
            }
        } else {
            if (!isset($setup[$subKey])) {
                $setup[$subKey] = [];
            }
            $this->setVal($remainingKey, $setup[$subKey], $value);
        }
    }

    /**
     * Determines the first key segment of a TypoScript key by searching for the first
     * unescaped dot in the given key string.
     *
     * Since the escape characters are only needed to correctly determine the key
     * segment any escape characters before the first unescaped dot are
     * stripped from the key.
     *
     * @param string $key The key, possibly consisting of multiple key segments separated by unescaped dots
     * @return array Array with key segment and remaining part of $key
     */
    protected function parseNextKeySegment($key)
    {
        // if no dot is in the key, nothing to do
        $dotPosition = strpos($key, '.');
        if ($dotPosition === false) {
            return [$key, ''];
        }

        if (str_contains($key, '\\')) {
            // backslashes are in the key, so we do further parsing

            while ($dotPosition !== false) {
                if ($dotPosition > 0 && $key[$dotPosition - 1] !== '\\' || $dotPosition > 1 && $key[$dotPosition - 2] === '\\') {
                    break;
                }
                // escaped dot found, continue
                $dotPosition = strpos($key, '.', $dotPosition + 1);
            }

            if ($dotPosition === false) {
                // no regular dot found
                $keySegment = $key;
                $remainingKey = '';
            } else {
                if ($dotPosition > 1 && $key[$dotPosition - 2] === '\\' && $key[$dotPosition - 1] === '\\') {
                    $keySegment = substr($key, 0, $dotPosition - 1);
                } else {
                    $keySegment = substr($key, 0, $dotPosition);
                }
                $remainingKey = substr($key, $dotPosition + 1);
            }

            // fix key segment by removing escape sequences
            $keySegment = str_replace('\\.', '.', $keySegment);
        } else {
            // no backslash in the key, we're fine off
            [$keySegment, $remainingKey] = explode('.', $key, 2);
        }
        return [$keySegment, $remainingKey];
    }

    /**
     * Stacks errors/messages from the TypoScript parser into an internal array, $this->error
     * If "TT" is a global object (as it is in the frontend when backend users are logged in) the message will be registered here as well.
     *
     * @param string $message The error message string
     * @param int|string $logLevel The error severity (in the scale of TimeTracker::setTSlogMessage: Approx: 2=warning, 1=info, 0=nothing, 3=fatal.)
     */
    protected function error($message, $logLevel = LogLevel::WARNING)
    {
        $this->getTimeTracker()->setTSlogMessage($message, $logLevel);
        $this->errors[] = [$message, $logLevel, $this->rawP - 1, $this->lineNumberOffset];
    }

    /**
     * Checks the input string (un-parsed TypoScript) for include-commands ("<INCLUDE_TYPOSCRIPT: ....")
     * Use: \TYPO3\CMS\Core\TypoScript\Parser\TypoScriptParser::checkIncludeLines()
     *
     * @param string $string Unparsed TypoScript
     * @param int $cycle_counter Counter for detecting endless loops
     * @param bool $returnFiles When set an array containing the resulting typoscript and all included files will get returned
     * @param string $parentFilenameOrPath The parent file (with absolute path) or path for relative includes
     * @return string|array Complete TypoScript with includes added.
     * @static
     */
    public static function checkIncludeLines($string, $cycle_counter = 1, $returnFiles = false, $parentFilenameOrPath = '')
    {
        $includedFiles = [];
        if ($cycle_counter > 100) {
            self::getLogger()->warning('It appears like TypoScript code is looping over itself. Check your templates for "<INCLUDE_TYPOSCRIPT: ..." tags');
            if ($returnFiles) {
                return [
                    'typoscript' => '',
                    'files' => $includedFiles,
                ];
            }
            return '
###
### ERROR: Recursion!
###
';
        }

        // Return early if $string is invalid
        if (!is_string($string) || empty($string)) {
            return !$returnFiles
                ? ''
                : [
                    'typoscript' => '',
                    'files' => $includedFiles,
                ]
            ;
        }

        $string = StringUtility::removeByteOrderMark($string);

        // Checking for @import syntax imported files
        $string = self::addImportsFromExternalFiles($string, $cycle_counter, $returnFiles, $includedFiles, $parentFilenameOrPath);

        // If no tags found, no need to do slower preg_split
        if (str_contains($string, '<INCLUDE_TYPOSCRIPT:')) {
            $splitRegEx = '/\r?\n\s*<INCLUDE_TYPOSCRIPT:\s*(?i)source\s*=\s*"((?i)file|dir):\s*([^"]*)"(.*)>[\ \t]*/';
            $parts = preg_split($splitRegEx, LF . $string . LF, -1, PREG_SPLIT_DELIM_CAPTURE);
            $parts = is_array($parts) ? $parts : [];

            // First text part goes through
            $newString = ($parts[0] ?? '') . LF;
            $partCount = count($parts);
            for ($i = 1; $i + 3 < $partCount; $i += 4) {
                // $parts[$i] contains 'FILE' or 'DIR'
                // $parts[$i+1] contains relative file or directory path to be included
                // $parts[$i+2] optional properties of the INCLUDE statement
                // $parts[$i+3] next part of the typoscript string (part in between include-tags)
                $includeType = $parts[$i];
                $filename = $parts[$i + 1];
                $originalFilename = $filename;
                $optionalProperties = $parts[$i + 2];
                $tsContentsTillNextInclude = $parts[$i + 3];

                // Check condition
                $matches = preg_split('#(?i)condition\\s*=\\s*"((?:\\\\\\\\|\\\\"|[^\\"])*)"(\\s*|>)#', $optionalProperties, 2, PREG_SPLIT_DELIM_CAPTURE);
                $matches = is_array($matches) ? $matches : [];

                // If there was a condition
                if (count($matches) > 1) {
                    // Unescape the condition
                    $condition = trim(stripslashes($matches[1]));
                    // If necessary put condition in square brackets
                    if ($condition[0] !== '[') {
                        $condition = '[' . $condition . ']';
                    }

                    $conditionMatcher = null;
                    if (($GLOBALS['TYPO3_REQUEST'] ?? null) instanceof ServerRequestInterface
                        && ApplicationType::fromRequest($GLOBALS['TYPO3_REQUEST'])->isFrontend()
                    ) {
                        $conditionMatcher = GeneralUtility::makeInstance(FrontendConditionMatcher::class);
                    } else {
                        $conditionMatcher = GeneralUtility::makeInstance(BackendConditionMatcher::class);
                    }

                    // If it didn't match then proceed to the next include, but prepend next normal (not file) part to output string
                    if (!$conditionMatcher->match($condition)) {
                        $newString .= $tsContentsTillNextInclude . LF;
                        continue;
                    }
                }

                // Resolve a possible relative paths if a parent file is given
                if ($parentFilenameOrPath !== '' && $filename[0] === '.') {
                    $filename = PathUtility::getAbsolutePathOfRelativeReferencedFileOrPath($parentFilenameOrPath, $filename);
                }

                // There must be a line-break char after - not sure why this check is necessary, kept it for being 100% backwards compatible
                // An empty string is also ok (means that the next line is also a valid include_typoscript tag)
                if (!preg_match('/(^\\s*\\r?\\n|^$)/', $tsContentsTillNextInclude)) {
                    $newString .= self::typoscriptIncludeError('Invalid characters after <INCLUDE_TYPOSCRIPT: source="' . $includeType . ':' . $filename . '">-tag (rest of line must be empty).');
                } elseif (str_contains('..', $filename)) {
                    $newString .= self::typoscriptIncludeError('Invalid filepath "' . $filename . '" (containing "..").');
                } else {
                    switch (strtolower($includeType)) {
                        case 'file':
                            self::includeFile($originalFilename, $cycle_counter, $returnFiles, $newString, $includedFiles, $optionalProperties, $parentFilenameOrPath);
                            break;
                        case 'dir':
                            self::includeDirectory($originalFilename, $cycle_counter, $returnFiles, $newString, $includedFiles, $optionalProperties, $parentFilenameOrPath);
                            break;
                        default:
                            $newString .= self::typoscriptIncludeError('No valid option for INCLUDE_TYPOSCRIPT source property (valid options are FILE or DIR)');
                    }
                }
                // Prepend next normal (not file) part to output string
                $newString .= $tsContentsTillNextInclude . LF;

                // load default TypoScript for content rendering templates like
                // fluid_styled_content if those have been included through f.e.
                // <INCLUDE_TYPOSCRIPT: source="FILE:EXT:fluid_styled_content/Configuration/TypoScript/setup.typoscript">
                if (strpos(strtolower($filename), 'ext:') === 0) {
                    $filePointerPathParts = explode('/', substr($filename, 4));

                    // remove file part, determine whether to load setup or constants
                    [$includeType, ] = explode('.', (string)array_pop($filePointerPathParts));

                    if (in_array($includeType, ['setup', 'constants'])) {
                        // adapt extension key to required format (no underscores)
                        $filePointerPathParts[0] = str_replace('_', '', $filePointerPathParts[0]);

                        // load default TypoScript
                        $defaultTypoScriptKey = implode('/', $filePointerPathParts) . '/';
                        if (in_array($defaultTypoScriptKey, $GLOBALS['TYPO3_CONF_VARS']['FE']['contentRenderingTemplates'], true)) {
                            $newString .= $GLOBALS['TYPO3_CONF_VARS']['FE']['defaultTypoScript_' . $includeType . '.']['defaultContentRendering'] ?? '';
                        }
                    }
                }
            }
            // Add a line break before and after the included code in order to make sure that the parser always has a LF.
            $string = LF . trim($newString) . LF;
        }
        // When all included files should get returned, simply return a compound array containing
        // the TypoScript with all "includes" processed and the files which got included
        if ($returnFiles) {
            return [
                'typoscript' => $string,
                'files' => $includedFiles,
            ];
        }
        return $string;
    }

    /**
     * Splits the unparsed TypoScript content into import statements
     *
     * @param string $typoScript unparsed TypoScript
     * @param int $cycleCounter counter to stop recursion
     * @param bool $returnFiles whether to populate the included Files or not
     * @param array $includedFiles - by reference - if any included files are added, they are added here
     * @param string $parentFilenameOrPath the current imported file to resolve relative paths - handled by reference
     * @return string the unparsed TypoScript with included external files
     */
    protected static function addImportsFromExternalFiles($typoScript, $cycleCounter, $returnFiles, &$includedFiles, &$parentFilenameOrPath)
    {
        // Check for new syntax "@import 'EXT:bennilove/Configuration/TypoScript/*'"
        if (is_string($typoScript) && (str_contains($typoScript, '@import \'') || str_contains($typoScript, '@import "'))) {
            $splitRegEx = '/\r?\n\s*@import\s[\'"]([^\'"]*)[\'"][\ \t]?/';
            $parts = preg_split($splitRegEx, LF . $typoScript . LF, -1, PREG_SPLIT_DELIM_CAPTURE);
            $parts = is_array($parts) ? $parts : [];
            // First text part goes through
            $newString = $parts[0] . LF;
            $partCount = count($parts);
            for ($i = 1; $i + 2 <= $partCount; $i += 2) {
                $filename = $parts[$i];
                $tsContentsTillNextInclude = $parts[$i + 1];
                // Resolve a possible relative paths if a parent file is given
                if ($parentFilenameOrPath !== '' && $filename[0] === '.') {
                    $filename = PathUtility::getAbsolutePathOfRelativeReferencedFileOrPath($parentFilenameOrPath, $filename);
                }
                $newString .= self::importExternalTypoScriptFile($filename, $cycleCounter, $returnFiles, $includedFiles);
                // Prepend next normal (not file) part to output string
                $newString .= $tsContentsTillNextInclude;
            }
            // Add a line break before and after the included code in order to make sure that the parser always has a LF.
            $typoScript = LF . trim($newString) . LF;
        }
        return $typoScript;
    }

    /**
     * Include file $filename. Contents of the file will be returned, filename is added to &$includedFiles.
     * Further include/import statements in the contents are processed recursively.
     *
     * @param string $filename Full absolute path+filename to the typoscript file to be included
     * @param int $cycleCounter Counter for detecting endless loops
     * @param bool $returnFiles When set, filenames of included files will be prepended to the array $includedFiles
     * @param array $includedFiles Array to which the filenames of included files will be prepended (referenced)
     * @return string the unparsed TypoScript content from external files
     */
    protected static function importExternalTypoScriptFile($filename, $cycleCounter, $returnFiles, array &$includedFiles)
    {
        if (str_contains('..', $filename)) {
            return self::typoscriptIncludeError('Invalid filepath "' . $filename . '" (containing "..").');
        }

        $content = '';
        $absoluteFileName = GeneralUtility::getFileAbsFileName($filename);
        if ((string)$absoluteFileName === '') {
            return self::typoscriptIncludeError('Illegal filepath "' . $filename . '".');
        }

        $finder = new Finder();
        $finder
            // no recursive mode on purpose
            ->depth(0)
            // no directories should be fetched
            ->files()
            ->sortByName();

        // Search all files in the folder
        if (is_dir($absoluteFileName)) {
            $finder
                ->in($absoluteFileName)
                ->name('*.typoscript');
            // Used for the TypoScript comments
            $readableFilePrefix = $filename;
        } else {
            try {
                // Apparently this is not a folder, so the restriction
                // is the folder so we restrict into this folder
                $finder->in(PathUtility::dirname($absoluteFileName));
                if (!is_file($absoluteFileName)
                    && !str_contains(PathUtility::basename($absoluteFileName), '*')
                    && substr(PathUtility::basename($absoluteFileName), -11) !== '.typoscript') {
                    $absoluteFileName .= '*.typoscript';
                }
                $finder->name(PathUtility::basename($absoluteFileName));
                $readableFilePrefix = PathUtility::dirname($filename);
            } catch (\InvalidArgumentException $e) {
                return self::typoscriptIncludeError($e->getMessage());
            }
        }

        foreach ($finder as $fileObject) {
            // Clean filename output for comments
            $readableFileName = rtrim($readableFilePrefix, '/') . '/' . $fileObject->getFilename();
            $content .= LF . '### @import \'' . $readableFileName . '\' begin ###' . LF;
            // Check for allowed files
            if (!GeneralUtility::makeInstance(FileNameValidator::class)->isValid($fileObject->getFilename())) {
                $content .= self::typoscriptIncludeError('File "' . $readableFileName . '" was not included since it is not allowed due to fileDenyPattern.');
            } else {
                $includedFiles[] = $fileObject->getPathname();
                // check for includes in included text
                $included_text = self::checkIncludeLines($fileObject->getContents(), $cycleCounter++, $returnFiles, $absoluteFileName);
                // If the method also has to return all included files, merge currently included
                // files with files included by recursively calling itself
                if ($returnFiles && is_array($included_text)) {
                    $includedFiles = array_merge($includedFiles, $included_text['files']);
                    $included_text = $included_text['typoscript'];
                }
                $content .= $included_text . LF;
            }
            $content .= '### @import \'' . $readableFileName . '\' end ###' . LF . LF;

            // load default TypoScript for content rendering templates like
            // fluid_styled_content if those have been included through e.g.
            // @import "fluid_styled_content/Configuration/TypoScript/setup.typoscript"
            if (PathUtility::isExtensionPath(strtoupper($filename))) {
                $filePointerPathParts = explode('/', substr($filename, 4));
                // remove file part, determine whether to load setup or constants
                [$includeType] = explode('.', (string)array_pop($filePointerPathParts));

                if (in_array($includeType, ['setup', 'constants'], true)) {
                    // adapt extension key to required format (no underscores)
                    $filePointerPathParts[0] = str_replace('_', '', $filePointerPathParts[0]);

                    // load default TypoScript
                    $defaultTypoScriptKey = implode('/', $filePointerPathParts) . '/';
                    if (in_array($defaultTypoScriptKey, $GLOBALS['TYPO3_CONF_VARS']['FE']['contentRenderingTemplates'], true)) {
                        $content .= $GLOBALS['TYPO3_CONF_VARS']['FE']['defaultTypoScript_' . $includeType . '.']['defaultContentRendering'] ?? '';
                    }
                }
            }
        }

        if (empty($content)) {
            return self::typoscriptIncludeError('No file or folder found for importing TypoScript on "' . $filename . '".');
        }
        return $content;
    }

    /**
     * Include file $filename. Contents of the file will be prepended to &$newstring, filename to &$includedFiles
     * Further include_typoscript tags in the contents are processed recursively
     *
     * @param string $filename Relative path to the typoscript file to be included
     * @param int $cycle_counter Counter for detecting endless loops
     * @param bool $returnFiles When set, filenames of included files will be prepended to the array $includedFiles
     * @param string $newString The output string to which the content of the file will be prepended (referenced
     * @param array $includedFiles Array to which the filenames of included files will be prepended (referenced)
     * @param string $optionalProperties
     * @param string $parentFilenameOrPath The parent file (with absolute path) or path for relative includes
     * @static
     * @internal
     */
    public static function includeFile($filename, $cycle_counter = 1, $returnFiles = false, &$newString = '', array &$includedFiles = [], $optionalProperties = '', $parentFilenameOrPath = '')
    {
        // Resolve a possible relative paths if a parent file is given
        if ($parentFilenameOrPath !== '' && $filename[0] === '.') {
            $absfilename = PathUtility::getAbsolutePathOfRelativeReferencedFileOrPath($parentFilenameOrPath, $filename);
        } else {
            $absfilename = $filename;
        }
        $absfilename = GeneralUtility::getFileAbsFileName($absfilename);

        $newString .= LF . '### <INCLUDE_TYPOSCRIPT: source="FILE:' . $filename . '"' . $optionalProperties . '> BEGIN:' . LF;
        if ((string)$filename !== '') {
            // Must exist and must not contain '..' and must be relative
            // Check for allowed files
            if (!GeneralUtility::makeInstance(FileNameValidator::class)->isValid($absfilename)) {
                $newString .= self::typoscriptIncludeError('File "' . $filename . '" was not included since it is not allowed due to fileDenyPattern.');
            } else {
                $fileExists = false;
                if (@file_exists($absfilename)) {
                    $fileExists = true;
                }

                if ($fileExists) {
                    $includedFiles[] = $absfilename;
                    // check for includes in included text
                    $included_text = self::checkIncludeLines((string)file_get_contents($absfilename), $cycle_counter + 1, $returnFiles, $absfilename);
                    // If the method also has to return all included files, merge currently included
                    // files with files included by recursively calling itself
                    if ($returnFiles && is_array($included_text)) {
                        $includedFiles = array_merge($includedFiles, $included_text['files']);
                        $included_text = $included_text['typoscript'];
                    }
                    $newString .= $included_text . LF;
                } else {
                    $newString .= self::typoscriptIncludeError('File "' . $filename . '" was not found.');
                }
            }
        }
        $newString .= '### <INCLUDE_TYPOSCRIPT: source="FILE:' . $filename . '"' . $optionalProperties . '> END:' . LF . LF;
    }

    /**
     * Include all files with matching Typoscript extensions in directory $dirPath. Contents of the files are
     * prepended to &$newstring, filename to &$includedFiles.
     * Order of the directory items to be processed: files first, then directories, both in alphabetical order.
     * Further include_typoscript tags in the contents of the files are processed recursively.
     *
     * @param string $dirPath Relative path to the directory to be included
     * @param int $cycle_counter Counter for detecting endless loops
     * @param bool $returnFiles When set, filenames of included files will be prepended to the array $includedFiles
     * @param string $newString The output string to which the content of the file will be prepended (referenced)
     * @param array $includedFiles Array to which the filenames of included files will be prepended (referenced)
     * @param string $optionalProperties
     * @param string $parentFilenameOrPath The parent file (with absolute path) or path for relative includes
     * @static
     */
    protected static function includeDirectory($dirPath, $cycle_counter = 1, $returnFiles = false, &$newString = '', array &$includedFiles = [], $optionalProperties = '', $parentFilenameOrPath = '')
    {
        // Extract the value of the property extensions="..."
        $matches = preg_split('#(?i)extensions\s*=\s*"([^"]*)"(\s*|>)#', $optionalProperties, 2, PREG_SPLIT_DELIM_CAPTURE);
        $matches = is_array($matches) ? $matches : [];
        if (count($matches) > 1) {
            $includedFileExtensions = $matches[1];
        } else {
            $includedFileExtensions = '';
        }

        // Resolve a possible relative paths if a parent file is given
        if ($parentFilenameOrPath !== '' && $dirPath[0] === '.') {
            $resolvedDirPath = PathUtility::getAbsolutePathOfRelativeReferencedFileOrPath($parentFilenameOrPath, $dirPath);
        } else {
            $resolvedDirPath = $dirPath;
        }
        $absDirPath = GeneralUtility::getFileAbsFileName($resolvedDirPath);
        if ($absDirPath) {
            $absDirPath = rtrim($absDirPath, '/') . '/';
            $newString .= LF . '### <INCLUDE_TYPOSCRIPT: source="DIR:' . $dirPath . '"' . $optionalProperties . '> BEGIN:' . LF;
            // Get alphabetically sorted file index in array
            $fileIndex = GeneralUtility::getAllFilesAndFoldersInPath([], $absDirPath, $includedFileExtensions);
            // Prepend file contents to $newString
            foreach ($fileIndex as $absFileRef) {
                self::includeFile($absFileRef, $cycle_counter, $returnFiles, $newString, $includedFiles);
            }
            $newString .= '### <INCLUDE_TYPOSCRIPT: source="DIR:' . $dirPath . '"' . $optionalProperties . '> END:' . LF . LF;
        } else {
            $newString .= self::typoscriptIncludeError('The path "' . $resolvedDirPath . '" is invalid.');
        }
    }

    /**
     * Process errors in INCLUDE_TYPOSCRIPT tags
     * Errors are logged and printed in the concatenated TypoScript result (as can be seen in Template Analyzer)
     *
     * @param string $error Text of the error message
     * @return string The error message encapsulated in comments
     * @static
     */
    protected static function typoscriptIncludeError($error)
    {
        self::getLogger()->warning($error);
        return "\n###\n### ERROR: " . $error . "\n###\n\n";
    }

    /**
     * Parses the string in each value of the input array for include-commands
     *
     * @param array $array Array with TypoScript in each value
     * @return array Same array but where the values has been parsed for include-commands
     */
    public static function checkIncludeLines_array(array $array)
    {
        foreach ($array as $k => $v) {
            $array[$k] = self::checkIncludeLines($array[$k]);
        }
        return $array;
    }

    /**
     * Search for commented INCLUDE_TYPOSCRIPT statements
     * and save the content between the BEGIN and the END line to the specified file
     *
     * @param string  $string Template content
     * @param int $cycle_counter Counter for detecting endless loops
     * @param array   $extractedFileNames
     * @param string  $parentFilenameOrPath
     *
     * @throws \RuntimeException
     * @throws \UnexpectedValueException
     * @return string Template content with uncommented include statements
     * @internal
     */
    public static function extractIncludes($string, $cycle_counter = 1, array $extractedFileNames = [], $parentFilenameOrPath = '')
    {
        if ($cycle_counter > 10) {
            self::getLogger()->warning('It appears like TypoScript code is looping over itself. Check your templates for "<INCLUDE_TYPOSCRIPT: ..." tags');
            return '
###
### ERROR: Recursion!
###
';
        }
        $expectedEndTag = '';
        $fileContent = [];
        $restContent = [];
        $fileName = null;
        $inIncludePart = false;
        $lines = preg_split("/\r\n|\n|\r/", $string);
        $skipNextLineIfEmpty = false;
        $openingCommentedIncludeStatement = null;
        $optionalProperties = '';
        foreach ($lines as $line) {
            // \TYPO3\CMS\Core\TypoScript\Parser\TypoScriptParser::checkIncludeLines inserts
            // an additional empty line, remove this again
            if ($skipNextLineIfEmpty) {
                if (trim($line) === '') {
                    continue;
                }
                $skipNextLineIfEmpty = false;
            }

            // Outside commented include statements
            if (!$inIncludePart) {
                // Search for beginning commented include statements
                if (preg_match('/###\\s*<INCLUDE_TYPOSCRIPT:\\s*source\\s*=\\s*"\\s*((?i)file|dir)\\s*:\\s*([^"]*)"(.*)>\\s*BEGIN/i', $line, $matches)) {
                    // Found a commented include statement

                    // Save this line in case there is no ending tag
                    $openingCommentedIncludeStatement = trim($line);
                    $openingCommentedIncludeStatement = preg_replace('/\\s*### Warning: .*###\\s*/', '', $openingCommentedIncludeStatement);

                    // type of match: FILE or DIR
                    $inIncludePart = strtoupper($matches[1]);
                    $fileName = $matches[2];
                    $optionalProperties = $matches[3];

                    $expectedEndTag = '### <INCLUDE_TYPOSCRIPT: source="' . $inIncludePart . ':' . $fileName . '"' . $optionalProperties . '> END';
                    // Strip all whitespace characters to make comparison safer
                    $expectedEndTag = strtolower(preg_replace('/\s/', '', $expectedEndTag) ?? '');
                } else {
                    // If this is not a beginning commented include statement this line goes into the rest content
                    $restContent[] = $line;
                }
            } else {
                // Inside commented include statements
                // Search for the matching ending commented include statement
                $strippedLine = preg_replace('/\s/', '', $line);
                if (stripos($strippedLine, $expectedEndTag) !== false) {
                    // Found the matching ending include statement
                    $fileContentString = implode(PHP_EOL, $fileContent);

                    // Write the content to the file

                    // Resolve a possible relative paths if a parent file is given
                    if ($parentFilenameOrPath !== '' && $fileName[0] === '.') {
                        $realFileName = PathUtility::getAbsolutePathOfRelativeReferencedFileOrPath($parentFilenameOrPath, $fileName);
                    } else {
                        $realFileName = $fileName;
                    }
                    $realFileName = GeneralUtility::getFileAbsFileName($realFileName);

                    if ($inIncludePart === 'FILE') {
                        // Some file checks
                        if (!GeneralUtility::makeInstance(FileNameValidator::class)->isValid($realFileName)) {
                            throw new \UnexpectedValueException(sprintf('File "%s" was not included since it is not allowed due to fileDenyPattern.', $fileName), 1382651858);
                        }
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
                        $fileContentString = self::extractIncludes($fileContentString, $cycle_counter + 1, $extractedFileNames, $realFileName);

                        // Write the content to the file
                        if (!GeneralUtility::writeFile($realFileName, $fileContentString)) {
                            throw new \RuntimeException(sprintf('Could not write file "%s"', $realFileName), 1294586444);
                        }
                        // Insert reference to the file in the rest content
                        $restContent[] = '<INCLUDE_TYPOSCRIPT: source="FILE:' . $fileName . '"' . $optionalProperties . '>';
                    } else {
                        // must be DIR

                        // Some file checks
                        if (empty($realFileName)) {
                            throw new \UnexpectedValueException(sprintf('"%s" is not a valid location.', $fileName), 1366493602);
                        }
                        if (!is_dir($realFileName)) {
                            throw new \RuntimeException(sprintf('"%s" is not a directory.', $fileName), 1366493603);
                        }
                        if (in_array($realFileName, $extractedFileNames)) {
                            throw new \RuntimeException(sprintf('Recursive/multiple inclusion of directory "%s"', $realFileName), 1366493604);
                        }
                        $extractedFileNames[] = $realFileName;

                        // Recursive call to detected nested commented include statements
                        self::extractIncludes($fileContentString, $cycle_counter + 1, $extractedFileNames, $realFileName);

                        // just drop content between tags since it should usually just contain individual files from that dir

                        // Insert reference to the dir in the rest content
                        $restContent[] = '<INCLUDE_TYPOSCRIPT: source="DIR:' . $fileName . '"' . $optionalProperties . '>';
                    }

                    // Reset variables (preparing for the next commented include statement)
                    $fileContent = [];
                    $fileName = null;
                    $inIncludePart = false;
                    $openingCommentedIncludeStatement = null;
                    // \TYPO3\CMS\Core\TypoScript\Parser\TypoScriptParser::checkIncludeLines inserts
                    // an additional empty line, remove this again
                    $skipNextLineIfEmpty = true;
                } else {
                    // If this is not an ending commented include statement this line goes into the file content
                    $fileContent[] = $line;
                }
            }
        }
        // If we're still inside commented include statements copy the lines back to the rest content
        if ($inIncludePart) {
            $restContent[] = $openingCommentedIncludeStatement . ' ### Warning: Corresponding end line missing! ###';
            $restContent = array_merge($restContent, $fileContent);
        }
        $restContentString = implode(PHP_EOL, $restContent);
        return $restContentString;
    }

    /**
     * Processes the string in each value of the input array with extractIncludes
     *
     * @param array $array Array with TypoScript in each value
     * @return array Same array but where the values has been processed with extractIncludes
     */
    public static function extractIncludes_array(array $array)
    {
        foreach ($array as $k => $v) {
            $array[$k] = self::extractIncludes($array[$k]);
        }
        return $array;
    }

    /**
     * @param string $string
     * @return string
     * @deprecated since v11, will be removed in v12.
     */
    public function doSyntaxHighlight($string)
    {
        return $string;
    }

    /**
     * @return TimeTracker
     */
    protected function getTimeTracker()
    {
        return GeneralUtility::makeInstance(TimeTracker::class);
    }

    /**
     * Get a logger instance
     *
     * This class uses logging mostly in static functions, hence we need a static getter for the logger.
     * Injection of a logger instance via GeneralUtility::makeInstance is not possible.
     *
     * @return LoggerInterface
     */
    protected static function getLogger()
    {
        return GeneralUtility::makeInstance(LogManager::class)->getLogger(__CLASS__);
    }
}
