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

namespace TYPO3\CMS\Install\ExtensionScanner\Php;

use PhpParser\BuilderFactory;
use PhpParser\Node;
use PhpParser\Node\Expr;
use PhpParser\Node\Expr\ClassConstFetch;
use PhpParser\Node\Expr\New_;
use PhpParser\Node\Expr\StaticCall;
use PhpParser\Node\Name\FullyQualified;
use PhpParser\Node\Scalar\String_;
use PhpParser\NodeVisitorAbstract;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Install\ExtensionScanner\Php\Matcher\AbstractCoreMatcher;

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
     * @var BuilderFactory
     */
    protected $builderFactory;

    public function __construct(BuilderFactory $builderFactory = null)
    {
        $this->builderFactory = $builderFactory ?? new BuilderFactory();
    }

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
            && $node->class->toString() === GeneralUtility::class
            && $node->name->name === 'makeInstance'
            && isset($node->args[0]->value)
            && $node->args[0]->value instanceof Expr
        ) {
            $argValue = $node->args[0]->value;
            $argAlternative = $this->substituteClassString($argValue);
            if ($argAlternative !== null) {
                $node->args[0]->value = $argAlternative;
                $argValue = $argAlternative;
            }

            $nodeAlternative = $this->substituteMakeInstance($node, $argValue);
            if ($nodeAlternative !== null) {
                $node->setAttribute(AbstractCoreMatcher::NODE_RESOLVED_AS, $nodeAlternative);
            }
        }
        return null;
    }

    /**
     * Substitutes class-string values with their corresponding class constant
     * representation (`'Vendor\\ClassName'` -> `\Vendor\ClassName::class`).
     *
     * @param Expr $argValue
     * @return ClassConstFetch|null
     */
    protected function substituteClassString(Expr $argValue): ?ClassConstFetch
    {
        // skip non-strings, and those starting with (invalid) namespace separator
        if (!$argValue instanceof String_ || $argValue->value[0] === '\\') {
            return null;
        }

        $classString = ltrim($argValue->value, '\\');
        $className = new FullyQualified($classString);
        $classArg = $this->builderFactory->classConstFetch($className, 'class');
        $this->duplicateNodeAttributes($argValue, $className, $classArg);
        return $classArg;
    }

    /**
     * Substitutes `makeInstance` invocations with proper `new` invocations.
     * `GeneralUtility(\Vendor\ClassName::class, 'a', 'b')` -> `new \Vendor\ClassName('a', 'b')`
     *
     * @param StaticCall $node
     * @param Expr $argValue
     * @return New_|null
     */
    protected function substituteMakeInstance(StaticCall $node, Expr $argValue): ?New_
    {
        if (!$argValue instanceof ClassConstFetch
            || !$argValue->class instanceof FullyQualified
        ) {
            return null;
        }

        $newExpr = $this->builderFactory->new(
            $argValue->class,
            array_slice($node->args, 1),
        );
        $this->duplicateNodeAttributes($node, $newExpr);
        return $newExpr;
    }

    /**
     * Duplicates node positions in source file, based on the assumption
     * that only lines are relevant. In case this shall be used for
     * code-migration, real offset positions would be required.
     *
     * @param Node $source
     * @param Node ...$targets
     */
    protected function duplicateNodeAttributes(Node $source, Node ...$targets): void
    {
        foreach ($targets as $target) {
            $target->setAttributes([
                'startLine' => $source->getStartLine(),
                'endLine' => $source->getEndLine(),
            ]);
        }
    }
}
