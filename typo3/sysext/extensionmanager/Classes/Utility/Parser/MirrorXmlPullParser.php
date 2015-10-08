<?php
namespace TYPO3\CMS\Extensionmanager\Utility\Parser;

/*
 * This file is part of the TYPO3 CMS project.
 *
 * It is free software; you can redistribute it and/or modify it under
 * the terms of the GNU General Public License, either version 2
 * of the License, or any later version.
 *
 * For the full copyright and license information, please read the
 * LICENSE.txt file that was distributed with this source code.
 *
 * The TYPO3 project - inspiring people to share!
 */

/**
 * Parser for TYPO3's mirrors.xml file.
 *
 * Depends on PHP ext/xmlreader which should be available
 * with PHP >= 5.1.0.
 * @sincer 2010-02-19
 */
class MirrorXmlPullParser extends AbstractMirrorXmlParser
{
    /**
     * Class constructor.
     *
     * @access public
     */
    public function __construct()
    {
        $this->requiredPhpExtensions = 'xmlreader';
    }

    /**
     * Create required parser
     *
     * @return void
     */
    protected function createParser()
    {
        $this->objXml = new \XMLReader();
    }

    /**
     * Method parses an extensions.xml file.
     *
     * @param string $file file resource, typically a stream
     * @return void
     * @throws \TYPO3\CMS\Extensionmanager\Exception\ExtensionManagerException in case of XML parser errors
     */
    public function parseXml($file)
    {
        $this->createParser();
        if (!(is_object($this->objXml) && get_class($this->objXml) == 'XMLReader')) {
            throw new \TYPO3\CMS\Extensionmanager\Exception\ExtensionManagerException('Unable to create XML parser.', 1342640820);
        }
        if ($this->objXml->open($file, 'utf-8') === false) {
            throw new \TYPO3\CMS\Extensionmanager\Exception\ExtensionManagerException(sprintf('Unable to open file resource %s.', $file), 1342640893);
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
     * @see endElement()
     */
    protected function startElement($elementName)
    {
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
            default:
                // Do nothing
        }
    }

    /**
     * Method is invoked when parser accesses end tag of an element.
     *
     * @param string $elementName element name at parser's current position
     * @return void
     * @see startElement()
     */
    protected function endElement($elementName)
    {
        switch ($elementName) {
            case 'mirror':
                $this->notify();
                $this->resetProperties();
                break;
            default:
                // Do nothing
        }
    }

    /**
     * Method returns the value of an element at XMLReader's current
     * position.
     *
     * Method will read until it finds the end of the given element.
     * If element has no value, method returns NULL.
     *
     * @param string &$elementName name of element to retrieve it's value from
     * @return string an element's value if it has a value, otherwise NULL
     */
    protected function getElementValue(&$elementName)
    {
        $value = null;
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
}
