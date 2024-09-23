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
use PhpParser\Node\Name\FullyQualified;
use PhpParser\Node\Scalar;

/**
 * Find usages of arguments in method calls which were removed / deprecated.
 * @internal This class is only meant to be used within EXT:install and is not part of the TYPO3 Core API.
 */
class MethodCallArgumentValueMatcher extends AbstractCoreMatcher
{
    /**
     * Prepare $this->flatMatcherDefinitions once
     *
     * @param array $matcherDefinitions Incoming main configuration
     */
    public function __construct(array $matcherDefinitions)
    {
        $this->matcherDefinitions = $matcherDefinitions;
        $this->validateMatcherDefinitions(['argumentMatches']);
        $this->initializeFlatMatcherDefinitions();
    }

    /**
     * Called by PhpParser.
     * Test for "->method($someArgument)" (weak match)
     *  and for "fqcn::method($someArgument)" (strong match)
     */
    public function enterNode(Node $node): null
    {
        // Match method call (not static)
        if ($this->isFileIgnored($node)
            || $this->isLineIgnored($node)
        ) {
            return null;
        }

        if ($node instanceof Node\Expr\StaticCall
            && $node->class instanceof FullyQualified
            && array_key_exists($node->class->toString() . '::' . $node->name->name, $this->matcherDefinitions)
        ) {
            $match = [
                'restFiles' => [],
                'line' => $node->getAttribute('startLine'),
                'message' => 'Call to specific argument (#%s) of static method "' . $node->class->toString() . '::' . $node->name->name . '()"',
                'indicator' => 'strong',
            ];

            $matchCandidate = [$this->matcherDefinitions[$node->class->toString() . '::' . $node->name->name]];
        } elseif ($node instanceof MethodCall
                && array_key_exists($node->name->name, $this->flatMatcherDefinitions)
        ) {
            $match = [
                'restFiles' => [],
                'line' => $node->getAttribute('startLine'),
                'message' => 'Call to specific argument (#%s) of method "' . $node->name->name . '()"',
                'indicator' => 'weak',
            ];

            $matchCandidate = $this->flatMatcherDefinitions[$node->name->name]['candidates'];

        } else {
            return null;
        }

        $isPossibleMatch = false;

        // So far, the candidates just have their argument numbering and method name matching applied
        // Now let's inspect whether the argument actually holds the value our droids are looking for
        foreach ($matchCandidate as $candidate) {
            $argumentNumbers = $this->isArgumentMatched($node, $candidate);
            if ($argumentNumbers !== []) {
                $isPossibleMatch = true;
                $match['restFiles'] = array_unique(array_merge($match['restFiles'], $candidate['restFiles']));
                $match['message'] = sprintf($match['message'], implode(', ', $argumentNumbers));
                // One match will shortcut checking for others.
                break;
            }
        }

        if ($isPossibleMatch) {
            $this->matches[] = $match;
        }

        return null;
    }

    /**
     * Returns the array of matched arguments on a match candidate.
     * Returns empty array if either none found or not ALL matches are matched (AND combined)
     */
    private function isArgumentMatched(Node $node, array $candidate): array
    {
        $matchedArgumentNumbers = [];

        foreach (($candidate['argumentMatches'] ?? []) as $argumentMatchArray) {
            if (isset($node->args[$argumentMatchArray['argumentIndex']]->value->value)
                && $node->args[$argumentMatchArray['argumentIndex']]->value instanceof Scalar
                && $node->args[$argumentMatchArray['argumentIndex']]->value->value === $argumentMatchArray['argumentValue']) {

                $matchedArgumentNumbers[] = $argumentMatchArray['argumentIndex'];
            }
        }

        if (count($matchedArgumentNumbers) !== count($candidate['argumentMatches'] ?? [])) {
            return [];
        }

        return $matchedArgumentNumbers;
    }
}
