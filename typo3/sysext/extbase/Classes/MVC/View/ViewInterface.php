<?php
/***************************************************************
*  Copyright notice
*
*  (c) 2009 Jochen Rau <jochen.rau@typoplanet.de>
*  All rights reserved
*
*  This class is a backport of the corresponding class of FLOW3.
*  All credits go to the v5 team.
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
 * Interface of a view
 *
 * @package Extbase
 * @subpackage MVC\View
 * @version $ID:$
 * @api
 */
interface Tx_Extbase_MVC_View_ViewInterface {

	/**
	 * Sets the current controller context
	 *
	 * @param Tx_Extbase_MVC_Controller_ControllerContext $controllerContext
	 * @return void
	 */
	public function setControllerContext(Tx_Extbase_MVC_Controller_ControllerContext $controllerContext);

	/**
	 * Renders the view
	 *
	 * @return string The rendered view
	 * @api
	 */
	public function render();

	/**
	 * Initializes this view.
	 *
	 * @return void
	 * @api
	 */
	public function initializeView();

}

?>