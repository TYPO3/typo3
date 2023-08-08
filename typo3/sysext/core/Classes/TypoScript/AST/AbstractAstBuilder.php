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
use TYPO3\CMS\Core\TypoScript\AST\Event\EvaluateModifierFunctionEvent;
use TYPO3\CMS\Core\TypoScript\AST\Node\ChildNode;
use TYPO3\CMS\Core\TypoScript\AST\Node\ChildNodeInterface;
use TYPO3\CMS\Core\TypoScript\AST\Node\NodeInterface;
use TYPO3\CMS\Core\TypoScript\AST\Node\ReferenceChildNode;
use TYPO3\CMS\Core\TypoScript\AST\Node\RootNode;
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

    protected function handleIdentifierUnsetLine(IdentifierUnsetLine $line, CurrentObjectPath $currentObjectPath): void
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

    protected function handleIdentifierCopyLine(IdentifierCopyLine $line, RootNode $rootNode, CurrentObjectPath $currentObjectPath): ?NodeInterface
    {
        $sourceIdentifierStream = $line->getValueTokenStream()->reset();
        $sourceNode = $rootNode;
        if ($sourceIdentifierStream->isRelative()) {
            // Entry node is current node from current object path if relative, otherwise RootNode.
            $sourceNode = $currentObjectPath->getLast();
        }
        while ($identifierToken = $sourceIdentifierStream->getNext()) {
            // Go through source token stream and locate the sourceNode to copy from.
            if (!$sourceNode = $sourceNode->getChildByName($identifierToken->getValue())) {
                // Source node not found - nothing to do for this line
                return null;
            }
        }
        $isSourceNodeValueNull = true;
        if ($sourceNode->getValue() !== null) {
            // When the source node value is not null, it will override the target node value if that exists.
            $isSourceNodeValueNull = false;
        }

        // Locate/create the targets parent node the copied source should be added as child to,
        // and get the name of the node we're dealing with.
        $targetIdentifierTokenStream = $line->getIdentifierTokenStream()->reset();
        $targetParentNode = $currentObjectPath->getFirst();
        $targetTokenName = null;
        while ($targetToken = $targetIdentifierTokenStream->getNext()) {
            $targetTokenName = $targetToken->getValue();
            if (!($targetIdentifierTokenStream->peekNext() ?? false)) {
                break;
            }
            if (!$foundNode = $targetParentNode->getChildByName($targetTokenName)) {
                // Add new node as new child of current last element in path
                $foundNode = new ChildNode($targetTokenName);
                $targetParentNode->addChild($foundNode);
            }
            $targetParentNode = $foundNode;
        }

        $existingTarget = null;
        if ($isSourceNodeValueNull) {
            // When the node to copy has no value, but the existing target has,
            // the value from the existing target is kept. Also, if the existing
            // node is a ReferenceChildNode and the source does not override this,
            // source children are added to the existing reference instead of
            // dropping the existing target.
            $existingTarget = $targetParentNode->getChildByName($targetTokenName);
            $existingTargetNodeValue = $existingTarget?->getValue();
        } else {
            // Blindly remove existing target node if exists and the value is not overridden by source.
            $targetParentNode->removeChildByName($targetTokenName);
        }
        if ($existingTarget instanceof ReferenceChildNode) {
            // When existing target is a ReferenceChildNode, keep it and
            // copy children from source into existing target.
            $targetNode = $existingTarget;
            foreach ($sourceNode->getNextChild() as $sourceChild) {
                $targetNode->addChild(clone $sourceChild);
            }
        } else {
            // Clone full source node tree, update name and add as child to parent node.
            /** @var ChildNodeInterface $targetNode */
            $targetNode = clone $sourceNode;
            $targetNode->updateName($targetTokenName);
            $targetParentNode->addChild($targetNode);
        }
        if ($isSourceNodeValueNull && $existingTargetNodeValue) {
            // If value of old existing target should be kept, set in now.
            $targetNode->setValue($existingTargetNodeValue);
        }

        return $targetNode;
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
            } elseif (!$node->getChildByName($identifierTokenValue) && $nextIdentifier === null) {
                // Parent of target node exists, but target node does not. Add new reference child.
                $foundNode = new ReferenceChildNode($identifierTokenValue);
                $foundNode->setReferenceSourceStream($line->getValueTokenStream());
                $node->addChild($foundNode);
            } elseif (($foundNode = $node->getChildByName($identifierTokenValue)) && $nextIdentifier === null) {
                // Target node exists already. We create a new one, remove old, but transfer existing children from old to new.
                $newNode = new ReferenceChildNode($identifierTokenValue);
                $newNode->setReferenceSourceStream($line->getValueTokenStream());
                foreach ($foundNode->getNextChild() as $existingNodeChild) {
                    $newNode->addChild($existingNodeChild);
                }
                $node->removeChildByName($identifierTokenValue);
                $node->addChild($newNode);
                $foundNode = $newNode;
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
