<?php
namespace TYPO3\CMS\Core\Configuration\TypoScript\ConditionMatching;

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

use Psr\Log\LoggerAwareInterface;
use Psr\Log\LoggerAwareTrait;
use Symfony\Component\ExpressionLanguage\SyntaxError;
use TYPO3\CMS\Core\Configuration\Features;
use TYPO3\CMS\Core\Configuration\TypoScript\Exception\InvalidTypoScriptConditionException;
use TYPO3\CMS\Core\Error\Exception;
use TYPO3\CMS\Core\Exception\MissingTsfeException;
use TYPO3\CMS\Core\ExpressionLanguage\Resolver;
use TYPO3\CMS\Core\Log\LogLevel;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Core\Utility\StringUtility;
use TYPO3\CMS\Core\Utility\VersionNumberUtility;

/**
 * Matching TypoScript conditions
 *
 * Used with the TypoScript parser.
 * Matches IPnumbers etc. for use with templates
 */
abstract class AbstractConditionMatcher implements LoggerAwareInterface
{
    use LoggerAwareTrait;

    /**
     * Id of the current page.
     *
     * @var int
     */
    protected $pageId;

    /**
     * The rootline for the current page.
     *
     * @var array
     */
    protected $rootline;

    /**
     * Whether to simulate the behaviour and match all conditions
     * (used in TypoScript object browser).
     *
     * @var bool
     */
    protected $simulateMatchResult = false;

    /**
     * Whether to simulat the behaviour and match specific conditions
     * (used in TypoScript object browser).
     *
     * @var array
     */
    protected $simulateMatchConditions = [];

    /**
     * @var Resolver
     */
    protected $expressionLanguageResolver;

    /**
     * @var array
     */
    protected $expressionLanguageResolverVariables = [];

    protected function initializeExpressionLanguageResolver(): void
    {
        $this->updateExpressionLanguageVariables();
        $this->expressionLanguageResolver = GeneralUtility::makeInstance(
            Resolver::class,
            'typoscript',
            $this->expressionLanguageResolverVariables
        );
    }

    protected function updateExpressionLanguageVariables(): void
    {
        // deliberately empty and not "abstract" due to backwards compatibility
        // implement this method in derived classes
    }

    /**
     * @return bool
     */
    protected function strictSyntaxEnabled(): bool
    {
        return GeneralUtility::makeInstance(Features::class)->isFeatureEnabled('TypoScript.strictSyntax');
    }

    /**
     * Sets the id of the page to evaluate conditions for.
     *
     * @param int $pageId Id of the page (must be positive)
     */
    public function setPageId($pageId)
    {
        if (is_int($pageId) && $pageId > 0) {
            $this->pageId = $pageId;
        }
        $this->initializeExpressionLanguageResolver();
    }

    /**
     * Gets the id of the page to evaluate conditions for.
     *
     * @return int Id of the page
     */
    public function getPageId()
    {
        return $this->pageId;
    }

    /**
     * Sets the rootline.
     *
     * @param array $rootline The rootline to be used for matching (must have elements)
     */
    public function setRootline(array $rootline)
    {
        if (!empty($rootline)) {
            $this->rootline = $rootline;
        }
        $this->initializeExpressionLanguageResolver();
    }

    /**
     * Gets the rootline.
     *
     * @return array The rootline to be used for matching
     */
    public function getRootline()
    {
        return $this->rootline;
    }

    /**
     * Sets whether to simulate the behaviour and match all conditions.
     *
     * @param bool $simulateMatchResult Whether to simulate positive matches
     */
    public function setSimulateMatchResult($simulateMatchResult)
    {
        if (is_bool($simulateMatchResult)) {
            $this->simulateMatchResult = $simulateMatchResult;
        }
    }

    /**
     * Sets whether to simulate the behaviour and match specific conditions.
     *
     * @param array $simulateMatchConditions Conditions to simulate a match for
     */
    public function setSimulateMatchConditions(array $simulateMatchConditions)
    {
        $this->simulateMatchConditions = $simulateMatchConditions;
    }

