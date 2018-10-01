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
use PhpParser\Node\Name\FullyQualified;

/**
 * Find usages of class / interface names which are entirely deprecated or removed
 * @internal This class is only meant to be used within EXT:install and is not part of the TYPO3 Core API.
 */
class ClassNameMatcher extends AbstractCoreMatcher
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
        if (!$this->isFileIgnored($node)
            && !$this->isLineIgnored($node)
            && $node instanceof FullyQualified
        ) {
            $fullyQualifiedClassName = $node->toString();
            if (array_key_exists($fullyQualifiedClassName, $this->matcherDefinitions)) {
                $this->matches[] = [
                    'restFiles' => $this->matcherDefinitions[$fullyQualifiedClassName]['restFiles'],
                    'line' => $node->getAttribute('startLine'),
                    'message' => 'Usage of class "' . $fullyQualifiedClassName . '"',
                    'indicator' => 'strong',
                ];
            }
        }
    }
}
