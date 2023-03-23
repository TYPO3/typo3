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

namespace TYPO3\CMS\Core\Tests\Unit\Localization\Parser;

use TYPO3\CMS\Core\Localization\Parser\XliffParser;
use TYPO3\TestingFramework\Core\Unit\UnitTestCase;

class XliffParserTest extends UnitTestCase
{
    /**
     * @test
     * @dataProvider canParseXliffDataProvider
     */
    public function canParseXliff(string $languageKey, array $expectedLabels, bool $requireApprovedLocalizations): void
    {
        $GLOBALS['TYPO3_CONF_VARS']['SYS']['lang']['requireApprovedLocalizations'] = $requireApprovedLocalizations;
        $LOCAL_LANG = (new XliffParser())->getParsedData(__DIR__ . '/Fixtures/locallang.xlf', $languageKey);
        self::assertArrayHasKey($languageKey, $LOCAL_LANG, sprintf('%s key not found in $LOCAL_LANG', $languageKey));
        foreach ($expectedLabels as $key => $expectedLabel) {
            self::assertEquals($expectedLabel, $LOCAL_LANG[$languageKey][$key][0]['target']);
        }
    }

    public static function canParseXliffDataProvider(): \Generator
    {
        yield 'Can handle default' => [
            'languageKey' => 'default',
            'expectedLabels' => [
                'label1' => 'This is label #1',
                'label2' => 'This is label #2',
                'label3' => 'This is label #3',
            ],
            false,
        ];
        yield 'Can handle translation with approved only' => [
            'languageKey' => 'fr',
            'expectedLabels' => [
                'label2' => 'Ceci est le libellé no. 2 [approved]',
            ],
            true,
        ];
        yield 'Can handle translation with non approved' => [
            'languageKey' => 'fr',
            'expectedLabels' => [
                'label1' => 'Ceci est le libellé no. 1',
                'label2' => 'Ceci est le libellé no. 2 [approved]',
                'label3' => 'Ceci est le libellé no. 3 [not approved]',
            ],
            false,
        ];
    }
}
