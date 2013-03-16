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
 * view helper for displaying a download extension data link
 *
 * @author Susanne Moog <typo3@susannemoog.de>
 */
class DownloadExtensionDataViewHelper extends \TYPO3\CMS\Fluid\ViewHelpers\Link\ActionViewHelper {

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
		$filePrefix = PATH_site . $extension['siteRelPath'];
		if (!file_exists(($filePrefix . '/ext_tables.sql')) && !file_exists(($filePrefix . '/ext_tables_static+adt.sql'))) {
			return '';
		}
		$uriBuilder = $this->controllerContext->getUriBuilder();
		$uriBuilder->reset();
		$uri = $uriBuilder->uriFor('downloadExtensionData', array(
			'extension' => $extension['key']
		), 'Action');
		$this->tag->addAttribute('href', $uri);
		$cssClass = 'downloadExtensionData';
		$this->tag->addAttribute('class', $cssClass);
		$this->tag->addAttribute('title', \TYPO3\CMS\Extbase\Utility\LocalizationUtility::translate('extensionList.downloadsql', 'extensionmanager'));
		$this->tag->setContent(\TYPO3\CMS\Backend\Utility\IconUtility::getSpriteIcon('actions-system-extension-sqldump'));
		return $this->tag->render();
	}

}


?>