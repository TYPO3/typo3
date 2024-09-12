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

namespace TYPO3\CMS\RteCKEditor\Tests\Functional\HtmlParser;

use PHPUnit\Framework\Attributes\Test;
use TYPO3\CMS\Core\Configuration\Richtext;
use TYPO3\CMS\Core\Html\RteHtmlParser;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\TestingFramework\Core\Functional\FunctionalTestCase;

final class HtmlParserProcessingTest extends FunctionalTestCase
{
    protected bool $initializeDatabase = false;
    protected array $coreExtensionsToLoad = ['rte_ckeditor'];

    protected array $removeTagsExpectation = [
        'link',
        'meta',
        'o:p',
        'sdfield',
        'style',
        'title',
    ];

    // Just some inserted block elements to make expectation more realistic
    protected array $allowedTagExpectation = [
        'p',
        'h1',
        'h2',
        'h3',
        'div',
    ];

    #[Test]
    public function HtmlParserProcessingReceivesRemovedTagsConfiguration(): void
    {
        $richTextConfigurationProvider = GeneralUtility::makeInstance(Richtext::class);
        $richTextConfigurationConfiguration = $richTextConfigurationProvider
            ->getConfiguration('any', 'any', 0, 'any', []);

        self::assertSame($this->removeTagsExpectation, $richTextConfigurationConfiguration['processing']['HTMLparser_db']['removeTags']);
        // Legacy assertion, still valid too.
        self::assertSame($this->removeTagsExpectation, $richTextConfigurationConfiguration['proc.']['HTMLparser_db.']['removeTags.']);
    }

    #[Test]
    public function HtmlParserProcessingAppliesRemovedTagsConfiguration(): void
    {
        $richTextConfigurationProvider = GeneralUtility::makeInstance(Richtext::class);
        $richTextConfigurationConfiguration = $richTextConfigurationProvider
            ->getConfiguration('any', 'any', 0, 'any', []);
        $richTextConfigurationConfigurationProc = $richTextConfigurationConfiguration['proc.'] ?? [];

        $itemsRemove = array_map(fn(string $tagName) => $this->buildHtmlElement($tagName), $this->removeTagsExpectation);
        $itemsAllowed = array_map(fn(string $tagName) => $this->buildHtmlElement($tagName), $this->allowedTagExpectation);
        $items = array_merge($itemsRemove, $itemsAllowed);
        $html = implode("\n", $items);

        $subject = $this->get(RteHtmlParser::class);
        $result = $subject->transformTextForPersistence($html, $richTextConfigurationConfigurationProc);

        // Expectation: All forbidden tags are removed and joined without newlines inside a 'p'
        //              The allowed tags are passed through as-is with \r\n linebreaks.
        $expected = '<p>' . strip_tags(implode(' ', $itemsRemove)) . '</p>' . "\r\n"
                     . implode("\r\n", $itemsAllowed);
        self::assertEquals($expected, $result);
    }

    private function buildHtmlElement(string $tagName): string
    {
        return sprintf('<%1$s>text-%1$s-text</%1$s>', $tagName);
    }
}
