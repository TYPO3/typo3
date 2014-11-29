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

/**
 * View helper for configure extension link
 *
 * @author Susanne Moog <typo3@susannemoog.de>
 * @internal
 */
class ConfigureExtensionViewHelper extends \TYPO3\CMS\Fluid\ViewHelpers\Link\ActionViewHelper {

	/**
	 * @var string
	 */
	protected $tagName = 'a';

	/**
	 * Renders a configure extension link if the extension has configuration options
	 *
	 * @param array $extension Extension configuration array with extension information
	 * @param bool $forceConfiguration If TRUE the content is only returned if a link could be generated
	 * @param bool $showDescription If TRUE the extension description is also shown in the title attribute
	 * @return string the rendered tag or child nodes content
	 */
	public function render($extension, $forceConfiguration = TRUE, $showDescription = FALSE) {
		$content = (string)$this->renderChildren();
		if ($extension['installed'] && file_exists(PATH_site . $extension['siteRelPath'] . 'ext_conf_template.txt')) {
			$uriBuilder = $this->controllerContext->getUriBuilder();
			$action = 'showConfigurationForm';
			$uri = $uriBuilder->reset()->uriFor(
				$action,
				array('extension' => array('key' => $extension['key'])),
				'Configuration'
			);
			if ($showDescription) {
				$title = $extension['description'] . PHP_EOL .
					\TYPO3\CMS\Extbase\Utility\LocalizationUtility::translate('extensionList.clickToConfigure', 'extensionmanager');

			} else {
				$title = \TYPO3\CMS\Extbase\Utility\LocalizationUtility::translate('extensionList.configure', 'extensionmanager');
			}
			$this->tag->addAttribute('href', $uri);
			$this->tag->addAttribute('title', $title);
			$this->tag->setContent($content);
			$content = $this->tag->render();
		} elseif ($forceConfiguration) {
			$content = '<span class="btn btn-default disabled">' . IconUtility::getSpriteIcon('empty-empty') . '</span>';
		}

		return $content;
	}

}
