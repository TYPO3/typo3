<?php

/*                                                                        *
 * This script is part of the TYPO3 project - inspiring people to share!  *
 *                                                                        *
 * TYPO3 is free software; you can redistribute it and/or modify it under *
 * the terms of the GNU General Public License version 2 as published by  *
 * the Free Software Foundation.                                          *
 *                                                                        *
 * This script is distributed in the hope that it will be useful, but     *
 * WITHOUT ANY WARRANTY; without even the implied warranty of MERCHAN-    *
 * TABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the GNU General      *
 * Public License for more details.                                       *
 *                                                                        */

/**
 * @package
 * @subpackage
 * @version $Id: DocbookGeneratorService.php 1734 2009-11-25 21:53:57Z stucki $
 */
/**
 * Class extending the docbook generator service for use in typo3 v4.
 *
 * Usage in TypoScript:
 *
 *

config.disableAllHeaderCode = 1
page = PAGE
page.10 = USER_INT
page.10.userFunc = Tx_Fluid_Compatibility_DocbookGeneratorService->userFunc

 * @internal
 */
class Tx_Fluid_Compatibility_DocbookGeneratorService extends Tx_Fluid_Service_DocbookGenerator {

	public function userFunc() {
		if (!class_exists('Tx_Extbase_Utility_ClassLoader')) {
			require(t3lib_extmgm::extPath('extbase') . 'Classes/Utility/ClassLoader.php');
		}

		$classLoader = new Tx_Extbase_Utility_ClassLoader();
		spl_autoload_register(array($classLoader, 'loadClass'));
		return $this->generateDocbook('Tx_Fluid_ViewHelpers');
	}
	protected function getClassNamesInNamespace($namespace) {
		$namespaceParts = explode('_', $namespace);
		if ($namespaceParts[count($namespaceParts) -1] == '') {

		}
		$classFilePathAndName = t3lib_extMgm::extPath(t3lib_div::camelCaseToLowerCaseUnderscored($namespaceParts[1])) . 'Classes/';
		$classFilePathAndName .= implode(array_slice($namespaceParts, 2, -1), '/') . '/';
		$classNames = array();
		$this->recursiveClassNameSearch($namespace, $classFilePathAndName, $classNames);

		sort($classNames);
		return $classNames;

	}

	private function recursiveClassNameSearch($namespace, $directory, &$classNames) {
		$dh = opendir($directory);
		while (($file = readdir($dh)) !== false) {
			if ($file == '.' || $file == '..' || $file == '.svn') continue;

			if (is_file($directory . $file)) {
				if (substr($file, 0, 8) == 'Abstract') continue;

				$classNames[] = $namespace . substr($file, 0, -4);
			} elseif (is_dir($directory . $file)) {
				$this->recursiveClassNameSearch($namespace . $file . '_' , $directory . $file . '/', $classNames);
			}
        }
        closedir($dh);
	}

	protected function instanciateViewHelper($className) {
		$objectFactory = t3lib_div::makeInstance('Tx_Fluid_Compatibility_ObjectFactory');
		return $objectFactory->create($className);
	}
}

?>