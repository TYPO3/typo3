<?php
namespace TYPO3\CMS\Extbase\Error;

/*                                                                        *
 * This script belongs to the Extbase framework                           *
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
 * An object representation of a generic message. Usually, you will use Error, Warning or Notice instead of this one.
 *
 * @license http://www.gnu.org/licenses/lgpl.html GNU Lesser General Public License, version 3 or later
 * @api
 */
class Message {

	/**
	 * The default (english) error message
	 *
	 * @var string
	 */
	protected $message = 'Unknown message';

	/**
	 * The error code
	 *
	 * @var string
	 */
	protected $code;

	/**
	 * The message arguments. Will be replaced in the message body.
	 *
	 * @var array
	 */
	protected $arguments = array();

	/**
	 * An optional title for the message (used eg. in flashMessages).
	 *
	 * @var string
	 */
	protected $title = '';

	/**
	 * Constructs this error
	 *
	 * @param string $message An english error message which is used if no other error message can be resolved
	 * @param integer $code A unique error code
	 * @param array $arguments Array of arguments to be replaced in message
	 * @param string $title optional title for the message
	 * @api
	 */
	public function __construct($message, $code, array $arguments = array(), $title = '') {
		$this->message = $message;
		$this->code = $code;
		$this->arguments = $arguments;
		$this->title = $title;
	}

	/**
	 * Returns the error message
	 *
	 * @return string The error message
	 * @api
	 */
	public function getMessage() {
		return $this->message;
	}

	/**
	 * Returns the error code
	 *
	 * @return string The error code
	 * @api
	 */
	public function getCode() {
		return $this->code;
	}

	/**
	 * Get arguments
	 *
	 * @return array
	 * @api
	 */
	public function getArguments() {
		return $this->arguments;
	}

	/**
	 * Get title
	 *
	 * @return string
	 * @api
	 */
	public function getTitle() {
		return $this->title;
	}

	/**
	 * Return the rendered message
	 *
	 * @return string
	 * @api
	 */
	public function render() {
		if (!empty($this->arguments)) {
			return vsprintf($this->message, $this->arguments);
		} else {
			return $this->message;
		}
	}

	/**
	 * Converts this error into a string
	 *
	 * @return string
	 * @api
	 */
	public function __toString() {
		return $this->render();
	}
}

?>