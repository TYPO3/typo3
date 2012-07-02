<?php
/***************************************************************
 *  Copyright notice
 *
 *  (c) 2012 Christian Kuhn <lolli@schwarzbu.ch>
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
 * Testcase for class t3lib_Configuration
 *
 * @author Christian Kuhn <lolli@schwarzbu.ch>
 *
 * @package TYPO3
 * @subpackage t3lib
 */
class t3lib_ConfigurationTest extends tx_phpunit_testcase {

	/**
	 * Absolute path to files that must be removed
	 * after a test - handled in tearDown
	 */
	protected $testFilesToDelete = array();

	/**
	 * Tear down test case
	 */
	public function tearDown() {
		foreach ($this->testFilesToDelete as $absoluteFileName) {
			t3lib_div::unlink_tempfile($absoluteFileName);
		}
	}

	#######################
	# Tests concerning getDefaultConfiguration
	#######################

	/**
	 * @test
	 * @expectedException RuntimeException
	 */
	public function getDefaultConfigurationExecutesDefinedDefaultConfigurationFile() {
		$defaultConfigurationFile = 'typo3temp/' . uniqid('defaultConfiguration');
		$className = uniqid('t3lib_Configuration');
		eval(
			'class ' . $className . ' extends t3lib_Configuration {' .
			'  const DEFAULT_CONFIGURATION_FILE = \'' . $defaultConfigurationFile . '\';' .
			'}'
		);
		file_put_contents(
			PATH_site . $defaultConfigurationFile,
			"<?php\n\nthrow new RuntimeException('foo', 1310203814);\n\n?>"
		);
		$this->testFilesToDelete[] = PATH_site . $defaultConfigurationFile;
		$className::getDefaultConfiguration();
	}

	#######################
	# Tests concerning getLocalConfiguration
	#######################

	/**
	 * @test
	 * @expectedException RuntimeException
	 */
	public function getLocalConfigurationExecutesDefinedConfigurationFile() {
		$configurationFile = 'typo3temp/' . uniqid('localConfiguration');
		$className = uniqid('t3lib_Configuration');
		eval(
			'class ' . $className . ' extends t3lib_Configuration {' .
			'  const LOCAL_CONFIGURATION_FILE = \'' . $configurationFile . '\';' .
			'}'
		);
		file_put_contents(
			PATH_site . $configurationFile,
			"<?php\n\nthrow new RuntimeException('foo', 1310203815);\n\n?>"
		);
		$this->testFilesToDelete[] = PATH_site . $configurationFile;
		$className::getLocalConfiguration();
	}

	#######################
	# Tests concerning updateLocalConfiguration
	#######################

	/**
	 * @test
	 */
	public function updateLocalConfigurationWritesNewMergedLocalConfigurationArray() {
		$className = uniqid('t3lib_Configuration');
		eval(
			'class ' . $className . ' extends t3lib_Configuration {' .
			'  public static function getLocalConfiguration() {' .
			'    $currentLocalConfiguration = array(' .
			'      \'notChanged\' => 23,' .
			'      \'changed\' => \'unChanged\',' .
			'    );' .
			'    return $currentLocalConfiguration;' .
			'  }' .
			'  public static function writeLocalConfiguration($conf) {' .
			'    $expectedConfiguration = array(' .
			'      \'notChanged\' => 23,' .
			'      \'changed\' => \'changed\',' .
			'      \'new\' => \'new\',' .
			'    );' .
			'    if (!($conf === $expectedConfiguration)) {' .
			'      throw new Exception(\'broken\');' .
			'    }' .
			'  }' .
			'}'
		);
		$overrideConfiguration = array(
			'changed' => 'changed',
			'new' => 'new',
		);
		$className::updateLocalConfiguration($overrideConfiguration);
	}

	#######################
	# Tests concerning getDefaultConfigurationValueByPath
	#######################

	/**
	 * @test
	 */
	public function getDefaultConfigurationValueByPathReturnsCorrectValue() {
		$className = uniqid('t3lib_Configuration');
		eval(
			'class ' . $className . ' extends t3lib_Configuration {' .
			'  public static function getDefaultConfiguration() {' .
			'    return array(\'path\' => \'value\');' .
			'  }' .
			'}'
		);
		$this->assertSame('value', $className::getDefaultConfigurationValueByPath('path'));
	}

	#######################
	# Tests concerning getLocalConfigurationValueByPath
	#######################

	/**
	 * @test
	 */
	public function getLocalConfigurationValueByPathReturnsCorrectValue() {
		$className = uniqid('t3lib_Configuration');
		eval(
			'class ' . $className . ' extends t3lib_Configuration {' .
			'  public static function getLocalConfiguration() {' .
			'    return array(\'path\' => \'value\');' .
			'  }' .
			'}'
		);
		$this->assertSame('value', $className::getLocalConfigurationValueByPath('path'));
	}

	#######################
	# Tests concerning getConfigurationValueByPath
	#######################

	/**
	 * @test
	 */
	public function getConfigurationValueByPathReturnsCorrectValue() {
		$className = uniqid('t3lib_Configuration');
		eval(
			'class ' . $className . ' extends t3lib_Configuration {' .
			'  public static function getDefaultConfiguration() {' .
			'    return array(\'path\' => \'value\');' .
			'  }' .
			'  public static function getLocalConfiguration() {' .
			'    return array(\'path\' => \'valueOverride\');' .
			'  }' .
			'}'
		);
		$this->assertSame('valueOverride', $className::getConfigurationValueByPath('path'));
	}

