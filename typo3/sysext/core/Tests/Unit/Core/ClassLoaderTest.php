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

use org\bovigo\vfs\vfsStream;

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
	 * @var \TYPO3\CMS\Core\Core\ClassLoader
	 */
	protected $classLoader;

	/**
	 * @var \TYPO3\CMS\Core\Core\ClassAliasMap
	 */
	protected $orinalClassAliasMap;

	/**
	 * Test flag used in in this test case
	 *
	 * @var boolean
	 */
	public static $testClassWasLoaded = FALSE;

	/**
	 * Fix a race condition that GeneralUtility is not available
	 * during tearDown if fiddling with the autoloader where
	 * backupGlobals is not set up again yet
	 */
	public function setUp() {
		vfsStream::setup('Test');

		mkdir('vfs://Test/Packages/Application/Acme.MyApp/Classes/', 0770, TRUE);
		file_put_contents('vfs://Test/Packages/Application/Acme.MyApp/composer.json', '{"name": "acme/myapp", "type": "flow-test"}');
		$package1 = new \TYPO3\Flow\Package\Package($this->getMock('TYPO3\Flow\Package\PackageManager'), 'Acme.MyApp', 'vfs://Test/Packages/Application/Acme.MyApp/', 'Classes');

		mkdir('vfs://Test/Packages/Application/Acme.MyAppAddon/Classes/', 0770, TRUE);
		file_put_contents('vfs://Test/Packages/Application/Acme.MyAppAddon/composer.json', '{"name": "acme/myappaddon", "type": "flow-test"}');
		$package2 = new \TYPO3\Flow\Package\Package($this->getMock('TYPO3\Flow\Package\PackageManager'), 'Acme.MyAppAddon', 'vfs://Test/Packages/Application/Acme.MyAppAddon/', 'Classes');

		$mockClassAliasMap = $this->getMock('TYPO3\\CMS\\Core\\Core\\ClassAliasMap', array('setPackagesButDontBuildMappingFilesReturnClassNameToAliasMappingInstead', 'buildMappingFiles'), array(), '', FALSE);
		$mockClassAliasMap->expects($this->any())->method('setPackagesButDontBuildMappingFilesReturnClassNameToAliasMappingInstead')->will($this->returnValue(array()));

		$this->orinalClassAliasMap = \TYPO3\CMS\Core\Core\Bootstrap::getInstance()->getEarlyInstance('TYPO3\\CMS\\Core\\Core\\ClassAliasMap');
		$this->classLoader = new \TYPO3\CMS\Core\Core\ClassLoader(\TYPO3\CMS\Core\Core\Bootstrap::getInstance()->getApplicationContext());
		$this->classLoader->injectClassAliasMap($mockClassAliasMap);
		$this->classLoader->setPackages(array('Acme.MyApp' => $package1, 'Acme.MyAppAddon' => $package2));
	}

	/**
	 * The class alias map is kept static in the class loader for legacy reasons
	 * and has to be reset after mocking.
	 */
	public function tearDown() {
		$this->classLoader->injectClassAliasMap($this->orinalClassAliasMap);
		parent::tearDown();
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
		$this->fakedExtensions[] = $extKey;
		\TYPO3\CMS\Core\Utility\ExtensionManagementUtility::clearExtensionKeyMap();
		return $extKey;
	}

	/**
	 * Checks if the package autoloader loads classes from subdirectories.
	 *
	 * @test
	 */
	public function classesFromSubDirectoriesAreLoaded() {
		mkdir('vfs://Test/Packages/Application/Acme.MyApp/Classes/Acme/MyApp/SubDirectory', 0770, TRUE);
		file_put_contents('vfs://Test/Packages/Application/Acme.MyApp/Classes/Acme/MyApp/SubDirectory/ClassInSubDirectory.php', '<?php ' . __CLASS__ . '::$testClassWasLoaded = TRUE; ?>');

		self::$testClassWasLoaded = FALSE;
		$this->classLoader->loadClass('Acme\MyApp\SubDirectory\ClassInSubDirectory');
		$this->assertTrue(self::$testClassWasLoaded);
	}

	/**
	 * @test
	 */
	public function classesFromDeeplyNestedSubDirectoriesAreLoaded() {
		mkdir('vfs://Test/Packages/Application/Acme.MyApp/Classes/Acme/MyApp/SubDirectory/A/B/C/D', 0770, TRUE);
		file_put_contents('vfs://Test/Packages/Application/Acme.MyApp/Classes/Acme/MyApp/SubDirectory/A/B/C/D/E.php', '<?php ' . __CLASS__ . '::$testClassWasLoaded = TRUE; ?>');

		self::$testClassWasLoaded = FALSE;
		$this->classLoader->loadClass('Acme\MyApp\SubDirectory\A\B\C\D\E');
		$this->assertTrue(self::$testClassWasLoaded);
	}

	/**
	 * Checks if the package autoloader loads classes from packages that match a
	 * substring of another package (e.g. TYPO3CR vs TYPO3).
	 *
	 * @test
	 */
	public function classesFromSubMatchingPackagesAreLoaded() {
		mkdir('vfs://Test/Packages/Application/Acme.MyAppAddon/Classes/Acme/MyAppAddon', 0770, TRUE);
		file_put_contents('vfs://Test/Packages/Application/Acme.MyAppAddon/Classes/Acme/MyAppAddon/Class.php', '<?php ' . __CLASS__ . '::$testClassWasLoaded = TRUE; ?>');

		self::$testClassWasLoaded = FALSE;
		$this->classLoader->loadClass('Acme\MyAppAddon\Class');
		$this->assertTrue(self::$testClassWasLoaded);
	}

	/**
	 * Checks if the package autoloader loads classes from subdirectories.
	 *
	 * @test
	 */
	public function classesWithUnderscoresAreLoaded() {
		mkdir('vfs://Test/Packages/Application/Acme.MyApp/Classes/Acme/MyApp', 0770, TRUE);
		file_put_contents('vfs://Test/Packages/Application/Acme.MyApp/Classes/Acme/MyApp/Foo.php', '<?php ' . __CLASS__ . '::$testClassWasLoaded = TRUE; ?>');

		self::$testClassWasLoaded = FALSE;
		$this->classLoader->loadClass('Acme\MyApp_Foo');
		$this->assertTrue(self::$testClassWasLoaded);
	}

	/**
	 * Checks if the package autoloader loads classes from subdirectories with underscores.
	 *
	 * @test
	 */
	public function namespaceWithUnderscoresAreLoaded() {
		mkdir('vfs://Test/Packages/Application/Acme.MyApp/Classes/Acme/MyApp/My_Underscore', 0770, TRUE);
		file_put_contents('vfs://Test/Packages/Application/Acme.MyApp/Classes/Acme/MyApp/My_Underscore/Foo.php', '<?php ' . __CLASS__ . '::$testClassWasLoaded = TRUE; ?>');

		self::$testClassWasLoaded = FALSE;
		$this->classLoader->loadClass('Acme\MyApp\My_Underscore\Foo');
		$this->assertTrue(self::$testClassWasLoaded);
	}

	/**
	 * Checks if the package autoloader loads classes from subdirectories.
	 *
	 * @test
	 */
	public function classesWithOnlyUnderscoresAreLoaded() {
		mkdir('vfs://Test/Packages/Application/Acme.MyApp/Classes/Acme/MyApp', 0770, TRUE);
		file_put_contents('vfs://Test/Packages/Application/Acme.MyApp/Classes/Acme/MyApp/UnderscoredOnly.php', '<?php ' . __CLASS__ . '::$testClassWasLoaded = TRUE; ?>');

		self::$testClassWasLoaded = FALSE;
		$this->classLoader->loadClass('Acme_MyApp_UnderscoredOnly');
		$this->assertTrue(self::$testClassWasLoaded);
	}

	/**
	 * @test
	 */
	public function classesWithLeadingBackslashAreLoaded() {
		mkdir('vfs://Test/Packages/Application/Acme.MyApp/Classes/Acme/MyApp', 0770, TRUE);
		file_put_contents('vfs://Test/Packages/Application/Acme.MyApp/Classes/Acme/MyApp/WithLeadingBackslash.php', '<?php ' . __CLASS__ . '::$testClassWasLoaded = TRUE; ?>');

		self::$testClassWasLoaded = FALSE;
		$this->classLoader->loadClass('\Acme\MyApp\WithLeadingBackslash');
		$this->assertTrue(self::$testClassWasLoaded);
	}

}
