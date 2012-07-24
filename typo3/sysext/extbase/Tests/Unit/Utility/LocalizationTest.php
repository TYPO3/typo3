<?php
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
class Tx_Extbase_Tests_Unit_Utility_LocalizationTest extends Tx_Extbase_Tests_Unit_BaseTestCase {

	/**
	 * @var Tx_Extbase_Utility_Localization
	 */
	protected $localization;

	/**
	 * LOCAL_LANG array mock
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
		$this->localization = $this->getAccessibleMock('Tx_Extbase_Utility_Localization', array('dummy'));
	}

	/**
	 * This method is overridden to provide _setStatic method
	 *
	 * Creates a proxy class of the specified class which allows
	 * for calling even protected methods and access of protected properties.
	 *
	 * @param $className Full qualified name of the original class
	 * @return string Full qualified name of the built class
	 */
	protected function buildAccessibleProxy($className) {
		$accessibleClassName = uniqid('AccessibleTestProxy');
		$class = new ReflectionClass($className);
		$abstractModifier = $class->isAbstract() ? 'abstract ' : '';
		eval('
			' . $abstractModifier . 'class ' . $accessibleClassName . ' extends ' . $className . ' {
				public function _call($methodName) {
					$args = func_get_args();
					return call_user_func_array(array($this, $methodName), array_slice($args, 1));
				}
				public function _callRef($methodName, &$arg1 = NULL, &$arg2 = NULL, &$arg3 = NULL, &$arg4 = NULL, &$arg5= NULL, &$arg6 = NULL, &$arg7 = NULL, &$arg8 = NULL, &$arg9 = NULL) {
					switch (func_num_args()) {
						case 0 : return $this->$methodName();
						case 1 : return $this->$methodName($arg1);
						case 2 : return $this->$methodName($arg1, $arg2);
						case 3 : return $this->$methodName($arg1, $arg2, $arg3);
						case 4 : return $this->$methodName($arg1, $arg2, $arg3, $arg4);
						case 5 : return $this->$methodName($arg1, $arg2, $arg3, $arg4, $arg5);
						case 6 : return $this->$methodName($arg1, $arg2, $arg3, $arg4, $arg5, $arg6);
						case 7 : return $this->$methodName($arg1, $arg2, $arg3, $arg4, $arg5, $arg6, $arg7);
						case 8 : return $this->$methodName($arg1, $arg2, $arg3, $arg4, $arg5, $arg6, $arg7, $arg8);
						case 9 : return $this->$methodName($arg1, $arg2, $arg3, $arg4, $arg5, $arg6, $arg7, $arg8, $arg9);
					}
				}
				public function _set($propertyName, $value) {
					$this->$propertyName = $value;
				}
				public function _setStatic($propertyName, $value) {
					self::$$propertyName = $value;
				}
				public function _setRef($propertyName, &$value) {
					$this->$propertyName = $value;
				}
				public function _get($propertyName) {
					return $this->$propertyName;
				}
			}
		');
		return $accessibleClassName;
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
		$this->assertNull(
			Tx_Extbase_Utility_Localization::translate('', 'extbase')
		);
	}

	/**
	 * @test
	 */
	public function translateForEmptyStringKeyWithArgumentsReturnsNull() {
		$this->assertNull(
			Tx_Extbase_Utility_Localization::translate('', 'extbase', array('argument'))
		);
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
	 * @dataProvider translateDataProvider
	 */
	public function translateTest($key, array $LOCAL_LANG, $languageKey, $expected, array $altLanguageKeys = array(), array $arguments = NULL) {
		$this->localization->_setStatic('LOCAL_LANG', $LOCAL_LANG);
		$this->localization->_setStatic('languageKey', $languageKey);
		$this->localization->_setStatic('alternativeLanguageKeys', $altLanguageKeys);

		$this->assertEquals($expected, $this->localization->translate($key, 'extensionKey', $arguments));
	}
}
?>