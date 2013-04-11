<?php
namespace TYPO3\CMS\Core\Tests\Unit\Core;

/***************************************************************
 * Copyright notice
 *
 * (c) 2011-2013 Andreas Wolf <andreas.wolf@ikt-werk.de>
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
 * Testcase for TYPO3\CMS\Core\Core\ClassLoader
 *
 * @author Andreas Wolf <andreas.wolf@ikt-werk.de>
 */
class ClassLoaderTest extends \TYPO3\CMS\Core\Tests\UnitTestCase {

	/**
	 * @var array Backup of typo3CacheManager
	 */
	protected $typo3CacheManager = NULL;

	/**
	 * @var array Register of temporary extensions in typo3temp
	 */
	protected $fakedExtensions = array();

	/**
	 * Fix a race condition that GeneralUtility is not available
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
		\TYPO3\CMS\Core\Core\ClassLoader::unregisterAutoloader();
		\TYPO3\CMS\Core\Core\ClassLoader::registerAutoloader();
		foreach ($this->fakedExtensions as $extension) {
			\TYPO3\CMS\Core\Utility\GeneralUtility::rmdir(PATH_site . 'typo3temp/' . $extension, TRUE);
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
		$absExtPath = PATH_site . 'typo3temp/' . $extKey . '/';
		$relPath = 'typo3temp/' . $extKey . '/';
		\TYPO3\CMS\Core\Utility\GeneralUtility::mkdir($absExtPath);
		$GLOBALS['TYPO3_LOADED_EXT'][$extKey] = array(
			'siteRelPath' => $relPath
		);
		$GLOBALS['TYPO3_CONF_VARS']['EXT']['extListArray'][] = $extKey;
		$this->fakedExtensions[] = $extKey;
		\TYPO3\CMS\Core\Utility\ExtensionManagementUtility::clearExtensionKeyMap();
		return $extKey;
	}

	/**
	 * @test
	 */
	public function unregisterAndRegisterAgainDoesNotFatal() {
		\TYPO3\CMS\Core\Core\ClassLoader::unregisterAutoloader();
		\TYPO3\CMS\Core\Core\ClassLoader::registerAutoloader();
			// If this fatals the autoload re registering went wrong
		\TYPO3\CMS\Core\Utility\GeneralUtility::makeInstance('TYPO3\\CMS\\Core\\TimeTracker\\NullTimeTracker');
	}

	/**
	 * @test
	 */
	public function unregisterAutoloaderSetsCacheEntryWithT3libNoTags() {
		$mockCache = $this->getMock('TYPO3\\CMS\\Core\\Cache\\Frontend\\AbstractFrontend', array('getIdentifier', 'set', 'get', 'getByTag', 'has', 'remove', 'flush', 'flushByTag', 'requireOnce'), array(), '', FALSE);
		$mockCache->expects($this->once())->method('set')->with($this->anything(), $this->anything(), array());
		$GLOBALS['typo3CacheManager'] = $this->getMock('TYPO3\\CMS\\Core\\Cache\\CacheManager', array('getCache'));
		$GLOBALS['typo3CacheManager']->expects($this->any())->method('getCache')->will($this->returnValue($mockCache));
		\TYPO3\CMS\Core\Core\ClassLoader::unregisterAutoloader();
	}

	/**
	 * @test
	 * @expectedException \RuntimeException
	 */
	public function autoloadFindsClassFileDefinedInExtAutoloadFile() {
		$extKey = $this->createFakeExtension();
		$extPath = PATH_site . 'typo3temp/' . $extKey . '/';
		$autoloaderFile = $extPath . 'ext_autoload.php';
		$class = strtolower('tx_{' . $extKey . '}_' . uniqid(''));
		$file = $extPath . uniqid('') . '.php';
		file_put_contents($file, '<?php' . LF . 'throw new \\RuntimeException(\'\', 1310203812);' . LF . '?>');
		file_put_contents($autoloaderFile, '<?php' . LF . 'return array(\'' . $class . '\' => \'' . $file . '\');' . LF . '?>');
			// Inject a dummy for the core_phpcode cache to force the autoloader
			// to re calculate the registry
		$mockCache = $this->getMock('TYPO3\\CMS\\Core\\Cache\\Frontend\\AbstractFrontend', array('getIdentifier', 'set', 'get', 'getByTag', 'has', 'remove', 'flush', 'flushByTag', 'requireOnce'), array(), '', FALSE);
		$GLOBALS['typo3CacheManager'] = $this->getMock('TYPO3\\CMS\\Core\\Cache\\CacheManager', array('getCache'));
		$GLOBALS['typo3CacheManager']->expects($this->any())->method('getCache')->will($this->returnValue($mockCache));
			// Re-initialize autoloader registry to force it to recognize the new extension
		\TYPO3\CMS\Core\Core\ClassLoader::unregisterAutoloader();
		\TYPO3\CMS\Core\Core\ClassLoader::registerAutoloader();
			// Expect the exception of the file to be thrown
		\TYPO3\CMS\Core\Core\ClassLoader::autoload($class);
	}

