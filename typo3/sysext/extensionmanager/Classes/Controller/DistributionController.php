<?php
namespace TYPO3\CMS\Extensionmanager\Controller;

/***************************************************************
 *  Copyright notice
 *
 *  (c) 2012-2013 Susanne Moog, <typo3@susannemoog.de>
 *  All rights reserved
 *
 *  This script is part of the TYPO3 project. The TYPO3 project is
 *  free software; you can redistribute it and/or modify
 *  it under the terms of the GNU General Public License as published by
 *  the Free Software Foundation; either version 2 of the License, or
 *  (at your option) any later version.
 *
 *  The GNU General Public License can be found at
 *  http://www.gnu.org/copyleft/gpl.html.
 *  A copy is found in the text file GPL.txt and important notices to the license
 *  from the author is found in LICENSE.txt distributed with these scripts.
 *
 *
 *  This script is distributed in the hope that it will be useful,
 *  but WITHOUT ANY WARRANTY; without even the implied warranty of
 *  MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 *  GNU General Public License for more details.
 *
 *  This copyright notice MUST APPEAR in all copies of the script!
 ***************************************************************/
/**
 * Controller for distribution related actions
 *
 * @author Susanne Moog <typo3@susannemoog.de>
 */
class DistributionController extends \TYPO3\CMS\Extensionmanager\Controller\AbstractController {

	/**
	 * @var \TYPO3\CMS\Core\Package\PackageManager
	 * @inject
	 */
	protected $packageManager;

	/**
	 * Shows information about the distribution
	 *
	 * @param \TYPO3\CMS\Extensionmanager\Domain\Model\Extension $extension
	 */
	public function showAction(\TYPO3\CMS\Extensionmanager\Domain\Model\Extension $extension) {
		$extensionKey = $extension->getExtensionKey();
		// Check if extension/package is installed
		$active = $this->packageManager->isPackageActive($extensionKey);

		// Create link for extension configuration
		if ($active && file_exists(\TYPO3\CMS\Core\Utility\ExtensionManagementUtility::extPath($extensionKey) . 'ext_conf_template.txt')) {
			$uriBuilder = $this->controllerContext->getUriBuilder();
			$action = 'showConfigurationForm';
			$configurationLink = $uriBuilder->reset()->uriFor(
				$action,
				array('extension' => array('key' => $extensionKey)),
				'Configuration'
			);
		} else {
			$configurationLink = FALSE;
		}
		$this->view->assign('distributionActive', $active);
		$this->view->assign('configurationLink', $configurationLink);
		$this->view->assign('extension', $extension);
	}
}
