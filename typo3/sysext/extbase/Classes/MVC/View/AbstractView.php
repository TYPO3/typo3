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
 * An abstract View
 *
 * @package Extbase
 * @subpackage MVC\View
 * @version $ID:$
 * @api
 */
abstract class Tx_Extbase_MVC_View_AbstractView implements Tx_Extbase_MVC_View_ViewInterface {

	/**
	 * @var Tx_Extbase_MVC_Controller_ControllerContext
	 * @api
	 */
	protected $controllerContext;

	/**
	 * View variables and their values
	 * @var array
	 * @see assign()
	 */
	protected $variables = array();

	/**
	 * Sets the current controller context
	 *
	 * @param Tx_Extbase_MVC_Controller_ControllerContext $controllerContext
	 * @return void
	 */
	public function setControllerContext(Tx_Extbase_MVC_Controller_ControllerContext $controllerContext) {
		$this->controllerContext = $controllerContext;
	}

	/**
	 * Add a variable to $this->viewData.
	 * Can be chained, so $this->view->assign(..., ...)->assign(..., ...); is possible,
	 *
	 * @param string $key Key of variable
	 * @param object $value Value of object
	 * @return Tx_Extbase_MVC_View_ViewInterface an instance of $this, to enable chaining.
	 * @api
	 */
	public function assign($key, $value) {
		$this->variables[$key] = $value;
		return $this;
	}

	/**
	 * Add multiple variables to $this->viewData.
	 *
	 * @param array $values array in the format array(key1 => value1, key2 => value2).
	 * @return void
	 * @api
	 */
	public function assignMultiple(array $values) {
		foreach($values as $key => $value) {
			$this->assign($key, $value);
		}
	}

	/**
	 * Initializes this view.
	 *
	 * Override this method for initializing your concrete view implementation.
	 *
	 * @return void
	 * @api
	 */
	public function initializeView() {
	}
}
?>