<?php

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
use TYPO3\CMS\Core\Localization\Parser\XliffParser;
use TYPO3\TestingFramework\Core\Unit\UnitTestCase;

/**
 * Test case
 */
class XliffParserTest extends UnitTestCase
{
    /**
     * @var array
     */
    protected $xliffFileNames;

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
        // We have to take the whole relative path as otherwise this test fails on Windows systems
        $fixturePath = Environment::getFrameworkBasePath() . '/core/Tests/Unit/Localization/Parser/Fixtures/';
        $this->xliffFileNames = [
            'locallang' => $fixturePath . 'locallang.xlf',
            'locallang_override' => $fixturePath . 'locallang_override.xlf',
            'locallang_override_fr' => $fixturePath . 'fr.locallang_override.xlf'
        ];
        $GLOBALS['TYPO3_CONF_VARS']['SYS']['lang']['format']['priority'] = 'xlf';

        $this->languageStoreProphecy = $this->prophesize(LanguageStore::class);
        $this->cacheManagerProphecy = $this->prophesize(CacheManager::class);
        $cacheFrontendProphecy = $this->prophesize(FrontendInterface::class);
        $this->cacheManagerProphecy->getCache('l10n')->willReturn($cacheFrontendProphecy->reveal());
        $cacheFrontendProphecy->get(Argument::cetera())->willReturn(false);
        $cacheFrontendProphecy->set(Argument::cetera())->willReturn(null);
        $cacheFrontendProphecy->flush()->willReturn(null);
    }

    /**
     * @test
     */
    public function canParseXliffInEnglish()
    {
        $LOCAL_LANG = (new XliffParser())->getParsedData($this->xliffFileNames['locallang'], 'default');
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
    public function canParseXliffInFrench()
    {
        $LOCAL_LANG = (new XliffParser())->getParsedData($this->xliffFileNames['locallang'], 'fr');
        self::assertArrayHasKey('fr', $LOCAL_LANG, 'fr key not found in $LOCAL_LANG');
        $expectedLabels = [
            'label1' => 'Ceci est le libellé no. 1',
            'label2' => 'Ceci est le libellé no. 2',
            'label3' => 'Ceci est le libellé no. 3'
        ];
        foreach ($expectedLabels as $key => $expectedLabel) {
            self::assertEquals($expectedLabel, $LOCAL_LANG['fr'][$key][0]['target']);
        }
    }

    /**
     * @test
     */
    public function canOverrideXliff()
    {
        /** @var $factory LocalizationFactory */
        $factory = new LocalizationFactory(new LanguageStore(), $this->cacheManagerProphecy->reveal());

        $GLOBALS['TYPO3_CONF_VARS']['SYS']['locallangXMLOverride'][$this->xliffFileNames['locallang']][] = $this->xliffFileNames['locallang_override'];
        $GLOBALS['TYPO3_CONF_VARS']['SYS']['locallangXMLOverride']['fr'][$this->xliffFileNames['locallang']][] = $this->xliffFileNames['locallang_override_fr'];
        $LOCAL_LANG = array_merge(
            $factory->getParsedData($this->xliffFileNames['locallang'], 'default'),
            $factory->getParsedData($this->xliffFileNames['locallang'], 'fr')
        );
        self::assertArrayHasKey('default', $LOCAL_LANG, 'default key not found in $LOCAL_LANG');
        self::assertArrayHasKey('fr', $LOCAL_LANG, 'fr key not found in $LOCAL_LANG');
        $expectedLabels = [
            'default' => [
                'label1' => 'This is my 1st label',
                'label2' => 'This is my 2nd label',
                'label3' => 'This is label #3'
            ],
            'fr' => [
                'label1' => 'Ceci est mon 1er libellé',
                'label2' => 'Ceci est le libellé no. 2',
                'label3' => 'Ceci est mon 3e libellé'
            ]
        ];
        foreach ($expectedLabels as $languageKey => $expectedLanguageLabels) {
            foreach ($expectedLanguageLabels as $key => $expectedLabel) {
                self::assertEquals($expectedLabel, $LOCAL_LANG[$languageKey][$key][0]['target']);
            }
        }
    }

    /**
     * This test will make sure method does not prefix twice the
     * language key to the localization file.
     *
     * @test
     */
    public function canOverrideXliffWithFrenchOnly()
    {
        $factory = new LocalizationFactory(new LanguageStore(), $this->cacheManagerProphecy->reveal());

        $GLOBALS['TYPO3_CONF_VARS']['SYS']['locallangXMLOverride']['fr'][$this->xliffFileNames['locallang']][] = $this->xliffFileNames['locallang_override_fr'];
        $LOCAL_LANG = $factory->getParsedData($this->xliffFileNames['locallang'], 'fr');
        self::assertArrayHasKey('fr', $LOCAL_LANG, 'fr key not found in $LOCAL_LANG');
        $expectedLabels = [
            'label1' => 'Ceci est mon 1er libellé',
            'label2' => 'Ceci est le libellé no. 2',
            'label3' => 'Ceci est mon 3e libellé'
        ];
        foreach ($expectedLabels as $key => $expectedLabel) {
            self::assertEquals($expectedLabel, $LOCAL_LANG['fr'][$key][0]['target']);
        }
    }
}
