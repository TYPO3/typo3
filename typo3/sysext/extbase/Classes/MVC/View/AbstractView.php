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
 * An abstract View
 *
 * @package TYPO3
 * @subpackage extbase
 * @version $ID:$
 */
abstract class Tx_ExtBase_MVC_View_AbstractView implements Tx_ExtBase_MVC_View_ViewInterface {

	/**
	 * @var Tx_ExtBase_MVC_Request
	 */
	protected $request;

	/**
	 * @var array of Tx_ExtBase_MVC_View_Helper_HelperInterface
	 */
	protected $viewHelpers;

	/**
	 * @var array
	 */
	protected $contextVariables = array();

	/**
	 * Sets the current request
	 *
	 * @param Tx_ExtBase_MVC_Request $request
	 * @return void
	 */
	public function setRequest(Tx_ExtBase_MVC_Request $request) {
		$this->request = $request;
	}

	/**
	 * Returns an View Helper instance.
	 * View Helpers must implement the interface Tx_ExtBase_MVC_View_Helper_HelperInterface
	 *
	 * @param string $viewHelperClassName the full name of the View Helper Class including 
	 * @return Tx_ExtBase_MVC_View_Helper_HelperInterface The View Helper instance
	 */
	public function getViewHelper($viewHelperClassName) {
		if (!isset($this->viewHelpers[$viewHelperClassName])) {
			$viewHelper = t3lib_div::makeInstance($viewHelperClassName);
			if (!$viewHelper instanceof Tx_ExtBase_MVC_View_Helper_HelperInterface) {
				throw new Tx_ExtBase_Exception_InvalidViewHelper('View Helpers must implement interface "Tx_ExtBase_MVC_View_Helper_HelperInterface"', 1222895456);
			}
			$viewHelper->setRequest($this->request);
			$this->viewHelpers[$viewHelperClassName] = $viewHelper;
		}
		return $this->viewHelpers[$viewHelperClassName];
	}

	/**
	 * Initializes this view.
	 *
	 * Override this method for initializing your concrete view implementation.
	 *
	 * @return void
	 */
	protected function initializeView() {
	}
	
}

?>