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

use PhpParser\Comment\Doc;
use PhpParser\Node;
use PhpParser\Node\Stmt\Property;
use PhpParser\Node\Stmt\PropertyProperty;

/**
 * Find usages of property annotations
 * @internal This class is only meant to be used within EXT:install and is not part of the TYPO3 Core API.
 */
class PropertyAnnotationMatcher extends AbstractCoreMatcher
{
    /**
     * Prepare $this->flatMatcherDefinitions once and validate config
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
     * Test for property annotations (strong match)
     *
     * @param Node $node
     */
    public function enterNode(Node $node)
    {
        if ($node instanceof Property
            && ($property = reset($node->props)) instanceof PropertyProperty
            && ($docComment = $node->getDocComment()) instanceof Doc
            && !$this->isFileIgnored($node)
            && !$this->isLineIgnored($node)
        ) {
            /** @var PropertyProperty $property */
            $isPossibleMatch = false;
            $match = [
                'restFiles' => [],
                'line' => $property->getAttribute('startLine'),
                'indicator' => 'strong',
            ];

            $matches = [];
            preg_match_all(
                '/\s*\s@(?<annotations>[^\s.]*).*\n/',
                $docComment->getText(),
                $matches
            );

            foreach ($matches['annotations'] as $annotation) {
                $annotation = '@' . $annotation;

                if (!isset($this->matcherDefinitions[$annotation])) {
                    continue;
                }

                $isPossibleMatch = true;
                $match['message'] = 'Property "' . $property->name . '" uses an ' . $annotation . ' annotation.';
                $match['restFiles'] = array_unique(array_merge(
                    $match['restFiles'],
                    $this->matcherDefinitions[$annotation]['restFiles']
                ));
            }

            if ($isPossibleMatch) {
                $this->matches[] = $match;
            }
        }
    }
}
