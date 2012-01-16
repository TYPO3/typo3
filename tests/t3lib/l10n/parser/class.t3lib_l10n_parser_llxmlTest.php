<?php
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
 * Testcase for class t3lib_l10n_parser_llxml.
 *
 * @author Xavier Perseguers <xavier@typo3.org>
 * @package TYPO3
 */
class t3lib_l10n_parser_llxmlTest extends tx_phpunit_testcase {

	/**
	 * @var t3lib_l10n_parser_llxml
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

		$this->parser = t3lib_div::makeInstance('t3lib_l10n_parser_llxml');
		$this->llxmlFileNames = array(
			'locallang' => PATH_site . 'typo3_src/tests/t3lib/l10n/parser/fixtures/locallang.xml',
			'locallang_override' => PATH_site . 'typo3_src/tests/t3lib/l10n/parser/fixtures/locallang_override.xml',
			'locallangOnlyDefaultLanguage' =>  PATH_site . 'typo3_src/tests/t3lib/l10n/parser/fixtures/locallangOnlyDefaultLanguage.xml',
		);
		$GLOBALS['TYPO3_CONF_VARS']['SYS']['lang']['format']['priority'] = 'xml';
		t3lib_div::makeInstance('t3lib_l10n_Store')->initialize();

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
		t3lib_div::makeInstance('t3lib_l10n_Store')->initialize();
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
			'label3' => 'This is label #3',
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
			'label3' => 'Ceci est le libellé no. 3',
		);

		foreach ($expectedLabels as $key => $expectedLabel) {
			$this->assertEquals($expectedLabel, $LOCAL_LANG['fr'][$key][0]['target']);
		}
	}

	/**
	 * @test
	 */
	public function canParseLlxmlInFrenchAndReturnsDefaultLabelsIfNoTranslationIsFound() {
		$LOCAL_LANG = $this->parser->getParsedData($this->llxmlFileNames['locallangOnlyDefaultLanguage'], 'fr');

		$expectedLabels = array(
			'label1' => 'This is label #1',
			'label2' => 'This is label #2',
			'label3' => 'This is label #3',
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

		$LOCAL_LANG = array_merge(
			t3lib_div::readLLfile($this->llxmlFileNames['locallang'], 'default'),
			t3lib_div::readLLfile($this->llxmlFileNames['locallang'], 'fr')
		);

		$this->assertArrayHasKey('default', $LOCAL_LANG, 'default key not found in $LOCAL_LANG');
		$this->assertArrayHasKey('fr', $LOCAL_LANG, 'fr key not found in $LOCAL_LANG');

		$expectedLabels = array(
			'default' => array(
				'label1' => 'This is my 1st label',
				'label2' => 'This is my 2nd label',
				'label3' => 'This is label #3',
			),
			'fr' => array(
				'label1' => 'Ceci est mon 1er libellé',
				'label2' => 'Ceci est le libellé no. 2',
				'label3' => 'Ceci est mon 3e libellé',
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