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

namespace TYPO3\CMS\Core\TypoScript\IncludeTree\Visitor;

use TYPO3\CMS\Core\TypoScript\AST\AstBuilderInterface;
use TYPO3\CMS\Core\TypoScript\AST\Node\RootNode;
use TYPO3\CMS\Core\TypoScript\IncludeTree\IncludeNode\IncludeInterface;
use TYPO3\CMS\Core\TypoScript\IncludeTree\IncludeNode\SysTemplateInclude;

/**
 * Main visitor that creates the TypoScript AST: When adding this visitor
 * and traversing the IncludeTree, the final AST can be fetched using getAst().
 *
 * This visitor is usually only used together with ConditionVerdictAwareIncludeTreeTraverser,
 * and the IncludeTreeConditionMatcherVisitor is added *before* this visitor to determine
 * condition verdicts, so AST is only extended for conditions with "true" verdict.
 *
 * When parsing "setup", "flattened" constants should be assigned to this visitor, so
 * the AstBuilder can resolve constants.
 *
 * @internal: Internal tree structure.
 */
final class IncludeTreeAstBuilderVisitor implements IncludeTreeVisitorInterface
{
    private RootNode $ast;

    /**
     * @var array<string, string>
     */
    private array $flatConstants = [];

    public function __construct(private readonly AstBuilderInterface $astBuilder)
    {
        $this->ast = new RootNode();
    }

    /**
     * When 'setup' is parsed, setting resolved flat constants here will make
     * the AST builder substitute these constants.
     *
     * @param array<string, string> $flatConstants
     */
    public function setFlatConstants(array $flatConstants)
    {
        $this->flatConstants = $flatConstants;
    }

    public function getAst(): RootNode
    {
        return $this->ast;
    }

    /**
     * Reset AST if "clear" flag is set. That's a sys_template record specific thing
     * to restart with a new RootNode and drop any AST calculated already.
     */
    public function visitBeforeChildren(IncludeInterface $include, int $currentDepth): void
    {
        if ($include instanceof SysTemplateInclude && $include->isClear()) {
            // Reset any given AST if this sys_template row has clear flag (constants or setup clear) set.
            $this->ast = new RootNode();
        }
    }

    /**
     * Extend current AST with given LineStream of include node.
     */
    public function visit(IncludeInterface $include, int $currentDepth): void
    {
        $lineStream = $include->getLineStream();
        if ($lineStream && !$include->isSplit()) {
            // A "split" include means that the entire TypoScript is split into child includes. The
            // TokenStream of the split include itself must not be parsed, so it's excluded here.
            $this->ast = $this->astBuilder->build($lineStream, $this->ast, $this->flatConstants);
        }
    }
}
