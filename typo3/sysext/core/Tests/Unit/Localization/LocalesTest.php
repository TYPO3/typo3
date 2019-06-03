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

use TYPO3\CMS\Core\Http\Uri;
use TYPO3\CMS\Core\Localization\Locales;
use TYPO3\CMS\Core\Site\Entity\SiteLanguage;
use TYPO3\TestingFramework\Core\Unit\UnitTestCase;

/**
 * Test case
 */
class LocalesTest extends UnitTestCase
{
    protected $resetSingletonInstances = true;

    /**
     * @var string
     */
    protected $originalLocale;

    protected function setUp(): void
    {
        parent::setUp();
        $this->originalLocale = setlocale(LC_COLLATE, 0);
    }

    protected function tearDown(): void
    {
        // Restore original locale
        setlocale(LC_COLLATE, $this->originalLocale);
        setlocale(LC_MONETARY, $this->originalLocale);
        setlocale(LC_TIME, $this->originalLocale);
        parent::tearDown();
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
        $detectedLanguage = (new Locales)->getPreferredClientLanguage(
            $acceptLanguageHeader
        );
        $this->assertSame($expected, $detectedLanguage);
    }

    /**
     * @test
     */
    public function setSystemLocaleFromSiteLanguageWithoutLocaleDoesNotSetLocale(): void
    {
        $language = new SiteLanguage(0, '', new Uri('/'), []);
        $result = Locales::setSystemLocaleFromSiteLanguage($language);
        static::assertFalse($result);
        $currentLocale = setlocale(LC_COLLATE, 0);
        // Check that the locale was not overridden
        static::assertEquals($this->originalLocale, $currentLocale);
    }

    /**
     * @test
     */
    public function setSystemLocaleFromSiteLanguageWithProperLocaleSetsLocale(): void
    {
        $locale = 'en_US';
        $language = new SiteLanguage(0, $locale, new Uri('/'), []);
        $result = Locales::setSystemLocaleFromSiteLanguage($language);
        static::assertTrue($result);
        $currentLocale = setlocale(LC_COLLATE, 0);
        // Check that the locale was overridden
        static::assertEquals($locale, $currentLocale);
    }

    /**
     * @test
     */
    public function setSystemLocaleFromSiteLanguageWithInvalidLocaleDoesNotSetLocale(): void
    {
        $locale = 'af_EUR';
        $language = new SiteLanguage(0, $locale, new Uri('/'), []);
        $result = Locales::setSystemLocaleFromSiteLanguage($language);
        static::assertFalse($result);
        $currentLocale = setlocale(LC_COLLATE, 0);
        // Check that the locale was not overridden
        static::assertEquals($this->originalLocale, $currentLocale);
    }
}
