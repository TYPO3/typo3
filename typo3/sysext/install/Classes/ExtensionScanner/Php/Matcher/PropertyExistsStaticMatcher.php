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
use PhpParser\Node\Stmt\Property;

/**
 * Find usages of properties which have been deprecated or removed.
 * Useful if abstract classes remove properties.
 */
class PropertyExistsStaticMatcher extends AbstractCoreMatcher
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
            && $node instanceof Property
            && $node->isStatic()
            && !$node->isPrivate()
            && in_array($node->props[0]->name, array_keys($this->matcherDefinitions), true)
        ) {
            $propertyName = $node->props[0]->name;
            $match = [
                'restFiles' => $this->matcherDefinitions[$propertyName]['restFiles'],
                'line' => $node->getAttribute('startLine'),
                'message' => 'Use of property "' . $node->props[0]->name . '"',
                'indicator' => 'weak',
            ];
            $this->matches[] = $match;
        }
    }
}
