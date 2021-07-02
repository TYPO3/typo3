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
use PhpParser\Node\Expr\PropertyFetch;
use PhpParser\Node\Identifier;

/**
 * Find usages of properties which have been made protected and are
 * not called in $this context.
 * @internal This class is only meant to be used within EXT:install and is not part of the TYPO3 Core API.
 */
class PropertyProtectedMatcher extends AbstractCoreMatcher
{
    /**
     * Validate config and prepare flat mach array
     *
     * @param array $matcherDefinitions Incoming main configuration
     */
    public function __construct(array $matcherDefinitions)
    {
        $this->matcherDefinitions = $matcherDefinitions;
        $this->validateMatcherDefinitions();
        $this->initializeFlatMatcherDefinitions();
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
            && $node instanceof PropertyFetch
            && $node->name instanceof Identifier
            && ($node->var->name ?? '') !== 'this'
            && array_key_exists($node->name->name, $this->flatMatcherDefinitions)
        ) {
            $match = [
                'restFiles' => [],
                'line' => $node->getAttribute('startLine'),
                'message' => 'Fetch of property "' . $node->name->name . '"',
                'indicator' => 'weak',
            ];

            foreach ($this->flatMatcherDefinitions[$node->name->name]['candidates'] as $candidate) {
                $match['restFiles'] = array_unique(array_merge($match['restFiles'], $candidate['restFiles']));
            }
            $this->matches[] = $match;
        }
    }
}
