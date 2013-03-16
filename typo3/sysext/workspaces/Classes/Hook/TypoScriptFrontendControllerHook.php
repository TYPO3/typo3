<?php
namespace TYPO3\CMS\Workspaces\Hook;

/***************************************************************
 *  Copyright notice
 *
 *  (c) 2010-2013 Workspaces Team (http://forge.typo3.org/projects/show/typo3v4-workspaces)
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
 * Frontend hooks
 *
 * @author Workspaces Team (http://forge.typo3.org/projects/show/typo3v4-workspaces)
 */
class TypoScriptFrontendControllerHook {

	/**
	 * @param array $params
	 * @param \TYPO3\CMS\Frontend\Controller\TypoScriptFrontendController $pObj
	 * @return mixed
	 */
	public function hook_eofe($params, $pObj) {
		if ($pObj->fePreview != 2) {
			return;
		}
		$previewParts = $GLOBALS['TSFE']->cObj->cObjGetSingle('FLUIDTEMPLATE', array(
			'file' => 'EXT:workspaces/Resources/Private/Templates/Preview/Preview.html',
			'variables.' => array(
				'backendDomain' => 'TEXT',
				'backendDomain.' => array('value' => $GLOBALS['BE_USER']->getSessionData('workspaces.backend_domain'))
			)
		));
		$GLOBALS['TSFE']->content = str_ireplace('</body>', $previewParts . '</body>', $GLOBALS['TSFE']->content);
	}

}


?>