<?php
namespace TYPO3\CMS\Core\Tests\Unit\Localization\Parser;

/***************************************************************
 *  Copyright notice
 *
 *  (c) 2011 Xavier Perseguers <xavier@typo3.org>
 *  All rights reserved
 *
 *  This script is part of the TYPO3 project. The TYPO3 project is
 *  free software; you can redistribute it and/or modify
 *  it under the terms of the GNU General Public License as published by
 *  the Free Software Foundation; either version 2 of the License, or
 *  (at your option) any later version.
 *
 *  The GNU General Public License can be found at
 *  http://www.gnu.org/copyleft/gpl.html.
 *
 *  This script is distributed in the hope that it will be useful,
 *  but WITHOUT ANY WARRANTY; without even the implied warranty of
 *  MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 *  GNU General Public License for more details.
 *
 *  This copyright notice MUST APPEAR in all copies of the script!
 ***************************************************************/

/**
 * Testcase for class \TYPO3\CMS\Core\Localization\Parser\LocallangXmlParser.
 *
 * @author Xavier Perseguers <xavier@typo3.org>
 */
class LocallangXmlParserTest extends \TYPO3\CMS\Core\Tests\UnitTestCase {

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

	/**
	 * @var array
	 */
	protected $llxmlFileNames;

	/**
	 * Prepares the environment before running a test.
	 */
	public function setUp() {
			// Backup locallangXMLOverride and localization format priority
		$this->locallangXMLOverride = $GLOBALS['TYPO3_CONF_VARS']['SYS']['locallangXMLOverride'];
		$this->l10nPriority = $GLOBALS['TYPO3_CONF_VARS']['SYS']['lang']['format']['priority'];
		$this->parser = \TYPO3\CMS\Core\Utility\GeneralUtility::makeInstance('TYPO3\\CMS\\Core\\Localization\\Parser\\LocallangXmlParser');

			// We have to take the whole relative path as otherwise this test fails on Windows systems
		$fixturePath = PATH_site . 'typo3/sysext/core/Tests/Unit/Localization/Parser/Fixtures/';
		$this->llxmlFileNames = array(
			'locallang' => $fixturePath . 'locallang.xml',
			'locallang_override' => $fixturePath . 'locallang_override.xml',
			'locallangOnlyDefaultLanguage' => $fixturePath . 'locallangOnlyDefaultLanguage.xml'
		);
		$GLOBALS['TYPO3_CONF_VARS']['SYS']['lang']['format']['priority'] = 'xml';
		\TYPO3\CMS\Core\Utility\GeneralUtility::makeInstance('TYPO3\\CMS\\Core\\Localization\\LanguageStore')->initialize();
			// Clear localization cache
		$GLOBALS['typo3CacheManager']->getCache('t3lib_l10n')->flush();
	}

	/**
	 * Cleans up the environment after running a test.
	 */
	public function tearDown() {
		unset($this->parser);
			// Restore locallangXMLOverride and localization format priority
		$GLOBALS['TYPO3_CONF_VARS']['SYS']['locallangXMLOverride'] = $this->locallangXMLOverride;
		$GLOBALS['TYPO3_CONF_VARS']['SYS']['lang']['format']['priority'] = $this->l10nPriority;
		\TYPO3\CMS\Core\Utility\GeneralUtility::makeInstance('TYPO3\\CMS\\Core\\Localization\\LanguageStore')->initialize();
	}

	/**
	 * @test
	 */
	public function canParseLlxmlInEnglish() {
		$LOCAL_LANG = $this->parser->getParsedData($this->llxmlFileNames['locallang'], 'default');
		$this->assertArrayHasKey('default', $LOCAL_LANG, 'default key not found in $LOCAL_LANG');
		$expectedLabels = array(
			'label1' => 'This is label #1',
			'label2' => 'This is label #2',
			'label3' => 'This is label #3'
		);
		foreach ($expectedLabels as $key => $expectedLabel) {
			$this->assertEquals($expectedLabel, $LOCAL_LANG['default'][$key][0]['target']);
		}
	}

	/**
	 * @test
	 */
	public function canParseLlxmlInFrench() {
		$LOCAL_LANG = $this->parser->getParsedData($this->llxmlFileNames['locallang'], 'fr');
		$this->assertArrayHasKey('fr', $LOCAL_LANG, 'fr key not found in $LOCAL_LANG');
		$expectedLabels = array(
			'label1' => 'Ceci est le libellé no. 1',
			'label2' => 'Ceci est le libellé no. 2',
			'label3' => 'Ceci est le libellé no. 3'
		);
		foreach ($expectedLabels as $key => $expectedLabel) {
			$this->assertEquals($expectedLabel, $LOCAL_LANG['fr'][$key][0]['target']);
		}
	}

	/**
	 * @test
	 */
	public function canParseLlxmlInFrenchAndReturnsNullLabelsIfNoTranslationIsFound() {
		$LOCAL_LANG = $this->parser->getParsedData($this->llxmlFileNames['locallangOnlyDefaultLanguage'], 'fr');
		$expectedLabels = array(
			'label1' => NULL,
			'label2' => NULL,
			'label3' => NULL
		);
		foreach ($expectedLabels as $key => $expectedLabel) {
			$this->assertEquals($expectedLabel, $LOCAL_LANG['fr'][$key][0]['target']);
		}
	}

	/**
	 * @test
	 */
	public function canOverrideLlxml() {
		$GLOBALS['TYPO3_CONF_VARS']['SYS']['locallangXMLOverride'][$this->llxmlFileNames['locallang']][] = $this->llxmlFileNames['locallang_override'];
		$LOCAL_LANG = array_merge(\TYPO3\CMS\Core\Utility\GeneralUtility::readLLfile($this->llxmlFileNames['locallang'], 'default'), \TYPO3\CMS\Core\Utility\GeneralUtility::readLLfile($this->llxmlFileNames['locallang'], 'fr'));
		$this->assertArrayHasKey('default', $LOCAL_LANG, 'default key not found in $LOCAL_LANG');
		$this->assertArrayHasKey('fr', $LOCAL_LANG, 'fr key not found in $LOCAL_LANG');
		$expectedLabels = array(
			'default' => array(
				'label1' => 'This is my 1st label',
				'label2' => 'This is my 2nd label',
				'label3' => 'This is label #3'
			),
			'fr' => array(
				'label1' => 'Ceci est mon 1er libellé',
				'label2' => 'Ceci est le libellé no. 2',
				'label3' => 'Ceci est mon 3e libellé'
			)
		);
		foreach ($expectedLabels as $languageKey => $expectedLanguageLabels) {
			foreach ($expectedLanguageLabels as $key => $expectedLabel) {
				$this->assertEquals($expectedLabel, $LOCAL_LANG[$languageKey][$key][0]['target']);
			}
		}
	}

}

?>