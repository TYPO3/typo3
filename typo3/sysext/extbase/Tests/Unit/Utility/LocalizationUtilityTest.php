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
 * Testcase for class \TYPO3\CMS\Extbase\Utility\LocalizationUtility
 */
class LocalizationUtilityTest extends \TYPO3\CMS\Extbase\Tests\Unit\BaseTestCase {

	/**
	 * @var \TYPO3\CMS\Extbase\Utility\LocalizationUtility|\PHPUnit_Framework_MockObject_MockObject|\TYPO3\CMS\Core\Tests\AccessibleObjectInterface
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
				// not translated in dk => no target (llxml)
				'key2' => array(
					array(
						'source' => 'English label for key2',
					)
				),
				'key3' => array(
					array(
						'source' => 'English label for key3',
					)
				),
				// not translated in dk => empty target (xliff)
				'key4' => array(
					array(
						'source' => 'English label for key4',
						'target' => '',
					)
				),
				// not translated in dk => empty target (xliff)
				'key5' => array(
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
			// fallback language for labels which are not translated in dk
			'dk_alt' => array(
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
				// not translated in dk_alt => empty target (xliff)
				'key4' => array(
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
		$this->localization = $this->getAccessibleMock('TYPO3\\CMS\\Extbase\\Utility\\LocalizationUtility', array('getConfigurationManager'));
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

	/**
	 * @return array
	 */
	public function loadTypoScriptLabelsProvider() {
		return array(
			'override labels with typoscript' => array(
				'LOCAL_LANG' => array(
					'extensionKey' => array(
						'dk' => array(
							'key1' => array(
								array(
									'source' => 'English label for key1',
									'target' => 'Dansk label for key1 extensionKey',
								)
							),
							'key2' => array(
								array(
									'source' => 'English label for key2',
								)
							),
							'key3.subkey1' => array(
								array(
									'source' => 'English label for key3',
								)
							),
						),
					),
					'extensionKey1' => array(
						'dk' => array(
							'key1' => array(
								array(
									'source' => 'English label for key1',
									'target' => 'Dansk label for key1 extensionKey1',
								)
							),
							'key2' => array(
								array(
									'source' => 'English label for key2',
								)
							),
							'key3.subkey1' => array(
								array(
									'source' => 'English label for key3',
								)
							),
						),
					),
				),
				'typoscript LOCAL_LANG' => array(
					'_LOCAL_LANG' => array(
						'dk' => array(
							'key1' => 'key1 value from TS extensionKey',
							'key3' => array(
								'subkey1' => 'key3.subkey1 value from TS extensionKey',
								// this key doesn't exist in xml files
								'subkey2' => array(
									'subsubkey' => 'key3.subkey2.subsubkey value from TS extensionKey'
								)
							)
						)
					)
				),
				'language key' => 'dk',
				'expected' => array(
					'key1' => array(
						array(
							'source' => 'English label for key1',
							'target' => 'key1 value from TS extensionKey',
						)
					),
					'key2' => array(
						array(
							'source' => 'English label for key2',
						)
					),
					'key3.subkey1' => array(
						array(
							'source' => 'English label for key3',
							'target' => 'key3.subkey1 value from TS extensionKey',
						)
					),
					'key3.subkey2.subsubkey' => array(
						array(
							'target' => 'key3.subkey2.subsubkey value from TS extensionKey',
						)
					),
				),
			)
		);
	}

	/**
	 * Tests whether labels from xml are overwritten by TypoScript labels
	 *
	 * @param array $LOCAL_LANG
	 * @param array $typoScriptLocalLang
	 * @param string $languageKey
	 * @param array $expected
	 * @return void
	 * @dataProvider loadTypoScriptLabelsProvider
	 * @test
	 */
	public function loadTypoScriptLabels(array $LOCAL_LANG, array $typoScriptLocalLang, $languageKey, array $expected) {

		$configurationType = \TYPO3\CMS\Extbase\Configuration\ConfigurationManagerInterface::CONFIGURATION_TYPE_FRAMEWORK;

		$configurationManager = $this->getAccessibleMock('TYPO3\\CMS\\Extbase\\Configuration\\ConfigurationManager', array('getConfiguration'));
		$configurationManager->expects($this->at(0))->method('getConfiguration')->with($configurationType, 'extensionKey', NULL)->will($this->returnValue($typoScriptLocalLang));

		$this->localization->staticExpects($this->atLeastOnce())->method('getConfigurationManager')->will($this->returnValue($configurationManager));

		// translations loaded from xml files
		$this->localization->_setStatic('LOCAL_LANG', $LOCAL_LANG);
		$this->localization->_setStatic('languageKey', $languageKey);

		$this->localization->_call('loadTypoScriptLabels', 'extensionKey');
		$result = $this->localization->_getStatic('LOCAL_LANG');
		$this->assertEquals($expected, $result['extensionKey'][$languageKey]);
	}

	/**
	 * @return void
	 * @test
	 */
	public function clearLabelWithTypoScript() {
		$this->localization->_setStatic('LOCAL_LANG', $this->LOCAL_LANG);
		$this->localization->_setStatic('languageKey', 'dk');

		$typoScriptLocalLang = array(
			'_LOCAL_LANG' => array(
				'dk' => array(
					'key1' => '',
				)
			)
		);

		$configurationType = \TYPO3\CMS\Extbase\Configuration\ConfigurationManagerInterface::CONFIGURATION_TYPE_FRAMEWORK;

		$configurationManager = $this->getAccessibleMock('TYPO3\\CMS\\Extbase\\Configuration\\ConfigurationManager', array('getConfiguration'));
		$configurationManager->expects($this->at(0))->method('getConfiguration')->with($configurationType, 'extensionKey', NULL)->will($this->returnValue($typoScriptLocalLang));

		$this->localization->staticExpects($this->atLeastOnce())->method('getConfigurationManager')->will($this->returnValue($configurationManager));

		$this->localization->_call('loadTypoScriptLabels', 'extensionKey');
		$result = $this->localization->translate('key1', 'extensionKey');
		$this->assertNotNull($result);
		$this->assertEquals('', $result);
	}
}

?>