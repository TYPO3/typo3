<?php
namespace TYPO3\CMS\About\Domain\Repository;

/***************************************************************
 *  Copyright notice
 *
 *  (c) 2012-2013 Felix Kopp <felix@phorax.com>
 *
 *  All rights reserved
 *
 *  This script is part of the TYPO3 project. The TYPO3 project is
 *  free software; you can redistribute it and/or modify
 *  it under the terms of the GNU General Public License as published by
 *  the Free Software Foundation; either version 3 of the License, or
 *  (at your option) any later version.
 *
 *  The GNU General Public License can be found at
 *  http://www.gnu.org/copyleft/gpl.html.
 *
 *  This script is distributed in the hope that it will be useful,
 *  but WITHOUT ANY WARRANTY; without even the implied warranty of
 *  MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 *  GNU General Public License for more details.
 *
 *  This copyright notice MUST APPEAR in all copies of the script!
 ***************************************************************/
/**
 * Repository for Tx_About_Domain_Model_Extension
 *
 * @author Felix Kopp <felix-source@phorax.com>
 */
class ExtensionRepository extends \TYPO3\CMS\Extbase\Persistence\Repository {

	/**
	 * @var \TYPO3\CMS\Extbase\Object\ObjectManagerInterface
	 */
	protected $objectManager;

	/**
	 * @param \TYPO3\CMS\Extbase\Object\ObjectManagerInterface $objectManager
	 */
	public function injectObjectManager(\TYPO3\CMS\Extbase\Object\ObjectManagerInterface $objectManager) {
		$this->objectManager = $objectManager;
	}

	/**
	 * Finds all loaded extensions
	 *
	 * @return \TYPO3\CMS\Extbase\Persistence\ObjectStorage<\TYPO3\CMS\About\Domain\Model\Extension>
	 */
	public function findAllLoaded() {
		$loadedExtensions = $this->objectManager->get('TYPO3\\CMS\\Extbase\\Persistence\\ObjectStorage');
		$loadedExtensionsArray = $GLOBALS['TYPO3_LOADED_EXT'];
		foreach ($loadedExtensionsArray as $extensionKey => $extension) {
			if (is_array($extension) && $extension['type'] != 'S') {
				$emconfPath = PATH_site . $extension['siteRelPath'] . 'ext_emconf.php';
				if (file_exists($emconfPath)) {
					include $emconfPath;
					$extension = $this->objectManager->get('TYPO3\\CMS\\About\\Domain\\Model\\Extension');
					$extension->setKey($extensionKey);
					$extension->setTitle($EM_CONF['']['title']);
					$extension->setAuthor($EM_CONF['']['author']);
					$extension->setAuthorEmail($EM_CONF['']['author_email']);
					$loadedExtensions->attach($extension);
				}
			}
		}
		return $loadedExtensions;
	}

}


?>