<?php
/***************************************************************
 *  Copyright notice
 *
 *  (c) 2009-2011 Ingo Renner <ingo@typo3.org>
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
 * Abstract class as base for standalone messages (error pages etc.)
 *
 * @author Steffen Gebert <steffen.gebert@typo3.org>
 * @package TYPO3
 * @subpackage t3lib/message
 */
abstract class t3lib_message_AbstractStandaloneMessage extends t3lib_message_AbstractMessage {

	/**
	 * Path to the HTML template file, relative to PATH_site
	 *
	 * @var string
	 */
	protected $htmlTemplate;

	/**
	 * Default markers
	 * @var array
	 */
	protected $defaultMarkers = array();

	/**
	 * Markers in template to be filled
	 *
	 * @var array
	 */
	protected $markers = array();

	/**
	 * Constructor
	 *
	 * @param string $message Message
	 * @param string $title Title
	 * @param int $severity Severity, see class constants of t3lib_message_AbstractMessage
	 */
	public function __construct($message = '', $title = '', $severity = t3lib_message_AbstractMessage::ERROR) {
		if (!empty($message)) {
			$this->setMessage($message);
		}
		$this->setTitle(!empty($title) ? $title : 'Error!');
		$this->setSeverity($severity);
	}

	/**
	 * Sets the markers of the templates, which have to be replaced with the specified contents.
	 * The marker array passed, will be merged with already present markers.
	 *
	 * @param array $markers Array containing the markers and values (e.g. ###MARKERNAME### => value)
	 * @return void
	 */
	public function setMarkers(array $markers) {
		$this->markers = array_merge($this->markers, $markers);
	}

	/**
	 * Returns the default markers like title and message, which exist for every standalone message
	 * @return array
	 */
	protected function getDefaultMarkers() {
		$classes = array(
			self::NOTICE  => 'notice',
			self::INFO    => 'information',
			self::OK      => 'ok',
			self::WARNING => 'warning',
			self::ERROR   => 'error',
		);

		$defaultMarkers = array(
			'###CSS_CLASS###'     => $classes[$this->severity],
			'###TITLE###'         => $this->title,
			'###MESSAGE###'       => $this->message,
			'###BASEURL###'       => t3lib_div::getIndpEnv('TYPO3_SITE_URL'),
			'###TYPO3_mainDir###' => TYPO3_mainDir,
			'###TYPO3_copyright_year###' => TYPO3_copyright_year,
		);

		return $defaultMarkers;
	}

	/**
	 * Gets the filename of the HTML template.
	 *
	 * @return string The filename of the HTML template.
	 */
	public function getHtmlTemplate() {
		if (!$this->htmlTemplate) {
			throw new RuntimeException(
				'No HTML template file has been defined, yet',
				1314390127
			);
		}
		return $this->htmlTemplate;
	}

	/**
	 * Sets the filename to the HTML template
	 *
	 * @param string $htmlTemplate The filename of the HTML template, relative to PATH_site
	 * @return void
	 */
	public function setHtmlTemplate($htmlTemplate) {
		$this->htmlTemplate = PATH_site . $htmlTemplate;

		if (!file_exists($this->htmlTemplate)) {
			throw new RuntimeException(
				'Template file "' . $this->htmlTemplate .'" not found',
				1312830504
			);
		}
	}

	/**
	 * Renders the message.
	 *
	 * @return string The message as HTML.
	 */
	public function render() {
		$markers = array_merge($this->getDefaultMarkers(), $this->markers);

		$content = t3lib_div::getUrl($this->htmlTemplate);
		$content = t3lib_parseHtml::substituteMarkerArray($content, $markers, '', FALSE, TRUE);
		return $content;
	}

	/**
	 * Renders the message and echoes it.
	 *
	 * @return void
	 */
	public function output() {
		$content = $this->render();
		echo $content;
	}

}


if (defined('TYPO3_MODE') && isset($GLOBALS['TYPO3_CONF_VARS'][TYPO3_MODE]['XCLASS']['t3lib/message/class.t3lib_message_abstractstandalonemessage.php'])) {
	include_once($GLOBALS['TYPO3_CONF_VARS'][TYPO3_MODE]['XCLASS']['t3lib/message/class.t3lib_message_abstractstandalonemessage.php']);
}

?>