	/**
	 * @test
	 */
	public function unregisterAutoloaderWritesLowerCasedClassFileToCache() {
		$extKey = $this->createFakeExtension();
		$extPath = PATH_site . 'typo3temp/' . $extKey . '/';
		$autoloaderFile = $extPath . 'ext_autoload.php';
			// A case sensitive key (FooBar) in ext_autoload file
		$class = 'tx_{' . $extKey . '}_' . uniqid('FooBar');
		$file = $extPath . uniqid('') . '.php';
		file_put_contents($autoloaderFile, '<?php' . LF . 'return array(\'' . $class . '\' => \'' . $file . '\');' . LF . '?>');
			// Inject a dummy for the core_phpcode cache to force the autoloader
			// to re calculate the registry
		$mockCache = $this->getMock('TYPO3\\CMS\\Core\\Cache\\Frontend\\AbstractFrontend', array('getIdentifier', 'set', 'get', 'getByTag', 'has', 'remove', 'flush', 'flushByTag', 'requireOnce'), array(), '', FALSE);
		$GLOBALS['typo3CacheManager'] = $this->getMock('TYPO3\\CMS\\Core\\Cache\\CacheManager', array('getCache'));
		$GLOBALS['typo3CacheManager']->expects($this->any())->method('getCache')->will($this->returnValue($mockCache));
			// Expect that the lower case version of the class name is written to cache
		$mockCache->expects($this->at(2))->method('set')->with($this->anything(), $this->stringContains(strtolower($class), FALSE));
			// Re-initialize autoloader registry to force it to recognize the new extension
		\TYPO3\CMS\Core\Core\ClassLoader::unregisterAutoloader();
		\TYPO3\CMS\Core\Core\ClassLoader::registerAutoloader();
		\TYPO3\CMS\Core\Core\ClassLoader::unregisterAutoloader();
	}

	/**
	 * @test
	 * @expectedException \RuntimeException
	 */
	public function autoloadFindsClassFileIfExtAutoloadEntryIsCamelCased() {
		$extKey = $this->createFakeExtension();
		$extPath = PATH_site . 'typo3temp/' . $extKey . '/';
			// A case sensitive key (FooBar) in ext_autoload file
		$class = 'tx_{' . $extKey . '}_' . uniqid('FooBar');
		$file = $extPath . uniqid('') . '.php';
		file_put_contents($file, '<?php' . LF . 'throw new \\RuntimeException(\'\', 1336756850);' . LF . '?>');
		$extAutoloadFile = $extPath . 'ext_autoload.php';
		file_put_contents($extAutoloadFile, '<?php' . LF . 'return array(\'' . $class . '\' => \'' . $file . '\');' . LF . '?>');
			// Inject cache and return false, so autoloader is forced to read ext_autoloads from extensions
		$mockCache = $this->getMock('TYPO3\\CMS\\Core\\Cache\\Frontend\\AbstractFrontend', array('getIdentifier', 'set', 'get', 'getByTag', 'has', 'remove', 'flush', 'flushByTag', 'requireOnce'), array(), '', FALSE);
		$GLOBALS['typo3CacheManager'] = $this->getMock('TYPO3\\CMS\\Core\\Cache\\CacheManager', array('getCache'));
		$GLOBALS['typo3CacheManager']->expects($this->any())->method('getCache')->will($this->returnValue($mockCache));
		$mockCache->expects($this->any())->method('has')->will($this->returnValue(FALSE));
			// Re-initialize autoloader registry to force it to recognize the new extension
		\TYPO3\CMS\Core\Core\ClassLoader::unregisterAutoloader();
		\TYPO3\CMS\Core\Core\ClassLoader::registerAutoloader();
		\TYPO3\CMS\Core\Core\ClassLoader::autoload($class);
	}

