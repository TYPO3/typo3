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
use PhpParser\Node\Expr\StaticCall;
use PhpParser\Node\Expr\Variable;
use PhpParser\Node\Name\FullyQualified;

/**
 * Find usages of static method calls which were removed / deprecated.
 *
 * This match is performed either is case of a direct "foo\bar::aMethod()" call
 * as "strong" match, or as only "::aMethod()" as "weak" match.
 *
 * As additional indicator, the number of required, mandatory arguments is
 * recognized: If calling a static method as "$foo::aMethod($arg1), but the
 * method needs two arguments, this is *not* considered a match. This would
 * have raised a fatal PHP error anyway and this is nothing we test here.
 * @internal This class is only meant to be used within EXT:install and is not part of the TYPO3 Core API.
 */
class MethodCallStaticMatcher extends AbstractCoreMatcher
{
    /**
     * Validate config and prepare weak matcher array
     *
     * @param array $matcherDefinitions Incoming main configuration
     */
    public function __construct(array $matcherDefinitions)
    {
        $this->matcherDefinitions = $matcherDefinitions;
        $this->validateMatcherDefinitions(['numberOfMandatoryArguments', 'maximumNumberOfArguments']);
        $this->initializeFlatMatcherDefinitions();
    }

    /**
     * Called by PhpParser.
     * Test for "foo\bar::deprecated()" (strong match)
     * Test for "::deprecated()" (weak match)
     *
     * @param Node $node
     */
    public function enterNode(Node $node)
    {
        // Static call, not method call
        if (!$this->isFileIgnored($node)
            && !$this->isLineIgnored($node)
            && $node instanceof StaticCall
        ) {
            if ($node->class instanceof FullyQualified) {
                // 'Foo\Bar::deprecated()' -> strong match
                $fqdnClassWithMethod = $node->class->toString() . '::' . $node->name->name;
                if (array_key_exists($fqdnClassWithMethod, $this->matcherDefinitions)) {
                    $this->matches[] = [
                        'restFiles' => $this->matcherDefinitions[$fqdnClassWithMethod]['restFiles'],
                        'line' => $node->getAttribute('startLine'),
                        'message' => 'Use of static class method call "' . $fqdnClassWithMethod . '()"',
                        'indicator' => 'strong',
                    ];
                }
            } elseif ($node->class instanceof Variable
                && array_key_exists($node->name->name, $this->flatMatcherDefinitions)
            ) {
                $match = [
                    'restFiles' => [],
                    'line' => $node->getAttribute('startLine'),
                    'message' => 'Use of static class method call "' . $node->name->name . '()"',
                    'indicator' => 'weak',
                ];

                $numberOfArguments = count($node->args);
                $isArgumentUnpackingUsed = $this->isArgumentUnpackingUsed($node->args);

                $isPossibleMatch = false;
                foreach ($this->flatMatcherDefinitions[$node->name->name]['candidates'] as $candidate) {
                    // A method call is considered a match if it is called with argument unpacking, or
                    // if the number of given arguments is within range of mandatory / max number of arguments
                    if ($isArgumentUnpackingUsed
                        || ($numberOfArguments >= $candidate['numberOfMandatoryArguments']
                            && $numberOfArguments <= $candidate['maximumNumberOfArguments'])
                    ) {
                        $isPossibleMatch = true;
                        $match['restFiles'] = array_unique(array_merge($match['restFiles'], $candidate['restFiles']));
                    }
                }
                if ($isPossibleMatch) {
                    $this->matches[] = $match;
                }
            }
        }
    }
}
