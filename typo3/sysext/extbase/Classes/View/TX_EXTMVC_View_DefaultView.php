<?php
declare(ENCODING = 'utf-8');

/*                                                                        *
 * This script belongs to the FLOW3 framework.                            *
 *                                                                        *
 * It is free software; you can redistribute it and/or modify it under    *
 * the terms of the GNU Lesser General Public License as published by the *
 * Free Software Foundation, either version 3 of the License, or (at your *
 * option) any later version.                                             *
 *                                                                        *
 * This script is distributed in the hope that it will be useful, but     *
 * WITHOUT ANY WARRANTY; without even the implied warranty of MERCHAN-    *
 * TABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the GNU Lesser       *
 * General Public License for more details.                               *
 *                                                                        *
 * You should have received a copy of the GNU Lesser General Public       *
 * License along with the script.                                         *
 * If not, see http://www.gnu.org/licenses/lgpl.html                      *
 *                                                                        *
 * The TYPO3 project - inspiring people to share!                         *
 *                                                                        */

/**
 * The default view - a special case.
 *
 * @version $Id:$
 * @license http://www.gnu.org/licenses/lgpl.html GNU Lesser General Public License, version 3 or later
 */
class TX_EXTMVC_View_DefaultView extends TX_EXTMVC_View_AbstractView {

	/**
	 * @var TX_EXTMVC_Request
	 */
	protected $request;

	/**
	 * Renders the default view
	 *
	 * @return string The rendered view
	 * @author Karsten Dambekalns <karsten@typo3.org>
	 * @throws TX_EXTMVC_Exception if no request has been set
	 */
	public function render() {
		if (!is_object($this->request)) throw new TX_EXTMVC_Exception('Can\'t render view without request object.', 1192450280);

		$template = $this->objectFactory->create('F3_FLOW3_MVC_View_Template');
		$template->setTemplateResource($this->resourceManager->getResource('file://FLOW3/Public/MVC/DefaultView_Template.html')->getContent());

		if ($this->request instanceof TX_EXTMVC_Web_Request) {
			$template->setMarkerContent('baseuri', $this->request->getBaseURI());
		}
		return $template->render();
	}
}

?>