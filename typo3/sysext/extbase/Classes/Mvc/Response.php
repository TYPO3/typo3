<?php
namespace TYPO3\CMS\Extbase\Mvc;

/***************************************************************
 *  Copyright notice
 *
 *  (c) 2010-2013 Extbase Team (http://forge.typo3.org/projects/typo3v4-mvc)
 *  Extbase is a backport of TYPO3 Flow. All credits go to the TYPO3 Flow team.
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
 *  A copy is found in the textfile GPL.txt and important notices to the license
 *  from the author is found in LICENSE.txt distributed with these scripts.
 *
 *
 *  This script is distributed in the hope that it will be useful,
 *  but WITHOUT ANY WARRANTY; without even the implied warranty of
 *  MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 *  GNU General Public License for more details.
 *
 *  This copyright notice MUST APPEAR in all copies of the script!
 ***************************************************************/
/**
 * A generic and very basic response implementation
 *
 * @api
 */
class Response implements \TYPO3\CMS\Extbase\Mvc\ResponseInterface {

	/**
	 * @var string The response content
	 */
	protected $content = NULL;

	/**
	 * Overrides and sets the content of the response
	 *
	 * @param string $content The response content
	 * @return void
	 * @api
	 */
	public function setContent($content) {
		$this->content = $content;
	}

	/**
	 * Appends content to the already existing content.
	 *
	 * @param string $content More response content
	 * @return void
	 * @api
	 */
	public function appendContent($content) {
		$this->content .= $content;
	}

	/**
	 * Returns the response content without sending it.
	 *
	 * @return string The response content
	 * @api
	 */
	public function getContent() {
		return $this->content;
	}

	/**
	 * Fetches the content, returns and clears it.
	 *
	 * @return string
	 * @api
	 */
	public function shutdown() {
		$content = $this->getContent();
		$this->setContent('');
		return $content;
	}

	/**
	 * Returns the content of the response.
	 *
	 * @return string
	 * @api
	 */
	public function __toString() {
		return $this->getContent();
	}
}

?>