<?php
namespace TYPO3\CMS\Lang\Domain\Repository;

/*
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

use TYPO3\CMS\Core\Utility\ExtensionManagementUtility;

/**
 * Extension repository
 */
class ExtensionRepository {

	/**
	 * @var \TYPO3\CMS\Extbase\Object\ObjectManagerInterface
	 * @inject
	 */
	protected $objectManager;

	/**
	 * @var \TYPO3\CMS\Extensionmanager\Utility\ListUtility
	 * @inject
	 */
	protected $listUtility;

	/**
	 * @var array
	 */
	protected $extensions = array();

	/**
	 * Returns all objects of this repository
	 *
	 * @return array The extensions
	 */
	public function findAll() {
		if (empty($this->extensions)) {
			$extensions = $this->listUtility->getAvailableAndInstalledExtensionsWithAdditionalInformation();
			foreach ($extensions as $entry) {
				/** @var $extension \TYPO3\CMS\Lang\Domain\Model\Extension */
				$extension = $this->objectManager->get(
					\TYPO3\CMS\Lang\Domain\Model\Extension::class,
					$entry['key'],
					$entry['title'],
					$this->getExtensionIconWithPath($entry)
				);
				$extension->setVersionFromString($entry['version']);
				$extension->setIconWidth($entry['ext_icon_width']);
				$extension->setIconHeight($entry['ext_icon_height']);

				$this->extensions[$entry['key']] = $extension;
			}
			ksort($this->extensions);
		}
		return $this->extensions;
	}

	/**
	 * Counts all objects of this repository
	 *
	 * @return int The extension count
	 */
	public function countAll() {
		$extensions = $this->findAll();
		return count($extensions);
	}

	/**
	 * Find one extension by offset
	 *
	 * @param int $offset The offset
	 * @return TYPO3\CMS\Lang\Domain\Model\Extension The extension
	 */
	public function findOneByOffset($offset) {
		$extensions = $this->findAll();
		$extensions = array_values($extensions);
		$offset = (int)$offset;
		if (!empty($extensions[$offset])) {
			return $extensions[$offset];
		}
		return NULL;
	}

	/**
	 * Returns the extension icon
	 *
	 * @param array $extensionEntry
	 * @return string
	 */
	protected function getExtensionIconWithPath($extensionEntry) {
		$extensionIcon = $GLOBALS['TYPO3_LOADED_EXT'][$extensionEntry['key']]['ext_icon'];
		if (empty($extensionIcon)) {
			$extensionIcon = ExtensionManagementUtility::getExtensionIcon(PATH_site . $extensionEntry['siteRelPath'] . '/');
		}
		if (empty($extensionIcon)) {
			$extensionIcon = '/typo3/clear.gif';
		} else {
			$extensionIcon = '../' . $extensionEntry['siteRelPath'] . '/' . $extensionIcon;
		}
		return $extensionIcon;
	}

}
