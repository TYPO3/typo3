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
    public function leaveNodeUnwrapsSpecifiedTags(string $tagName, string $html, string $expectedContent): void
    {
        $visitor = new UnwrapTagVisitor();
        $doc = new \DOMDocument();
        $doc->loadHTML($html, LIBXML_HTML_NOIMPLIED | LIBXML_HTML_NODEFDTD);

        // Find the tag to unwrap - DOM normalizes tag names to lowercase
        $tag = $doc->getElementsByTagName(strtolower($tagName))->item(0);
        self::assertNotNull($tag, "Tag {$tagName} should exist in test HTML");

        // Call leaveNode to unwrap the tag (unwrapping happens post-order,
        // after children have already been traversed by other visitors)
        $result = $visitor->leaveNode($tag);

        // Visitor should return null to indicate the node should be removed
        self::assertNull($result, 'leaveNode should return null for unwrapped tags');

        // Check that the content was preserved
        self::assertStringContainsString($expectedContent, $doc->saveHTML($doc->getElementsByTagName('div')->item(0)));
    }

    #[Test]
    public function enterNodeAlwaysReturnsNodeUnchanged(): void
    {
        $visitor = new UnwrapTagVisitor();
        $doc = new \DOMDocument();
        $html = '<div><a href="#">link</a><span class="test">content</span></div>';
        $doc->loadHTML($html, LIBXML_HTML_NOIMPLIED | LIBXML_HTML_NODEFDTD);

        // enterNode no longer unwraps anything - unwrapping is deferred to
        // leaveNode so that children are sanitized before being reparented
        $anchor = $doc->getElementsByTagName('a')->item(0);
        self::assertSame($anchor, $visitor->enterNode($anchor));

        $span = $doc->getElementsByTagName('span')->item(0);
        self::assertSame($span, $visitor->enterNode($span));
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
    public function leaveNodeReturnsNullForTagWithoutParent(): void
    {
        $visitor = new UnwrapTagVisitor();
        $doc = new \DOMDocument();

        // Create an anchor element without attaching it to the document
        $anchor = $doc->createElement('a');
        $anchor->textContent = 'test';

        // Should return null when parent is null
        $result = $visitor->leaveNode($anchor);
        self::assertNull($result, 'leaveNode should return null when tag has no parent');
    }

    #[Test]
    public function leaveNodeReturnsNodeUnchangedForNonUnwrapTags(): void
    {
        $visitor = new UnwrapTagVisitor();
        $doc = new \DOMDocument();
        $node = $doc->createElement('div');

        $result = $visitor->leaveNode($node);
        self::assertSame($node, $result, 'leaveNode should return the node unchanged for non-unwrap tags');
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

            $result = $visitor->leaveNode($tag);
            self::assertNull($result, "Tag {$tagName} should be unwrapped (return null)");
        }
    }

    #[Test]
    public function leaveNodeUnwrapsAlreadySanitizedChildren(): void
    {
        // Regression test: children must be reparented only in leaveNode,
        // after other visitors have had a chance to sanitize them. Simulate
        // an already-sanitized child (e.g. a disallowed <script> tag that a
        // prior visitor has converted to escaped text) nested in an anchor.
        $visitor = new UnwrapTagVisitor();
        $doc = new \DOMDocument();
        $html = '<div><a href="#"></a></div>';
        $doc->loadHTML($html, LIBXML_HTML_NOIMPLIED | LIBXML_HTML_NODEFDTD);

        $anchor = $doc->getElementsByTagName('a')->item(0);
        self::assertNotNull($anchor);
        $sanitizedText = $doc->createTextNode('&lt;script&gt;alert(1)&lt;/script&gt;');
        $anchor->appendChild($sanitizedText);

        $result = $visitor->leaveNode($anchor);
        self::assertNull($result);
        self::assertStringContainsString(
            '&amp;lt;script&amp;gt;alert(1)&amp;lt;/script&amp;gt;',
            $doc->saveHTML($doc->getElementsByTagName('div')->item(0))
        );
    }
}
