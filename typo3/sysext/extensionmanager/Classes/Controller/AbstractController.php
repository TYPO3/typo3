<?php
/***************************************************************
 *  Copyright notice
 *
 *  (c) 2012 Susanne Moog, <typo3@susannemoog.de>
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
 * Abstract action controller.
 *
 * @author Susanne Moog <typo3@susannemoog.de>
 * @package Extension Manager
 * @subpackage controller
 */
class Tx_Extensionmanager_Controller_AbstractController extends Tx_Extbase_MVC_Controller_ActionController {

	/**
	 * Resolve view and initialize the general view-variables extensionName,
	 * controllerName and actionName based on the request object
	 *
	 * @return Tx_Fluid_View_TemplateView
	 */
	protected function resolveView() {
		$view = parent::resolveView();
		$view->assignMultiple(array(
			'extensionName' => $this->request->getControllerExtensionName(),
			'controllerName' => $this->request->getControllerName(),
			'actionName' => $this->request->getControllerActionName(),
		));
		return $view;
	}
}
?>