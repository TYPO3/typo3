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

use TYPO3\CMS\Core\TypoScript\AST\AstBuilder;
use TYPO3\CMS\Core\TypoScript\AST\Node\RootNode;
use TYPO3\CMS\Core\TypoScript\IncludeTree\IncludeNode\IncludeInterface;
use TYPO3\CMS\Core\TypoScript\IncludeTree\IncludeNode\SysTemplateInclude;

/**
 * Main visitor that creates the TypoScript AST: When adding this visitor
 * and traversing the IncludeTree, the final AST can be fetched using getAst().
 *
 * This visitor is usually only used together with ConditionVerdictAwareIncludeTreeTraverser,
 * and the IncludeTreeConditionMatcherVisitor is added *before* this visitor to determine
 * condition verdicts, so AST is only extended for matching conditions.
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

    public function __construct(private readonly AstBuilder $astBuilder)
    {
        $this->ast = new RootNode();
    }

    /**
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

    public function visitBeforeChildren(IncludeInterface $include, int $currentDepth): void
    {
        if ($include instanceof SysTemplateInclude && $include->isClear()) {
            // Reset any given AST if this sys_template row has clear flag (constants or setup clear) set.
            $this->ast = new RootNode();
        }
    }

    public function visit(IncludeInterface $include, int $currentDepth): void
    {
        $tokenStream = $include->getLineStream();
        if ($tokenStream && !$include->isSplit()) {
            $this->ast = $this->astBuilder->build($tokenStream, $this->ast, $this->flatConstants);
        }
    }
}