    /**
     * Normalizes an expression
     * + OR normalization: "...]OR[...", "...]||[...", "...][..." --> "...]||[..."
     * + AND normalization: "...]AND[...", "...]&&[..."		   --> "...]&&[..."
     *
     * @param string $expression The expression to be normalized (e.g. "[A] && [B] OR [C]")
     * @return string The normalized expression (e.g. "[A]&&[B]||[C]")
     * @deprecated since TYPO3 v9.4, will be removed in TYPO3 v10.0.
     */
    protected function normalizeExpression($expression)
    {
        $removeSpaces = '/
          \\s*
          (                    # subroutine 1
            \\[
              (?:
                [^\\[\\]]      # any character except []
                | (?1)         # recursive subroutine 1 when brackets are around
              )*
            \\]
          )
          \\s*
          /xi';

        $adjacentBrackets = '/
          (                    # subroutine 1
            \\[
              (?:
                [^\\[\\]]      # any character except []
                | (?1)         # recursive subroutine 1 when brackets are around
              )*
            \\]
          )
          (*SKIP)              # avoid backtracking into completed bracket expressions
          \\[                  # match the following [
          /xi';

        return preg_replace(
            [
                $removeSpaces,
                '/\\]AND\\[/i',
                '/\\]OR\\[/i',
                $adjacentBrackets
            ],
            [
                '\\1',
                ']&&[',
                ']||[',
                '\\1||['
            ],
            trim($expression)
        );
    }

    /**
     * Matches a TypoScript condition expression.
     *
     * @param string $expression The expression to match
     * @return bool Whether the expression matched
     */
    public function match($expression)
    {
        // Return directly if result should be simulated:
        if ($this->simulateMatchResult) {
            return $this->simulateMatchResult;
        }
        // Return directly if matching for specific condition is simulated only:
        if (!empty($this->simulateMatchConditions)) {
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
        $result = false;
        $normalizedExpression = $this->normalizeExpression($expression);
        // First and last character must be square brackets (e.g. "[A]&&[B]":
        if ($normalizedExpression[0] === '[' && substr($normalizedExpression, -1) === ']') {
            $innerExpression = substr($normalizedExpression, 1, -1);
            $orParts = explode(']||[', $innerExpression);
            if ($this->strictSyntaxEnabled() && count($orParts) > 1) {
                trigger_error('Multiple conditions blocks combined with AND, OR, && or || will be removed in TYPO3 v10.0, use the new expression language.', E_USER_DEPRECATED);
            }
            foreach ($orParts as $orPart) {
                $andParts = explode(']&&[', $orPart);
                if ($this->strictSyntaxEnabled() && count($andParts) > 1) {
                    trigger_error('Multiple conditions blocks combined with AND, OR, && or || will be removed in TYPO3 v10.0, use the new expression language.', E_USER_DEPRECATED);
                }
                foreach ($andParts as $andPart) {
                    $result = $this->evaluateExpression($andPart);
                    if (!is_bool($result)) {
                        $result = $this->evaluateCondition($andPart);
                    }
                    // If condition in AND context fails, the whole block is FALSE:
                    if ($result === false) {
                        break;
                    }
                }
                // If condition in OR context succeeds, the whole expression is TRUE:
                if ($result === true) {
                    break;
                }
            }
        }
        return $result;
    }

    /**
     * @param string $expression
     * @return bool|null
     */
    protected function evaluateExpression(string $expression): ?bool
    {
        try {
            $result = $this->expressionLanguageResolver->evaluate($expression);
            if ($result !== null) {
                return $result;
            }
        } catch (MissingTsfeException $e) {
            // TSFE is not available in the current context (e.g. TSFE in BE context),
            // we set all conditions false for this case.
            return false;
        } catch (SyntaxError $exception) {
            // SyntaxException means no support, let's try the fallback
            $message = 'Expression could not be parsed, fallback kicks in.';
            if (strpos($exception->getMessage(), 'Unexpected character "="') !== false) {
                $message .= ' It looks like an old condition with only one equal sign.';
            }
            $this->logger->log(
                $this->strictSyntaxEnabled() ? LogLevel::WARNING : LogLevel::INFO,
                $message,
                ['expression' => $expression]
            );
        } catch (\Throwable $exception) {
            // The following error handling is required to mitigate a missing type check
            // in the Symfony Expression Language handling. In case a condition
            // use "in" or "not in" check in combination with a non array a PHP Warning
            // is thrown. Example: [1 in "foo"] or ["bar" in "foo,baz"]
            // This conditions are wrong for sure, but they will break the complete installation
            // including the backend. To mitigate the problem we do the following:
            // 1) In FE an InvalidTypoScriptConditionException is thrown (if strictSyntax is enabled)
            // 2) In FE silent catch this error and log it (if strictSyntax is disabled)
            // 3) In BE silent catch this error and log it, but never break the backend.
            $this->logger->error($exception->getMessage(), [
                'expression' => $expression,
                'exception' => $exception
            ]);
            if (TYPO3_MODE === 'FE'
                && $exception instanceof Exception
                && $this->strictSyntaxEnabled()
                && strpos($exception->getMessage(), 'in_array() expects parameter 2 to be array') !== false
            ) {
                throw new InvalidTypoScriptConditionException('Invalid expression in condition: [' . $expression . ']', 1536950931);
            }
        }
        return null;
    }

    /**
     * Evaluates a TypoScript condition given as input, eg. "[applicationContext = Production][...(other condition)...]"
     *
     * @param string $key The condition to match against its criteria.
     * @param string $value
     * @return bool|null Result of the evaluation; NULL if condition could not be evaluated
     * @deprecated since TYPO3 v9.4, will be removed in TYPO3 v10.0.
     */
    protected function evaluateConditionCommon($key, $value)
    {
        $keyParts = GeneralUtility::trimExplode('|', $key);
        switch ($keyParts[0]) {
            case 'applicationContext':
                $values = GeneralUtility::trimExplode(',', $value, true);
                $currentApplicationContext = GeneralUtility::getApplicationContext();
                foreach ($values as $applicationContext) {
                    if ($this->searchStringWildcard($currentApplicationContext, $applicationContext)) {
                        return true;
                    }
                }
                return false;
            case 'language':
                if (GeneralUtility::getIndpEnv('HTTP_ACCEPT_LANGUAGE') === $value) {
                    return true;
                }
                $values = GeneralUtility::trimExplode(',', $value, true);
                foreach ($values as $test) {
                    // matches a string with asterix in front and back. See https://docs.typo3.org/typo3cms/TyposcriptReference/Conditions/Reference.html#language for use case.
                    if (preg_match('/^\\*.+\\*$/', $test)) {
                        $allLanguages = preg_split('/[,;]/', GeneralUtility::getIndpEnv('HTTP_ACCEPT_LANGUAGE'));
                        if (in_array(substr($test, 1, -1), $allLanguages)) {
                            return true;
                        }
                    } elseif (GeneralUtility::getIndpEnv('HTTP_ACCEPT_LANGUAGE') == $test) {
                        return true;
                    }
                }
                return false;
            case 'IP':
                if ($value === 'devIP') {
                    $value = trim($GLOBALS['TYPO3_CONF_VARS']['SYS']['devIPmask']);
                }

                return (bool)GeneralUtility::cmpIP(GeneralUtility::getIndpEnv('REMOTE_ADDR'), $value);
            case 'hostname':
                return (bool)GeneralUtility::cmpFQDN(GeneralUtility::getIndpEnv('REMOTE_ADDR'), $value);
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
                    default:
                        $theTestValue = 0;
                        break;
                }
                $theTestValue = (int)$theTestValue;
                // comp
                $values = GeneralUtility::trimExplode(',', $value, true);
                foreach ($values as $test) {
                    if (\TYPO3\CMS\Core\Utility\MathUtility::canBeInterpretedAsInteger($test)) {
                        $test = '=' . $test;
                    }
                    if ($this->compareNumber($test, $theTestValue)) {
                        return true;
                    }
                }
                return false;
            case 'compatVersion':
                return VersionNumberUtility::convertVersionNumberToInteger(TYPO3_branch) >= VersionNumberUtility::convertVersionNumberToInteger($value);
            case 'loginUser':
                if ($this->isUserLoggedIn()) {
                    $values = GeneralUtility::trimExplode(',', $value, true);
                    foreach ($values as $test) {
                        if ($test === '*' || (string)$this->getUserId() === (string)$test) {
                            return true;
                        }
                    }
                } elseif ($value === '') {
                    return true;
                }
                return false;
            case 'page':
                if ($keyParts[1]) {
                    $page = $this->getPage();
                    $property = $keyParts[1];
                    if (!empty($page) && isset($page[$property]) && (string)$page[$property] === (string)$value) {
                        return true;
                    }
                }
                return false;
            case 'globalVar':
                $values = GeneralUtility::trimExplode(',', $value, true);
                foreach ($values as $test) {
                    $point = strcspn($test, '!=<>');
                    $theVarName = substr($test, 0, $point);
                    $nv = $this->getVariable(trim($theVarName));
                    $testValue = substr($test, $point);
                    if ($this->compareNumber($testValue, $nv)) {
                        return true;
                    }
                }
                return false;
            case 'globalString':
                $values = GeneralUtility::trimExplode(',', $value, true);
                foreach ($values as $test) {
                    $point = strcspn($test, '=');
                    $theVarName = substr($test, 0, $point);
                    $nv = (string)$this->getVariable(trim($theVarName));
                    $testValue = substr($test, $point + 1);
                    if ($this->searchStringWildcard($nv, trim($testValue))) {
                        return true;
                    }
                }
                return false;
            case 'userFunc':
                $matches = [];
                preg_match_all('/^\s*([^\(\s]+)\s*(?:\((.*)\))?\s*$/', $value, $matches);
                $funcName = $matches[1][0];
                $funcValues = trim($matches[2][0]) !== '' ? $this->parseUserFuncArguments($matches[2][0]) : [];
                if (is_callable($funcName) && call_user_func_array($funcName, $funcValues)) {
                    return true;
                }
                return false;
        }
        return null;
    }

    /**
     * Evaluates a TypoScript condition given as input with a custom class name,
     * e.g. "[MyCompany\MyPackage\ConditionMatcher\MyOwnConditionMatcher = myvalue]"
     *
     * @param string $condition The condition to match
     * @return bool|null Result of the evaluation; NULL if condition could not be evaluated
     * @throws \TYPO3\CMS\Core\Configuration\TypoScript\Exception\InvalidTypoScriptConditionException
     * @deprecated since TYPO3 v9.4, will be removed in TYPO3 v10.0.
     */
    protected function evaluateCustomDefinedCondition($condition)
    {
        $conditionResult = null;

        list($conditionClassName, $conditionParameters) = GeneralUtility::trimExplode(' ', $condition, false, 2);

        // Check if the condition class name is a valid class
        // This is necessary to not stop here for the conditions ELSE and GLOBAL
        if (class_exists($conditionClassName)) {
            // Use like this: [MyCompany\MyPackage\ConditionMatcher\MyOwnConditionMatcher = myvalue]
            /** @var \TYPO3\CMS\Core\Configuration\TypoScript\ConditionMatching\AbstractCondition $conditionObject */
            $conditionObject = GeneralUtility::makeInstance($conditionClassName);
            if (($conditionObject instanceof \TYPO3\CMS\Core\Configuration\TypoScript\ConditionMatching\AbstractCondition) === false) {
                throw new \TYPO3\CMS\Core\Configuration\TypoScript\Exception\InvalidTypoScriptConditionException(
                    '"' . $conditionClassName . '" is not a valid TypoScript Condition object.',
                    1410286153
                );
            }

            $conditionParameters = $this->parseUserFuncArguments($conditionParameters);
            $conditionObject->setConditionMatcherInstance($this);
            $conditionResult = $conditionObject->matchCondition($conditionParameters);
        }

        return $conditionResult;
    }

    /**
     * Parses arguments to the userFunc.
     *
     * @param string $arguments
     * @return array
     * @deprecated since TYPO3 v9.4, will be removed in TYPO3 v10.0.
     */
    protected function parseUserFuncArguments($arguments)
    {
        $result = [];
        $arguments = trim($arguments);
        while ($arguments !== '') {
            if ($arguments[0] === ',') {
                $result[] = '';
                $arguments = substr($arguments, 1);
            } else {
                $pos = strcspn($arguments, ',\'"');
                if ($pos == 0) {
                    // We hit a quote of some kind
                    $quote = $arguments[0];
                    $segment = preg_replace('/^(.*?[^\\\])' . $quote . '.*$/', '\1', substr($arguments, 1));
                    $segment = str_replace('\\' . $quote, $quote, $segment);
                    $result[] = $segment;
                    // shorten $arguments
                    $arguments = substr($arguments, strlen($segment) + 2);
                    $offset = strpos($arguments, ',');
                    if ($offset === false) {
                        $offset = strlen($arguments);
                    }
                    $arguments = substr($arguments, $offset + 1);
                } else {
                    $result[] = trim(substr($arguments, 0, $pos));
                    $arguments = substr($arguments, $pos + 1);
                }
            }
            $arguments = trim($arguments);
        }
        return $result;
    }

    /**
     * Get variable common
     *
     * @param array $vars
     * @return mixed Whatever value. If none, then NULL.
     * @deprecated since TYPO3 v9.4, will be removed in TYPO3 v10.0.
     */
    protected function getVariableCommon(array $vars)
    {
        $value = null;
        $namespace = trim($vars[0]);
        if (count($vars) === 1) {
            $value = $this->getGlobal($vars[0]);
        } elseif ($namespace === 'LIT') {
            $value = trim($vars[1]);
        } else {
            $splitAgain = explode('|', $vars[1], 2);
            $k = trim($splitAgain[0]);
            if ($k) {
                switch ($namespace) {
                    case 'GP':
                        $value = GeneralUtility::_GP($k);
                        break;
                    case 'GPmerged':
                        $value = GeneralUtility::_GPmerged($k);
                        break;
                    case 'ENV':
                        $value = getenv($k);
                        break;
                    case 'IENV':
                        $value = GeneralUtility::getIndpEnv($k);
                        break;
                    default:
                        return null;
                }
                // If array:
                if (count($splitAgain) > 1) {
                    if (is_array($value) && trim($splitAgain[1]) !== '') {
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
     * @param float $leftValue The value on the left side
     * @return bool If $value is "50" and $test is "< 123" then it will return TRUE.
     * @deprecated since TYPO3 v9.4, will be removed in TYPO3 v10.0.
     */
    protected function compareNumber($test, $leftValue)
    {
        if (preg_match('/^(!?=+|<=?|>=?)\\s*([^\\s]*)\\s*$/', $test, $matches)) {
            $operator = $matches[1];
            $rightValue = $matches[2];
            switch ($operator) {
                case '>=':
                    return $leftValue >= (float)$rightValue;
                    break;
                case '<=':
                    return $leftValue <= (float)$rightValue;
                    break;
                case '!=':
                    // multiple values may be split with '|'
                    // see if none matches ("not in list")
                    $found = false;
                    $rightValueParts = GeneralUtility::trimExplode('|', $rightValue);
                    foreach ($rightValueParts as $rightValueSingle) {
                        if ($leftValue == (float)$rightValueSingle) {
                            $found = true;
                            break;
                        }
                    }
                    return $found === false;
                    break;
                case '<':
                    return $leftValue < (float)$rightValue;
                    break;
                case '>':
                    return $leftValue > (float)$rightValue;
                    break;
                default:
                    // nothing valid found except '=', use '='
                    // multiple values may be split with '|'
                    // see if one matches ("in list")
                    $found = false;
                    $rightValueParts = GeneralUtility::trimExplode('|', $rightValue);
                    foreach ($rightValueParts as $rightValueSingle) {
                        if ($leftValue == $rightValueSingle) {
                            $found = true;
                            break;
                        }
                    }
                    return $found;
            }
        }
        return false;
    }

    /**
     * Matching two strings against each other, supporting a "*" wildcard or (if wrapped in "/") PCRE regular expressions
     *
     * @param string $haystack The string in which to find $needle.
     * @param string $needle The string to find in $haystack
     * @return bool Returns TRUE if $needle matches or is found in (according to wildcards) in $haystack. Eg. if $haystack is "Netscape 6.5" and $needle is "Net*" or "Net*ape" then it returns TRUE.
     * @deprecated since TYPO3 v9.4, will be removed in TYPO3 v10.0.
     */
    protected function searchStringWildcard($haystack, $needle)
    {
        return StringUtility::searchStringWildcard($haystack, $needle);
    }

    /**
     * Return global variable where the input string $var defines array keys separated by "|"
     * Example: $var = "HTTP_SERVER_VARS | something" will return the value $GLOBALS['HTTP_SERVER_VARS']['something'] value
     *
     * @param string $var Global var key, eg. "HTTP_GET_VAR" or "HTTP_GET_VARS|id" to get the GET parameter "id" back.
     * @param array $source Alternative array than $GLOBAL to get variables from.
     * @return mixed Whatever value. If none, then blank string.
     * @deprecated since TYPO3 v9.4, will be removed in TYPO3 v10.0.
     */
    protected function getGlobal($var, $source = null)
    {
        $vars = explode('|', $var);
        $c = count($vars);
        $k = trim($vars[0]);
        $theVar = isset($source) ? ($source[$k] ?? null) : ($GLOBALS[$k] ?? null);
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
        }
        return '';
    }

    /**
     * Evaluates a TypoScript condition given as input, eg. "[browser=net][...(other conditions)...]"
     *
     * @param string $string The condition to match against its criteria.
     * @return bool Whether the condition matched
     * @see \TYPO3\CMS\Core\TypoScript\Parser\TypoScriptParser::parse()
     * @deprecated since TYPO3 v9.4, will be removed in TYPO3 v10.0.
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
     * @deprecated since TYPO3 v9.4, will be removed in TYPO3 v10.0.
     */
    abstract protected function getVariable($name);

    /**
     * Gets the usergroup list of the current user.
     *
     * @return string The usergroup list of the current user
     * @deprecated since TYPO3 v9.4, will be removed in TYPO3 v10.0.
     */
    abstract protected function getGroupList();

    /**
     * Determines the current page Id.
     *
     * @return int The current page Id
     * @deprecated since TYPO3 v9.4, will be removed in TYPO3 v10.0.
     */
    abstract protected function determinePageId();

    /**
     * Gets the properties for the current page.
     *
     * @return array The properties for the current page.
     * @deprecated since TYPO3 v9.4, will be removed in TYPO3 v10.0.
     */
    abstract protected function getPage();

    /**
     * Determines the rootline for the current page.
     *
     * @return array The rootline for the current page.
     * @deprecated since TYPO3 v9.4, will be removed in TYPO3 v10.0.
     */
    abstract protected function determineRootline();

    /**
     * Gets the id of the current user.
     *
     * @return int The id of the current user
     * @deprecated since TYPO3 v9.4, will be removed in TYPO3 v10.0.
     */
    abstract protected function getUserId();

    /**
     * Determines if a user is logged in.
     *
     * @return bool Determines if a user is logged in
     * @deprecated since TYPO3 v9.4, will be removed in TYPO3 v10.0.
     */
    abstract protected function isUserLoggedIn();
}
