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

namespace TYPO3\CMS\Core\Tests\Functional\DataHandling\SoftReference;

use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Test;
use TYPO3\CMS\Core\DataHandling\SoftReference\TypolinkTagSoftReferenceParser;
use TYPO3\TestingFramework\Core\Functional\FunctionalTestCase;

final class TypolinkTagSoftReferenceParserRecordLinkTest extends FunctionalTestCase
{
    public static function recordLinksAreParsedDataProvider(): array
    {
        return [
            'identifier mapped to table via page TSconfig' => [
                1,
                't3://record?identifier=news&amp;uid=99',
                'tx_news_domain_model_news:99',
            ],
            'identifier used as table without page TSconfig mapping' => [
                1,
                't3://record?identifier=tx_other&amp;uid=5',
                'tx_other:5',
            ],
            'identifier used as table for unknown reference record' => [
                999,
                't3://record?identifier=tx_other&amp;uid=5',
                'tx_other:5',
            ],
        ];
    }

    #[DataProvider('recordLinksAreParsedDataProvider')]
    #[Test]
    public function recordLinksAreParsed(int $referenceUid, string $href, string $expectedRecordReference): void
    {
        $this->importCSVDataSet(__DIR__ . '/Fixtures/RecordLinkPages.csv');
        $content = '<p><a href="' . $href . '">Link</a></p>';

        $result = $this->get(TypolinkTagSoftReferenceParser::class)->parse('tt_content', 'bodytext', $referenceUid, $content);

        self::assertTrue($result->hasMatched());
        $elements = array_values($result->getMatchedElements());
        self::assertCount(1, $elements);
        self::assertSame('db', $elements[0]['subst']['type']);
        self::assertSame($expectedRecordReference, $elements[0]['subst']['recordRef']);
        self::assertSame(html_entity_decode($href), html_entity_decode($elements[0]['subst']['tokenValue']));
        self::assertStringContainsString('{softref:' . $elements[0]['subst']['tokenID'] . '}', $result->getContent());
    }

    #[Test]
    public function severalRecordLinksAreParsedIndividually(): void
    {
        $this->importCSVDataSet(__DIR__ . '/Fixtures/RecordLinkPages.csv');
        $content = '<p><a href="t3://record?identifier=news&amp;uid=42">One</a>'
            . '<a href="t3://record?identifier=news&amp;uid=43">Two</a></p>';

        $result = $this->get(TypolinkTagSoftReferenceParser::class)->parse('tt_content', 'bodytext', 1, $content);

        $recordReferences = array_column(array_column($result->getMatchedElements(), 'subst'), 'recordRef');
        self::assertSame(['tx_news_domain_model_news:42', 'tx_news_domain_model_news:43'], array_values($recordReferences));
    }
}
