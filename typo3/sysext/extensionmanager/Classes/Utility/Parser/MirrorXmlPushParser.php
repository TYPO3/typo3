<?php
namespace TYPO3\CMS\Extensionmanager\Utility\Parser;

/***************************************************************
 * Copyright notice
 *
 * (c) 2010-2013 Marcus Krause <marcus#exp2010@t3sec.info>
 *		 Steffen Kamper <info@sk-typo3.de>
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
 * Parser for TYPO3's mirrors.xml file.
 *
 * Depends on PHP ext/xml which should be available
 * with PHP 4+. This is the parser used in TYPO3
 * Core <= 4.3 (without the "collect all data in one
 * array" behaviour).
 * Notice: ext/xml has proven to be buggy with entities.
 * Use at least PHP 5.2.9+ and libxml2 2.7.3+!
 *
 * @author Marcus Krause <marcus#exp2010@t3sec.info>
 * @author Steffen Kamper <info@sk-typo3.de>
 * @since 2010-11-17
 */
class MirrorXmlPushParser extends \TYPO3\CMS\Extensionmanager\Utility\Parser\AbstractMirrorXmlParser implements \SplSubject {

	/**
	 * Keeps list of attached observers.
	 *
	 * @var SplObserver[]
	 */
	protected $observers = array();

	/**
	 * Class constructor.
	 */
	public function __construct() {
		$this->requiredPhpExtensions = 'xml';
	}

	/**
	 * Create required parser
	 *
	 * @return  void
	 */
	protected function createParser() {
		$this->objXml = xml_parser_create();
		xml_set_object($this->objXml, $this);
	}

	/**
	 * Method parses a mirror.xml file.
	 *
	 * @param string $file GZIP stream resource
	 * @return void
	 * @throws \TYPO3\CMS\Extensionmanager\Exception\ExtensionManagerException in case of XML parser errors
	 */
	public function parseXml($file) {
		$this->createParser();
		if (!is_resource($this->objXml)) {
			throw new \TYPO3\CMS\Extensionmanager\Exception\ExtensionManagerException('Unable to create XML parser.', 1342641009);
		}
		// keep original character case of XML document
		xml_parser_set_option($this->objXml, XML_OPTION_CASE_FOLDING, FALSE);
		xml_parser_set_option($this->objXml, XML_OPTION_SKIP_WHITE, FALSE);
		xml_parser_set_option($this->objXml, XML_OPTION_TARGET_ENCODING, 'utf-8');
		xml_set_element_handler($this->objXml, 'startElement', 'endElement');
		xml_set_character_data_handler($this->objXml, 'characterData');
		if (!($fp = fopen($file, 'r'))) {
			throw new \TYPO3\CMS\Extensionmanager\Exception\ExtensionManagerException(sprintf('Unable to open file resource %s.', htmlspecialchars($file)), 1342641010);
		}
		while ($data = fread($fp, 4096)) {
			if (!xml_parse($this->objXml, $data, feof($fp))) {
				throw new \TYPO3\CMS\Extensionmanager\Exception\ExtensionManagerException(sprintf('XML error %s in line %u of file resource %s.', xml_error_string(xml_get_error_code($this->objXml)), xml_get_current_line_number($this->objXml), htmlspecialchars($file)), 1342641011);
			}
		}
		xml_parser_free($this->objXml);
	}

	/**
	 * Method is invoked when parser accesses start tag of an element.
	 *
	 * @param resource $parser parser resource
	 * @param string $elementName element name at parser's current position
	 * @param array $attrs array of an element's attributes if available
	 * @return void
	 */
	protected function startElement($parser, $elementName, $attrs) {
		switch ($elementName) {
		default:
			$this->element = $elementName;
		}
	}

	/**
	 * Method is invoked when parser accesses end tag of an element.
	 * Although the first parameter seems unused, it needs to be there for
	 * adherence to the API of xml_set_element_handler
	 *
	 * @see xml_set_element_handler
	 * @param resource $parser parser resource
	 * @param string $elementName element name at parser's current position
	 * @return void
	 */
	protected function endElement($parser, $elementName) {
		switch ($elementName) {
		case 'mirror':
			$this->notify();
			$this->resetProperties();
			break;
		default:
			$this->element = NULL;
		}
	}

	/**
	 * Method is invoked when parser accesses any character other than elements.
	 * Although the first parameter seems unused, it needs to be there for
	 * adherence to the API of xml_set_character_data_handler
	 *
	 * @see xml_set_character_data_handler
	 * @param resource $parser parser resource
	 * @param string $data an element's value
	 * @return void
	 */
	protected function characterData($parser, $data) {
		if (isset($this->element)) {
			switch ($this->element) {
			case 'title':
				$this->title = $data;
				break;
			case 'host':
				$this->host = $data;
				break;
			case 'path':
				$this->path = $data;
				break;
			case 'country':
				$this->country = $data;
				break;
			case 'name':
				$this->sponsorname = $data;
				break;
			case 'link':
				$this->sponsorlink = $data;
				break;
			case 'logo':
				$this->sponsorlogo = $data;
				break;
			default:

			}
		}
	}

	/**
	 * Method attaches an observer.
	 *
	 * @param SplObserver $observer an observer to attach
	 * @return void
	 * @see $observers, detach(), notify()
	 */
	public function attach(\SplObserver $observer) {
		$this->observers[] = $observer;
	}

	/**
	 * Method detaches an attached observer
	 *
	 * @param SplObserver $observer an observer to detach
	 * @return void
	 * @see $observers, attach(), notify()
	 */
	public function detach(\SplObserver $observer) {
		$key = array_search($observer, $this->observers, TRUE);
		if (!($key === FALSE)) {
			unset($this->observers[$key]);
		}
	}

	/**
	 * Method notifies attached observers.
	 *
	 * @access public
	 * @return void
	 * @see $observers, attach(), detach()
	 */
	public function notify() {
		foreach ($this->observers as $observer) {
			$observer->update($this);
		}
	}

}


?>