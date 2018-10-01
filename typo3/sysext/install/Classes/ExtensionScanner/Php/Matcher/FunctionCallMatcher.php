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
use PhpParser\Node\Expr\FuncCall;
use PhpParser\Node\Name\FullyQualified;

/**
 * Find usages of global function calls which were removed / deprecated.
 * This is a strong match.
 * @internal This class is only meant to be used within EXT:install and is not part of the TYPO3 Core API.
 */
class FunctionCallMatcher extends AbstractCoreMatcher
{
    /**
     * Prepare $this->flatMatcherDefinitions once
     *
     * @param array $matcherDefinitions Incoming main configuration
     */
    public function __construct(array $matcherDefinitions)
    {
        $this->matcherDefinitions = $matcherDefinitions;
        $this->validateMatcherDefinitions(['numberOfMandatoryArguments', 'maximumNumberOfArguments']);
    }

    /**
     * Called by PhpParser.
     * Test for "removedFunction()" (strong match)
     *
     * @param Node $node
     */
    public function enterNode(Node $node)
    {
        // Match method call (not static)
        if (!$this->isFileIgnored($node)
            && !$this->isLineIgnored($node)
            && $node instanceof FuncCall
            && $node->name instanceof FullyQualified
            && array_key_exists($node->name->toString(), $this->matcherDefinitions)
        ) {
            $functionName = $node->name->toString();
            $matchDefinition = $this->matcherDefinitions[$functionName];

            $numberOfArguments = count($node->args);
            $isArgumentUnpackingUsed = $this->isArgumentUnpackingUsed($node->args);

            if ($isArgumentUnpackingUsed
                || ($numberOfArguments >= $matchDefinition['numberOfMandatoryArguments']
                    && $numberOfArguments <= $matchDefinition['maximumNumberOfArguments'])
            ) {
                $this->matches[] = [
                    'restFiles' => $matchDefinition['restFiles'],
                    'line' => $node->getAttribute('startLine'),
                    'message' => 'Call to function "' . $functionName . '"',
                    'indicator' => 'strong',
                ];
            }
        }
    }
}
