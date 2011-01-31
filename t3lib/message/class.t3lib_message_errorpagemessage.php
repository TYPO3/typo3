<?php
/***************************************************************
 *  Copyright notice
 *
 *  (c) 2010-2011 Benjamin Mack <benni@typo3.org>
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
 * A class representing error messages shown on a page.
 * Classic Example: "No pages are found on rootlevel"
 *
 * @author	Benjamin Mack <benni@typo3.org>
 * @package TYPO3
 * @subpackage t3lib/message
 */
class t3lib_message_ErrorpageMessage extends t3lib_message_AbstractMessage {

	/**
	 * defines whether the message should be stored in the session
	 * (to survive redirects) or only for one request (default)
	 *
	 * @var string
	 */
	protected $htmlTemplate;

	/**
	 * Constructor for a error message
	 *
	 * @param	string	The message.
	 * @param	string	message title.
	 * @param	integer	Optional severity, must be either of t3lib_message_ErrorpageMessage::INFO, t3lib_message_ErrorpageMessage::OK,
	 *				  t3lib_message_ErrorpageMessage::WARNING or t3lib_message_ErrorpageMessage::ERROR. Default is t3lib_message_ErrorpageMessage::ERROR.
	 * @return	void
	 */
	public function __construct($message, $title, $severity = self::ERROR) {
		$this->htmlTemplate = TYPO3_mainDir . 'sysext/t3skin/templates/errorpage-message.html';
		$this->setMessage($message);
		$this->setTitle(strlen($title) > 0 ? $title : 'Error!');
		$this->setSeverity($severity);
	}


	/**
	 * Gets the filename of the HTML template.
	 *
	 * @return	string	The filename of the HTML template.
	 */
	public function getHtmlTemplate() {
		return $this->htmlTemplate;
	}

	/**
	 * Sets the filename to the HTML template
	 *
	 * @param	string	The filename to the HTML template.
	 * @return	void
	 */
	public function setHtmlTemplate($htmlTemplate) {
		$this->htmlTemplate = (string) $htmlTemplate;
	}

	/**
	 * Renders the flash message.
	 *
	 * @return	string	The flash message as HTML.
	 */
	public function render() {
		$classes = array(
			self::NOTICE  => 'notice',
			self::INFO    => 'information',
			self::OK      => 'ok',
			self::WARNING => 'warning',
			self::ERROR   => 'error',
		);

		$markers = array(
			'###CSS_CLASS###'     => $classes[$this->severity],
			'###TITLE###'         => $this->title,
			'###MESSAGE###'       => $this->message,
			'###BASEURL###'       => t3lib_div::getIndpEnv('TYPO3_SITE_URL'),
			'###TYPO3_mainDir###' => TYPO3_mainDir,
			'###TYPO3_copyright_year###' => TYPO3_copyright_year,
		);

		$content = t3lib_div::getUrl(PATH_site . $this->htmlTemplate);
		$content = t3lib_parseHtml::substituteMarkerArray($content, $markers, '', FALSE, TRUE);
		return $content;
	}

	/**
	 * Renders the flash message and echoes it.
	 *
	 * @return	void
	 */
	public function output() {
		$content = $this->render();
		echo $content;
	}

}


if (defined('TYPO3_MODE') && isset($GLOBALS['TYPO3_CONF_VARS'][TYPO3_MODE]['XCLASS']['t3lib/message/class.t3lib_message_errorpagemessage.php'])) {
	include_once($GLOBALS['TYPO3_CONF_VARS'][TYPO3_MODE]['XCLASS']['t3lib/message/class.t3lib_message_errorpagemessage.php']);
}

?>