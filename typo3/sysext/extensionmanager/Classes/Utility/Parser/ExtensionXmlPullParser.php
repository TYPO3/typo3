<?php
namespace TYPO3\CMS\Extensionmanager\Utility\Parser;

/***************************************************************
 *  Copyright notice
 *
 *  (c) 2010-2013 Marcus Krause <marcus#exp2010@t3sec.info>
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
 * Module: Extension manager - Extension.xml pull-parser
 */
/**
 * Parser for TYPO3's extension.xml file.
 *
 * Depends on PHP ext/xmlreader which should be available
 * with PHP >= 5.1.0.
 *
 * @author 	  Marcus Krause <marcus#exp2010@t3sec.info>
 * @author 	  Steffen Kamper <info@sk-typo3.de>
 * @since 	   2010-02-09
 */
class ExtensionXmlPullParser extends \TYPO3\CMS\Extensionmanager\Utility\Parser\AbstractExtensionXmlParser implements \SplSubject {

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
		$this->requiredPhpExtensions = 'xmlreader';
	}

	/**
	 * Create required parser
	 *
	 * @return  void
	 */
	protected function createParser() {
		$this->objXml = new \XMLReader();
	}

	/**
	 * Method parses an extensions.xml file.
	 *
	 * @param string $file GZIP stream resource
	 * @return void
	 * @throws \TYPO3\CMS\Extensionmanager\Exception\ExtensionManagerException in case of parser error
	 */
	public function parseXml($file) {
		$this->createParser();
		if (!(is_object($this->objXml) && get_class($this->objXml) == 'XMLReader')) {
			throw new \TYPO3\CMS\Extensionmanager\Exception\ExtensionManagerException('Unable to create XML parser.', 1342640540);
		}
		if ($this->objXml->open($file, 'utf-8') === FALSE) {
			throw new \TYPO3\CMS\Extensionmanager\Exception\ExtensionManagerException(sprintf('Unable to open file resource %s.', htmlspecialchars($file)));
		}
		while ($this->objXml->read()) {
			if ($this->objXml->nodeType == \XMLReader::ELEMENT) {
				$this->startElement($this->objXml->name);
			} else {
				if ($this->objXml->nodeType == \XMLReader::END_ELEMENT) {
					$this->endElement($this->objXml->name);
				} else {
					continue;
				}
			}
		}
		$this->objXml->close();
	}

	/**
	 * Method is invoked when parser accesses start tag of an element.
	 *
	 * @param string $elementName element name at parser's current position
	 * @return void
	 */
	protected function startElement($elementName) {
		switch ($elementName) {
		case 'extension':
			$this->extensionKey = $this->objXml->getAttribute('extensionkey');
			break;
		case 'version':
			$this->version = $this->objXml->getAttribute('version');
			break;
		case 'downloadcounter':
			// downloadcounter could be a child node of
			// extension or version
			if ($this->version == NULL) {
				$this->extensionDownloadCounter = $this->getElementValue($elementName);
			} else {
				$this->versionDownloadCounter = $this->getElementValue($elementName);
			}
			break;
		case 'title':
			$this->title = $this->getElementValue($elementName);
			break;
		case 'description':
			$this->description = $this->getElementValue($elementName);
			break;
		case 'state':
			$this->state = $this->getElementValue($elementName);
			break;
		case 'reviewstate':
			$this->reviewstate = $this->getElementValue($elementName);
			break;
		case 'category':
			$this->category = $this->getElementValue($elementName);
			break;
		case 'lastuploaddate':
			$this->lastuploaddate = $this->getElementValue($elementName);
			break;
		case 'uploadcomment':
			$this->uploadcomment = $this->getElementValue($elementName);
			break;
		case 'dependencies':
			$this->dependencies = $this->convertDependencies($this->getElementValue($elementName));
			break;
		case 'authorname':
			$this->authorname = $this->getElementValue($elementName);
			break;
		case 'authoremail':
			$this->authoremail = $this->getElementValue($elementName);
			break;
		case 'authorcompany':
			$this->authorcompany = $this->getElementValue($elementName);
			break;
		case 'ownerusername':
			$this->ownerusername = $this->getElementValue($elementName);
			break;
		case 't3xfilemd5':
			$this->t3xfilemd5 = $this->getElementValue($elementName);
			break;
		}
	}

	/**
	 * Method is invoked when parser accesses end tag of an element.
	 *
	 * @param string $elementName: element name at parser's current position
	 * @return void
	 */
	protected function endElement($elementName) {
		switch ($elementName) {
		case 'extension':
			$this->resetProperties(TRUE);
			break;
		case 'version':
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
	 * @param string  $elementName: name of element to retrieve it's value from
	 * @return string  an element's value if it has a value, otherwise NULL
	 */
	protected function getElementValue(&$elementName) {
		$value = NULL;
		if (!$this->objXml->isEmptyElement) {
			$value = '';
			while ($this->objXml->read()) {
				if ($this->objXml->nodeType == \XMLReader::TEXT || $this->objXml->nodeType == \XMLReader::CDATA || $this->objXml->nodeType == \XMLReader::WHITESPACE || $this->objXml->nodeType == \XMLReader::SIGNIFICANT_WHITESPACE) {
					$value .= $this->objXml->value;
				} else {
					if ($this->objXml->nodeType == \XMLReader::END_ELEMENT && $this->objXml->name === $elementName) {
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
	 * @param SplObserver  $observer: an observer to attach
	 * @return void
	 */
	public function attach(\SplObserver $observer) {
		$this->observers[] = $observer;
	}

	/**
	 * Method detaches an attached observer
	 *
	 * @param SplObserver  $observer: an observer to detach
	 * @return void
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
	 * @return void
	 */
	public function notify() {
		foreach ($this->observers as $observer) {
			$observer->update($this);
		}
	}

}


?>