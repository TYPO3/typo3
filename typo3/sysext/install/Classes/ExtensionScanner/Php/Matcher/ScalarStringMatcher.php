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

/**
 * Find usage of special "magic" strings like TYPO3_MODE, so that
 * usage scenarios like `defined('TYPO3_MODE') || die()` will be scanned,
 * where the actual constant is NOT used.
 * @internal This class is only meant to be used within EXT:install and is not part of the TYPO3 Core API.
 */
class ScalarStringMatcher extends AbstractCoreMatcher
{
    /**
     * Default constructor validates matcher definition.
     *
     * @param array $matcherDefinitions Incoming main configuration
     */
    public function __construct(array $matcherDefinitions)
    {
        $this->matcherDefinitions = $matcherDefinitions;
        $this->validateMatcherDefinitions();
    }

    /**
     * Called by PhpParser.
     *
     * @param Node $node Given node to test
     */
    public function enterNode(Node $node)
    {
        // Early return
        if ($this->isFileIgnored($node)
            || $this->isLineIgnored($node)) {
            return null;
        }

        // Check if the node contains the specific string
        if (!$node instanceof Node\Scalar\String_) {
            return null;
        }

        // Note: This is intentionally meant to be an exact match for now, no trimming or substring.
        // Could be enhanced in the future with options to the configuration how to match.
        // Using weak match to indicate that the magic string usage may not necessarily
        // refer to the functionality we're matching. Other than TYPO3_MODE, future definitions
        // will probably be weaker than this strong constant comparison.
        $stringToMatch = (string)($node->name ?? $node->value);
        if (array_key_exists($stringToMatch, $this->matcherDefinitions)) {
            $this->matches[] = [
                'restFiles' => $this->matcherDefinitions[$stringToMatch]['restFiles'],
                'line' => $node->getAttribute('startLine'),
                'message' => 'Usage of string "' . $stringToMatch . '"',
                'indicator' => 'weak',
            ];
        }
        return null;
    }
}
