<?php
namespace TYPO3\CMS\Workspaces\Service;

/***************************************************************
 * Copyright notice
 *
 * (c) 2013 Oliver Hader <oliver.hader@typo3.org>
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
 * A copy is found in the text file GPL.txt and important notices to the license
 * from the author is found in LICENSE.txt distributed with these scripts.
 *
 *
 * This script is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
 * GNU General Public License for more details.
 *
 * This copyright notice MUST APPEAR in all copies of the script!
 ***************************************************************/
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
