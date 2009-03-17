<?php
/***************************************************************
*  Copyright notice
*
*  (c) 2009 Jochen Rau <jochen.rau@typoplanet.de>
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
*
*  This script is distributed in the hope that it will be useful,
*  but WITHOUT ANY WARRANTY; without even the implied warranty of
*  MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
*  GNU General Public License for more details.
*
*  This copyright notice MUST APPEAR in all copies of the script!
***************************************************************/

/**
 * The default view - a special case.
 *
 * @package TYPO3
 * @subpackage extmvc
 * @version $ID:$
 */
class TX_EXTMVC_View_DefaultView extends TX_EXTMVC_View_AbstractView {

	/**
	 * Renders the default view
	 *
	 * @return string The rendered view
	 * @throws TX_EXTMVC_Exception if no request has been set
	 */
	public function render() {
		if (!is_object($this->request)) throw new TX_EXTMVC_Exception('Can\'t render view without request object.', 1192450280);

		$template = t3lib_div::makeInstance('TX_EXTMVC_View_TemplateView');
		$template->setTemplateResource($this->resourceManager->getResource('file://FLOW3/Public/MVC/DefaultView_Template.html')->getContent());

		if ($this->request instanceof TX_EXTMVC_Web_Request) {
			$template->setMarkerContent('baseuri', $this->request->getBaseURI());
		}
		return $template->render();
	}
}

?>