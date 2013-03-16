<?php
namespace TYPO3\CMS\Frontend\Hooks;

/***************************************************************
 *  Copyright notice
 *
 *  (c) 1999-2013 Kasper Skårhøj (kasperYYYY@typo3.com)
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
 * Uses frontend hooks to show preview informations
 *
 */
class FrontendHooks {

	/**
	 * Include the preview block in cause we're looking at a hidden page
	 * in the LIVE workspace
	 *
	 * @param array $params
	 * @param \TYPO3\CMS\Frontend\Controller\TypoScriptFrontendController $pObj
	 * @return string
	 */
	public function hook_previewInfo($params, $pObj) {
		if ($pObj->fePreview !== 1) {
			return;
		}
		$message = '';
		if ($pObj->config['config']['message_preview']) {
			$message = $pObj->config['config']['message_preview'];
		} else {
			$message = '<div id="typo3-previewInfo" style="position: absolute; top: 20px; right: 20px; border: 2px solid #000; padding: 5px 5px; background: #f00; font: 1em Verdana; color: #000; font-weight: bold; z-index: 10001">PREVIEW!</div>';
		}
		return $message;
	}

}


?>