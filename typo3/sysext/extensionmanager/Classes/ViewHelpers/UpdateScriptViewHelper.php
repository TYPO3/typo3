<?php
namespace TYPO3\CMS\Extensionmanager\ViewHelpers;

/***************************************************************
 *  Copyright notice
 *
 *  (c) 2013 Francois Suter, <francois.suter@typo3.org>
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
 * View helper for update script link
 *
 * @author Francois Suter <francois.suter@typo3.org>
 */
class UpdateScriptViewHelper extends \TYPO3\CMS\Fluid\ViewHelpers\Link\ActionViewHelper {

	/**
	 * @var \TYPO3\CMS\Extbase\Object\ObjectManagerInterface
	 * @inject
	 */
	protected $objectManager;

	/**
	 * @var string
	 */
	protected $tagName = 'a';

	/**
	 * Renders a link to the update script screen if the extension has one
	 *
	 * @param string $extensionKey Extension key
	 * @return string The rendered a tag
	 */
	public function render($extensionKey) {
		$tag = '';

		// If the "class.ext_update.php" file exists, build link to the update script screen
		/** @var $updateScriptUtility \TYPO3\CMS\Extensionmanager\Utility\UpdateScriptUtility */
		$updateScriptUtility = $this->objectManager->get('TYPO3\\CMS\\Extensionmanager\\Utility\\UpdateScriptUtility');
		if ($updateScriptUtility->checkUpdateScriptExists($extensionKey)) {
			$uriBuilder = $this->controllerContext->getUriBuilder();
			$action = 'show';
			$uri = $uriBuilder->reset()->uriFor(
				$action,
				array('extensionKey' => $extensionKey),
				'UpdateScript'
			);
			$this->tag->addAttribute('href', $uri);
			$this->tag->addAttribute('title', \TYPO3\CMS\Extbase\Utility\LocalizationUtility::translate('extensionList.update.script', 'extensionmanager'));
			$this->tag->setContent(\TYPO3\CMS\Backend\Utility\IconUtility::getSpriteIcon('extensions-extensionmanager-update-script'));
			$tag = $this->tag->render();
		}
		return $tag;
	}

}


?>