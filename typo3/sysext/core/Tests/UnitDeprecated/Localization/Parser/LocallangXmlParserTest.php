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

use Prophecy\Argument;
use TYPO3\CMS\Core\Cache\CacheManager;
use TYPO3\CMS\Core\Cache\Frontend\FrontendInterface;
use TYPO3\CMS\Core\Core\Environment;
use TYPO3\CMS\Core\Localization\LanguageStore;
use TYPO3\CMS\Core\Localization\LocalizationFactory;
use TYPO3\CMS\Core\Localization\Parser\LocallangXmlParser;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\TestingFramework\Core\Unit\UnitTestCase;

/**
 * Test case
 */
class LocallangXmlParserTest extends UnitTestCase
{
    /**
     * @var ObjectProphecy|CacheManager
     */
    protected $cacheManagerProphecy;

    /**
     * Prepares the environment before running a test.
     */
    protected function setUp(): void
    {
        parent::setUp();
        $GLOBALS['TYPO3_CONF_VARS']['SYS']['lang']['format']['priority'] = 'xml';
        $GLOBALS['TYPO3_CONF_VARS']['SYS']['lang']['parser']['xml'] = \TYPO3\CMS\Core\Localization\Parser\LocallangXmlParser::class;

        $this->cacheManagerProphecy = $this->prophesize(CacheManager::class);
        $cacheFrontendProphecy = $this->prophesize(FrontendInterface::class);
        $this->cacheManagerProphecy->getCache('l10n')->willReturn($cacheFrontendProphecy->reveal());
        $cacheFrontendProphecy->get(Argument::cetera())->willReturn(false);
        $cacheFrontendProphecy->set(Argument::cetera())->willReturn(null);

        GeneralUtility::makeInstance(LanguageStore::class)->initialize();
    }
    /**
     * Cleans up the environment after running a test.
     */
    protected function tearDown(): void
    {
        GeneralUtility::purgeInstances();
        parent::tearDown();
    }

    protected static function getFixtureFilePath($filename)
    {
        // We have to take the whole relative path as otherwise this test fails on Windows systems
        return Environment::getFrameworkBasePath() . '/core/Tests/UnitDeprecated/Localization/Parser/Fixtures/' . $filename;
    }

    /**
     * @test
     */
    public function canParseLlxmlInEnglish()
    {
        $LOCAL_LANG = (new LocallangXmlParser())->getParsedData(self::getFixtureFilePath('locallang.xml'), 'default');
        self::assertArrayHasKey('default', $LOCAL_LANG, 'default key not found in $LOCAL_LANG');
        $expectedLabels = [
            'label1' => 'This is label #1',
            'label2' => 'This is label #2',
            'label3' => 'This is label #3'
        ];
        foreach ($expectedLabels as $key => $expectedLabel) {
            self::assertEquals($expectedLabel, $LOCAL_LANG['default'][$key][0]['target']);
        }
    }

    /**
     * @test
     */
    public function canParseLlxmlInMd5Code()
    {
        $LOCAL_LANG = (new LocallangXmlParser())->getParsedData(self::getFixtureFilePath('locallang.xml'), 'md5');
        self::assertArrayHasKey('md5', $LOCAL_LANG, 'md5 key not found in $LOCAL_LANG');
        $expectedLabels = [
            'label1' => '409a6edbc70dbeeccbfe5f1e569d6717',
            'label2' => 'b5dc71ae9f52ecb9e7704c50562e39b0',
            'label3' => '51eac55fa5ca15789ce9bbb0cf927296'
        ];
        foreach ($expectedLabels as $key => $expectedLabel) {
            self::assertEquals($expectedLabel, $LOCAL_LANG['md5'][$key][0]['target']);
        }
    }

    /**
     * @test
     */
    public function canParseLlxmlInFrenchAndReturnsNullLabelsIfNoTranslationIsFound()
    {
        $localLang = (new LocallangXmlParser())->getParsedData(
            self::getFixtureFilePath('locallangOnlyDefaultLanguage.xml'),
            'fr'
        );
        // This test case is odd: The system under test does NOT
        // return 'target' at all if there is no such translation.
        // @todo: Either change / fix subject, or adapt test and test name!
        self::assertNull($localLang['fr']['label1'][0]['target'] ?? null);
        self::assertNull($localLang['fr']['label2'][0]['target'] ?? null);
        self::assertNull($localLang['fr']['label3'][0]['target'] ?? null);
    }

    /**
     * @test
     */
    public function canOverrideLlxml()
    {
        /** @var $factory LocalizationFactory */
        $factory = new LocalizationFactory(new LanguageStore(), $this->cacheManagerProphecy->reveal());

        $GLOBALS['TYPO3_CONF_VARS']['SYS']['locallangXMLOverride'][self::getFixtureFilePath('locallang.xml')][] = self::getFixtureFilePath('locallang_override.xml');
        $LOCAL_LANG = array_merge(
            $factory->getParsedData(self::getFixtureFilePath('locallang.xml'), 'default'),
            $factory->getParsedData(self::getFixtureFilePath('locallang.xml'), 'md5')
        );
        self::assertArrayHasKey('default', $LOCAL_LANG, 'default key not found in $LOCAL_LANG');
        self::assertArrayHasKey('md5', $LOCAL_LANG, 'md5 key not found in $LOCAL_LANG');
        $expectedLabels = [
            'default' => [
                'label1' => 'This is my 1st label',
                'label2' => 'This is my 2nd label',
                'label3' => 'This is label #3'
            ],
            'md5' => [
                'label1' => '409a6edbc70dbeeccbfe5f1e569d6717',
                'label2' => 'b5dc71ae9f52ecb9e7704c50562e39b0',
                'label3' => '51eac55fa5ca15789ce9bbb0cf927296'
            ]
        ];
        foreach ($expectedLabels as $languageKey => $expectedLanguageLabels) {
            foreach ($expectedLanguageLabels as $key => $expectedLabel) {
                self::assertEquals($expectedLabel, $LOCAL_LANG[$languageKey][$key][0]['target']);
            }
        }
    }

    public function numericKeysDataProvider()
    {
        return [
            'Numeric key 1' => [
                1,
                'This is label #1 [FR]'
            ],
            'Numeric key 2' => [
                2,
                'This is label #2 [FR]'
            ],
            'Numeric key 3' => [
                3,
                'This is label #3 [FR]'
            ],
            'Numeric key 5' => [
                5,
                'This is label #5 [FR]'
            ],
            'Numeric key 10' => [
                10,
                'This is label #10 [FR]'
            ],
        ];
    }

    /**
     * @test
     * @dataProvider numericKeysDataProvider
     */
    public function canTranslateNumericKeys($key, $expectedResult)
    {
        /** @var $factory LocalizationFactory */
        $factory = new LocalizationFactory(new LanguageStore(), $this->cacheManagerProphecy->reveal());

        $LOCAL_LANG = $factory->getParsedData(self::getFixtureFilePath('locallangNumericKeys.xml'), 'fr');

        self::assertEquals($expectedResult, $LOCAL_LANG['fr'][$key][0]['target']);
    }
}
