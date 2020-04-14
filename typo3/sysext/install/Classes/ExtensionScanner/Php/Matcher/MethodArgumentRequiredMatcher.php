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
use PhpParser\Node\Expr\MethodCall;

/**
 * Find usages of method calls which changed signature and added required arguments.
 * This is a "weak" match since we're just testing for method name
 * but not connected class.
 * @internal This class is only meant to be used within EXT:install and is not part of the TYPO3 Core API.
 */
class MethodArgumentRequiredMatcher extends AbstractCoreMatcher
{
    /**
     * Prepare $this->flatMatcherDefinitions once and validate config
     *
     * @param array $matcherDefinitions Incoming main configuration
     */
    public function __construct(array $matcherDefinitions)
    {
        $this->matcherDefinitions = $matcherDefinitions;
        $this->validateMatcherDefinitions(['numberOfMandatoryArguments']);
        $this->initializeFlatMatcherDefinitions();
    }

    /**
     * Called by PhpParser.
     * Test for "->deprecated()" (weak match)
     *
     * @param Node $node
     */
    public function enterNode(Node $node)
    {
        // Match method call (not static)
        if (!$this->isFileIgnored($node)
            && !$this->isLineIgnored($node)
            && $node instanceof MethodCall
            && array_key_exists($node->name->name, $this->flatMatcherDefinitions)
        ) {
            $match = [
                'restFiles' => [],
                'line' => $node->getAttribute('startLine'),
                'indicator' => 'weak',
            ];

            $isArgumentUnpackingUsed = $this->isArgumentUnpackingUsed($node->args);

            $numberOfArguments = count($node->args);
            $isPossibleMatch = false;
            foreach ($this->flatMatcherDefinitions[$node->name->name]['candidates'] as $candidate) {
                // A method call is considered a match if it is not called with argument unpacking
                // and number of used arguments is lower than numberOfMandatoryArguments
                if (!$isArgumentUnpackingUsed
                    && $numberOfArguments < $candidate['numberOfMandatoryArguments']
                    && $numberOfArguments <= $candidate['maximumNumberOfArguments']
                ) {
                    $isPossibleMatch = true;
                    $match['message'] = 'Method ' . $node->name->name . '() needs at least ' . $candidate['numberOfMandatoryArguments'] . ' arguments.';
                    $match['restFiles'] = array_unique(array_merge($match['restFiles'], $candidate['restFiles']));
                }
            }
            if ($isPossibleMatch) {
                $this->matches[] = $match;
            }
        }
    }
}
