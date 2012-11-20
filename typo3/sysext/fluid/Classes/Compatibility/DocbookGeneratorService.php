<?php
namespace TYPO3\CMS\Fluid\Compatibility;

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
 * Class extending the docbook generator service for use in typo3 v4.
 *
 * Usage in TypoScript:
 *
 *
 *
 * config.disableAllHeaderCode = 1
 * page = PAGE
 * page.10 = USER_INT
 * page.10.userFunc = Tx_Fluid_Compatibility_DocbookGeneratorService->userFunc
 *
 * @internal
 */
class DocbookGeneratorService extends \TYPO3\CMS\Fluid\Service\DocbookGenerator {

	public function userFunc() {
		if (!class_exists('TYPO3\\CMS\\Extbase\\Utility\\ClassLoaderUtility')) {
			require \t3lib_extmgm::extPath('extbase') . 'Classes/Utility/ClassLoader.php';
		}
		$classLoader = new \TYPO3\CMS\Extbase\Utility\ClassLoaderUtility();
		spl_autoload_register(array($classLoader, 'loadClass'));
		return $this->generateDocbook('Tx_Fluid_ViewHelpers');
	}

	protected function getClassNamesInNamespace($namespace) {
		$namespaceParts = explode('_', $namespace);
		if ($namespaceParts[count($namespaceParts) - 1] == '') {
		}
		$classFilePathAndName = \TYPO3\CMS\Core\Utility\ExtensionManagementUtility::extPath(\TYPO3\CMS\Core\Utility\GeneralUtility::camelCaseToLowerCaseUnderscored($namespaceParts[1])) . 'Classes/';
		$classFilePathAndName .= implode(array_slice($namespaceParts, 2, -1), '/') . '/';
		$classNames = array();
		$this->recursiveClassNameSearch($namespace, $classFilePathAndName, $classNames);
		sort($classNames);
		return $classNames;
	}

	private function recursiveClassNameSearch($namespace, $directory, &$classNames) {
		$dh = opendir($directory);
		while (($file = readdir($dh)) !== FALSE) {
			if ($file == '.' || $file == '..' || $file == '.svn') {
				continue;
			}
			if (is_file($directory . $file)) {
				if (substr($file, 0, 8) == 'Abstract') {
					continue;
				}
				$classNames[] = $namespace . substr($file, 0, -4);
			} elseif (is_dir($directory . $file)) {
				$this->recursiveClassNameSearch($namespace . $file . '_', $directory . $file . '/', $classNames);
			}
		}
		closedir($dh);
	}

	protected function instanciateViewHelper($className) {
		$objectFactory = \TYPO3\CMS\Core\Utility\GeneralUtility::makeInstance('TYPO3\\CMS\\Extbase\\Object\\ObjectManager');
		return $objectFactory->create($className);
	}
}

?>