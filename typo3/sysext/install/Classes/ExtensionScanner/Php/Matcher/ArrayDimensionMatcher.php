<?php

declare(strict_types=1);

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

namespace TYPO3\CMS\Install\ExtensionScanner\Php\Matcher;

use PhpParser\Node;
use PhpParser\Node\Expr\ArrayDimFetch;
use TYPO3\CMS\Core\Utility\GeneralUtility;

/**
 * Find usages of dropped configuration values and hook registrations.
 * Matches on "last" key only.
 * Definition of $GLOBALS['foo']['bar'] and usage as $foo['bar'] matches.
 * @internal This class is only meant to be used within EXT:install and is not part of the TYPO3 Core API.
 */
class ArrayDimensionMatcher extends AbstractCoreMatcher
{
    /**
     * Initialize "flat" matcher array from matcher definitions.
     *
     * @param array $matcherDefinitions Incoming main configuration
     */
    public function __construct(array $matcherDefinitions)
    {
        $this->matcherDefinitions = $matcherDefinitions;
        $this->validateMatcherDefinitions();
        $this->initializeLastArrayKeyNameArray();
    }

    /**
     * Called by PhpParser.
     *
     * @param Node $node
     */
    public function enterNode(Node $node)
    {
        if (!$this->isFileIgnored($node)
            && !$this->isLineIgnored($node)
            && $node instanceof ArrayDimFetch
            && isset($node->dim->value)
            && array_key_exists($node->dim->value, $this->flatMatcherDefinitions)
        ) {
            $match = [
                'restFiles' => [],
                'line' => $node->getAttribute('startLine'),
                'message' => 'Access to array key "' . $node->dim->value . '"',
                'indicator' => 'weak',
            ];

            foreach ($this->flatMatcherDefinitions[$node->dim->value]['candidates'] as $candidate) {
                $match['restFiles'] = array_unique(array_merge($match['restFiles'], $candidate['restFiles']));
            }
            $this->matches[] = $match;
        }
    }

    /**
     * Prepare 'lastKey' => [$details] array in flatMatcherDefinitions
     */
    protected function initializeLastArrayKeyNameArray()
    {
        $methodNameArray = [];
        foreach ($this->matcherDefinitions as $fullArrayString => $details) {
            // Goal: find last part "foobar" of an array path "$foo['bar']['foobar']"
            // Reverse string $foo['bar']['foobar']
            $lastKey = strrev($fullArrayString);
            // Cut off "['"
            $lastKey = substr($lastKey, 2);
            $lastKey = GeneralUtility::trimExplode('\'[', $lastKey);
            // Last key name
            $lastKey = $lastKey[0];
            // And reverse key name again
            $lastKey = strrev($lastKey);

            if (!array_key_exists($lastKey, $methodNameArray)) {
                $methodNameArray[$lastKey]['candidates'] = [];
            }
            $methodNameArray[$lastKey]['candidates'][] = $details;
        }
        $this->flatMatcherDefinitions = $methodNameArray;
    }
}
