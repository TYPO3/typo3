<?php
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
 * view helper
 *
 * @author Susanne Moog <susanne.moog@typo3.org>
 * @package Extension Manager
 * @subpackage ViewHelpers
 */
class Tx_Extensionmanager_ViewHelpers_DownloadExtensionViewHelper
	extends Tx_Fluid_ViewHelpers_FormViewHelper {

	/**
	 * @var string
	 */
	protected $tagName = 'form';

	/**
	 * Renders a download link
	 *
	 * @param Tx_Extensionmanager_Domain_Model_Extension $extension
	 * @return string the rendered a tag
	 */
	public function render(Tx_Extensionmanager_Domain_Model_Extension $extension) {
		$installPaths = Tx_Extensionmanager_Domain_Model_Extension::returnAllowedInstallPaths();
		$pathSelector = '<ul>';
		foreach ($installPaths as $installPathType => $installPath) {
			$pathSelector .= '<li>
				<input type="radio" id="' . $extension->getExtensionKey() .'-downloadPath-' . $installPathType . '" name="' . $this->getFieldNamePrefix('downloadPath') . '[downloadPath]" class="downloadPath" value="' . $installPathType . '"' .
				($installPathType == 'Local' ? 'checked="checked"' : '') . '/>
				<label for="' . $extension->getExtensionKey() .'-downloadPath-' . $installPathType . '">' . $installPathType . '</label>
			</li>';
		}
		$pathSelector .= '</ul>';
		$uriBuilder = $this->controllerContext->getUriBuilder();
		$action = 'checkDependencies';
		$uriBuilder->reset();
		$uriBuilder->setFormat('json');
		$uri = $uriBuilder->uriFor($action, array(
			'extension' => $extension->getUid()
		), 'Download');
		$this->tag->addAttribute('href', $uri);
		$label = '<input type="submit" value="Import and Install" />';
		$this->tag->setContent($label . $pathSelector);
		$this->tag->addAttribute('class', 'download');

		return '<div id="' . $extension->getExtensionKey() . '-downloadFromTer" class="downloadFromTer">' . $this->tag->render() . '</div>';
	}
}
