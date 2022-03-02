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

namespace TYPO3\CMS\Core\Configuration\TypoScript\ConditionMatching;

use Psr\Http\Message\ServerRequestInterface;
use Psr\Log\LoggerAwareInterface;
use Psr\Log\LoggerAwareTrait;
use Symfony\Component\ExpressionLanguage\SyntaxError;
use TYPO3\CMS\Core\Configuration\TypoScript\Exception\InvalidTypoScriptConditionException;
use TYPO3\CMS\Core\Error\Exception;
use TYPO3\CMS\Core\Exception\MissingTsfeException;
use TYPO3\CMS\Core\ExpressionLanguage\Resolver;
use TYPO3\CMS\Core\Http\ApplicationType;
use TYPO3\CMS\Core\Utility\GeneralUtility;

/**
 * Matching TypoScript conditions
 *
 * Used with the TypoScript parser.
 * Matches IP numbers etc. for use with templates
 */
abstract class AbstractConditionMatcher implements LoggerAwareInterface, ConditionMatcherInterface
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
     * Whether to simulate the behaviour and match specific conditions
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
     * Matches a TypoScript condition expression.
     *
     * @param string $expression The expression to match
     * @return bool Whether the expression matched
     */
    public function match($expression): bool
    {
        // Return directly if result should be simulated:
        if ($this->simulateMatchResult) {
            return $this->simulateMatchResult;
        }
        // Return directly if matching for specific condition is simulated only:
        if (!empty($this->simulateMatchConditions)) {
            return in_array($expression, $this->simulateMatchConditions, true);
        }
        $result = false;
        // First and last character must be square brackets:
        if (strpos($expression, '[') === 0 && substr($expression, -1) === ']') {
            $innerExpression = substr($expression, 1, -1);
            $result = $this->evaluateExpression($innerExpression);
        }
        return $result;
    }

    /**
     * @param string $expression
     * @return bool
     */
    protected function evaluateExpression(string $expression): bool
    {
        // The TypoScript [ELSE] condition is not known by the Symfony Expression Language
        // and must not be evaluated. If/else logic is handled in TypoScriptParser.
        if (strtoupper($expression) === 'ELSE') {
            return false;
        }

        try {
            return $this->expressionLanguageResolver->evaluate($expression);
        } catch (MissingTsfeException $e) {
            // TSFE is not available in the current context (e.g. TSFE in BE context),
            // we set all conditions false for this case.
            return false;
        } catch (SyntaxError $exception) {
            $message = 'Expression could not be parsed.';
            $this->logger->error($message, ['expression' => $expression]);
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
                'exception' => $exception,
            ]);
            if (($GLOBALS['TYPO3_REQUEST'] ?? null) instanceof ServerRequestInterface
                && ApplicationType::fromRequest($GLOBALS['TYPO3_REQUEST'])->isFrontend()
                && $exception instanceof Exception
                && str_contains($exception->getMessage(), 'in_array() expects parameter 2 to be array')
            ) {
                throw new InvalidTypoScriptConditionException('Invalid expression in condition: [' . $expression . ']', 1536950931);
            }
        }
        return false;
    }
}