	#######################
	# Tests concerning setLocalConfigurationValueByPath
	#######################

	/**
	 * @test
	 */
	public function setLocalConfigurationValueByPathReturnFalseIfPathIsNotValid() {
		$className = uniqid('t3lib_Configuration');
		eval(
			'class ' . $className . ' extends t3lib_Configuration {' .
			'  public static function isValidLocalConfigurationPath() {' .
			'    return FALSE;' .
			'  }' .
			'}'
		);
		$this->assertFalse($className::setLocalConfigurationValueByPath('path'));
	}

	/**
	 * @test
	 */
	public function setLocalConfigurationValueByPathUpdatesValueDefinedByPath() {
		$className = uniqid('t3lib_Configuration');
		eval(
			'class ' . $className . ' extends t3lib_Configuration {' .
			'  public static function isValidLocalConfigurationPath() {' .
			'    return TRUE;' .
			'  }' .
			'  public static function getLocalConfiguration() {' .
			'    $currentLocalConfiguration = array(' .
			'      \'notChanged\' => 23,' .
			'      \'toUpdate\' => \'notUpdated\',' .
			'    );' .
			'    return $currentLocalConfiguration;' .
			'  }' .
			'  public static function writeLocalConfiguration($conf) {' .
			'    $expectedConfiguration = array(' .
			'      \'notChanged\' => 23,' .
			'      \'toUpdate\' => \'updated\',' .
			'    );' .
			'    if (!($conf === $expectedConfiguration)) {' .
			'      throw new Exception(\'broken\');' .
			'    }' .
			'    return TRUE;' .
			'  }' .
			'}'
		);
		$pathToUpdate = 'toUpdate';
		$valueToUpdate = 'updated';
		$this->assertTrue($className::setLocalConfigurationValueByPath($pathToUpdate, $valueToUpdate));
	}

	#######################
	# Tests concerning setLocalConfigurationValuesByPathValuePairs
	#######################

	/**
	 * @test
	 */
	public function setLocalConfigurationValuesByPathValuePairsSetsPathValuePairs() {
		$className = uniqid('t3lib_Configuration');
		eval(
			'class ' . $className . ' extends t3lib_Configuration {' .
			'  public static function isValidLocalConfigurationPath() {' .
			'    return TRUE;' .
			'  }' .
			'  public static function getLocalConfiguration() {' .
			'    $currentLocalConfiguration = array(' .
			'      \'notChanged\' => 23,' .
			'      \'toUpdate\' => \'notUpdated\',' .
			'    );' .
			'    return $currentLocalConfiguration;' .
			'  }' .
			'  public static function writeLocalConfiguration($conf) {' .
			'    $expectedConfiguration = array(' .
			'      \'notChanged\' => 23,' .
			'      \'toUpdate\' => \'updated\',' .
			'      \'new\' => \'new\',' .
			'    );' .
			'    if (!($conf === $expectedConfiguration)) {' .
			'      throw new Exception(\'broken\');' .
			'    }' .
			'    return TRUE;' .
			'  }' .
			'}'
		);
		$pairs = array(
			'toUpdate' => 'updated',
			'new' => 'new',
		);
		$this->assertTrue($className::setLocalConfigurationValuesByPathValuePairs($pairs));
	}

	#######################
	# Tests concerning writeLocalConfiguration
	#######################

	/**
	 * @test
	 */
	public function writeLocalConfigurationWritesSortedContentToConfigurationFile() {
		$configurationFile = 'typo3temp/' . uniqid('localConfiguration');
		$className = uniqid('t3lib_Configuration');
		eval(
			'class ' . $className . ' extends t3lib_Configuration {' .
			'  const LOCAL_CONFIGURATION_FILE = \'' . $configurationFile . '\';' .
			'  public static function writeLocalConfiguration($conf) {' .
			'    return parent::writeLocalConfiguration($conf);' .
			'  }' .
			'}'
		);
		$this->testFilesToDelete[] = PATH_site . $configurationFile;
		$pairs = array(
			'foo' => 42,
			'bar' => 23
		);
		$expectedContent =
		'<?php' . LF .
			'return array(' . LF .
			TAB . '\'bar\' => 23,' . LF .
			TAB . '\'foo\' => 42,' . LF .
			');' . LF .
		'?>';
		$this->assertTrue($className::writeLocalConfiguration($pairs));
		$this->assertEquals($expectedContent, file_get_contents(PATH_site . $configurationFile));
	}

	#######################
	# Tests concerning isValidLocalConfigurationPath
	#######################

	/**
	 * @test
	 */
	public function isValidLocalConfigurationPathAcceptsWhitelistedPath() {
		$className = uniqid('t3lib_Configuration');
		eval(
			'class ' . $className . ' extends t3lib_Configuration {' .
			'  protected static $whiteListedLocalConfigurationPaths = array(' .
			'    \'foo\',' .
			'  );' .
			'  public static function isValidLocalConfigurationPath($path) {' .
			'    return parent::isValidLocalConfigurationPath($path);' .
			'  }' .
			'}'
		);
		$this->assertTrue($className::isValidLocalConfigurationPath('foo'));
	}
}

?>