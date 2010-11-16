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
 * An empty view - a special case.
 *
 * @package Extbase
 * @subpackage MVC\View
 * @version $Id: EmptyView.php 1729 2009-11-25 21:37:20Z stucki $
 * @license http://www.gnu.org/licenses/lgpl.html GNU Lesser General Public License, version 3 or later
 */
final class Tx_Extbase_MVC_View_EmptyView implements Tx_Extbase_MVC_View_ViewInterface {

	/**
	 * Dummy method to satisfy the ViewInterface
	 *
	 * @param Tx_Extbase_MVC_Controller_ControllerContext $controllerContext
	 * @return void
	 */
	public function setControllerContext(Tx_Extbase_MVC_Controller_ControllerContext $controllerContext) {
	}

	/**
	 * Dummy method to satisfy the ViewInterface
	 *
	 * @param string $key
	 * @param mixed $value
	 * @return Tx_Extbase_MVC_View_EmptyView instance of $this to allow chaining
	 * @api
	 */
	public function assign($key, $value) {
		return $this;
	}

	/**
	 * Dummy method to satisfy the ViewInterface
	 *
	 * @param array $values
	 * @return Tx_Extbase_MVC_View_EmptyView instance of $this to allow chaining
	 * @api
	 */
	public function assignMultiple(array $values) {
		return $this;
	}

	/**
	 * This view can be used in any case.
	 *
	 * @param Tx_Extbase_MVC_Controller_ControllerContext $controllerContext
	 * @return boolean TRUE
	 * @api
	 */
	public function canRender(Tx_Extbase_MVC_Controller_ControllerContext $controllerContext) {
		return TRUE;
	}

	/**
	 * Renders the empty view
	 *
	 * @return string An empty string
	 */
	public function render() {
		return '<!-- This is the output of the Empty View. An appropriate View was not found. -->';
	}

	/**
	 * A magic call method.
	 *
	 * Because this empty view is used as a Special Case in situations when no matching
	 * view is available, it must be able to handle method calls which originally were
	 * directed to another type of view. This magic method should prevent PHP from issuing
	 * a fatal error.
	 *
	 * @return void
	 */
	public function __call($methodName, array $arguments) {
	}
}
?>