<?php
namespace TYPO3\CMS\Install\Controller\Action;

/***************************************************************
 *  Copyright notice
 *
 *  (c) 2013 Christian Kuhn <lolli@schwarzbu.ch>
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
 * General action interface
 */
interface ActionInterface {

	/**
	 * Handle this action
	 *
	 * @return string Rendered content
	 */
	public function handle();

	/**
	 * Set form protection token
	 *
	 * @param string $token Form protection token
	 * @return void
	 */
	public function setToken($token);

	/**
	 * Set controller, Either string 'step', 'tool' or 'common'
	 *
	 * @param string $controller Controller name
	 * @return void
	 */
	public function setController($controller);

	/**
	 * Set action name. This is usually similar to the class name,
	 * only for loginForm, the action is login
	 *
	 * @param string $action Name of target action for forms
	 * @return void
	 */
	public function setAction($action);

	/**
	 * Set POST values
	 *
	 * @param array $postValues List of values submitted via POST
	 * @return void
	 */
	public function setPostValues(array $postValues);

	/**
	 * Set the last error array as returned by error_get_last()
	 *
	 * @param array $lastError
	 */
	public function setLastError(array $lastError);

	/**
	 * Status messages from controller
	 *
	 * @param array<\TYPO3\CMS\Install\Status\StatusInterface> $messages
	 */
	public function setMessages(array $messages = array());
}
