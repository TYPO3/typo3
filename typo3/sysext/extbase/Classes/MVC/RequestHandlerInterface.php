<?php
/***************************************************************
 *  Copyright notice
 *
 *  (c) 2010 Jochen Rau <jochen.rau@typoplanet.de>
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
 * The interface for a request handler
 *
 * @api
 */
interface Tx_Extbase_MVC_RequestHandlerInterface {

	/**
	 * Handles a raw request and returns the respsonse.
	 *
	 * @return Tx_Extbase_MVC_ResponseInterface
	 * @api
	 */
	public function handleRequest();

	/**
	 * Checks if the request handler can handle the current request.
	 *
	 * @return boolean TRUE if it can handle the request, otherwise FALSE
	 * @api
	 */
	public function canHandleRequest();

	/**
	 * Returns the priority - how eager the handler is to actually handle the
	 * request. An integer > 0 means "I want to handle this request" where
	 * "100" is default. "0" means "I am a fallback solution".
	 *
	 * @return integer The priority of the request handler
	 * @api
	 */
	public function getPriority();


}

?>