	/**
	 * @test
	 * @expectedException \RuntimeException
	 */
	public function autoloadFindsCamelCasedClassFileIfExtAutoloadEntryIsReadLowerCasedFromCache() {
		$extKey = $this->createFakeExtension();
		$extPath = PATH_site . 'typo3temp/' . $extKey . '/';
			// A case sensitive key (FooBar) in ext_autoload file
		$class = 'tx_{' . $extKey . '}_' . uniqid('FooBar');
		$file = $extPath . uniqid('') . '.php';
		file_put_contents($file, '<?php' . LF . 'throw new \RuntimeException(\'\', 1336756850);' . LF . '?>');
			// Inject cache mock and let the cache entry return the lowercased class name as key
		$mockCache = $this->getMock('TYPO3\\CMS\\Core\\Cache\\Frontend\\AbstractFrontend', array('getIdentifier', 'set', 'get', 'getByTag', 'has', 'remove', 'flush', 'flushByTag', 'requireOnce'), array(), '', FALSE);
		$GLOBALS['typo3CacheManager'] = $this->getMock('TYPO3\\CMS\\Core\\Cache\\CacheManager', array('getCache'));
		$GLOBALS['typo3CacheManager']->expects($this->any())->method('getCache')->will($this->returnValue($mockCache));
		$mockCache->expects($this->any())->method('has')->will($this->returnValue(TRUE));
		$mockCache->expects($this->once())->method('requireOnce')->will($this->returnValue(array(array(strtolower($class) => $file))));
			// Re-initialize autoloader registry to force it to recognize the new extension
		\TYPO3\CMS\Core\Core\ClassLoader::unregisterAutoloader();
		\TYPO3\CMS\Core\Core\ClassLoader::registerAutoloader();
		\TYPO3\CMS\Core\Core\ClassLoader::autoload($class);
	}

	/**
	 * @test
	 * @expectedException \RuntimeException
	 */
	public function autoloadFindsClassFileThatRespectsExtbaseNamingSchemeWithoutExtAutoloadFile() {
		$extKey = $this->createFakeExtension();
		$extPath = PATH_site . 'typo3temp/' . $extKey . '/';
			// Create a class named Tx_Extension_Foo123_Bar456
			// to find file extension/Classes/Foo123/Bar456.php
		$pathSegment = 'Foo' . uniqid();
		$fileName = 'Bar' . uniqid();
		$class = 'Tx_' . ucfirst($extKey) . '_' . $pathSegment . '_' . $fileName;

		$file = $extPath . 'Classes/' . $pathSegment . '/' . $fileName . '.php';
		\TYPO3\CMS\Core\Utility\GeneralUtility::mkdir_deep($extPath . 'Classes/' . $pathSegment);
		file_put_contents($file, '<?php' . LF . 'throw new \\RuntimeException(\'\', 1310203813);' . LF . '?>');
			// Inject a dummy for the core_phpcode cache to cache
			// the calculated cache entry to a dummy cache
		$mockCache = $this->getMock('TYPO3\\CMS\\Core\\Cache\\Frontend\\AbstractFrontend', array('getIdentifier', 'set', 'get', 'getByTag', 'has', 'remove', 'flush', 'flushByTag', 'requireOnce'), array(), '', FALSE);
		$GLOBALS['typo3CacheManager'] = $this->getMock('TYPO3\\CMS\\Core\\Cache\\CacheManager', array('getCache'));
		$GLOBALS['typo3CacheManager']->expects($this->any())->method('getCache')->will($this->returnValue($mockCache));
			// Expect the exception of the file to be thrown
		\TYPO3\CMS\Core\Core\ClassLoader::autoload($class);
	}

