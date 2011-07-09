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

	protected $fakedExtensions = array();

	/**
	 * Creates a fake extension inside typo3temp/. No configuration is created,
	 * just the folder, plus the extension is registered in $TYPO3_LOADED_EXT
	 *
	 * The extension key you specify might be changed if the extension already exists.
	 *
	 * @return string The extension key
	 */
	protected function createFakeExtension($key = '') {
		if ($key == '') {
			$key = uniqid();
		}
		do {
			$extKey = 'autoloadertest' . $key;
			$absExtPath = PATH_site . "typo3temp/$extKey/";
			$absRelPath = "typo3temp/$extKey/";
			$key = uniqid();
		} while(file_exists($absExtPath));
		t3lib_div::mkdir($absExtPath);

		$GLOBALS['TYPO3_LOADED_EXT'][$extKey] = array(
			'siteRelPath' => $absRelPath
		);

		$this->fakedExtensions[] = $extKey;

			// reset extension key map
		t3lib_extMgm::clearExtensionKeyMap();

		return $extKey;
	}

	/**
	 * @test
	 */
	public function extensionAutoloadFileIsIncludedIfAvailable() {
			// expect this exception that is thrown in the included "class" file we generate
		$this->setExpectedException('RuntimeException', '', 1310203812);

		$extKey = $this->createFakeExtension();
		$extPath = PATH_site . "typo3temp/$extKey/";
		$autoloaderFile = $extPath . "ext_autoload.php";
		touch($autoloaderFile);

		$class = strtolower("tx_${extKey}_" . uniqid());
		$file = $extPath . uniqid() . '.php';
		$testVarName = 'autoloaderTest' . uniqid();

		file_put_contents($file, "<?php\n\nthrow new RuntimeException('', 1310203812);\n\n?>");
		file_put_contents($autoloaderFile, "<?php\n\nreturn array(\n    '$class' => '$file'\n);\n\n?>");

		t3lib_autoloader::autoload($class);
	}
}


?>
