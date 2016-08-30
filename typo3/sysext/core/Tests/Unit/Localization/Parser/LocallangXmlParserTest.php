<?php
namespace TYPO3\CMS\Core\Tests\Unit\Localization\Parser;

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
use TYPO3\CMS\Core\Localization\LocalizationFactory;

/**
 * Testcase for class \TYPO3\CMS\Core\Localization\Parser\LocallangXmlParser.
 */
class LocallangXmlParserTest extends \TYPO3\CMS\Core\Tests\UnitTestCase
{
    /**
     * @var \TYPO3\CMS\Core\Localization\Parser\LocallangXmlParser
     */
    protected $parser;

    /**
     * @var array
     */
    protected $locallangXMLOverride;

    /**
     * @var string
     */
    protected $l10nPriority;

    protected static function getFixtureFilePath($filename)
    {
        // We have to take the whole relative path as otherwise this test fails on Windows systems
        return PATH_site . 'typo3/sysext/core/Tests/Unit/Localization/Parser/Fixtures/' . $filename;
    }

    /**
     * Prepares the environment before running a test.
     */
    protected function setUp()
    {
        // Backup locallangXMLOverride and localization format priority
        $this->locallangXMLOverride = $GLOBALS['TYPO3_CONF_VARS']['SYS']['locallangXMLOverride'];
        $this->l10nPriority = $GLOBALS['TYPO3_CONF_VARS']['SYS']['lang']['format']['priority'];
        $this->parser = \TYPO3\CMS\Core\Utility\GeneralUtility::makeInstance(\TYPO3\CMS\Core\Localization\Parser\LocallangXmlParser::class);

        $GLOBALS['TYPO3_CONF_VARS']['SYS']['lang']['format']['priority'] = 'xml';
        \TYPO3\CMS\Core\Utility\GeneralUtility::makeInstance(\TYPO3\CMS\Core\Localization\LanguageStore::class)->initialize();
            // Clear localization cache
        \TYPO3\CMS\Core\Utility\GeneralUtility::makeInstance(\TYPO3\CMS\Core\Cache\CacheManager::class)->getCache('l10n')->flush();
    }

    /**
     * Cleans up the environment after running a test.
     */
    protected function tearDown()
    {
        // Restore locallangXMLOverride and localization format priority
        $GLOBALS['TYPO3_CONF_VARS']['SYS']['locallangXMLOverride'] = $this->locallangXMLOverride;
        $GLOBALS['TYPO3_CONF_VARS']['SYS']['lang']['format']['priority'] = $this->l10nPriority;
        \TYPO3\CMS\Core\Utility\GeneralUtility::makeInstance(\TYPO3\CMS\Core\Localization\LanguageStore::class)->initialize();
        parent::tearDown();
    }

    /**
     * @test
     */
    public function canParseLlxmlInEnglish()
    {
        $LOCAL_LANG = $this->parser->getParsedData(self::getFixtureFilePath('locallang.xml'), 'default');
        $this->assertArrayHasKey('default', $LOCAL_LANG, 'default key not found in $LOCAL_LANG');
        $expectedLabels = [
            'label1' => 'This is label #1',
            'label2' => 'This is label #2',
            'label3' => 'This is label #3'
        ];
        foreach ($expectedLabels as $key => $expectedLabel) {
            $this->assertEquals($expectedLabel, $LOCAL_LANG['default'][$key][0]['target']);
        }
    }

    /**
     * @test
     */
    public function canParseLlxmlInMd5Code()
    {
        $LOCAL_LANG = $this->parser->getParsedData(self::getFixtureFilePath('locallang.xml'), 'md5');
        $this->assertArrayHasKey('md5', $LOCAL_LANG, 'md5 key not found in $LOCAL_LANG');
        $expectedLabels = [
            'label1' => '409a6edbc70dbeeccbfe5f1e569d6717',
            'label2' => 'b5dc71ae9f52ecb9e7704c50562e39b0',
            'label3' => '51eac55fa5ca15789ce9bbb0cf927296'
        ];
        foreach ($expectedLabels as $key => $expectedLabel) {
            $this->assertEquals($expectedLabel, $LOCAL_LANG['md5'][$key][0]['target']);
        }
    }

    /**
     * @test
     */
    public function canParseLlxmlInFrenchAndReturnsNullLabelsIfNoTranslationIsFound()
    {
        $LOCAL_LANG = $this->parser->getParsedData(self::getFixtureFilePath('locallangOnlyDefaultLanguage.xml'), 'fr');
        $expectedLabels = [
            'label1' => null,
            'label2' => null,
            'label3' => null
        ];
        foreach ($expectedLabels as $key => $expectedLabel) {
            $this->assertEquals($expectedLabel, $LOCAL_LANG['fr'][$key][0]['target']);
        }
    }

    /**
     * @test
     */
    public function canOverrideLlxml()
    {
        /** @var $factory LocalizationFactory */
        $factory = new LocalizationFactory;

        $GLOBALS['TYPO3_CONF_VARS']['SYS']['locallangXMLOverride'][self::getFixtureFilePath('locallang.xml')][] = self::getFixtureFilePath('locallang_override.xml');
        $LOCAL_LANG = array_merge(
            $factory->getParsedData(self::getFixtureFilePath('locallang.xml'), 'default'),
            $factory->getParsedData(self::getFixtureFilePath('locallang.xml'), 'md5')
        );
        $this->assertArrayHasKey('default', $LOCAL_LANG, 'default key not found in $LOCAL_LANG');
        $this->assertArrayHasKey('md5', $LOCAL_LANG, 'md5 key not found in $LOCAL_LANG');
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
                $this->assertEquals($expectedLabel, $LOCAL_LANG[$languageKey][$key][0]['target']);
            }
        }
    }

    public function numericKeysDataProvider()
    {
        /** @var $factory LocalizationFactory */
        $factory = new LocalizationFactory;

        $LOCAL_LANG = $factory->getParsedData(self::getFixtureFilePath('locallangNumericKeys.xml'), 'default');
        $translations = [];

        foreach ($LOCAL_LANG['default'] as $key => $labelData) {
            $translations['Numerical key ' . $key] = [$key, $labelData[0]['source'] . ' [FR]'];
        }

        return $translations;
    }

    /**
     * @test
     * @dataProvider numericKeysDataProvider
     */
    public function canTranslateNumericKeys($key, $expectedResult)
    {
        /** @var $factory LocalizationFactory */
        $factory = new LocalizationFactory;

        $LOCAL_LANG = $factory->getParsedData(self::getFixtureFilePath('locallangNumericKeys.xml'), 'fr');

        $this->assertEquals($expectedResult, $LOCAL_LANG['fr'][$key][0]['target']);
    }
}