	/**
	 * @test
	 */
	public function unregisterAutoloaderWritesClassFileThatRespectsExtbaseNamingSchemeToCacheFile() {
		$extKey = $this->createFakeExtension();
		$extPath = PATH_site . 'typo3temp/' . $extKey . '/';
		$pathSegment = 'Foo' . uniqid();
		$fileName = 'Bar' . uniqid();
		$class = 'Tx_' . $extKey . '_' . $pathSegment . '_' . $fileName;
		$file = $extPath . 'Classes/' . $pathSegment . '/' . $fileName . '.php';
		\TYPO3\CMS\Core\Utility\GeneralUtility::mkdir_deep($extPath . 'Classes/' . $pathSegment);
		file_put_contents($file, '<?php' . LF . '$foo = \'bar\';' . LF . '?>');
		$mockCache = $this->getMock('TYPO3\\CMS\\Core\\Cache\\Frontend\\AbstractFrontend', array('getIdentifier', 'set', 'get', 'getByTag', 'has', 'remove', 'flush', 'flushByTag', 'requireOnce'), array(), '', FALSE);
		$GLOBALS['typo3CacheManager'] = $this->getMock('TYPO3\\CMS\\Core\\Cache\\CacheManager', array('getCache'));
		$GLOBALS['typo3CacheManager']->expects($this->any())->method('getCache')->will($this->returnValue($mockCache));
			// Expect that an entry to the cache is written containing the newly found class
		$mockCache->expects($this->once())->method('set')->with($this->anything(), $this->stringContains(strtolower($class), $this->anything()));
		\TYPO3\CMS\Core\Core\ClassLoader::autoload($class);
		\TYPO3\CMS\Core\Core\ClassLoader::unregisterAutoloader();
	}

	/**
	 * @test
	 */
	public function unregisterAutoloaderWritesClassFileLocationOfClassRespectingExtbaseNamingSchemeToCacheFile() {
		$extKey = $this->createFakeExtension();
		$extPath = PATH_site . 'typo3temp/' . $extKey . '/';
		$pathSegment = 'Foo' . uniqid();
		$fileName = 'Bar' . uniqid();
		$class = 'Tx_' . $extKey . '_' . $pathSegment . '_' . $fileName;
		$file = $extPath . 'Classes/' . $pathSegment . '/' . $fileName . '.php';
		\TYPO3\CMS\Core\Utility\GeneralUtility::mkdir_deep($extPath . 'Classes/' . $pathSegment);
		file_put_contents($file, '<?php' . LF . '$foo = \'bar\';' . LF . '?>');
		$mockCache = $this->getMock('TYPO3\\CMS\\Core\\Cache\\Frontend\\AbstractFrontend', array('getIdentifier', 'set', 'get', 'getByTag', 'has', 'remove', 'flush', 'flushByTag', 'requireOnce'), array(), '', FALSE);
		$GLOBALS['typo3CacheManager'] = $this->getMock('TYPO3\\CMS\\Core\\Cache\\CacheManager', array('getCache'));
		$GLOBALS['typo3CacheManager']->expects($this->any())->method('getCache')->will($this->returnValue($mockCache));
			// Expect that an entry to the cache is written containing the newly found class
		$mockCache->expects($this->once())->method('set')->with($this->anything(), $this->stringContains(strtolower($file), $this->anything()));
		\TYPO3\CMS\Core\Core\ClassLoader::autoload($class);
		\TYPO3\CMS\Core\Core\ClassLoader::unregisterAutoloader();
	}

	/**
	 * @test
	 * @expectedException \RuntimeException
	 */
	public function autoloadFindsClassFileThatRespectsExtbaseNamingSchemeWithNamespace() {
		$extKey = $this->createFakeExtension();
		$extPath = PATH_site . 'typo3temp/' . $extKey . '/';
			// Create a class named \Tx\Extension\Foo123\Bar456
			// to find file extension/Classes/Foo123/Bar456.php
		$pathSegment = 'Foo' . uniqid();
		$fileName = 'Bar' . uniqid();
		$namespacedClass = '\\Vendor\\' . ucfirst($extKey) . '\\' . $pathSegment . '\\' . $fileName;
		$file = $extPath . 'Classes/' . $pathSegment . '/' . $fileName . '.php';
		\TYPO3\CMS\Core\Utility\GeneralUtility::mkdir_deep($extPath . 'Classes/' . $pathSegment);
		file_put_contents($file, '<?php' . LF . 'throw new \\RuntimeException(\'\', 1342800577);' . LF . '?>');
			// Re-initialize autoloader registry to force it to recognize the new extension
		\TYPO3\CMS\Core\Core\ClassLoader::unregisterAutoloader();
		\TYPO3\CMS\Core\Core\ClassLoader::registerAutoloader();
			// Expect the exception of the file to be thrown
		\TYPO3\CMS\Core\Core\ClassLoader::autoload($namespacedClass);
	}

