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
 */
class ConfigurationManagerTest extends \TYPO3\CMS\Core\Tests\UnitTestCase {

	/**
	 * Absolute path to files that must be removed
	 * after a test - handled in tearDown
	 */
	protected $testFilesToDelete = array();

	/**
	 * @var \TYPO3\CMS\Core\Configuration\ConfigurationManager|\PHPUnit_Framework_MockObject_MockObject
	 */
	protected $fixture;

	public function setUp() {
		$this->createFixtureWithMockedMethods(
			array(
				'getDefaultConfigurationFileResource',
				'getLocalConfigurationFileResource',
			)
		);
	}

	/**
	 * Tear down test case
	 */
	public function tearDown() {
		foreach ($this->testFilesToDelete as $absoluteFileName) {
			\TYPO3\CMS\Core\Utility\GeneralUtility::unlink_tempfile($absoluteFileName);
		}
	}

	/**
	 * @param array $methods
	 */
	protected function createFixtureWithMockedMethods(array $methods) {
		$this->fixture = $this->getMock(
			'TYPO3\\CMS\\Core\\Configuration\\ConfigurationManager',
			$methods
		);

	}

	/**
	 * Tests concerning getDefaultConfiguration
	 *
	 */
	/**
	 * @test
	 * @expectedException \RuntimeException
	 */
	public function getDefaultConfigurationExecutesDefinedDefaultConfigurationFile() {
		$defaultConfigurationFile = PATH_site . 'typo3temp/' . uniqid('defaultConfiguration');
		file_put_contents($defaultConfigurationFile, '<?php throw new \RuntimeException(\'foo\', 1310203814); ?>');
		$this->testFilesToDelete[] = $defaultConfigurationFile;

		$this->fixture->expects($this->once())->method('getDefaultConfigurationFileResource')->will($this->returnValue($defaultConfigurationFile));
		$this->fixture->getDefaultConfiguration();
	}

	/**
	 * Tests concerning getLocalConfiguration
	 */
	/**
	 * @test
	 * @expectedException \RuntimeException
	 */
	public function getLocalConfigurationExecutesDefinedConfigurationFile() {
		$configurationFile = PATH_site . 'typo3temp/' . uniqid('localConfiguration');
		file_put_contents($configurationFile, '<?php throw new \RuntimeException(\'foo\', 1310203815); ?>');
		$this->testFilesToDelete[] = $configurationFile;

		$this->fixture->expects($this->once())->method('getLocalConfigurationFileResource')->will($this->returnValue($configurationFile));
		$this->fixture->getLocalConfiguration();
	}

	/**
	 * Tests concerning updateLocalConfiguration
	 */
	/**
	 * @test
	 */
	public function updateLocalConfigurationWritesNewMergedLocalConfigurationArray() {
		$currentLocalConfiguration = array(
			'notChanged' => 23,
			'changed' => 'unChanged',
		);
		$overrideConfiguration = array(
			'changed' => 'changed',
			'new' => 'new'
		);
		$expectedConfiguration = array(
			'notChanged' => 23,
			'changed' => 'changed',
			'new' => 'new',
		);

		$this->createFixtureWithMockedMethods(array(
				'getLocalConfiguration',
				'writeLocalConfiguration',
			));
		$this->fixture->expects($this->once())
				->method('getLocalConfiguration')
				->will($this->returnValue($currentLocalConfiguration));
		$this->fixture->expects($this->once())
				->method('writeLocalConfiguration')
				->with($expectedConfiguration);

		$this->fixture->updateLocalConfiguration($overrideConfiguration);
	}

	/**
	 * Tests concerning getDefaultConfigurationValueByPath
	 */
	/**
	 * @test
	 */
	public function getDefaultConfigurationValueByPathReturnsCorrectValue() {
		$this->createFixtureWithMockedMethods(array(
				'getDefaultConfiguration',
			));
		$this->fixture->expects($this->once())
				->method('getDefaultConfiguration')
				->will($this->returnValue(array(
					'path' => 'value',
				)
			));

		$this->assertSame('value', $this->fixture->getDefaultConfigurationValueByPath('path'));
	}

	/**
	 * Tests concerning getLocalConfigurationValueByPath
	 */
	/**
	 * @test
	 */
	public function getLocalConfigurationValueByPathReturnsCorrectValue() {
		$this->createFixtureWithMockedMethods(array(
				'getLocalConfiguration',
			));
		$this->fixture->expects($this->once())
				->method('getLocalConfiguration')
				->will($this->returnValue(array(
					'path' => 'value',
				)
			));

		$this->assertSame('value', $this->fixture->getLocalConfigurationValueByPath('path'));
	}

	/**
	 * Tests concerning getConfigurationValueByPath
	 */
	/**
	 * @test
	 */
	public function getConfigurationValueByPathReturnsCorrectValue() {
		$this->createFixtureWithMockedMethods(array(
				'getDefaultConfiguration',
				'getLocalConfiguration',
			));
		$this->fixture->expects($this->once())
				->method('getDefaultConfiguration')
				->will($this->returnValue(array(
					'path' => 'value',
				)
			));
		$this->fixture->expects($this->once())
				->method('getLocalConfiguration')
				->will($this->returnValue(array(
					'path' => 'valueOverride',
				)
			));

		$this->assertSame('valueOverride', $this->fixture->getConfigurationValueByPath('path'));
	}

