<?php
namespace TYPO3\CMS\Extensionmanager\ViewHelpers;

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
 * View helper for configure extension link
 *
 * @author Susanne Moog <typo3@susannemoog.de>
 */
class ConfigureExtensionViewHelper extends \TYPO3\CMS\Fluid\ViewHelpers\Link\ActionViewHelper {

	/**
	 * @var string
	 */
	protected $tagName = 'a';

	/**
	 * Renders a configure extension link if the extension has configuration options
	 *
	 * @param array $extension
	 * @return string the rendered a tag
	 */
	public function render($extension) {
		$tag = '';
		if ($extension['installed'] && file_exists(PATH_site . $extension['siteRelPath'] . '/ext_conf_template.txt')) {
			$uriBuilder = $this->controllerContext->getUriBuilder();
			$action = 'showConfigurationForm';
			$uri = $uriBuilder->reset()->uriFor($action, array(
				'extension' => array(
					'key' => $extension['key']
				)
			), 'Configuration');
			$this->tag->addAttribute('href', $uri);
			$this->tag->addAttribute('title', \TYPO3\CMS\Extbase\Utility\LocalizationUtility::translate('extensionList.configure', 'extensionmanager'));
			$this->tag->setContent(\TYPO3\CMS\Backend\Utility\IconUtility::getSpriteIcon('actions-system-extension-configure'));
			$tag = $this->tag->render();
		}
		return $tag;
	}

}


?>