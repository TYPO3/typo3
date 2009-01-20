<?php
declare(ENCODING = 'utf-8');
namespace F3_FLOW3_MVC;

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
 * @package FLOW3
 * @subpackage MVC
 * @version $Id: F3_FLOW3_MVC_DefaultRequestHandler.php 1749 2009-01-15 15:06:30Z k-fish $
 */

/**
 * A Special Case of a Request Handler: This default handler is used, if no other request
 * handler was found which could handle the request.
 *
 * @package FLOW3
 * @subpackage MVC
 * @version $Id: F3_FLOW3_MVC_DefaultRequestHandler.php 1749 2009-01-15 15:06:30Z k-fish $
 * @license http://www.gnu.org/licenses/lgpl.html GNU Lesser General Public License, version 3 or later
 */
class DefaultRequestHandler implements F3_FLOW3_MVC_RequestHandlerInterface {

	/**
	 * Handles the request
	 *
	 * @return void
	 * @author Robert Lemke <robert@typo3.org>
	 */
	public function handleRequest() {
		echo ('FLOW3: This is the default request handler - no other suitable request handler could be determined.');
	}

	/**
	 * This request handler can handle any request, as it is the default request handler.
	 *
	 * @return boolean TRUE
	 * @author Robert Lemke <robert@typo3.org>
	 */
	public function canHandleRequest() {
		return TRUE;
	}

	/**
	 * Returns the priority - how eager the handler is to actually handle the
	 * request.
	 *
	 * @return integer The priority of the request handler. Always "0" = fallback.
	 * @author Robert Lemke <robert@typo3.org>
	 */
	public function getPriority() {
		return 0;
	}
}

?>