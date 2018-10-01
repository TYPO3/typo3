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
 * @internal This class is only meant to be used within EXT:install and is not part of the TYPO3 Core API.
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
            && array_key_exists($node->props[0]->name->name, $this->matcherDefinitions)
        ) {
            $propertyName = $node->props[0]->name->name;
            $match = [
                'restFiles' => $this->matcherDefinitions[$propertyName]['restFiles'],
                'line' => $node->getAttribute('startLine'),
                'message' => 'Use of property "' . $node->props[0]->name->name . '"',
                'indicator' => 'weak',
            ];
            $this->matches[] = $match;
        }
    }
}
