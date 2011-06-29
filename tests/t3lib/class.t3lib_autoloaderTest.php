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
	 * @var array Backup of typo3CacheManager
	 */
	protected $typo3CacheManager = NULL;

	/**
	 * @var array Register of temporary extensions in typo3temp
	 */
	protected $fakedExtensions = array();

	/**
	 * Fix a race condition that t3lib_div is not available
	 * during tearDown if fiddling with the autoloader where
	 * backupGlobals is not set up again yet
	 */
	public function setUp() {
		$this->typo3CacheManager = $GLOBALS['typo3CacheManager'];
	}

	/**
	 * Clean up
	 * Warning: Since phpunit itself is php and we are fiddling with php
	 * autoloader code here, the tests are a bit fragile. This tearDown
	 * method ensures that all main classes are available again during
	 * tear down of a testcase.
	 * This construct will fail if the class under test is changed and
	 * not compatible anymore. Make sure to always run the whole test
	 * suite if fiddling with the autoloader unit tests to ensure that
	 * there is no fatal error thrown in other unit test classes triggered
	 * by errors in this one.
	 */
	public function tearDown() {
		$GLOBALS['typo3CacheManager'] = $this->typo3CacheManager;
		t3lib_autoloader::unregisterAutoloader();
		t3lib_autoloader::registerAutoloader();
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
		$extKey = strtolower(uniqid('testing'));
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
	public function UnregisterAndRegisterAgainDoesNotFatal() {
		t3lib_autoloader::unregisterAutoloader();
		t3lib_autoloader::registerAutoloader();
			// If this fatals the autoload re registering went wrong
		t3lib_div::makeInstance('t3lib_timetracknull');
	}

	/**
	 * @test
	 */
	public function registerSetsCacheEntryWithT3libAutoloaderTag() {
		$mockCache = $this->getMock('t3lib_cache_frontend_AbstractFrontend', array('getIdentifier', 'set', 'get', 'getByTag', 'has', 'remove', 'flush', 'flushByTag', 'requireOnce'), array(), '', FALSE);
			// Expect the mock cache set method to be called
			// once with t3lib_autoloader as third parameter
		$mockCache->expects($this->once())->method('set')->with(TRUE, TRUE, array('t3lib_autoloader'));
		$GLOBALS['typo3CacheManager'] = $this->getMock('t3lib_cache_Manager', array('getCache'));
		$GLOBALS['typo3CacheManager']->expects($this->any())->method('getCache')->will($this->returnValue($mockCache));
		t3lib_autoloader::unregisterAutoloader();
		t3lib_autoloader::registerAutoloader();
	}

	/**
	 * @test
	 */
	public function autoloadFindsClassFileDefinedInExtAutoloadFile() {
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

			// Re-initialize autoloader registry to force it to recognize the new extension
		t3lib_autoloader::unregisterAutoloader();
		t3lib_autoloader::registerAutoloader();

			// Expect the exception of the file to be thrown
		$this->setExpectedException('RuntimeException', '', 1310203812);
		t3lib_autoloader::autoload($class);
	}

	/**
	 * @test
	 */
	public function autoloadFindsClassFileThatRespectsExtbaseNamingSchemeWithoutExtAutoloadFile() {
		$extKey = $this->createFakeExtension();
		$extPath = PATH_site . "typo3temp/$extKey/";

			// Create a class named Tx_Extension_Foo123_Bar456
			// to find file extension/Classes/Foo123/Bar456.php
		$pathSegment = 'Foo' . uniqid();
		$fileName = 'Bar' . uniqid();
		$class = 'Tx_' . $extKey . '_' . $pathSegment . '_' . $fileName;
		$file = $extPath . 'Classes/' . $pathSegment . '/' . $fileName . '.php';

		t3lib_div::mkdir_deep($extPath . 'Classes/' . $pathSegment);
		file_put_contents($file, "<?php\n\nthrow new RuntimeException('', 1310203813);\n\n?>");

			// Inject a dummy for the core_phpcode cache to cache
			// the calculated cache entry to a dummy cache
		$mockCache = $this->getMock('t3lib_cache_frontend_AbstractFrontend', array('getIdentifier', 'set', 'get', 'getByTag', 'has', 'remove', 'flush', 'flushByTag', 'requireOnce'), array(), '', FALSE);
		$GLOBALS['typo3CacheManager'] = $this->getMock('t3lib_cache_Manager', array('getCache'));
		$GLOBALS['typo3CacheManager']->expects($this->any())->method('getCache')->will($this->returnValue($mockCache));

			// Expect the exception of the file to be thrown
		$this->setExpectedException('RuntimeException', '', 1310203813);
		t3lib_autoloader::autoload($class);
	}

	/**
	 * @test
	 */
	public function autoloadWritesClassFileThatRespectsExtbaseNamingSchemeToCacheFile() {
		$extKey = $this->createFakeExtension();
		$extPath = PATH_site . "typo3temp/$extKey/";

		$pathSegment = 'Foo' . uniqid();
		$fileName = 'Bar' . uniqid();
		$class = 'Tx_' . $extKey . '_' . $pathSegment . '_' . $fileName;
		$file = $extPath . 'Classes/' . $pathSegment . '/' . $fileName . '.php';

		t3lib_div::mkdir_deep($extPath . 'Classes/' . $pathSegment);
		file_put_contents($file, "<?php\n\n\$foo = 'bar';\n\n?>");

		$mockCache = $this->getMock('t3lib_cache_frontend_AbstractFrontend', array('getIdentifier', 'set', 'get', 'getByTag', 'has', 'remove', 'flush', 'flushByTag', 'requireOnce'), array(), '', FALSE);
		$GLOBALS['typo3CacheManager'] = $this->getMock('t3lib_cache_Manager', array('getCache'));
		$GLOBALS['typo3CacheManager']->expects($this->any())->method('getCache')->will($this->returnValue($mockCache));

			// Expect that an entry to the cache is written containing the newly found class
		$mockCache->expects($this->once())->method('set')->with(TRUE, $this->stringContains(strtolower($class), TRUE));

		t3lib_autoloader::autoload($class);
	}

	/**
	 * @test
	 */
	public function autoloadWritesClassFileLocationOfClassRespectingExtbaseNamingSchemeToCacheFile() {
		$extKey = $this->createFakeExtension();
		$extPath = PATH_site . "typo3temp/$extKey/";

		$pathSegment = 'Foo' . uniqid();
		$fileName = 'Bar' . uniqid();
		$class = 'Tx_' . $extKey . '_' . $pathSegment . '_' . $fileName;
		$file = $extPath . 'Classes/' . $pathSegment . '/' . $fileName . '.php';

		t3lib_div::mkdir_deep($extPath . 'Classes/' . $pathSegment);
		file_put_contents($file, "<?php\n\n\$foo = 'bar';\n\n?>");

		$mockCache = $this->getMock('t3lib_cache_frontend_AbstractFrontend', array('getIdentifier', 'set', 'get', 'getByTag', 'has', 'remove', 'flush', 'flushByTag', 'requireOnce'), array(), '', FALSE);
		$GLOBALS['typo3CacheManager'] = $this->getMock('t3lib_cache_Manager', array('getCache'));
		$GLOBALS['typo3CacheManager']->expects($this->any())->method('getCache')->will($this->returnValue($mockCache));

			// Expect that an entry to the cache is written containing the newly found class
		$mockCache->expects($this->once())->method('set')->with(TRUE, $this->stringContains(strtolower($file), TRUE));

		t3lib_autoloader::autoload($class);
	}

	/**
	 * @test
	 */
	public function autoloadDoesNotSetCacheEntryForClassThatRespectsExtbaseNamingSchemeOnConsecutiveCallsForSameClass() {
		$extKey = $this->createFakeExtension();
		$extPath = PATH_site . "typo3temp/$extKey/";

		$pathSegment = 'Foo' . uniqid();
		$fileName = 'Bar' . uniqid();
		$class = 'Tx_' . $extKey . '_' . $pathSegment . '_' . $fileName;
		$file = $extPath . 'Classes/' . $pathSegment . '/' . $fileName . '.php';

		t3lib_div::mkdir_deep($extPath . 'Classes/' . $pathSegment);
		file_put_contents($file, "<?php\n\n\$foo = 'bar';\n\n?>");

		$mockCache = $this->getMock('t3lib_cache_frontend_AbstractFrontend', array('getIdentifier', 'set', 'get', 'getByTag', 'has', 'remove', 'flush', 'flushByTag', 'requireOnce'), array(), '', FALSE);
		$GLOBALS['typo3CacheManager'] = $this->getMock('t3lib_cache_Manager', array('getCache'));
		$GLOBALS['typo3CacheManager']->expects($this->any())->method('getCache')->will($this->returnValue($mockCache));

			// Expect the set method is called exactly once, even if the class is called multiple times.
			// This means that the internal array of the autoloader class is successfully used
		$mockCache->expects($this->once())->method('set');

		t3lib_autoloader::autoload($class);
		t3lib_autoloader::autoload($class);
	}

	/**
	 * @test
	 */
	public function autoloadReadsClassFileLocationFromCacheFileForClassThatRespectsExtbaseNamingScheme() {
		$extKey = $this->createFakeExtension();
		$extPath = PATH_site . "typo3temp/$extKey/";

		$pathSegment = 'Foo' . uniqid();
		$fileName = 'Bar' . uniqid();
		$class = 'Tx_' . $extKey . '_' . $pathSegment . '_' . $fileName;
		$file = $extPath . 'Classes/' . $pathSegment . '/' . $fileName . '.php';

		t3lib_div::mkdir_deep($extPath . 'Classes/' . $pathSegment);
		file_put_contents($file, "<?php\n\n\$foo = 'bar';\n\n?>");

		$mockCache = $this->getMock('t3lib_cache_frontend_AbstractFrontend', array('getIdentifier', 'set', 'get', 'getByTag', 'has', 'remove', 'flush', 'flushByTag', 'requireOnce'), array(), '', FALSE);
		$GLOBALS['typo3CacheManager'] = $this->getMock('t3lib_cache_Manager', array('getCache'));
		$GLOBALS['typo3CacheManager']->expects($this->any())->method('getCache')->will($this->returnValue($mockCache));

			// Expect the set method is called exactly once, even if the class is called multiple times.
			// This means that the internal array of the autoloader class is successfully used
		$mockCache->expects($this->once())
			->method('requireOnce')
			->will($this->returnValue(array(strtolower($class) => $file)));

		t3lib_autoloader::autoload($class);
		t3lib_autoloader::unregisterAutoloader();
		t3lib_autoloader::registerAutoloader();
		t3lib_autoloader::autoload($class);
	}
}
?>