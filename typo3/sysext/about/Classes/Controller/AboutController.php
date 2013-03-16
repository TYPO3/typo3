<?php
namespace TYPO3\CMS\About\Controller;

/***************************************************************
 *  Copyright notice
 *
 *  (c) 2012-2013 Christian Kuhn <lolli@schwarzbu.ch>
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
 *  A copy is found in the textfile GPL.txt and important notices to the license
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
 * Module 'about' shows some standard information for TYPO3 CMS: About-text, version number and so on.
 *
 * @author Kasper Skårhøj <kasperYYYY@typo3.com>
 * @author Steffen Kamper <steffen@typo3.org>
 * @author Christian Kuhn <lolli@schwarzbu.ch>
 */
class AboutController extends \TYPO3\CMS\Extbase\Mvc\Controller\ActionController {

	/**
	 * @var \TYPO3\CMS\About\Domain\Repository\ExtensionRepository
	 */
	protected $extensionRepository;

	/**
	 * @param \TYPO3\CMS\About\Domain\Repository\ExtensionRepository
	 * @return void
	 */
	public function injectExtensionRepository(\TYPO3\CMS\About\Domain\Repository\ExtensionRepository $extensionRepository) {
		$this->extensionRepository = $extensionRepository;
	}

	/**
	 * Main action: Show standard information
	 *
	 * @return void
	 */
	public function indexAction() {
		$extensions = $this->extensionRepository->findAllLoaded();
		$this->view
			->assign('TYPO3Version', TYPO3_version)
			->assign('TYPO3CopyrightYear', TYPO3_copyright_year)
			->assign('TYPO3UrlDonate', TYPO3_URL_DONATE)
			->assign('loadedExtensions', $extensions);
	}

}

?>