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

	/**
	 * @var boolean Enable backup of global and system variables
	 */
	protected $backupGlobals = TRUE;

	/**
	 * Exclude TYPO3_DB from backup/ restore of $GLOBALS
	 * because resource types cannot be handled during serializing
	 *
	 * @var array
	 */
	protected $backupGlobalsBlacklist = array('TYPO3_DB');

	/**
	 * @var array Register of temporary extensions in typo3temp
	 */
	protected $fakedExtensions = array();

	/**
	 * Clean up
	 */
	public function tearDown() {
		foreach ($this->fakedExtensions as $extension) {
			t3lib_div::rmdir(PATH_site . 'typo3temp/' . $extension, TRUE);
		}
	}

	/**
	 * Creates a fake extension inside typo3temp/. No configuration is created,
	 * just the folder, plus the extension is registered in $TYPO3_LOADED_EXT
	 *
	 * @return string The extension key
	 */
	protected function createFakeExtension() {
		$extKey = uniqid('testing');
		$absExtPath = PATH_site . "typo3temp/$extKey/";
		$relPath = "typo3temp/$extKey/";
		t3lib_div::mkdir($absExtPath);

		$GLOBALS['TYPO3_LOADED_EXT'][$extKey] = array(
			'siteRelPath' => $relPath
		);
		$GLOBALS['TYPO3_CONF_VARS']['EXT']['extList'] .= ',' . $extKey;

		$this->fakedExtensions[] = $extKey;
		t3lib_extMgm::clearExtensionKeyMap();

		return $extKey;
	}

	/**
	 * @test
	 */
	public function autoloaderCanBeUnregisteredAndRegisteredAgain() {
		t3lib_autoloader::unregisterAutoloader();
		t3lib_autoloader::registerAutoloader();
		t3lib_div::makeInstance('t3lib_timetracknull');
	}

	/**
	 * @test
	 */
	public function extensionAutoloadFileIsIncludedIfAvailable() {
		$extKey = $this->createFakeExtension();
		$extPath = PATH_site . "typo3temp/$extKey/";
		$autoloaderFile = $extPath . "ext_autoload.php";

		$class = strtolower("tx_${extKey}_" . uniqid(''));
		$file = $extPath . uniqid('') . '.php';

		file_put_contents($file, "<?php\n\nthrow new RuntimeException('', 1310203812);\n\n?>");
		file_put_contents($autoloaderFile, "<?php\n\nreturn array('$class' => '$file');\n\n?>");

			// Inject a dummy for the core_phpcode cache to force the autoloader
			// to re calculate the registry
		$mockCache = $this->getMock('t3lib_cache_frontend_AbstractFrontend', array('getIdentifier', 'set', 'get', 'getByTag', 'has', 'remove', 'flush', 'flushByTag', 'requireOnce'), array(), '', FALSE);
		$GLOBALS['typo3CacheManager'] = $this->getMock('t3lib_cache_Manager', array('getCache'));
		$GLOBALS['typo3CacheManager']->expects($this->any())->method('getCache')->will($this->returnValue($mockCache));

			// Re initialize autoloader registry to force it to recognize the new extension
		t3lib_autoloader::unregisterAutoloader();
		t3lib_autoloader::registerAutoloader();

			// Expect the exception of the file to be thrown
		$this->setExpectedException('RuntimeException', '', 1310203812);
		t3lib_autoloader::autoload($class);
	}
}
?>