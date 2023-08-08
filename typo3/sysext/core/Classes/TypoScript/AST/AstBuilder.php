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

namespace TYPO3\CMS\Core\TypoScript\AST;

use Psr\EventDispatcher\EventDispatcherInterface;
use TYPO3\CMS\Core\TypoScript\AST\CurrentObjectPath\CurrentObjectPath;
use TYPO3\CMS\Core\TypoScript\AST\CurrentObjectPath\CurrentObjectPathStack;
use TYPO3\CMS\Core\TypoScript\AST\Node\RootNode;
use TYPO3\CMS\Core\TypoScript\Tokenizer\Line\BlockCloseLine;
use TYPO3\CMS\Core\TypoScript\Tokenizer\Line\IdentifierAssignmentLine;
use TYPO3\CMS\Core\TypoScript\Tokenizer\Line\IdentifierBlockOpenLine;
use TYPO3\CMS\Core\TypoScript\Tokenizer\Line\IdentifierCopyLine;
use TYPO3\CMS\Core\TypoScript\Tokenizer\Line\IdentifierFunctionLine;
use TYPO3\CMS\Core\TypoScript\Tokenizer\Line\IdentifierReferenceLine;
use TYPO3\CMS\Core\TypoScript\Tokenizer\Line\IdentifierUnsetLine;
use TYPO3\CMS\Core\TypoScript\Tokenizer\Line\LineStream;
use TYPO3\CMS\Core\TypoScript\Tokenizer\Token\ConstantAwareTokenStream;

/**
 * The main TypoScript AST builder.
 *
 * This creates a tree of Nodes, starting with the root node. Each node can have
 * children. The implementation basically iterates a LineStream created by the
 * tokenizers, and creates AST depending on the line type. It handles all the
 * different operator lines like "=", "<" and so on.
 *
 * @internal: Internal AST structure.
 */
final class AstBuilder extends AbstractAstBuilder implements AstBuilderInterface
{
    public function __construct(
        EventDispatcherInterface $eventDispatcher,
    ) {
        $this->eventDispatcher = $eventDispatcher;
    }

    /**
     * @param array<string, string> $flatConstants
     */
    public function build(LineStream $lineStream, RootNode $ast, array $flatConstants = []): RootNode
    {
        $this->flatConstants = $flatConstants;

        $currentObjectPath = new CurrentObjectPath($ast);
        $currentObjectPathStack = new CurrentObjectPathStack();
        $currentObjectPathStack->push($currentObjectPath);

        foreach ($lineStream->getNextLine() as $line) {
            if ($line instanceof IdentifierAssignmentLine) {
                // "foo = bar" and "foo ( bar )": Single and multi line assignments
                $this->handleIdentifierAssignmentLine($line, $currentObjectPath);
            } elseif ($line instanceof IdentifierBlockOpenLine) {
                // "foo {": Opening a block - push to object path stack
                $node = $this->getOrAddNodeFromIdentifierStream($currentObjectPath, $line->getIdentifierTokenStream());
                $currentObjectPath = (new CurrentObjectPath($node));
                $currentObjectPathStack->push($currentObjectPath);
            } elseif ($line instanceof BlockCloseLine) {
                // "}": Closing a block - pop from object path stack
                $currentObjectPath = $currentObjectPathStack->pop();
            } elseif ($line instanceof IdentifierUnsetLine) {
                // "foo >": Remove a path
                $this->handleIdentifierUnsetLine($line, $currentObjectPath);
            } elseif ($line instanceof IdentifierCopyLine) {
                // "foo < bar": Copy a node source path to a target path
                $this->handleIdentifierCopyLine($line, $ast, $currentObjectPath);
            } elseif ($line instanceof IdentifierFunctionLine) {
                // "foo := addToList(42)": Evaluate functions
                $node = $this->getOrAddNodeFromIdentifierStream($currentObjectPath, $line->getIdentifierTokenStream());
                $node->setValue($this->evaluateValueModifier($line->getFunctionNameToken(), $line->getFunctionValueToken(), $node->getValue()));
            } elseif ($line instanceof IdentifierReferenceLine) {
                // "foo =< bar": Prepare a reference resolving
                $this->handleIdentifierReferenceLine($line, $currentObjectPath);
            }
        }

        return $ast;
    }

    private function handleIdentifierAssignmentLine(IdentifierAssignmentLine $line, CurrentObjectPath $currentObjectPath): void
    {
        $node = $this->getOrAddNodeFromIdentifierStream($currentObjectPath, $line->getIdentifierTokenStream());
        $valueTokenStream = $line->getValueTokenStream();
        if ($valueTokenStream instanceof ConstantAwareTokenStream) {
            $valueTokenStream->setFlatConstants($this->flatConstants);
            $node->setValue((string)$valueTokenStream);
            $valueTokenStream->setFlatConstants(null);
            $node->setOriginalValueTokenStream($valueTokenStream);
            return;
        }
        $node->setValue((string)$valueTokenStream);
    }
}
