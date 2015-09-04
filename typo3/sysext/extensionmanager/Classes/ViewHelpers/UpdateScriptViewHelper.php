<?php
namespace TYPO3\CMS\Extensionmanager\ViewHelpers;

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

use TYPO3\CMS\Backend\Utility\IconUtility;
use TYPO3\CMS\Core\Imaging\Icon;
use TYPO3\CMS\Core\Imaging\IconFactory;
use TYPO3\CMS\Core\Utility\GeneralUtility;

/**
 * View helper for update script link
 * @internal
 */
class UpdateScriptViewHelper extends \TYPO3\CMS\Fluid\ViewHelpers\Link\ActionViewHelper {

	/**
	 * @var \TYPO3\CMS\Extbase\Object\ObjectManagerInterface
	 */
	protected $objectManager;

	/**
	 * @var string
	 */
	protected $tagName = 'a';

	/**
	 * @param \TYPO3\CMS\Extbase\Object\ObjectManagerInterface $objectManager
	 */
	public function injectObjectManager(\TYPO3\CMS\Extbase\Object\ObjectManagerInterface $objectManager) {
		$this->objectManager = $objectManager;
	}

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
		$updateScriptUtility = $this->objectManager->get(\TYPO3\CMS\Extensionmanager\Utility\UpdateScriptUtility::class);
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
		} else {
			$iconFactory = GeneralUtility::makeInstance(IconFactory::class);
			return '<span class="btn btn-default disabled">' . $iconFactory->getIcon('empty-empty', Icon::SIZE_SMALL) . '</span>';
		}
		return $tag;
	}

}
