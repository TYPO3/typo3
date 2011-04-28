<?php
/***************************************************************
 *  Copyright notice
 *
 *  (c) 2010 Marcus Krause <marcus#exp2010@t3sec.info>
 *		   Steffen Kamper <info@sk-typo3.de>
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
 * class.tx_em_parser_mirrorxmlpullparser.php
 *
 * Module: Extension manager - mirrors.xml pull-parser
 *
 * @author  Marcus Krause <marcus#exp2010@t3sec.info>
 * @author  Steffen Kamper <info@sk-typo3.de>
 */


/**
 * Parser for TYPO3's mirrors.xml file.
 *
 * Depends on PHP ext/xmlreader which should be available
 * with PHP >= 5.1.0.
 *
 * @author	  Marcus Krause <marcus#exp2010@t3sec.info>
 * @author	  Steffen Kamper <info@sk-typo3.de>
 *
 * @since	   2010-02-19
 * @package	 TYPO3
 * @subpackage  EM
 */
class tx_em_Parser_MirrorXmlPullParser extends tx_em_Parser_MirrorXmlAbstractParser implements SplSubject {


	/**
	 * Keeps list of attached observers.
	 *
	 * @var  array
	 */
	protected $observers = array();


	/**
	 * Class constructor.
	 *
	 * @access  public
	 * @return  void
	 */
	function __construct() {
		$this->requiredPHPExt = 'xmlreader';

		if ($this->isAvailable()) {
			$this->objXML = new XMLReader();
		}
	}

	/**
	 * Method parses an extensions.xml file.
	 *
	 * @access  public
	 * @param   string  $file  file resource, typically a stream
	 * @return  void
	 * @throws  em_mirrorxml_Exception  in case of XML parser errors
	 */
	public function parseXML($file) {
		if (!(is_object($this->objXML) && (get_class($this->objXML) == 'XMLReader'))) {
			$this->throwException('Unable to create XML parser.');
		}
		$this->objXML->open($file, 'utf-8') || $this->throwException(sprintf('Unable to open file ressource %s.', htmlspecialchars($file)));

		while ($this->objXML->read()) {

			if ($this->objXML->nodeType == XMLReader::ELEMENT) {
				$this->startElement($this->objXML->name);
			} else {
				if ($this->objXML->nodeType == XMLReader::END_ELEMENT) {
					$this->endElement($this->objXML->name);
				} else {
					continue;
				}
			}
		}
		$this->objXML->close();
	}

	/**
	 * Method is invoked when parser accesses start tag of an element.
	 *
	 * @access  protected
	 * @param   string	 $elementName  element name at parser's current position
	 * @return  void
	 * @see	 endElement()
	 */
	protected function startElement($elementName) {
		switch ($elementName) {
			case 'title':
				$this->title = $this->getElementValue($elementName);
				break;
			case 'host':
				$this->host = $this->getElementValue($elementName);
				break;
			case 'path':
				$this->path = $this->getElementValue($elementName);
				break;
			case 'country':
				$this->country = $this->getElementValue($elementName);
				break;
			case 'name':
				$this->sponsorname = $this->getElementValue($elementName);
				break;
			case 'link':
				$this->sponsorlink = $this->getElementValue($elementName);
				break;
			case 'logo':
				$this->sponsorlogo = $this->getElementValue($elementName);
				break;
		}
	}

	/**
	 * Method is invoked when parser accesses end tag of an element.
	 *
	 * @access  protected
	 * @param   string	 $elementName  element name at parser's current position
	 * @return  void
	 * @see	 startElement()
	 */
	protected function endElement($elementName) {
		switch ($elementName) {
			case 'mirror':
				$this->notify();
				$this->resetProperties();
				break;
		}
	}

	/**
	 * Method returns the value of an element at XMLReader's current
	 * position.
	 *
	 * Method will read until it finds the end of the given element.
	 * If element has no value, method returns NULL.
	 *
	 * @access  protected
	 * @param   string	 $elementName  name of element to retrieve it's value from
	 * @return  string	 an element's value if it has a value, otherwise NULL
	 */
	protected function getElementValue(&$elementName) {
		$value = NULL;
		if (!$this->objXML->isEmptyElement) {
			$value = '';
			while ($this->objXML->read()) {
				if ($this->objXML->nodeType == XMLReader::TEXT
						|| $this->objXML->nodeType == XMLReader::CDATA
						|| $this->objXML->nodeType == XMLReader::WHITESPACE
						|| $this->objXML->nodeType == XMLReader::SIGNIFICANT_WHITESPACE) {
					$value .= $this->objXML->value;
				} else {
					if ($this->objXML->nodeType == XMLReader::END_ELEMENT
							&& $this->objXML->name === $elementName) {
						break;
					}
				}
			}
		}
		return $value;
	}

	/**
	 * Method attaches an observer.
	 *
	 * @access  public
	 * @param   SplObserver  $observer  an observer to attach
	 * @return  void
	 * @see	 $observers, detach(), notify()
	 */
	public function attach(SplObserver $observer) {
		$this->observers[] = $observer;
	}

	/**
	 * Method detaches an attached observer
	 *
	 * @access  public
	 * @param   SplObserver  $observer  an observer to detach
	 * @return  void
	 * @see	 $observers, attach(), notify()
	 */
	public function detach(SplObserver $observer) {
		$key = array_search($observer, $this->observers, TRUE);
		if (!($key === false)) {
			unset($this->observers[$key]);
		}
	}

	/**
	 * Method notifies attached observers.
	 *
	 * @access  public
	 * @return  void
	 * @see	 $observers, attach(), detach()
	 */
	public function notify() {
		foreach ($this->observers as $observer) {
			$observer->update($this);
		}
	}
}

if (defined('TYPO3_MODE') && isset($GLOBALS['TYPO3_CONF_VARS'][TYPO3_MODE]['XCLASS']['typo3/sysext/em/classes/parser/class.tx_em_parser_mirrorxmlpullparser.php'])) {
	include_once($GLOBALS['TYPO3_CONF_VARS'][TYPO3_MODE]['XCLASS']['typo3/sysext/em/classes/parser/class.tx_em_parser_mirrorxmlpullparser.php']);
}

?>