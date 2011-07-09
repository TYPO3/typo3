<?php
/***************************************************************
 * Copyright notice
 *
 * (c) 2011 Andreas Wolf <andreas.wolf@ikt-werk.de>
 * All rights reserved
 *
 * This script is part of the TYPO3 project. The TYPO3 project is
 * free software; you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation; either version 2 of the License, or
 * (at your option) any later version.
 *
 * The GNU General Public License can be found at
 * http://www.gnu.org/copyleft/gpl.html.
 *
 * This script is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * This copyright notice MUST APPEAR in all copies of the script!
 ***************************************************************/

/**
 * Testcase for the t3lib_autoloader class in the TYPO3 Core.
 *
 * @package TYPO3
 * @subpackage t3lib
 *
 * @author Andreas Wolf <andreas.wolf@ikt-werk.de>
 */
class t3lib_autoloaderTest extends Tx_Phpunit_TestCase {

	protected $safePathSite = '';

	public function setUp() {
		$this->safePathSite = t3lib_extMgm::$PATH_site;
	}

	public function tearDown() {
		t3lib_extMgm::$PATH_site = $this->safePathSite;
	}

	/**
	 * @test
	 */
	public function extensionAutoloadFileIsIncludedIfAvailable() {
		if (!class_exists('\vfsStreamWrapper')) {
			$this->markTestSkipped('Autoloader tests are not available with this phpunit version.');
		}

			// Create extension name, a class name and autoloader file
		$vfsRoot = 'vfs://Foo/';
		$extName = 'bar';
		$autoloadFile = $extName . '/ext_autoload.php';
		$className = 'tx_bar_myclass';
		$classFile = $extName . '/class.' . $className . '.php';

			// Set up vfs and create files
		\vfsStreamWrapper::register();
		\vfsStreamWrapper::setRoot(new \vfsStreamDirectory('Foo'));
		mkdir($vfsRoot . $extName);
		file_put_contents($vfsRoot . $autoloadFile, "<?php\n\nreturn array('$className' => '$vfsRoot$classFile');\n\n?>");
		file_put_contents($vfsRoot . $classFile, "<?php\n\nthrow new RuntimeException('', 1310203812);\n\n?>");

			// Register extension path in TYPO3_LOADED_EXT
		$GLOBALS['TYPO3_LOADED_EXT'][$extName] = array(
			'siteRelPath' => 'bar/',
		);
		t3lib_extMgm::clearExtensionKeyMap();

			// Expect the exception of the faked class is thrown if we autoload it
		$this->setExpectedException('RuntimeException', '', 1310203812);
		t3lib_extMgm::$PATH_site = $vfsRoot;
		t3lib_autoloader::autoload($className);
	}
}
?>