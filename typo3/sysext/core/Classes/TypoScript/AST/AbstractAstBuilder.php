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
use TYPO3\CMS\Core\TypoScript\AST\Event\EvaluateModifierFunctionEvent;
use TYPO3\CMS\Core\TypoScript\AST\Node\ChildNode;
use TYPO3\CMS\Core\TypoScript\AST\Node\ChildNodeInterface;
use TYPO3\CMS\Core\TypoScript\AST\Node\NodeInterface;
use TYPO3\CMS\Core\TypoScript\AST\Node\ReferenceChildNode;
use TYPO3\CMS\Core\TypoScript\Tokenizer\Line\IdentifierCopyLine;
use TYPO3\CMS\Core\TypoScript\Tokenizer\Line\IdentifierReferenceLine;
use TYPO3\CMS\Core\TypoScript\Tokenizer\Line\IdentifierUnsetLine;
use TYPO3\CMS\Core\TypoScript\Tokenizer\Token\IdentifierTokenStream;
use TYPO3\CMS\Core\TypoScript\Tokenizer\Token\Token;
use TYPO3\CMS\Core\Utility\GeneralUtility;

/**
 * Common methods of both AST builders.
 *
 * @internal: Internal AST structure.
 */
abstract class AbstractAstBuilder
{
    /**
     * @var array<string, string>
     */
    protected array $flatConstants = [];
    protected EventDispatcherInterface $eventDispatcher;

    protected function handleIdentifierUnsetLine(IdentifierUnsetLine $line, CurrentObjectPath $currentObjectPath)
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

    protected function handleIdentifierCopyLine(IdentifierCopyLine $line, CurrentObjectPathStack $currentObjectPathStack, CurrentObjectPath $currentObjectPath): ?NodeInterface
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
                return null;
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
                return $clonedNode;
            }
            $previousTargetIdentifierToken = $targetIdentifierToken;
            $newTargetNode = $targetNode->getChildByName($targetIdentifierToken->getValue());
            if ($newTargetNode === null) {
                $newTargetNode = new ChildNode($targetIdentifierToken->getValue());
                $targetNode->addChild($newTargetNode);
            }
            $targetNode = $newTargetNode;
        }
        return null;
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
    protected function handleIdentifierReferenceLine(IdentifierReferenceLine $line, CurrentObjectPath $currentObjectPath): NodeInterface
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
        return $node;
    }

    protected function getOrAddNodeFromIdentifierStream(CurrentObjectPath $currentObjectPath, IdentifierTokenStream $tokenStream): NodeInterface
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
    protected function evaluateValueModifier(Token $functionNameToken, ?Token $functionArgumentToken, ?string $originalValue): ?string
    {
        $functionName = $functionNameToken->getValue();
        $functionArgument = null;
        if ($functionArgumentToken) {
            $functionArgument = $functionArgumentToken->getValue();
        }
        switch ($functionName) {
            case 'prependString':
                return $functionArgument . $originalValue;
            case 'appendString':
                return $originalValue . $functionArgument;
            case 'removeString':
                return str_replace((string)$functionArgument, '', $originalValue);
            case 'replaceString':
                $functionValueArray = explode('|', (string)$functionArgument, 2);
                $fromStr = $functionValueArray[0] ?? '';
                $toStr = $functionValueArray[1] ?? '';
                return str_replace($fromStr, $toStr, $originalValue);
            case 'addToList':
                return ($originalValue !== null ? $originalValue . ',' : '') . $functionArgument;
            case 'removeFromList':
                $existingElements = GeneralUtility::trimExplode(',', $originalValue);
                $removeElements = GeneralUtility::trimExplode(',', (string)$functionArgument);
                if (!empty($removeElements)) {
                    return implode(',', array_diff($existingElements, $removeElements));
                }
                return $originalValue;
            case 'uniqueList':
                $elements = GeneralUtility::trimExplode(',', $originalValue);
                return implode(',', array_unique($elements));
            case 'reverseList':
                $elements = GeneralUtility::trimExplode(',', $originalValue);
                return implode(',', array_reverse($elements));
            case 'sortList':
                $elements = GeneralUtility::trimExplode(',', $originalValue);
                $arguments = GeneralUtility::trimExplode(',', (string)$functionArgument);
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
                                'The list "' . $originalValue . '" should be sorted numerically but contains a non-numeric value',
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
                $environmentValue = getenv(trim((string)$functionArgument));
                if ($environmentValue !== false) {
                    return $environmentValue;
                }
                return $originalValue;
            default:
                return $this->eventDispatcher->dispatch(new EvaluateModifierFunctionEvent($functionName, $functionArgument, $originalValue))->getValue() ?? $originalValue;
        }
    }
}
