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
 * Interface of a view
 *
 * @package TYPO3
 * @subpackage extmvc
 * @version $ID:$
 */
interface TX_EXTMVC_View_ViewInterface {

	/**
	 * Sets the current request
	 *
	 * @param TX_EXTMVC_Request $request
	 * @return void
	 */
	public function setRequest(TX_EXTMVC_Request $request);

	/**
	 * Returns an View Helper instance.
	 * View Helpers must implement the interface TX_EXTMVC_View_Helper_HelperInterface
	 *
	 * @param string $viewHelperObjectName the full name of the View Helper object including namespace
	 * @return TX_EXTMVC_View_Helper_HelperInterface The View Helper instance
	 */
	public function getViewHelper($viewHelperObjectName);

	/**
	 * Renders the view
	 *
	 * @return string The rendered view
	 */
	public function render();
}

?>