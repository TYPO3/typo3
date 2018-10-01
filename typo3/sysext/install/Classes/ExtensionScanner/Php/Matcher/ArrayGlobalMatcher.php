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
use PhpParser\Node\Expr\ArrayDimFetch;
use PhpParser\Node\Expr\Variable;
use PhpParser\Node\Scalar\String_;

/**
 * Match access to a one dimensional $GLOBAL array
 * Example "$GLOBALS['TYPO3_DB']"
 * @internal This class is only meant to be used within EXT:install and is not part of the TYPO3 Core API.
 */
class ArrayGlobalMatcher extends AbstractCoreMatcher
{
    /**
     * Initialize "flat" matcher array from matcher definitions.
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
            && $node instanceof ArrayDimFetch
            && $node->var instanceof Variable
            && $node->var->name === 'GLOBALS'
            && $node->dim instanceof String_
            && array_key_exists('$GLOBALS[\'' . $node->dim->value . '\']', $this->matcherDefinitions)
        ) {
            $this->matches[] = [
                'restFiles' => $this->matcherDefinitions['$GLOBALS[\'' . $node->dim->value . '\']']['restFiles'],
                'line' => $node->getAttribute('startLine'),
                'message' => 'Access to array global array "' . $node->dim->value . '"',
                'indicator' => 'strong',
            ];
        }
    }
}
