<?php
namespace TYPO3\CMS\Extensionmanager\ViewHelpers;

/***************************************************************
 *  Copyright notice
 *
 *  (c) 2012 Susanne Moog <susanne.moog@typo3.org>
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
 * Renders a link to update the extension list from TER
 *
 * @author Susanne Moog <susanne.moog@typo3.org>
 * @package Extension Manager
 * @subpackage ViewHelpers
 */
class UpdateFromTerViewHelper extends \TYPO3\CMS\Fluid\ViewHelpers\Link\ActionViewHelper {

	/**
	 * @var string
	 */
	protected $tagName = 'a';

	/**
	 * Renders a link to update extension list from TER
	 *
	 * @return string the rendered a tag
	 */
	public function render() {
		$uriBuilder = $this->controllerContext->getUriBuilder();
		$action = 'updateExtensionListFromTer';
		$uriBuilder->reset();
		$uriBuilder->setFormat('json');
		$uri = $uriBuilder->uriFor($action, array(), 'UpdateFromTer');
		$this->tag->addAttribute('href', $uri);
		$label = 'Update Extension List';
		$this->tag->setContent($label);
		$this->tag->addAttribute('class', 'text');
		return '<div class="updateFromTer">' . $this->tag->render() . '<span class="spinner"></span></div>';
	}

}


?>