	/**
	 * @test
	 */
	public function unregisterAutoloaderWritesClassFileLocationOfClassRespectingExtbaseNamingSchemeWithNamespaceToCacheFile() {
		$extKey = $this->createFakeExtension();
		$extPath = PATH_site . 'typo3temp/' . $extKey . '/';
		$pathSegment = 'Foo' . uniqid();
		$fileName = 'Bar' . uniqid();
		$namespacedClass = '\\Tx\\' . $extKey . '\\' . $pathSegment . '\\' . $fileName;
		$file = $extPath . 'Classes/' . $pathSegment . '/' . $fileName . '.php';
		\TYPO3\CMS\Core\Utility\GeneralUtility::mkdir_deep($extPath . 'Classes/' . $pathSegment);
		file_put_contents($file, "<?php\n\n\$foo = 'bar';\n\n?>");
		$mockCache = $this->getMock('TYPO3\\CMS\\Core\\Cache\\Frontend\\AbstractFrontend', array('getIdentifier', 'set', 'get', 'getByTag', 'has', 'remove', 'flush', 'flushByTag', 'requireOnce'), array(), '', FALSE);
		$GLOBALS['typo3CacheManager'] = $this->getMock('TYPO3\\CMS\\Core\\Cache\\CacheManager', array('getCache'));
		$GLOBALS['typo3CacheManager']->expects($this->any())->method('getCache')->will($this->returnValue($mockCache));
			// Expect that an entry to the cache is written containing the newly found class
		$mockCache->expects($this->once())->method('set')->with($this->anything(), $this->stringContains(strtolower($file), $this->anything()));
		\TYPO3\CMS\Core\Core\ClassLoader::autoload($namespacedClass);
		\TYPO3\CMS\Core\Core\ClassLoader::unregisterAutoloader();
	}

	/**
	 * @test
	 */
	public function checkClassNamesNotExtbaseSchemePassAutoloaderUntouched() {
		$class = '\\Symfony\\Foo\\Bar';
		$this->assertNull(\TYPO3\CMS\Core\Core\ClassLoader::getClassPathByRegistryLookup($class));
	}

	/**
	 * @test
	 */
	public function checkAutoloaderSetsNamespacedClassnamesInExtAutoloadAreWrittenToCache() {
		$extKey = $this->createFakeExtension();
		$extPath = PATH_site . 'typo3temp/' . $extKey . '/';
		$pathSegment = 'Foo' . uniqid();
		$fileName = 'Bar' . uniqid();
		$autoloaderFile = $extPath . 'ext_autoload.php';
			// A case sensitive key (FooBar) in ext_autoload file
		$namespacedClass = '\\Tx\\' . $extKey . '\\' . $pathSegment . '\\' . $fileName;
		$classFile = 'EXT:someExt/Classes/Foo/bar.php';
		file_put_contents($autoloaderFile, '<?php' . LF . 'return ' . var_export(array($namespacedClass => $classFile), TRUE) . ';' . LF . '?>');
			// Inject a dummy for the core_phpcode cache to force the autoloader
			// to re calculate the registry
		$mockCache = $this->getMock('TYPO3\\CMS\\Core\\Cache\\Frontend\\AbstractFrontend', array('getIdentifier', 'set', 'get', 'getByTag', 'has', 'remove', 'flush', 'flushByTag', 'requireOnce'), array(), '', FALSE);
		$GLOBALS['typo3CacheManager'] = $this->getMock('TYPO3\\CMS\\Core\\Cache\\CacheManager', array('getCache'));
		$GLOBALS['typo3CacheManager']->expects($this->any())->method('getCache')->will($this->returnValue($mockCache));
			// Expect that the lower case version of the class name is written to cache
		$mockCache->expects($this->at(2))->method('set')->with($this->anything(), $this->stringContains(strtolower(addslashes($namespacedClass)), FALSE));
			// Re-initialize autoloader registry to force it to recognize the new extension
		\TYPO3\CMS\Core\Core\ClassLoader::unregisterAutoloader();
		\TYPO3\CMS\Core\Core\ClassLoader::registerAutoloader();
		\TYPO3\CMS\Core\Core\ClassLoader::unregisterAutoloader();
	}
}

?>