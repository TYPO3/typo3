<?php
declare(strict_types = 1);
namespace TYPO3\CMS\Install\ExtensionScanner\Php;

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
use PhpParser\Node\Name\FullyQualified;
use PhpParser\Node\Scalar\String_;
use PhpParser\NodeVisitorAbstract;

/**
 * Create a fully qualified class name object from first argument of
 * GeneralUtility::makeInstance('My\\Package\\Class\\Name') if given as string
 * and not as My\Package\Class\Name::class language construct.
 *
 * This resolver is to be called after generic NameResolver::class, but before
 * other search and find visitors that implement CodeScannerInterface::class
 * @internal This class is only meant to be used within EXT:install and is not part of the TYPO3 Core API.
 */
class GeneratorClassesResolver extends NodeVisitorAbstract
{
    /**
     * Called by PhpParser.
     * Create an fqdn object from first makeInstance argument if it is a String
     *
     * @param Node $node Incoming node
     */
    public function enterNode(Node $node)
    {
        if ($node instanceof StaticCall
            && $node->class instanceof FullyQualified
            && $node->class->toString() === 'TYPO3\CMS\Core\Utility\GeneralUtility'
            && $node->name->name === 'makeInstance'
            && isset($node->args[0]->value)
            && $node->args[0]->value instanceof String_
        ) {
            $newSubNode = new FullyQualified($node->args[0]->value->value, $node->args[0]->value->getAttributes());
            $node->args[0]->value = $newSubNode;
        }
    }
}
