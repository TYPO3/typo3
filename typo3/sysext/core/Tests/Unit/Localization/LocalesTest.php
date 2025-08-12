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

namespace TYPO3\CMS\Core\Tests\Unit\Localization;

use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Test;
use TYPO3\CMS\Core\Localization\Locales;
use TYPO3\CMS\Core\Site\Entity\Site;
use TYPO3\TestingFramework\Core\Unit\UnitTestCase;

final class LocalesTest extends UnitTestCase
{
    protected bool $resetSingletonInstances = true;

    protected string|bool $originalLocale = false;

    protected function setUp(): void
    {
        parent::setUp();
        $this->originalLocale = setlocale(LC_COLLATE, '0');
    }

    protected function tearDown(): void
    {
        // Restore original locale
        setlocale(LC_COLLATE, $this->originalLocale);
        setlocale(LC_MONETARY, $this->originalLocale);
        setlocale(LC_TIME, $this->originalLocale);
        parent::tearDown();
    }

    #[Test]
    public function isValidLanguageKeyAlsoDetectsRegionSpecificKeys(): void
    {
        $GLOBALS['TYPO3_CONF_VARS']['SYS']['localization']['locales']['user'] = [
            'fr-CG' => 'French (Congo)',
        ];
        $locales = new Locales();
        // Fixed defined language keys
        self::assertTrue($locales->isValidLanguageKey('fr_CA'));
        self::assertTrue($locales->isValidLanguageKey('fr-CA'));
        // User-defined language keys
        self::assertTrue($locales->isValidLanguageKey('fr-CG'));
        self::assertTrue($locales->isValidLanguageKey('de'));
        // Transient language key
        self::assertTrue($locales->isValidLanguageKey('de-AT'));
        // Deal with "en" and "en_US"
        self::assertTrue($locales->isValidLanguageKey('en-US'));
        self::assertTrue($locales->isValidLanguageKey('en'));
        // valid language key "en" is automatically applied with "default"
        self::assertTrue($locales->isValidLanguageKey('default'));
    }

    #[Test]
    public function getLocaleDependenciesResolvesAutomaticAndDefinedDependencies(): void
    {
        $GLOBALS['TYPO3_CONF_VARS']['SYS']['localization']['locales']['user'] = [
            'fr-CG' => 'French (Congo)',
        ];
        $GLOBALS['TYPO3_CONF_VARS']['SYS']['localization']['locales']['dependencies'] = [
            'de-CH' => ['fr', 'es'],
        ];
        $locales = new Locales();
        // Automatic dependency
        $dependencies = $locales->getLocaleDependencies('de_AT');
        self::assertEquals(['de'], $dependencies);
        // Explicitly defined language with 5 keys
        $dependencies = $locales->getLocaleDependencies('pt_BR');
        self::assertEquals(['pt'], $dependencies);
        // Explicitly defined 2-letter custom dependency
        $dependencies = $locales->getLocaleDependencies('lb');
        self::assertEquals(['de'], $dependencies);
        // Dependency with custom dependencies
        $dependencies = $locales->getLocaleDependencies('de-CH');
        self::assertEquals(['fr', 'es'], $dependencies);
        // Custom registered language
        $dependencies = $locales->getLocaleDependencies('fr_CG');
        self::assertEquals(['fr'], $dependencies);
    }

    public static function browserLanguageDetectionWorksDataProvider(): array
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
            'french canadian' => [
                'fr-CA;q=0.8,en;q=0.6;de-DE,de;q=0.4',
                'fr_CA',
            ],
            'chinese simplified' => [
                'zh-CN,en-US;q=0.5,en;q=0.3',
                'zh_CN',
            ],
            'chinese simplified han' => [
                'zh-Hans-CN,zh-Hans;q=0.8,en-US;q=0.5,en;q=0.3',
                'zh_Hans_CN',
            ],
        ];
    }

    #[DataProvider('browserLanguageDetectionWorksDataProvider')]
    #[Test]
    public function browserLanguageDetectionWorks(string $acceptLanguageHeader, string $expected): void
    {
        $detectedLanguage = (new Locales())->getPreferredClientLanguage(
            $acceptLanguageHeader
        );
        self::assertSame($expected, $detectedLanguage);
    }

    #[Test]
    public function setSystemLocaleFromSiteLanguageWithoutLocaleDoesNotSetLocale(): void
    {
        $site = $this->createSiteWithDefaultLanguage('');
        $result = Locales::setSystemLocaleFromSiteLanguage($site->getLanguageById(0));
        self::assertFalse($result);
        $currentLocale = setlocale(LC_COLLATE, '0');
        // Check that the locale was not overridden
        self::assertEquals($this->originalLocale, $currentLocale);
    }

    #[Test]
    public function setSystemLocaleFromSiteLanguageWithProperLocaleSetsLocale(): void
    {
        $locale = 'en_US';
        $site = $this->createSiteWithDefaultLanguage($locale);
        $result = Locales::setSystemLocaleFromSiteLanguage($site->getLanguageById(0));
        self::assertTrue($result);
        $currentLocale = setlocale(LC_COLLATE, '0');
        // Check that the locale was overridden
        self::assertEquals($locale, $currentLocale);
    }

    private function createSiteWithDefaultLanguage(string $locale): Site
    {
        return new Site('test', 1, [
            'identifier' => 'test',
            'rootPageId' => 1,
            'base' => '/',
            'languages' => [
                [
                    'languageId' => 0,
                    'locale' => $locale,
                    'base' => '/',
                ],
            ],
        ]);
    }
}
