<?php
declare(strict_types = 1);

namespace TYPO3\CMS\Core\Tests\Unit\Site\Entity;

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

use TYPO3\CMS\Core\Http\Uri;
use TYPO3\CMS\Core\Site\Entity\SiteLanguage;
use TYPO3\TestingFramework\Core\Unit\UnitTestCase;

class SiteLanguageTest extends UnitTestCase
{
    public function languageFallbackIdConversionDataProvider()
    {
        return [
            'no fallback set' => [
                null,
                []
            ],
            'fallback given as empty string returns no fallback' => [
                '',
                []
            ],
            'fallback to default language as string returns proper fallback' => [
                '0',
                [0]
            ],
            'fallback to multiple languages as string returns proper fallback' => [
                '3,0',
                [3, 0]
            ],
            'fallback to default language as array returns proper fallback' => [
                ['0'],
                [0]
            ],
            'fallback to multiple languages as array returns proper fallback' => [
                ['3', '0'],
                [3, 0]
            ],
            'fallback to multiple languages as array with integers returns proper fallback' => [
                [3, 0],
                [3, 0]
            ],

        ];
    }

    /**
     * @dataProvider languageFallbackIdConversionDataProvider
     * @test
     * @param string|array|null $input
     * @param array $expected
     */
    public function languageFallbackIdConversion($input, array $expected)
    {
        $configuration = [
            'fallbacks' => $input
        ];
        $subject = new SiteLanguage(1, 'fr', new Uri('/'), $configuration);
        $this->assertSame($expected, $subject->getFallbackLanguageIds());
    }

    /**
     * @test
     */
    public function toArrayReturnsProperOverlaidData()
    {
        $configuration = [
            'navigationTitle' => 'NavTitle',
            'customValue' => 'a custom value',
            'fallbacks' => '1,2',
        ];
        $subject = new SiteLanguage(1, 'de', new Uri('/'), $configuration);
        $expected = [
            'navigationTitle' => 'NavTitle',
            'customValue' => 'a custom value',
            'fallbacks' => '1,2',
            'languageId' => 1,
            'locale' => 'de',
            'base' => '/',
            'title' => 'Default',
            'twoLetterIsoCode' => 'en',
            'hreflang' => 'en-US',
            'direction' => '',
            'typo3Language' => 'default',
            'flagIdentifier' => '',
            'fallbackType' => 'strict',
            'enabled' => true,
            'fallbackLanguageIds' => [
                1,
                2
            ],
        ];
        $this->assertSame($expected, $subject->toArray());
    }
}
