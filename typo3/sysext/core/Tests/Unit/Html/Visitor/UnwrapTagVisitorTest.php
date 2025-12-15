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

namespace TYPO3\CMS\Core\Tests\Unit\Html\Visitor;

use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Test;
use TYPO3\CMS\Core\Html\Visitor\UnwrapTagVisitor;
use TYPO3\TestingFramework\Core\Unit\UnitTestCase;

final class UnwrapTagVisitorTest extends UnitTestCase
{
    public static function unwrapTagsDataProvider(): array
    {
        return [
            'simple link' => [
                'a',
                '<div><a href="https://example.com">link text</a></div>',
                'link text',
            ],
            'link with attributes' => [
                'a',
                '<div><a href="https://example.com" class="btn">link</a></div>',
                'link',
            ],
            'h1 tag' => [
                'h1',
                '<div><h1>Heading</h1></div>',
                'Heading',
            ],
            'h2 tag' => [
                'h2',
                '<div><h2>Heading</h2></div>',
                'Heading',
            ],
            'h3 tag' => [
                'h3',
                '<div><h3>Heading</h3></div>',
                'Heading',
            ],
            'case insensitive uppercase A' => [
                'A',
                '<div><A>link</A></div>',
                'link',
            ],
            'case insensitive uppercase H1' => [
                'H1',
                '<div><H1>heading</H1></div>',
                'heading',
            ],
            'case insensitive mixed case' => [
                'a',
                '<div><A href="test">link</A></div>',
                'link',
            ],
            'preserves child elements' => [
                'a',
                '<div><a href="#">Text <strong>bold</strong> more</a></div>',
                'Text <strong>bold</strong> more',
            ],
            'empty tag' => [
                'a',
                '<div><a href="#"></a></div>',
                '',
            ],
            'multiple children' => [
                'h1',
                '<div><h1><span>Part 1</span> <span>Part 2</span></h1></div>',
                '<span>Part 1</span> <span>Part 2</span>',
            ],
        ];
    }

    #[DataProvider('unwrapTagsDataProvider')]
    #[Test]
    public function enterNodeUnwrapsSpecifiedTags(string $tagName, string $html, string $expectedContent): void
    {
        $visitor = new UnwrapTagVisitor();
        $doc = new \DOMDocument();
        $doc->loadHTML($html, LIBXML_HTML_NOIMPLIED | LIBXML_HTML_NODEFDTD);

        // Find the tag to unwrap - DOM normalizes tag names to lowercase
        $tag = $doc->getElementsByTagName(strtolower($tagName))->item(0);
        self::assertNotNull($tag, "Tag {$tagName} should exist in test HTML");

        // Call enterNode to unwrap the tag
        $result = $visitor->enterNode($tag);

        // Visitor should return null to indicate the node should be removed
        self::assertNull($result, 'enterNode should return null for unwrapped tags');

        // Check that the content was preserved
        self::assertStringContainsString($expectedContent, $doc->saveHTML($doc->getElementsByTagName('div')->item(0)));
    }

    #[Test]
    public function enterNodePreservesNonUnwrapTags(): void
    {
        $visitor = new UnwrapTagVisitor();
        $doc = new \DOMDocument();
        $html = '<div><span class="test">content</span></div>';
        $doc->loadHTML($html, LIBXML_HTML_NOIMPLIED | LIBXML_HTML_NODEFDTD);

        $span = $doc->getElementsByTagName('span')->item(0);
        self::assertNotNull($span);

        // Call enterNode - should return the node unchanged
        $result = $visitor->enterNode($span);

        // Should return the original node since span is not in UNWRAP_TAGS
        self::assertSame($span, $result);
    }

    #[Test]
    public function enterNodeHandlesNonElementNodes(): void
    {
        $visitor = new UnwrapTagVisitor();
        $doc = new \DOMDocument();
        $textNode = $doc->createTextNode('test text');

        // Should return the node unchanged for non-element nodes
        $result = $visitor->enterNode($textNode);
        self::assertSame($textNode, $result);
    }

    #[Test]
    public function enterNodeReturnsNullForTagWithoutParent(): void
    {
        $visitor = new UnwrapTagVisitor();
        $doc = new \DOMDocument();

        // Create an anchor element without attaching it to the document
        $anchor = $doc->createElement('a');
        $anchor->textContent = 'test';

        // Should return null when parent is null
        $result = $visitor->enterNode($anchor);
        self::assertNull($result, 'enterNode should return null when tag has no parent');
    }

    #[Test]
    public function leaveNodeReturnsNodeUnchanged(): void
    {
        $visitor = new UnwrapTagVisitor();
        $doc = new \DOMDocument();
        $node = $doc->createElement('div');

        $result = $visitor->leaveNode($node);
        self::assertSame($node, $result, 'leaveNode should return the node unchanged');
    }

    #[Test]
    public function allTargetTagsAreUnwrapped(): void
    {
        $visitor = new UnwrapTagVisitor();

        // Test all tags in UNWRAP_TAGS constant
        $unwrapTags = ['a', 'h1', 'h2', 'h3', 'h4', 'h5', 'h6'];

        foreach ($unwrapTags as $tagName) {
            $doc = new \DOMDocument();
            $html = "<div><{$tagName}>content</{$tagName}></div>";
            $doc->loadHTML($html, LIBXML_HTML_NOIMPLIED | LIBXML_HTML_NODEFDTD);

            $tag = $doc->getElementsByTagName($tagName)->item(0);
            self::assertNotNull($tag, "Tag {$tagName} should exist");

            $result = $visitor->enterNode($tag);
            self::assertNull($result, "Tag {$tagName} should be unwrapped (return null)");
        }
    }
}
