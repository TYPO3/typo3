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

use TYPO3\CMS\Core\TypoScript\AST\CurrentObjectPath\CurrentObjectPath;
use TYPO3\CMS\Core\TypoScript\AST\CurrentObjectPath\CurrentObjectPathStack;
use TYPO3\CMS\Core\TypoScript\AST\Node\ChildNode;
use TYPO3\CMS\Core\TypoScript\AST\Node\ChildNodeInterface;
use TYPO3\CMS\Core\TypoScript\AST\Node\NodeInterface;
use TYPO3\CMS\Core\TypoScript\AST\Node\ReferenceChildNode;
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
use TYPO3\CMS\Core\TypoScript\Tokenizer\Token\IdentifierTokenStream;
use TYPO3\CMS\Core\TypoScript\Tokenizer\Token\Token;
use TYPO3\CMS\Core\Utility\GeneralUtility;

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
final class AstBuilder
{
    /**
     * @var array<string, string>
     */
    private array $flatConstants = [];

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
                $this->handleIdentifierCopyLine($line, $currentObjectPathStack, $currentObjectPath);
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

    private function handleIdentifierAssignmentLine(IdentifierAssignmentLine $line, CurrentObjectPath $currentObjectPath)
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

    private function handleIdentifierUnsetLine(IdentifierUnsetLine $line, CurrentObjectPath $currentObjectPath)
    {
        $node = $currentObjectPath->getFirst();
        $identifierStream = $line->getIdentifierTokenStream()->reset();
        $previousIdentifierToken = $identifierStream->peekNext();
        while ($identifierToken = $identifierStream->getNext()) {
            if (!$foundNode = $node->getChildByName($identifierToken->getValue())) {
                break;
            }
            $nextIdentifierToken = $identifierStream->peekNext();
            if ($nextIdentifierToken) {
                $previousIdentifierToken = $identifierToken;
                $node = $foundNode;
                continue;
            }
            $node->removeChildByName($previousIdentifierToken->getValue());
            $node->removeChildByName($identifierToken->getValue());
            break;
        }
    }

    private function handleIdentifierCopyLine(IdentifierCopyLine $line, CurrentObjectPathStack $currentObjectPathStack, CurrentObjectPath $currentObjectPath)
    {
        $sourceIdentifierStream = $line->getValueTokenStream()->reset();
        if ($sourceIdentifierStream->isRelative()) {
            $sourceNode = $currentObjectPath->getLast();
        } else {
            $sourceNode = $currentObjectPathStack->getFirst()->getFirst();
        }
        while ($identifierToken = $sourceIdentifierStream->getNext()) {
            if (!$foundNode = $sourceNode->getChildByName($identifierToken->getValue())) {
                // Source node not found - nothing to do for this line
                return;
            }
            $sourceNode = $foundNode;
        }
        $isSourceNodeValueNull = true;
        if ($sourceNode->getValue() !== null) {
            $isSourceNodeValueNull = false;
        }
        $targetNode = $currentObjectPath->getFirst();
        $targetIdentifierTokenStream = $line->getIdentifierTokenStream()->reset();
        $previousTargetIdentifierToken = $targetIdentifierTokenStream->peekNext();
        while ($targetIdentifierToken = $targetIdentifierTokenStream->getNext()) {
            $hasNext = (bool)($targetIdentifierTokenStream->peekNext() ?? false);
            if (!$hasNext) {
                if ($isSourceNodeValueNull) {
                    $existingTargetNodeValue = $targetNode->getChildByName($previousTargetIdentifierToken->getValue())?->getValue();
                    if ($existingTargetNodeValue === null) {
                        $existingTargetNodeValue = $targetNode->getChildByName($targetIdentifierToken->getValue())?->getValue();
                    }
                } else {
                    // Blindly remove existing node if exists
                    $targetNode->removeChildByName($previousTargetIdentifierToken->getValue());
                }
                // Clone full source tree and update identifier name
                /** @var ChildNodeInterface $clonedNode */
                $clonedNode = clone $sourceNode;
                $clonedNode->updateName($targetIdentifierToken->getValue());
                if ($isSourceNodeValueNull && $existingTargetNodeValue) {
                    $clonedNode->setValue($existingTargetNodeValue);
                }
                $targetNode->addChild($clonedNode);
                // Done
                return;
            }
            $previousTargetIdentifierToken = $targetIdentifierToken;
            $newTargetNode = $targetNode->getChildByName($targetIdentifierToken->getValue());
            if ($newTargetNode === null) {
                $newTargetNode = new ChildNode($targetIdentifierToken->getValue());
                $targetNode->addChild($newTargetNode);
            }
            $targetNode = $newTargetNode;
        }
    }

