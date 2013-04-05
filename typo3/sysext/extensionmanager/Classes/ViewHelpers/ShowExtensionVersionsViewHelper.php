<?php
namespace TYPO3\CMS\Extensionmanager\ViewHelpers;

/***************************************************************
 *  Copyright notice
 *
 *  (c) 2012-2013 Susanne Moog <susanne.moog@typo3.org>
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
 * Display a link to show all versions of an extension
 *
 * @author Susanne Moog <susanne.moog@typo3.org>
 */
class ShowExtensionVersionsViewHelper extends \TYPO3\CMS\Fluid\ViewHelpers\Link\ActionViewHelper {

	/**
	 * @var string
	 */
	protected $tagName = 'a';

	/**
	 * Renders an install link
	 *
	 * @param \TYPO3\CMS\Extensionmanager\Domain\Model\Extension $extension
	 * @return string the rendered a tag
	 */
	public function render($extension) {
		$uriBuilder = $this->controllerContext->getUriBuilder();
		$action = 'showAllVersions';
		$uri = $uriBuilder->reset()->uriFor($action, array(
			'extensionKey' => $extension->getExtensionKey(),
		), 'List');
		$this->tag->addAttribute('href', $uri);

		// Set class
		$this->tag->addAttribute('class', 'versions-all ui-icon ui-icon-triangle-1-s');

		$label = \TYPO3\CMS\Extbase\Utility\LocalizationUtility::translate('extensionList.showAllVersions.label', 'extensionmanager');
		$this->tag->addAttribute('title', $label);
		$this->tag->setContent($label);
		return $this->tag->render();
	}

}

?>