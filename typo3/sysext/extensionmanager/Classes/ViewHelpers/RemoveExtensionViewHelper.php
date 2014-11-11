<?php
namespace TYPO3\CMS\Extensionmanager\ViewHelpers;

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
 * view helper for displaying a remove extension link
 *
 * @author Susanne Moog <typo3@susannemoog.de>
 * @internal
 */
class RemoveExtensionViewHelper extends \TYPO3\CMS\Fluid\ViewHelpers\Link\ActionViewHelper {

	/**
	 * @var string
	 */
	protected $tagName = 'a';

	/**
	 * Renders an install link
	 *
	 * @param array $extension
	 * @return string the rendered a tag
	 */
	public function render($extension) {
		if (\TYPO3\CMS\Core\Utility\ExtensionManagementUtility::isLoaded($extension['key'])) {
			return '';
		}
		if (
			!in_array($extension['type'], \TYPO3\CMS\Extensionmanager\Domain\Model\Extension::returnAllowedInstallTypes()) ||
			$extension['type'] === 'System'
		) {
			return '';
		}
		$uriBuilder = $this->controllerContext->getUriBuilder();
		$action = 'removeExtension';
		$uriBuilder->reset();
		$uriBuilder->setFormat('json');
		$uri = $uriBuilder->uriFor($action, array(
			'extension' => $extension['key']
		), 'Action');
		$this->tag->addAttribute('href', $uri);
		$cssClass = 'removeExtension';
		$this->tag->addAttribute('class', $cssClass);
		$this->tag->addAttribute('title', \TYPO3\CMS\Extbase\Utility\LocalizationUtility::translate('extensionList.remove', 'extensionmanager'));
		$this->tag->setContent(\TYPO3\CMS\Backend\Utility\IconUtility::getSpriteIcon('actions-edit-delete'));
		return $this->tag->render();
	}

}
