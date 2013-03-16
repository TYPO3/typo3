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
 * HTTP Status Exception
 *
 * @author Steffen Gebert <steffen.gebert@typo3.org>
 */
class StatusException extends \TYPO3\CMS\Core\Error\Exception {

	/**
	 * @var array HTTP Status Header lines
	 */
	protected $statusHeaders;

	/**
	 * @var string Title of the message
	 */
	protected $title = 'Oops, an error occurred!';

	/**
	 * Constructor for this Status Exception
	 *
	 * @param string|array $statusHeaders HTTP Status header line(s)
	 * @param string $title Title of the error message
	 * @param string $message Error Message
	 * @param integer $code Exception Code
	 */
	public function __construct($statusHeaders, $message, $title = '', $code = 0) {
		if (is_array($statusHeaders)) {
			$this->statusHeaders = $statusHeaders;
		} else {
			$this->statusHeaders = array($statusHeaders);
		}
		$this->title = $title ? $title : $this->title;
		parent::__construct($message, $code);
	}

	/**
	 * Setter for the title.
	 *
	 * @param string $title
	 * @return void
	 */
	public function setTitle($title) {
		$this->title = $title;
	}

	/**
	 * Getter for the title.
	 *
	 * @return string
	 */
	public function getTitle() {
		return $this->title;
	}

	/**
	 * Getter for the Status Header.
	 *
	 * @return string
	 */
	public function getStatusHeaders() {
		return $this->statusHeaders;
	}

}


?>