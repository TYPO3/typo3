<?php
namespace TYPO3\CMS\Core\Tests\Unit\Localization;

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

use TYPO3\CMS\Core\Localization\Locales;
use TYPO3\CMS\Core\Utility\GeneralUtility;

/**
 * Test case
 */
class LocalesTest extends \TYPO3\TestingFramework\Core\Unit\UnitTestCase
{
    /**
     * @var Locales
     */
    protected $subject;

    protected function setUp()
    {
        $this->subject = GeneralUtility::makeInstance(Locales::class);
        Locales::initialize();
    }

    /**
     * @return array
     */
    public function browserLanguageDetectionWorksDataProvider(): array
    {
        return [
            'german' => [
                'de-DE,de;q=0.8,en-US;q=0.6,en;q=0.4',
                'de',
            ],
            'english as default' => [
                'en-US;q=0.8,en;q=0.6;de-DE,de;q=0.4',
                'default',
            ],
            'chinese simplified' => [
                'zh-CN,en-US;q=0.5,en;q=0.3',
                'ch'
            ],
            'chinese simplified han' => [
                'zh-Hans-CN,zh-Hans;q=0.8,en-US;q=0.5,en;q=0.3',
                'ch'
            ],
        ];
    }

    /**
     * @param string $acceptLanguageHeader
     * @param string $expected
     *
     * @test
     * @dataProvider browserLanguageDetectionWorksDataProvider
     */
    public function browserLanguageDetectionWorks(string $acceptLanguageHeader, string $expected)
    {
        $detectedLanguage = $this->subject->getPreferredClientLanguage(
            $acceptLanguageHeader
        );
        $this->assertSame($expected, $detectedLanguage);
    }
}
