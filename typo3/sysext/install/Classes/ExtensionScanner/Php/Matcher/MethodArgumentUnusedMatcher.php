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
use PhpParser\Node\Expr\ConstFetch;
use PhpParser\Node\Expr\MethodCall;

/**
 * Match method usages where arguments "in between" are unused but not given as "null":
 *
 * public function foo($arg1, $unused1 = null, $unused2 = null, $arg4)
 * but called with:
 * ->foo('arg1', 'notNull', null, 'arg4');
 * @internal This class is only meant to be used within EXT:install and is not part of the TYPO3 Core API.
 */
class MethodArgumentUnusedMatcher extends AbstractCoreMatcher
{
    /**
     * Prepare $this->flatMatcherDefinitions once and validate config
     *
     * @param array $matcherDefinitions Incoming main configuration
     */
    public function __construct(array $matcherDefinitions)
    {
        $this->matcherDefinitions = $matcherDefinitions;
        $this->validateMatcherDefinitions(['unusedArgumentNumbers']);
        $this->initializeFlatMatcherDefinitions();
    }

    /**
     * Called by PhpParser.
     *
     * @param Node $node
     */
    public function enterNode(Node $node)
    {
        // Match method call (not static)
        if (!$this->isFileIgnored($node)
            && !$this->isLineIgnored($node)
            && $node instanceof MethodCall
            && array_key_exists($node->name->name, $this->flatMatcherDefinitions)
        ) {
            $match = [
                'restFiles' => [],
                'line' => $node->getAttribute('startLine'),
                'indicator' => 'weak',
            ];

            $isArgumentUnpackingUsed = $this->isArgumentUnpackingUsed($node->args);

            $numberOfArguments = count($node->args);
            $isPossibleMatch = false;
            foreach ($this->flatMatcherDefinitions[$node->name->name]['candidates'] as $candidate) {
                foreach ($candidate['unusedArgumentNumbers'] as $droppedArgumentNumber) {
                    // A method call is considered a match if name matches, unpacking is not used
                    // and the registered argument is not given as null.
                    if (!$isArgumentUnpackingUsed
                        && $numberOfArguments >= $droppedArgumentNumber
                        && !($node->args[$droppedArgumentNumber - 1]->value instanceof ConstFetch)
                        && (!isset($node->args[$droppedArgumentNumber - 1]->value->name->name->parts[0])
                            || $node->args[$droppedArgumentNumber - 1]->value->name->name->parts[0] !== null)
                    ) {
                        $isPossibleMatch = true;
                        $match['message'] = 'Call to method "' . $node->name->name . '()" with'
                            . ' argument ' . $droppedArgumentNumber . ' not given as null.';
                        $match['restFiles'] = array_unique(array_merge($match['restFiles'], $candidate['restFiles']));
                    }
                }
            }
            if ($isPossibleMatch) {
                $this->matches[] = $match;
            }
        }
    }
}
