<?php
namespace TYPO3\CMS\Core\Http;

/*
 * This file is part of the TYPO3 CMS project.
 *
 * It is free software; you can redistribute it and/or modify it under
 * the terms of the GNU General Public License, either version 2
 * of the License, or any later version.
 *
 * For the full copyright and license information, please read the
 * LICENSE.txt file that was distributed with this source code.
 *
 * The TYPO3 project - inspiring people to share!
 */

use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\RequestInterface;

/**
 * An interface every controller should implement
 * in order to deal with PSR-7 standard.
 *
 * @internal please note that this API will be extended until TYPO3 CMS 7 LTS and is not public yet.
 */
interface ControllerInterface {

	/**
	 * Processes a typical request.
	 *
	 * @param RequestInterface $request The request object
	 * @return ResponseInterface $response The response, created by the controller
	 * @api
	 */
	public function processRequest(RequestInterface $request);

}
