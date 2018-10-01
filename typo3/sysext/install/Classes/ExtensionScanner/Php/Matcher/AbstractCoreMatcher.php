<?php
declare(strict_types = 1);
namespace TYPO3\CMS\Install\ExtensionScanner\Php\Matcher;

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

use PhpParser\Node;
use PhpParser\Node\Stmt\Class_;
use PhpParser\NodeVisitorAbstract;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Install\ExtensionScanner\CodeScannerInterface;

/**
 * Single "core matcher" classes extend from this.
 * It brings a set of protected methods to help single matcher classes doing common stuff.
 * This abstract extends the nikic/php-parser NodeVisitorAbstract which implements the main
 * parser interface, and it implements the TYPO3 specific CodeScannerInterface to retrieve matches.
 * @internal This class is only meant to be used within EXT:install and is not part of the TYPO3 Core API.
 */
abstract class AbstractCoreMatcher extends NodeVisitorAbstract implements CodeScannerInterface
{
    /**
     * Incoming main configuration array.
     *
     * @var array
     */
    protected $matcherDefinitions = [];

    /**
     * @var array List of accumulated matches
     */
    protected $matches = [];

    /**
     * Helper property containing an array derived from $this->matcherDefinitions
     * created in __construct() if needed.
     *
     * @var array
     */
    protected $flatMatcherDefinitions = [];

    /**
     * @var int Helper variable for ignored line detection
     */
    protected $currentCodeLine = 0;

    /**
     * @var bool True if line with $lastIgnoredLineNumber is ignored
     */
    protected $isCurrentLineIgnored = false;

    /**
     * @var bool True if the entire file is ignored due to a @extensionScannerIgnoreFile class comment
     */
    protected $isFullFileIgnored = false;

    /**
     * Return list of matches after processing
     *
     * @return array
     */
    public function getMatches(): array
    {
        return $this->matches;
    }

    /**
     * Some matcher need specific keys in the array definition to work properly.
     * This method is called typically in __construct() of a matcher to
     * verify these are given.
     * This method is a measure against broken core configuration. It should be
     * pretty quick and is only called in __construct() once, no kitten should be harmed.
     *
     * This method works on $this->matcherDefinitions.
     *
     * @param array $requiredArrayKeys List of required keys for single matchers
     * @throws \RuntimeException
     */
    protected function validateMatcherDefinitions(array $requiredArrayKeys = [])
    {
        foreach ($this->matcherDefinitions as $key => $matcherDefinition) {
            // Each config must point to at least one .rst file
            if (empty($matcherDefinition['restFiles'])) {
                throw new \InvalidArgumentException(
                    'Each configuration must have at least one referenced "restFiles" entry. Offending key: ' . $key,
                    1500496068
                );
            }
            foreach ($matcherDefinition['restFiles'] as $file) {
                if (empty($file)) {
                    throw new \InvalidArgumentException(
                        'Empty restFiles definition',
                        1500735983
                    );
                }
            }
            // Config broken if not all required array keys are specified in config
            $sharedArrays = array_intersect(array_keys($matcherDefinition), $requiredArrayKeys);
            if ($sharedArrays !== $requiredArrayKeys) {
                $missingKeys = array_diff($requiredArrayKeys, array_keys($matcherDefinition));
                throw new \InvalidArgumentException(
                    'Required matcher definitions missing: ' . implode(', ', $missingKeys) . ' offending key: ' . $key,
                    1500492001
                );
            }
        }
    }

    /**
     * Initialize helper lookup array $this->flatMatcherDefinitions.
     * For class\name->foo matcherDefinitions, it creates a helper array
     * containing only the method name as array keys for "weak" matches.
     *
     * If methods with the same name from different classes are defined,
     * a "candidate" array is created containing details of single possible
     * matches for further analysis.
     *
     * @throws \RuntimeException
     */
    protected function initializeFlatMatcherDefinitions()
    {
        $methodNameArray = [];
        foreach ($this->matcherDefinitions as $classAndMethod => $details) {
            $method = GeneralUtility::trimExplode('::', $classAndMethod);
            if (count($method) !== 2) {
                $method = GeneralUtility::trimExplode('->', $classAndMethod);
            }
            if (count($method) !== 2) {
                throw new \RuntimeException(
                    'Keys in $this->matcherDefinitions must have a Class\Name->method or Class\Name::method structure',
                    1500557309
                );
            }
            $method = $method[1];
            if (!array_key_exists($method, $methodNameArray)) {
                $methodNameArray[$method]['candidates'] = [];
            }
            $methodNameArray[$method]['candidates'][] = $details;
        }
        $this->flatMatcherDefinitions = $methodNameArray;
    }

    /**
     * Test if one argument is given as "...$someArray".
     * If so, it kinda defeats any "argument count" approach.
     *
     * @param array $arguments List of arguments
     * @return bool
     */
    protected function isArgumentUnpackingUsed(array $arguments = []): bool
    {
        foreach ($arguments as $arg) {
            if ($arg->unpack === true) {
                return true;
            }
        }
        return false;
    }

    /**
     * Returns true if a comment before a statement is
     * marked as "@extensionScannerIgnoreLine"
     *
     * @param Node $node
     * @return bool
     */
    protected function isLineIgnored(Node $node): bool
    {
        // Early return if this line is marked as ignored
        $startLineOfNode = $node->getAttribute('startLine');
        if ($startLineOfNode === $this->currentCodeLine) {
            return $this->isCurrentLineIgnored;
        }

        $currentLineIsIgnored = false;
        if ($startLineOfNode !== $this->currentCodeLine) {
            $this->currentCodeLine = $startLineOfNode;
            // First node of a new line may contain the annotation
            $comments = $node->getAttribute('comments');
            if (!empty($comments)) {
                foreach ($comments as $comment) {
                    if (strstr($comment->getText(), '@extensionScannerIgnoreLine') !== false) {
                        $this->isCurrentLineIgnored = true;
                        $currentLineIsIgnored = true;
                        break;
                    }
                }
            }
        }
        return $currentLineIsIgnored;
    }

    /**
     * Return true if the node is ignored since the entire file is ignored.
     * Sets ignore status if a class node is given having the annotation.
     *
     * @param Node $node
     * @return bool
     */
    protected function isFileIgnored(Node $node): bool
    {
        if ($this->isFullFileIgnored) {
            return true;
        }
        $currentFileIsIgnored = false;
        if ($node instanceof Class_) {
            $comments = $node->getAttribute('comments');
            if (!empty($comments)) {
                foreach ($comments as $comment) {
                    if (strstr($comment->getText(), '@extensionScannerIgnoreFile') !== false) {
                        $this->isFullFileIgnored = true;
                        $currentFileIsIgnored = true;
                        break;
                    }
                }
            }
        }
        return $currentFileIsIgnored;
    }
}
