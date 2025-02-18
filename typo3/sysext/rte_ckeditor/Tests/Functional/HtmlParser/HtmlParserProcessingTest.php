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
    protected bool $initializeDatabase = true;
    protected array $coreExtensionsToLoad = ['rte_ckeditor'];

    protected array $removeTagsExpectation = [
        'link',
        'meta',
        'o:p',
        'sdfield',
        'style',
        'title',
        'font',
        'center',
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
        // TypoScript/TSconfig assertion, still valid too.
        self::assertSame($this->removeTagsExpectation, $richTextConfigurationConfiguration['proc.']['HTMLparser_db.']['removeTags.']);
    }

    #[Test]
    public function HtmlParserProcessingReceivesTypoScriptStringConfiguration(): void
    {
        $this->importCSVDataSet(__DIR__ . '/../Fixtures/rte-pages-string.csv');

        $extraConfig = [
            'richtextConfiguration' => 'testing',
        ];
        // NOTICE: This is just plain TSconfig. No YAML preset exist. For TSconfig,
        // the "preset" name has no relevance.
        $richTextConfigurationProvider = GeneralUtility::makeInstance(Richtext::class);
        $richTextConfigurationConfiguration = $richTextConfigurationProvider
            ->getConfiguration('pages', 'TSconfig', 1, 'any', $extraConfig);

        self::assertSame('span,style,meta,code', $richTextConfigurationConfiguration['proc.']['HTMLparser_db.']['removeTags']);
    }

    #[Test]
    public function HtmlParserProcessingReceivesTypoScriptArrayConfiguration(): void
    {
        $this->importCSVDataSet(__DIR__ . '/../Fixtures/rte-pages-array.csv');

        $extraConfig = [
            'richtextConfiguration' => 'testing',
        ];
        // NOTICE: This is just plain TSconfig. No YAML preset exist. For TSconfig,
        // the "preset" name has no relevance.
        $richTextConfigurationProvider = GeneralUtility::makeInstance(Richtext::class);
        $richTextConfigurationConfiguration = $richTextConfigurationProvider
            ->getConfiguration('pages', 'TSconfig', 1, 'any', $extraConfig);

        self::assertSame(['empty', 'span', 'style', 'meta', 'code'], $richTextConfigurationConfiguration['proc.']['HTMLparser_db.']['removeTags.']);
    }

    #[Test]
    public function HtmlParserProcessingAppliesTypoScriptStringConfiguration(): void
    {
        $this->importCSVDataSet(__DIR__ . '/../Fixtures/rte-pages-string.csv');

        $extraConfig = [
            'richtextConfiguration' => 'testing',
        ];
        // NOTICE: This is just plain TSconfig. No YAML preset exist. For TSconfig,
        // the "preset" name has no relevance.
        $richTextConfigurationProvider = GeneralUtility::makeInstance(Richtext::class);
        $richTextConfigurationConfiguration = $richTextConfigurationProvider
            ->getConfiguration('pages', 'TSconfig', 1, 'any', $extraConfig);
        $richTextConfigurationConfigurationProc = $richTextConfigurationConfiguration['proc.'] ?? [];

        // Note: RteHtmlParser->TS_transform_db() is another layer of scrubbing
        //       which already removes any inline-level tags if they occur at
        //       block-level range. Thus, removeTags has no influence on that.
        //       We're testing removeTags functionality for removing inline-level tags.
        $html = "<p>This stays.</p>\n<blockquote>This stays</blockquote>\n<div><b>This stays</b>.</div><div><code>This vanishes</code></div>\n<div><span>This vanishes</span></div>\n<div><style type='text/css'>This vanishes</style></div>\n<div><meta>This vanishes</meta></div>\n";
        $htmlExpected = "<p>This stays.</p>\r\n<blockquote>This stays</blockquote>\r\n<div><b>This stays</b>.</div>\r\n<div>This vanishes</div>\r\n<div>This vanishes</div>\r\n<div>This vanishes</div>\r\n<div>This vanishes</div>";

        $subject = $this->get(RteHtmlParser::class);
        $result = $subject->transformTextForPersistence($html, $richTextConfigurationConfigurationProc);

        self::assertSame($htmlExpected, $result);
    }

    #[Test]
    public function HtmlParserProcessingAppliesTypoScriptArrayConfiguration(): void
    {
        $this->importCSVDataSet(__DIR__ . '/../Fixtures/rte-pages-array.csv');

        $extraConfig = [
            'richtextConfiguration' => 'testing',
        ];
        // NOTICE: This is just plain TSconfig. No YAML preset exist. For TSconfig,
        // the "preset" name has no relevance.
        $richTextConfigurationProvider = GeneralUtility::makeInstance(Richtext::class);
        $richTextConfigurationConfiguration = $richTextConfigurationProvider
            ->getConfiguration('pages', 'TSconfig', 1, 'any', $extraConfig);
        $richTextConfigurationConfigurationProc = $richTextConfigurationConfiguration['proc.'] ?? [];

        // Note: RteHtmlParser->TS_transform_db() is another layer of scrubbing
        //       which already removes any inline-level tags if they occur at
        //       block-level range. Thus, removeTags has no influence on that.
        //       We're testing removeTags functionality for removing inline-level tags.
        $html = "<p>This stays.</p>\n<blockquote>This stays</blockquote>\n<div><i><invalid>This partially stays</invalid></i>.</div><div><code>This vanishes</code></div>\n<div><span>This vanishes</span></div>\n<div><style type='text/css'>This vanishes</style></div>\n<div><meta>This vanishes</meta></div>\n";
        $htmlExpected = "<p>This stays.</p>\r\n<blockquote>This stays</blockquote>\r\n<div><i>This partially stays</i>.</div>\r\n<div>This vanishes</div>\r\n<div>This vanishes</div>\r\n<div>This vanishes</div>\r\n<div>This vanishes</div>";

        $subject = $this->get(RteHtmlParser::class);
        $result = $subject->transformTextForPersistence($html, $richTextConfigurationConfigurationProc);

        self::assertSame($htmlExpected, $result);
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
