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

namespace TYPO3\CMS\Core\Html\Visitor;

use TYPO3\HtmlSanitizer\Context;
use TYPO3\HtmlSanitizer\Visitor\VisitorInterface;

/**
 * Visitor to remove tags but keep its content
 *
 * @internal
 */
final class UnwrapTagVisitor implements VisitorInterface
{
    private const UNWRAP_TAGS = [
        'a',
        'h1',
        'h2',
        'h3',
        'h4',
        'h5',
        'h6',
    ];

    public function beforeTraverse(Context $context) {}

    public function enterNode(\DOMNode $domNode): ?\DOMNode
    {
        if (
            !$domNode instanceof \DOMElement
            || !in_array(strtolower($domNode->tagName), self::UNWRAP_TAGS, true)
        ) {
            return $domNode;
        }

        $parent = $domNode->parentNode;
        if ($parent === null) {
            return null;
        }

        // Move all children before the current node
        while ($domNode->firstChild !== null) {
            $parent->insertBefore($domNode->firstChild, $domNode);
        }

        // Remove the wrapping element
        return null;
    }

    public function leaveNode(\DOMNode $domNode): \DOMNode
    {
        return $domNode;
    }

    public function afterTraverse(Context $context) {}
}
