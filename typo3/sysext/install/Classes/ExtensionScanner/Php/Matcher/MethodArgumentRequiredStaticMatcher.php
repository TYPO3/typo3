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
use PhpParser\Node\Expr\StaticCall;
use PhpParser\Node\Expr\Variable;
use PhpParser\Node\Name\FullyQualified;

/**
 * Find usages of static method calls which gained new mandatory arguments.
 * This is a "strong" match if class name is given and "weak" if not.
 * @internal This class is only meant to be used within EXT:install and is not part of the TYPO3 Core API.
 */
class MethodArgumentRequiredStaticMatcher extends AbstractCoreMatcher
{
    /**
     * Prepare $this->flatMatcherDefinitions once and validate config
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
     * Test for "::function($1, $2, $3)" (strong match)
     *
     * @param Node $node
     */
    public function enterNode(Node $node)
    {
        // Match static method call
        if (!$this->isFileIgnored($node)
            && !$this->isLineIgnored($node)
            && $node instanceof StaticCall
        ) {
            $isArgumentUnpackingUsed = $this->isArgumentUnpackingUsed($node->args);

            if ($node->class instanceof FullyQualified) {
                // 'Foo\Bar::aMethod()' -> strong match
                $fqdnClassWithMethod = $node->class->toString() . '::' . $node->name->name;
                $numberOfArguments = count($node->args);
                if (!$isArgumentUnpackingUsed
                    && array_key_exists($fqdnClassWithMethod, $this->matcherDefinitions)
                    && $numberOfArguments < $this->matcherDefinitions[$fqdnClassWithMethod]['numberOfMandatoryArguments']
                    // maximum number of arguments is just a measure agains false positives
                    && $numberOfArguments <= $this->matcherDefinitions[$fqdnClassWithMethod]['maximumNumberOfArguments']
                ) {
                    $this->matches[] = [
                        'restFiles' => $this->matcherDefinitions[$fqdnClassWithMethod]['restFiles'],
                        'line' => $node->getAttribute('startLine'),
                        'message' => 'Method "' . $node->name->name . '()" needs at least '
                            . $this->matcherDefinitions[$fqdnClassWithMethod]['numberOfMandatoryArguments']
                            . ' arguments.',
                        'indicator' => 'strong',
                    ];
                }
            } elseif ($node->class instanceof Variable
                && array_key_exists($node->name->name, $this->flatMatcherDefinitions)
            ) {
                $match = [
                    'restFiles' => [],
                    'line' => $node->getAttribute('startLine'),
                    'indicator' => 'weak',
                ];

                $numberOfArguments = count($node->args);
                $isPossibleMatch = false;
                foreach ($this->flatMatcherDefinitions[$node->name->name]['candidates'] as $candidate) {
                    // A method call is considered a match if it is not called with argument unpacking
                    // and number of used arguments is lesser than numberOfMandatoryArguments
                    if (!$isArgumentUnpackingUsed
                        && $numberOfArguments < $candidate['numberOfMandatoryArguments']
                        // maximum number of arguments is just a measure agains false positives
                        && $numberOfArguments <= $candidate['maximumNumberOfArguments']
                    ) {
                        $isPossibleMatch = true;
                        $match['message'] = 'Method "' . $node->name->name . '()" needs at least '
                            . $candidate['numberOfMandatoryArguments'] . ' arguments.';
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
