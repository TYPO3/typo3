<?php
namespace TYPO3\CMS\Core\Tests\Functional\Framework\Frontend;

/***************************************************************
 * Copyright notice
 *
 * (c) 2014 Oliver Hader <oliver.hader@typo3.org>
 * All rights reserved
 *
 * This script is part of the TYPO3 project. The TYPO3 project is
 * free software; you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation; either version 2 of the License, or
 * (at your option) any later version.
 *
 * The GNU General Public License can be found at
 * http://www.gnu.org/copyleft/gpl.html.
 *
 * This script is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
 * GNU General Public License for more details.
 *
 * This copyright notice MUST APPEAR in all copies of the script!
 ***************************************************************/

/**
 * Model of frontend response
 */
class Response {

	const STATUS_Success = 'success';
	const STATUS_Failure = 'failure';

	/**
	 * @var string
	 */
	protected $status;

	/**
	 * @var NULL|string|array
	 */
	protected $content;

	/**
	 * @var string
	 */
	protected $error;

	/**
	 * @var ResponseContent
	 */
	protected $responseContent;

	/**
	 * @param string $status
	 * @param string $content
	 * @param string $error
	 */
	public function __construct($status, $content, $error) {
		$this->status = $status;
		$this->content = $content;
		$this->error = $error;
	}

	/**
	 * @return string
	 */
	public function getStatus() {
		return $this->status;
	}

	/**
	 * @return array|NULL|string
	 */
	public function getContent() {
		return $this->content;
	}

	/**
	 * @return string
	 */
	public function getError() {
		return $this->error;
	}

	/**
	 * @return ResponseContent
	 */
	public function getResponseContent() {
		if (!isset($this->responseContent)) {
			$this->responseContent = new ResponseContent($this);
		}
		return $this->responseContent;
	}

}