    /**
     * "foo =< bar": Prepare a reference resolving.
     * Note this does *not* resolve "=<" itself at this point since this operator can only be
     * evaluated after the full AST has been established. Also, having a full AST-traverser run
     * that does this is *very* expensive and "=<" is only done for "tt_content.myElement" and
     * "lib.parseFunc" anyways. As such, "=<" is NOT a language construct itself and the AST-parser
     * only marks nodes that use it by using the special node "ObjectReference".
     * Resolving then happens "lazy" and "on demand" in ContentObjectRenderer cObjGetSingle()
     * and mergeTSRef() for frontend "setup" TypoScript.
     */
    private function handleIdentifierReferenceLine(IdentifierReferenceLine $line, CurrentObjectPath $currentObjectPath)
    {
        $tokenStream = $line->getIdentifierTokenStream();
        $node = $currentObjectPath->getFirst();
        $identifierStream = $tokenStream->reset();
        while ($identifierToken = $identifierStream->getNext()) {
            $nextIdentifier = $identifierStream->peekNext();
            $identifierTokenValue = $identifierToken->getValue();
            if (!($node->getChildByName($identifierTokenValue)) && $nextIdentifier) {
                // Add new node as new child of current last element in path
                $foundNode = new ChildNode($identifierTokenValue);
                $node->addChild($foundNode);
            } elseif (!($foundNode = $node->getChildByName($identifierTokenValue)) && $nextIdentifier === null) {
                $foundNode = new ReferenceChildNode($identifierTokenValue);
                $foundNode->setReferenceSourceStream($line->getValueTokenStream());
                $node->addChild($foundNode);
            }
            $node = $foundNode;
        }
    }

    private function getOrAddNodeFromIdentifierStream(CurrentObjectPath $currentObjectPath, IdentifierTokenStream $tokenStream): NodeInterface
    {
        $node = $currentObjectPath->getFirst();
        $identifierStream = $tokenStream->reset();
        while ($identifierToken = $identifierStream->getNext()) {
            $identifierTokenValue = $identifierToken->getValue();
            if (!$foundNode = $node->getChildByName($identifierTokenValue)) {
                // Add new node as new child of current last element in path
                $foundNode = new ChildNode($identifierTokenValue);
                $node->addChild($foundNode);
            }
            $node = $foundNode;
        }
        return $node;
    }

    /**
     * Evaluate operator functions, example TypoScript:
     * "page.10.value := appendString(foo)"
     */
    private function evaluateValueModifier(Token $functionNameToken, ?Token $functionValueToken, ?string $currentValue): ?string
    {
        $functionValue = '';
        if ($functionValueToken) {
            $functionValue = $functionValueToken->getValue();
        }
        switch ($functionNameToken->getValue()) {
            case 'prependString':
                return $functionValue . $currentValue;
            case 'appendString':
                return $currentValue . $functionValue;
            case 'removeString':
                return str_replace($functionValue, '', $currentValue);
            case 'replaceString':
                $functionValueArray = explode('|', $functionValue, 2);
                $fromStr = $functionValueArray[0] ?? '';
                $toStr = $functionValueArray[1] ?? '';
                return str_replace($fromStr, $toStr, $currentValue);
            case 'addToList':
                return ($currentValue !== null ? $currentValue . ',' : '') . $functionValue;
            case 'removeFromList':
                $existingElements = GeneralUtility::trimExplode(',', $currentValue);
                $removeElements = GeneralUtility::trimExplode(',', $functionValue);
                if (!empty($removeElements)) {
                    return implode(',', array_diff($existingElements, $removeElements));
                }
                return $currentValue;
            case 'uniqueList':
                $elements = GeneralUtility::trimExplode(',', $currentValue);
                return implode(',', array_unique($elements));
            case 'reverseList':
                $elements = GeneralUtility::trimExplode(',', $currentValue);
                return implode(',', array_reverse($elements));
            case 'sortList':
                $elements = GeneralUtility::trimExplode(',', $currentValue);
                $arguments = GeneralUtility::trimExplode(',', $functionValue);
                $arguments = array_map('strtolower', $arguments);
                $sortFlags = SORT_REGULAR;
                if (in_array('numeric', $arguments)) {
                    $sortFlags = SORT_NUMERIC;
                    // If the sorting modifier "numeric" is given, all values
                    // are checked and an exception is thrown if a non-numeric value is given
                    // otherwise there is a different behaviour between PHP 7 and PHP 5.x
                    // See also the warning on http://us.php.net/manual/en/function.sort.php
                    foreach ($elements as $element) {
                        if (!is_numeric($element)) {
                            throw new \InvalidArgumentException(
                                'The list "' . $currentValue . '" should be sorted numerically but contains a non-numeric value',
                                1650893781
                            );
                        }
                    }
                }
                sort($elements, $sortFlags);
                if (in_array('descending', $arguments)) {
                    $elements = array_reverse($elements);
                }
                return implode(',', $elements);
            case 'getEnv':
                $environmentValue = getenv(trim($functionValue));
                if ($environmentValue !== false) {
                    return $environmentValue;
                }
                return $currentValue;
            default:
                return $currentValue;
                // @todo: Implement (and test) hook again or switch to event along the way
                /*
                if (isset($GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS']['t3lib/class.t3lib_tsparser.php']['preParseFunc'][$modifierName])) {
                    $hookMethod = $GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS']['t3lib/class.t3lib_tsparser.php']['preParseFunc'][$modifierName];
                    $params = ['currentValue' => $currentValue, 'functionArgument' => $modifierArgument];
                    $fakeThis = null;
                    $newValue = GeneralUtility::callUserFunction($hookMethod, $params, $fakeThis);
                } else {
                    self::getLogger()->warning('Missing function definition for {modifier_name} on TypoScript', [
                        'modifier_name' => $modifierName,
                    ]);
                }
                */
        }
    }
}
