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
use PhpParser\Node\Expr\ClassConstFetch;
use PhpParser\Node\Name\FullyQualified;

/**
 * Find usages of class constants.
 *
 * Test for "Class\Name::THE_CONSTANT", matches are considered "strong"
 * @internal This class is only meant to be used within EXT:install and is not part of the TYPO3 Core API.
 */
class ClassConstantMatcher extends AbstractCoreMatcher
{
    /**
     * Validate config
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
     * @param Node $node
     */
    public function enterNode(Node $node)
    {
        if (!$this->isFileIgnored($node)
            && !$this->isLineIgnored($node)
            && $node instanceof ClassConstFetch
            && $node->class instanceof FullyQualified
            && array_key_exists($node->class->toString() . '::' . $node->name, $this->matcherDefinitions)
        ) {
            // No weak test implemented - combination class::const name tested
            $this->matches[] = [
                'restFiles' => $this->matcherDefinitions[$node->class->toString() . '::' . $node->name]['restFiles'],
                'line' => $node->getAttribute('startLine'),
                'message' => 'Call to class constant "' . $node->class->toString() . '::' . $node->name . '"',
                'indicator' => 'strong',
            ];
        }
    }
}
