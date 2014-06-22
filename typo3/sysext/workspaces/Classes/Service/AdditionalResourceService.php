<?php
namespace TYPO3\CMS\Workspaces\Service;

/**
 * This file is part of the TYPO3 CMS project.
 *
 * It is free software; you can redistribute it and/or modify it under
 * the terms of the GNU General Public License, either version 2
 * of the License, or any later version.
 *
 * For the full copyright and license information, please read the
 * LICENSE.txt file that was distributed with this source code.
 *
 * The TYPO3 project - inspiring people to share!
 */
/**
 * Service for additional columns in GridPanel
 *
 * @author Oliver Hader <oliver.hader@typo3.org>
 */
class AdditionalResourceService implements \TYPO3\CMS\Core\SingletonInterface {

	/**
	 * @var array
	 */
	protected $javaScriptResources = array();

	/**
	 * @var array
	 */
	protected $stylesheetResources = array();

	/**
	 * @return \TYPO3\CMS\Workspaces\Service\AdditionalResourceService
	 */
	static public function getInstance() {
		return self::getObjectManager()->get('TYPO3\\CMS\\Workspaces\\Service\\AdditionalResourceService');
	}

	/**
	 * @return \TYPO3\CMS\Extbase\Object\ObjectManager
	 */
	static public function getObjectManager() {
		return \TYPO3\CMS\Core\Utility\GeneralUtility::makeInstance('TYPO3\\CMS\\Extbase\\Object\\ObjectManager');
	}

	/**
	 * @param string $name
	 * @param string $resourcePath
	 * @return void
	 */
	public function addJavaScriptResource($name, $resourcePath) {
		$this->javaScriptResources[$name] = $this->resolvePath($resourcePath);
	}

	/**
	 * @param string $name
	 * @param string $resourcePath
	 * @return void
	 */
	public function addStylesheetResource($name, $resourcePath) {
		$this->stylesheetResources[$name] = $this->resolvePath($resourcePath);
	}

	/**
	 * @return array
	 */
	public function getJavaScriptResources() {
		return $this->javaScriptResources;
	}

	/**
	 * @return array
	 */
	public function getStyleSheetResources() {
		return $this->stylesheetResources;
	}

	/**
	 * @param string $resourcePath
	 * @return NULL|string
	 */
	protected function resolvePath($resourcePath) {
		$absoluteFilePath = \TYPO3\CMS\Core\Utility\GeneralUtility::getFileAbsFileName($resourcePath);
		$absolutePath = dirname($absoluteFilePath);
		$fileName = basename($absoluteFilePath);

		return \TYPO3\CMS\Core\Utility\PathUtility::getRelativePathTo($absolutePath) . $fileName;
	}

}