	/**
	 * Tests concerning setLocalConfigurationValueByPath
	 */
	/**
	 * @test
	 */
	public function setLocalConfigurationValueByPathReturnFalseIfPathIsNotValid() {
		$this->createFixtureWithMockedMethods(array(
				'isValidLocalConfigurationPath',
			));
		$this->fixture->expects($this->once())
				->method('isValidLocalConfigurationPath')
				->will($this->returnValue(FALSE));

		$this->assertFalse($this->fixture->setLocalConfigurationValueByPath('path', 'value'));
	}

	/**
	 * @test
	 */
	public function setLocalConfigurationValueByPathUpdatesValueDefinedByPath() {
		$currentLocalConfiguration = array(
			'notChanged' => 23,
			'toUpdate' => 'notUpdated',
		);
		$expectedConfiguration = array(
			'notChanged' => 23,
			'toUpdate' => 'updated',
		);

		$this->createFixtureWithMockedMethods(array(
				'isValidLocalConfigurationPath',
				'getLocalConfiguration',
				'writeLocalConfiguration',
			));
		$this->fixture->expects($this->once())
				->method('isValidLocalConfigurationPath')
				->will($this->returnValue(TRUE));
		$this->fixture->expects($this->once())
				->method('getLocalConfiguration')
				->will($this->returnValue($currentLocalConfiguration));
		$this->fixture->expects($this->once())
				->method('writeLocalConfiguration')
				->with($expectedConfiguration);

		$this->fixture->setLocalConfigurationValueByPath('toUpdate', 'updated');
	}

	/**
	 * Tests concerning setLocalConfigurationValuesByPathValuePairs
	 */
	/**
	 * @test
	 */
	public function setLocalConfigurationValuesByPathValuePairsSetsPathValuePairs() {
		$currentLocalConfiguration = array(
			'notChanged' => 23,
			'toUpdate' => 'notUpdated',
		);
		$expectedConfiguration = array(
			'notChanged' => 23,
			'toUpdate' => 'updated',
			'new' => 'new',
		);

		$this->createFixtureWithMockedMethods(array(
				'isValidLocalConfigurationPath',
				'getLocalConfiguration',
				'writeLocalConfiguration',
			));
		$this->fixture->expects($this->any())
				->method('isValidLocalConfigurationPath')
				->will($this->returnValue(TRUE));
		$this->fixture->expects($this->once())
				->method('getLocalConfiguration')
				->will($this->returnValue($currentLocalConfiguration));
		$this->fixture->expects($this->once())
				->method('writeLocalConfiguration')
				->with($expectedConfiguration);

		$pairs = array(
			'toUpdate' => 'updated',
			'new' => 'new'
		);
		$this->fixture->setLocalConfigurationValuesByPathValuePairs($pairs);
	}

	/**
	 * Tests concerning writeLocalConfiguration
	 */
	/**
	 * @test
	 * @expectedException \RuntimeException
	 */
	public function writeLocalConfigurationThrowsExceptionForInvalidFile() {
		$configurationFile = 'typo3temp/' . uniqid('localConfiguration');
		$this->fixture->expects($this->once())->method('getLocalConfigurationFileResource')->will($this->returnValue($configurationFile));

		$pairs = array(
			'foo' => 42,
			'bar' => 23
		);
		$this->fixture->writeLocalConfiguration($pairs);
	}

	/**
	 * @test
	 */
	public function writeLocalConfigurationWritesSortedContentToConfigurationFile() {
		$configurationFile = PATH_site . 'typo3temp/' . uniqid('localConfiguration');
		if (!is_file($configurationFile)) {
			if (!$fh = fopen($configurationFile, 'wb')) {
				$this->markTestSkipped('Can not create file ' . $configurationFile . '. Please check your write permissions.');
			}
			fclose($fh);
		}

		if (!@is_file($configurationFile)) {
			throw new \RuntimeException('File ' . $configurationFile . ' could not be found. Please check your write permissions', 1346364362);
		}
		$this->testFilesToDelete[] = $configurationFile;

		$this->fixture->expects($this->once())->method('getLocalConfigurationFileResource')->will($this->returnValue($configurationFile));

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

		$this->fixture->writeLocalConfiguration($pairs);
		$this->assertSame($expectedContent, file_get_contents($configurationFile));
	}

	/**
	 * Tests concerning isValidLocalConfigurationPath
	 */
	/**
	 * @test
	 */
	public function isValidLocalConfigurationPathAcceptsWhitelistedPath() {
		/** @var $fixture \TYPO3\CMS\Core\Configuration\ConfigurationManager|\PHPUnit_Framework_MockObject_MockObject|\TYPO3\CMS\Core\Tests\AccessibleObjectInterface */
		$fixture = $this->getAccessibleMock('TYPO3\\CMS\\Core\\Configuration\\ConfigurationManager', array('dummy'));
		$fixture->_set('whiteListedLocalConfigurationPaths', array('foo/bar'));
		$this->assertTrue($fixture->_call('isValidLocalConfigurationPath', 'foo/bar/baz'));
	}

	/**
	 * @test
	 */
	public function isValidLocalConfigurationPathDeniesNotWhitelistedPath() {
		/** @var $fixture \TYPO3\CMS\Core\Configuration\ConfigurationManager|\PHPUnit_Framework_MockObject_MockObject|\TYPO3\CMS\Core\Tests\AccessibleObjectInterface */
		$fixture = $this->getAccessibleMock('TYPO3\\CMS\\Core\\Configuration\\ConfigurationManager', array('dummy'));
		$fixture->_set('whiteListedLocalConfigurationPaths', array('foo/bar'));
		$this->assertFalse($fixture->_call('isValidLocalConfigurationPath', 'bar/baz'));
	}
}
?>