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
 * Exception for Error 400 - Bad Request
 *
 * @author Steffen Gebert <steffen.gebert@typo3.org>
 */
class BadRequestException extends \TYPO3\CMS\Core\Error\Http\AbstractClientErrorException {

	/**
	 * @var array HTTP Status Header lines
	 */
	protected $statusHeaders = array(\TYPO3\CMS\Core\Utility\HttpUtility::HTTP_STATUS_400);

	/**
	 * @var string Title of the message
	 */
	protected $title = 'Bad Request (400)';

	/**
	 * @var string Error Message
	 */
	protected $message = 'The request cannot be fulfilled due to bad syntax.';

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