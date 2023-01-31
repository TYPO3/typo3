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

namespace TYPO3\CMS\Core\TypoScript\AST\Node;

use TYPO3\CMS\Core\TypoScript\Tokenizer\Token\TokenStreamInterface;

/**
 * The created AST consists of a NodeRoot object with nested NodeObject children.
 * This is the main interface to any node type.
 *
 * Example TypoScript:
 * "foo = fooValue"
 * "foo.bar = barValue"
 * This creates a RootNode with one ChildNode name "foo" and value "fooValue",
 * that has a child ChildNode name "bar" and value "barValue".
 *
 * @internal: Internal AST structure.
 */
interface NodeInterface
{
    /**
     * An identifier for this node. Typically, a hash of some kind. This identifier
     * is unique within the tree, by being created from the parent identifier plus
     * the name. This identifier is used in the backend, when referencing single nodes.
     * Calculating identifiers is initiated by calling setIdentifier() on RootNode, which
     * will recurse the tree. Call this on the final tree, after AST calculation finished,
     * so AST building itself does not need to fiddle with identifier updates when for
     * instance tree parts are cloned using '<' operator.
     * Note this value is skipped when persisting to caches since it's a Backend related
     * thing that does not use cached context: When retrieving nodes from cache (e.g. in Frontend),
     * the identifier is null and calling the getter will throw an exception.
     */
    public function setIdentifier(string $identifier): void;
    public function getIdentifier(): string;

    /**
     * Helper methods for node name.
     */
    public function getName(): ?string;
    public function updateName(string $name): void;

    /**
     * Helper methods to operate on children.
     */
    public function addChild(ChildNodeInterface $node): void;
    public function getChildByName(string $name): ?ChildNodeInterface;
    public function removeChildByName(string $name): void;
    public function hasChildren(): bool;
    /**
     * @return iterable<ChildNodeInterface>
     */
    public function getNextChild(): iterable;
    public function sortChildren(): void;

    /**
     * Helper methods for value.
     */
    public function setValue(?string $value): void;
    public function appendValue(string $value): void;
    public function getValue(): ?string;
    public function isValueNull(): bool;

    /**
     * Previous value is only set by comment aware ast builder. It is used in
     * constant editor to see if a value has been changed.
     */
    public function setPreviousValue(?string $value): void;
    public function getPreviousValue(): ?string;

    /**
     * Helper method mostly for backend object browser to retrieve the original
     * stream when a constant substitution happened.
     */
    public function setOriginalValueTokenStream(?TokenStreamInterface $tokenStream): void;
    public function getOriginalValueTokenStream(): ?TokenStreamInterface;

    /**
     * Helper methods to attach TypoScript tokens to a node.
     * This is used in ext:tstemplate "Constant Editor" and "Object Browser" and handled
     * by CommentAwareAstBuilder.
     */
    public function addComment(TokenStreamInterface $tokenStream): void;
    /**
     * @return TokenStreamInterface[]
     */
    public function getComments(): array;

    /**
     * b/w compat method to turn AST into an array.
     * Note we're NOT using magic __toArray() here to avoid calling array-cast of AST by
     * accident: toArray() should be called explicitly if needed, which makes it much easier
     * to drop this b/w compat method when we later want to drop that layer.
     *
     * Note RootNode *always* returns an array, while ObjectNode's may return null.
     */
    public function toArray(): ?array;

    /**
     * Flatten the tree. A RootNode with a ChildNode "foo" and value "fooValue", with this
     * ChildNode again having a ChildNode "bar" and value "barValue" becomes:
     * [
     *      'foo' => 'fooValue',
     *      'foo.bar'  => 'barValue',
     * ]
     *
     * Flattening a TypoScript tree is especially used for constants to quickly look
     * up constants when parsing setup node value streams that use T_CONSTANT tokens.
     */
    public function flatten(string $prefix = ''): array;
}
