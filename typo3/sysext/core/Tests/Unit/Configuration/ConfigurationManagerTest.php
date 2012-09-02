<?php
namespace TYPO3\CMS\Core\Tests\Unit\Configuration;

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
 * Testcase for class \TYPO3\CMS\Core\Configuration\ConfigurationManager
 *
 * @author Christian Kuhn <lolli@schwarzbu.ch>
 * @package TYPO3
 * @subpackage t3lib
 */
class ConfigurationManagerTest extends \tx_phpunit_testcase {

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
			\TYPO3\CMS\Core\Utility\GeneralUtility::unlink_tempfile($absoluteFileName);
		}
	}

	///////////////////////
	// Tests concerning getDefaultConfiguration
	///////////////////////

	/**
	 * @test
	 * @expectedException \RuntimeException
	 */
	public function getDefaultConfigurationExecutesDefinedDefaultConfigurationFile() {
		$defaultConfigurationFile = 'typo3temp/' . uniqid('defaultConfiguration');
		$namespace = 'TYPO3\\CMS\\Core\\Configuration';
		$className = uniqid('ConfigurationManager');
		eval(
			'namespace ' . $namespace . ';' .
			'class ' . $className . ' extends \\TYPO3\\CMS\\Core\\Configuration\\ConfigurationManager {' .
			'  const DEFAULT_CONFIGURATION_FILE = \'' . $defaultConfigurationFile . '\';' .
			'}'
		);
		$className = $namespace . '\\' . $className;
		file_put_contents(PATH_site . $defaultConfigurationFile, '<?php throw new RuntimeException(\'foo\', 1310203814); ?>');
		$this->testFilesToDelete[] = PATH_site . $defaultConfigurationFile;
		$className::getDefaultConfiguration();
	}

	///////////////////////
	// Tests concerning getLocalConfiguration
	///////////////////////
	/**
	 * @test
	 * @expectedException RuntimeException
	 */
	public function getLocalConfigurationExecutesDefinedConfigurationFile() {
		$configurationFile = 'typo3temp/' . uniqid('localConfiguration');
		$namespace = 'TYPO3\\CMS\\Core\\Configuration';
		$className = uniqid('ConfigurationManager');
		eval(
			'namespace ' . $namespace . ';' .
			'class ' . $className . ' extends \\TYPO3\\CMS\\Core\\Configuration\\ConfigurationManager {' .
			'  const LOCAL_CONFIGURATION_FILE = \'' . $configurationFile . '\';' .
			'}'
		);
		$className = $namespace . '\\' . $className;
		file_put_contents(PATH_site . $configurationFile, '<?php throw new RuntimeException(\'foo\', 1310203815); ?>');
		$this->testFilesToDelete[] = PATH_site . $configurationFile;
		$className::getLocalConfiguration();
	}

	///////////////////////
	// Tests concerning updateLocalConfiguration
	///////////////////////
	/**
	 * @test
	 */
	public function updateLocalConfigurationWritesNewMergedLocalConfigurationArray() {
		$namespace = 'TYPO3\\CMS\\Core\\Configuration';
		$className = uniqid('ConfigurationManager');
		eval(
			'namespace ' . $namespace . '; ' .
			'class ' . $className . ' extends \\TYPO3\\CMS\\Core\\Configuration\\ConfigurationManager {' .
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
			'      \'new\' => \'new\',' . '    );' .
			'    if (!($conf === $expectedConfiguration)) {' .
			'      throw new Exception(\'broken\');' .
			'    }' .
			'  }' .
			'}'
		);
		$className = $namespace . '\\' . $className;
		$overrideConfiguration = array(
			'changed' => 'changed',
			'new' => 'new'
		);
		$className::updateLocalConfiguration($overrideConfiguration);
	}

	///////////////////////
	// Tests concerning getDefaultConfigurationValueByPath
	///////////////////////
	/**
	 * @test
	 */
	public function getDefaultConfigurationValueByPathReturnsCorrectValue() {
		$namespace = 'TYPO3\\CMS\\Core\\Configuration';
		$className = uniqid('ConfigurationManager');
		eval(
			'namespace ' . $namespace . ';' .
			'class ' . $className . ' extends \\TYPO3\\CMS\\Core\\Configuration\\ConfigurationManager {' .
			'  public static function getDefaultConfiguration() {' .
			'    return array(\'path\' => \'value\');' .
			'  }' .
			'}'
		);
		$className = $namespace . '\\' . $className;
		$this->assertSame('value', $className::getDefaultConfigurationValueByPath('path'));
	}

	///////////////////////
	// Tests concerning getLocalConfigurationValueByPath
	///////////////////////
	/**
	 * @test
	 */
	public function getLocalConfigurationValueByPathReturnsCorrectValue() {
		$namespace = 'TYPO3\\CMS\\Core\\Configuration';
		$className = uniqid('ConfigurationManager');
		eval(
			'namespace ' . $namespace . ';' .
			'class ' . $className . ' extends \\TYPO3\\CMS\\Core\\Configuration\\ConfigurationManager {' .
			'  public static function getLocalConfiguration() {' .
			'    return array(\'path\' => \'value\');' .
			'  }' .
			'}'
		);
		$className = $namespace . '\\' . $className;
		$this->assertSame('value', $className::getLocalConfigurationValueByPath('path'));
	}

	///////////////////////
	// Tests concerning getConfigurationValueByPath
	///////////////////////
	/**
	 * @test
	 */
	public function getConfigurationValueByPathReturnsCorrectValue() {
		$namespace = 'TYPO3\\CMS\\Core\\Configuration';
		$className = uniqid('ConfigurationManager');
		eval(
			'namespace ' . $namespace . ';' .
			'class ' . $className . ' extends \\TYPO3\\CMS\\Core\\Configuration\\ConfigurationManager {' .
			'  public static function getDefaultConfiguration() {' .
			'    return array(\'path\' => \'value\');' .
			'  }' .
			'  public static function getLocalConfiguration() {' .
			'    return array(\'path\' => \'valueOverride\');' .
			'  }' .
			'}'
		);
		$className = $namespace . '\\' . $className;
		$this->assertSame('valueOverride', $className::getConfigurationValueByPath('path'));
	}

	///////////////////////
	// Tests concerning setLocalConfigurationValueByPath
	///////////////////////
	/**
	 * @test
	 */
	public function setLocalConfigurationValueByPathReturnFalseIfPathIsNotValid() {
		$namespace = 'TYPO3\\CMS\\Core\\Configuration';
		$className = uniqid('ConfigurationManager');
		eval(
			'namespace ' . $namespace . ';' .
			'class ' . $className . ' extends \\TYPO3\\CMS\\Core\\Configuration\\ConfigurationManager {' .
			'  public static function isValidLocalConfigurationPath() {' .
			'    return FALSE;' .
			'  }' .
			'}'
		);
		$className = $namespace . '\\' . $className;
		$this->assertFalse($className::setLocalConfigurationValueByPath('path', 'value'));
	}

	/**
	 * @test
	 */
	public function setLocalConfigurationValueByPathUpdatesValueDefinedByPath() {
		$namespace = 'TYPO3\\CMS\\Core\\Configuration';
		$className = uniqid('ConfigurationManager');
		eval(
			'namespace ' . $namespace . ';' .
			'class ' . $className . ' extends \\TYPO3\\CMS\\Core\\Configuration\\ConfigurationManager {' .
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
		$className = $namespace . '\\' . $className;
		$pathToUpdate = 'toUpdate';
		$valueToUpdate = 'updated';
		$this->assertTrue($className::setLocalConfigurationValueByPath($pathToUpdate, $valueToUpdate));
	}

	///////////////////////
	// Tests concerning setLocalConfigurationValuesByPathValuePairs
	///////////////////////
	/**
	 * @test
	 */
	public function setLocalConfigurationValuesByPathValuePairsSetsPathValuePairs() {
		$namespace = 'TYPO3\\CMS\\Core\\Configuration';
		$className = uniqid('ConfigurationManager');
		eval(
			'namespace ' . $namespace . ';' .
			'class ' . $className .
			' extends \\TYPO3\\CMS\\Core\\Configuration\\ConfigurationManager {' .
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
		$className = $namespace . '\\' . $className;
		$pairs = array(
			'toUpdate' => 'updated',
			'new' => 'new'
		);
		$this->assertTrue($className::setLocalConfigurationValuesByPathValuePairs($pairs));
	}

	///////////////////////
	// Tests concerning writeLocalConfiguration
	///////////////////////
	/**
	 * @test
	 */
	public function writeLocalConfigurationWritesSortedContentToConfigurationFile() {
		$configurationFile = 'typo3temp/' . uniqid('localConfiguration');
		$namespace = 'TYPO3\\CMS\\Core\\Configuration';
		$className = uniqid('ConfigurationManager');
		eval(
			'namespace ' . $namespace . ';' .
			'class ' . $className . ' extends \\TYPO3\\CMS\\Core\\Configuration\\ConfigurationManager {' .
			'  const LOCAL_CONFIGURATION_FILE = \'' . $configurationFile . '\';' .
			'  public static function writeLocalConfiguration($conf) {' .
			'    return parent::writeLocalConfiguration($conf);' .
			'  }' .
			'}'
		);
		$className = $namespace . '\\' . $className;
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

	///////////////////////
	// Tests concerning isValidLocalConfigurationPath
	///////////////////////
	/**
	 * @test
	 */
	public function isValidLocalConfigurationPathAcceptsWhitelistedPath() {
		$namespace = 'TYPO3\\CMS\\Core\\Configuration';
		$className = uniqid('ConfigurationManager');
		eval(
			'namespace ' . $namespace . ';' .
			'class ' . $className . ' extends \\TYPO3\\CMS\\Core\\Configuration\\ConfigurationManager {' .
			'  protected static $whiteListedLocalConfigurationPaths = array(' .
			'    \'foo\',' .
			'  );' .
			'  public static function isValidLocalConfigurationPath($path) {' .
			'    return parent::isValidLocalConfigurationPath($path);' .
			'  }' .
			'}'
		);
		$className = $namespace . '\\' . $className;
		$this->assertTrue($className::isValidLocalConfigurationPath('foo'));
	}

}

?>