<?php
namespace TYPO3\CMS\Core\Error\Http;

/***************************************************************
 *  Copyright notice
 *
 *  (c) 2011-2013 Steffen Gebert <steffen.gebert@typo3.org>
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
 * Exception for Error 503 - Service Unavailable
 *
 * @author Steffen Gebert <steffen.gebert@typo3.org>
 */
class ServiceUnavailableException extends \TYPO3\CMS\Core\Error\Http\AbstractServerErrorException {

	/**
	 * @var array HTTP Status Header lines
	 */
	protected $statusHeaders = array(\TYPO3\CMS\Core\Utility\HttpUtility::HTTP_STATUS_503);

	/**
	 * @var string Title of the message
	 */
	protected $title = 'Service Unavailable (503)';

	/**
	 * @var string Error Message
	 */
	protected $message = 'This page is currently not available.';

	/**
	 * Constructor for this Status Exception
	 *
	 * @param string $message Error Message
	 * @param integer $code Exception Code
	 */
	public function __construct($message = NULL, $code = 0) {
		if (!empty($message)) {
			$this->message = $message;
		}
		parent::__construct($this->statusHeaders, $this->message, $this->title, $code);
	}

}


?>