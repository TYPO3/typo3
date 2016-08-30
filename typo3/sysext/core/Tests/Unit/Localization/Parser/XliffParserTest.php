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
 * Testcase for class \TYPO3\CMS\Core\Localization\Parser\XliffParser.
 */
class XliffParserTest extends \TYPO3\CMS\Core\Tests\UnitTestCase
{
    /**
     * @var \TYPO3\CMS\Core\Localization\Parser\XliffParser
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

    /**
     * @var array
     */
    protected $xliffFileNames;

    /**
     * Prepares the environment before running a test.
     */
    protected function setUp()
    {
        // Backup locallangXMLOverride and localization format priority
        $this->locallangXMLOverride = $GLOBALS['TYPO3_CONF_VARS']['SYS']['locallangXMLOverride'];
        $this->l10nPriority = $GLOBALS['TYPO3_CONF_VARS']['SYS']['lang']['format']['priority'];
        $this->parser = \TYPO3\CMS\Core\Utility\GeneralUtility::makeInstance(\TYPO3\CMS\Core\Localization\Parser\XliffParser::class);

            // We have to take the whole relative path as otherwise this test fails on Windows systems
        $fixturePath = PATH_site . 'typo3/sysext/core/Tests/Unit/Localization/Parser/Fixtures/';
        $this->xliffFileNames = [
            'locallang' => $fixturePath . 'locallang.xlf',
            'locallang_override' => $fixturePath . 'locallang_override.xlf',
            'locallang_override_fr' => $fixturePath . 'fr.locallang_override.xlf'
        ];
        $GLOBALS['TYPO3_CONF_VARS']['SYS']['lang']['format']['priority'] = 'xlf';
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
    public function canParseXliffInEnglish()
    {
        $LOCAL_LANG = $this->parser->getParsedData($this->xliffFileNames['locallang'], 'default');
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
    public function canParseXliffInFrench()
    {
        $LOCAL_LANG = $this->parser->getParsedData($this->xliffFileNames['locallang'], 'fr');
        $this->assertArrayHasKey('fr', $LOCAL_LANG, 'fr key not found in $LOCAL_LANG');
        $expectedLabels = [
            'label1' => 'Ceci est le libellé no. 1',
            'label2' => 'Ceci est le libellé no. 2',
            'label3' => 'Ceci est le libellé no. 3'
        ];
        foreach ($expectedLabels as $key => $expectedLabel) {
            $this->assertEquals($expectedLabel, $LOCAL_LANG['fr'][$key][0]['target']);
        }
    }

    /**
     * @test
     */
    public function canOverrideXliff()
    {
        /** @var $factory LocalizationFactory */
        $factory = new LocalizationFactory;

        $GLOBALS['TYPO3_CONF_VARS']['SYS']['locallangXMLOverride'][$this->xliffFileNames['locallang']][] = $this->xliffFileNames['locallang_override'];
        $GLOBALS['TYPO3_CONF_VARS']['SYS']['locallangXMLOverride']['fr'][$this->xliffFileNames['locallang']][] = $this->xliffFileNames['locallang_override_fr'];
        $LOCAL_LANG = array_merge(
            $factory->getParsedData($this->xliffFileNames['locallang'], 'default'),
            $factory->getParsedData($this->xliffFileNames['locallang'], 'fr')
        );
        $this->assertArrayHasKey('default', $LOCAL_LANG, 'default key not found in $LOCAL_LANG');
        $this->assertArrayHasKey('fr', $LOCAL_LANG, 'fr key not found in $LOCAL_LANG');
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
                $this->assertEquals($expectedLabel, $LOCAL_LANG[$languageKey][$key][0]['target']);
            }
        }
    }

    /**
     * This test will make sure method \TYPO3\CMS\Core\Utility\GeneralUtility::llXmlAutoFileName() will not prefix twice the
     * language key to the localization file.
     *
     * @test
     */
    public function canOverrideXliffWithFrenchOnly()
    {
        /** @var $factory LocalizationFactory */
        $factory = new LocalizationFactory;

        $GLOBALS['TYPO3_CONF_VARS']['SYS']['locallangXMLOverride']['fr'][$this->xliffFileNames['locallang']][] = $this->xliffFileNames['locallang_override_fr'];
        $LOCAL_LANG = $factory->getParsedData($this->xliffFileNames['locallang'], 'fr');
        $this->assertArrayHasKey('fr', $LOCAL_LANG, 'fr key not found in $LOCAL_LANG');
        $expectedLabels = [
            'label1' => 'Ceci est mon 1er libellé',
            'label2' => 'Ceci est le libellé no. 2',
            'label3' => 'Ceci est mon 3e libellé'
        ];
        foreach ($expectedLabels as $key => $expectedLabel) {
            $this->assertEquals($expectedLabel, $LOCAL_LANG['fr'][$key][0]['target']);
        }
    }
}
