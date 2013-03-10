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
 * config.disableAllHeaderCode = 1
 * page = PAGE
 * page.10 = USER_INT
 * page.10.userFunc = \TYPO3\CMS\Fluid\Compatibility\DocbookGeneratorService->userFunc
 *
 * @internal
 */
class DocbookGeneratorService extends \TYPO3\CMS\Fluid\Service\DocbookGenerator {

	/**
	 *
	 * @var \TYPO3\CMS\Extbase\Object\ObjectManagerInterface
	 */
	protected $objectManager;

	/**
	 * User function
	 *
	 * @return string
	 */
	public function userFunc() {
		$this->objectManager = \TYPO3\CMS\Core\Utility\GeneralUtility::makeInstance('TYPO3\\CMS\\Extbase\\Object\\ObjectManager');
		$this->injectDocCommentParser($this->objectManager->get('TYPO3\\CMS\\Extbase\\Reflection\\DocCommentParser'));
		$this->injectReflectionService($this->objectManager->get('TYPO3\\CMS\\Extbase\\Reflection\\ReflectionService'));
		return $this->generateDocbook('TYPO3\CMS\Fluid\ViewHelpers');
	}

	/**
	 * Get class names within namespace
	 *
	 * @param string $namespace
	 * @return array
	 */
	protected function getClassNamesInNamespace($namespace) {
		$namespaceParts = explode('\\', $namespace);
		if ($namespaceParts[count($namespaceParts) - 1] == '') {
		}
		$classFilePathAndName = \TYPO3\CMS\Core\Utility\ExtensionManagementUtility::extPath(\TYPO3\CMS\Core\Utility\GeneralUtility::camelCaseToLowerCaseUnderscored($namespaceParts[2])) . 'Classes/';
		$classFilePathAndName .= implode(array_slice($namespaceParts, 3, -1), '/') . '/';
		$classNames = array();
		$this->recursiveClassNameSearch($namespace, $classFilePathAndName, $classNames);
		sort($classNames);
		return $classNames;
	}

	/**
	 * Search recursivly class names within namespace
	 *
	 * @param string $namespace
	 * @param string $directory
	 * @param array $classNames
	 * @return void
	 */
	private function recursiveClassNameSearch($namespace, $directory, &$classNames) {

		$dh = opendir($directory);
		$counter = 0;
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
				$this->recursiveClassNameSearch($namespace . $file . '\\', $directory . $file . '/', $classNames);
			}
		}
		closedir($dh);
	}

}

?>