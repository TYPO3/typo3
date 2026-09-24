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
use TYPO3\CMS\Core\DataHandling\SoftReference\TypolinkSoftReferenceParser;
use TYPO3\TestingFramework\Core\Functional\FunctionalTestCase;

final class TypolinkSoftReferenceParserRecordLinkTest extends FunctionalTestCase
{
    public static function recordLinksAreParsedDataProvider(): array
    {
        return [
            'identifier mapped to table via page TSconfig' => [
                1,
                't3://record?identifier=news&uid=99',
                'tx_news_domain_model_news:99',
            ],
            'identifier used as table without page TSconfig mapping' => [
                1,
                't3://record?identifier=tx_other&uid=5',
                'tx_other:5',
            ],
            'identifier used as table for unknown reference record' => [
                999,
                't3://record?identifier=tx_other&uid=5',
                'tx_other:5',
            ],
        ];
    }

    #[DataProvider('recordLinksAreParsedDataProvider')]
    #[Test]
    public function recordLinksAreParsed(int $referenceUid, string $link, string $expectedRecordReference): void
    {
        $this->importCSVDataSet(__DIR__ . '/Fixtures/RecordLinkPages.csv');

        $result = $this->get(TypolinkSoftReferenceParser::class)->parse('tt_content', 'header_link', $referenceUid, $link);

        self::assertTrue($result->hasMatched());
        $elements = array_values($result->getMatchedElements());
        self::assertCount(1, $elements);
        self::assertSame('db', $elements[0]['subst']['type']);
        self::assertSame($expectedRecordReference, $elements[0]['subst']['recordRef']);
    }
}
