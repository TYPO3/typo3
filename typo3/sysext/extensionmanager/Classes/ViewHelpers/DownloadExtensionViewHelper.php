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
 * view helper
 *
 * @author Susanne Moog <susanne.moog@typo3.org>
 * @internal
 */
class DownloadExtensionViewHelper extends \TYPO3\CMS\Fluid\ViewHelpers\FormViewHelper {

	/**
	 * @var string
	 */
	protected $tagName = 'form';

	/**
	 * Renders a download link
	 *
	 * @param \TYPO3\CMS\Extensionmanager\Domain\Model\Extension $extension
	 * @return string the rendered a tag
	 */
	public function render(\TYPO3\CMS\Extensionmanager\Domain\Model\Extension $extension) {
		$installPaths = \TYPO3\CMS\Extensionmanager\Domain\Model\Extension::returnAllowedInstallPaths();
		$pathSelector = '<ul class="is-hidden">';
		foreach ($installPaths as $installPathType => $installPath) {
			$pathSelector .= '<li>
				<input type="radio" id="' . htmlspecialchars($extension->getExtensionKey()) . '-downloadPath-' . htmlspecialchars($installPathType) . '" name="' . htmlspecialchars($this->getFieldNamePrefix('downloadPath')) . '[downloadPath]" class="downloadPath" value="' . htmlspecialchars($installPathType) . '"' . ($installPathType == 'Local' ? ' checked="checked"' : '') . '/>
				<label for="' . htmlspecialchars($extension->getExtensionKey()) . '-downloadPath-' . htmlspecialchars($installPathType) . '">' . htmlspecialchars($installPathType) . '</label>
			</li>';
		}
		$pathSelector .= '</ul>';
		$uriBuilder = $this->controllerContext->getUriBuilder();
		$action = 'checkDependencies';
		$uriBuilder->reset();
		$uriBuilder->setFormat('json');
		$uri = $uriBuilder->uriFor($action, array(
			'extension' => (int)$extension->getUid()
		), 'Download');
		$this->tag->addAttribute('data-href', $uri);

		// @TODO Clean-up
		$iconClasses = "t3-icon t3-icon-actions t3-icon-system-extension-import";
		$label = '<input title="' . \TYPO3\CMS\Extbase\Utility\LocalizationUtility::translate('extensionList.downloadViewHelper.submit', 'extensionmanager') . '" type="submit" class="' . $iconClasses . '" value="' . \TYPO3\CMS\Extbase\Utility\LocalizationUtility::translate('extensionList.downloadViewHelper.submit', 'extensionmanager') . '">';

		$this->tag->setContent($label . $pathSelector);
		$this->tag->addAttribute('class', 'download');
		return '<div id="' . htmlspecialchars($extension->getExtensionKey()) . '-downloadFromTer" class="downloadFromTer">' . $this->tag->render() . '</div>';
	}

}
