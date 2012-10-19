<?php
namespace TYPO3\CMS\Extbase\Tests\Unit\Utility;

/***************************************************************
 *  Copyright notice
 *
 *  (c) 2011 Extbase Team
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
 * Testcase for class Tx_Extbase_Utility_Localization
 *
 * @package Extbase
 * @subpackage Utility
 */
class LocalizationUtilityTest extends \TYPO3\CMS\Extbase\Tests\Unit\BaseTestCase {

	/**
	 * @var \TYPO3\CMS\Extbase\Utility\LocalizationUtility
	 */
	protected $localization;

	/**
	 * LOCAL_LANG array fixture
	 *
	 * @var array
	 */
	protected $LOCAL_LANG = array(
		'extensionKey' => array(
			'default' => array(
				'key1' => array(
					array(
						'source' => 'English label for key1',
						'target' => 'English label for key1',
					)
				),
				'key2' => array(
					array(
						'source' => 'English label for key2',
						'target' => 'English label for key2',
					)
				),
				'key3' => array(
					array(
						'source' => 'English label for key3',
						'target' => 'English label for key3',
					)
				),
				'key4' => array(
					array(
						'source' => 'English label for key4',
						'target' => 'English label for key4',
					)
				),
				'keyWithPlaceholder' => array(
					array(
						'source' => 'English label with number %d',
						'target' => 'English label with number %d',
					)
				),
			),
			'dk' => array(
				'key1' => array(
					array(
						'source' => 'English label for key1',
						'target' => 'Dansk label for key1',
					)
				),
				'key2' => array( //not translated in dk => no target (llxml)
					array(
						'source' => 'English label for key2',
					)
				),
				'key3' => array(
					array(
						'source' => 'English label for key3',
					)
				),
				'key4' => array( //not translated in dk => empty target (xlif)
					array(
						'source' => 'English label for key4',
						'target' => '',
					)
				),
				'key5' => array( //not translated in dk => empty target (xlif)
					array(
						'source' => 'English label for key5',
						'target' => '',
					)
				),
				'keyWithPlaceholder' => array(
					array(
						'source' => 'English label with number %d',
					)
				),
			),
			'dk_alt' => array( //fallback language for labels which are not translated in dk
				'key1' => array(
					array(
						'source' => 'English label for key1',
					)
				),
				'key2' => array(
					array(
						'source' => 'English label for key2',
						'target' => 'Dansk alternative label for key2',
					)
				),
				'key3' => array(
					array(
						'source' => 'English label for key3',
					)
				),
				'key4' => array( //not translated in dk_alt => empty target (xlif)
					array(
						'source' => 'English label for key4',
						'target' => '',
					)
				),
				'key5' => array(
					array(
						'source' => 'English label for key5',
						'target' => 'Dansk alternative label for key5',
					)
				),
				'keyWithPlaceholder' => array(
					array(
						'source' => 'English label with number %d',
					)
				),
			),

		),
	);

	public function setUp() {
		$this->localization = $this->getAccessibleMock('TYPO3\\CMS\\Extbase\\Utility\\LocalizationUtility', array('dummy'));
	}

	public function tearDown() {
		$this->localization = NULL;
	}

	/**
	 * @test
	 * @author Sebastian Kurfürst <sebastian@typo3.org>
	 */
	public function implodeTypoScriptLabelArrayWorks() {
		$expected = array(
			'key1' => 'value1',
			'key2' => 'value2',
			'key3.subkey1' => 'subvalue1',
			'key3.subkey2.subsubkey' => 'val'
		);
		$actual = $this->localization->_call('flattenTypoScriptLabelArray', array(
			'key1' => 'value1',
			'key2' => 'value2',
			'key3' => array(
				'subkey1' => 'subvalue1',
				'subkey2' => array(
					'subsubkey' => 'val'
				)
			)
		));
		$this->assertEquals($expected, $actual);
	}

	/**
	 * @test
	 */
	public function translateForEmptyStringKeyReturnsNull() {
		$this->assertNull(\TYPO3\CMS\Extbase\Utility\LocalizationUtility::translate('', 'extbase'));
	}

	/**
	 * @test
	 */
	public function translateForEmptyStringKeyWithArgumentsReturnsNull() {
		$this->assertNull(\TYPO3\CMS\Extbase\Utility\LocalizationUtility::translate('', 'extbase', array('argument')));
	}

	/**
	 * @return array
	 */
	public function translateDataProvider() {
		return array(
			'get translated key' =>
				array('key1', $this->LOCAL_LANG, 'dk', 'Dansk label for key1'),

			'fallback to English when translation is missing for key' =>
				array('key2', $this->LOCAL_LANG, 'dk', 'English label for key2'),

			'fallback to English for non existing language' =>
				array('key2', $this->LOCAL_LANG, 'xx', 'English label for key2'),

			'replace placeholder with argument' =>
				array('keyWithPlaceholder', $this->LOCAL_LANG, 'en', 'English label with number 100', array(), array(100)),

			'get translated key from primary language' =>
				array('key1', $this->LOCAL_LANG, 'dk', 'Dansk label for key1', array('dk_alt')),

			'fallback to alternative language if translation is missing(llxml)' =>
				array('key2', $this->LOCAL_LANG, 'dk', 'Dansk alternative label for key2', array('dk_alt')),

			'fallback to alternative language if translation is missing(xlif)' =>
				array('key5', $this->LOCAL_LANG, 'dk', 'Dansk alternative label for key5', array('dk_alt')),

			'fallback to English for label not translated in dk and dk_alt(llxml)' =>
				array('key3', $this->LOCAL_LANG, 'dk', 'English label for key3', array('dk_alt')),

			'fallback to English for label not translated in dk and dk_alt(xlif)' =>
				array('key4', $this->LOCAL_LANG, 'dk', 'English label for key4', array('dk_alt')),
		);
	}

	/**
	 * @param string $key
	 * @param array $LOCAL_LANG
	 * @param string $languageKey
	 * @param string $expected
	 * @param array $altLanguageKeys
	 * @param array $arguments
	 * @return void
	 * @dataProvider translateDataProvider
	 * @test
	 */
	public function translateTest($key, array $LOCAL_LANG, $languageKey, $expected, array $altLanguageKeys = array(), array $arguments = NULL) {
		$this->localization->_setStatic('LOCAL_LANG', $LOCAL_LANG);
		$this->localization->_setStatic('languageKey', $languageKey);
		$this->localization->_setStatic('alternativeLanguageKeys', $altLanguageKeys);

		$this->assertEquals($expected, $this->localization->translate($key, 'extensionKey', $arguments));
	}
}

?>