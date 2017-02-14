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
 * Depends on PHP ext/xml which should be available
 * with PHP 4+. This is the parser used in TYPO3
 * Core <= 4.3 (without the "collect all data in one
 * array" behaviour).
 * Notice: ext/xml has proven to be buggy with entities.
 * Use at least PHP 5.2.9+ and libxml2 2.7.3+!
 */
class MirrorXmlPushParser extends AbstractMirrorXmlParser
{
    /**
     * @var string
     */
    protected $element;

    /**
     * Class constructor.
     */
    public function __construct()
    {
        $this->requiredPhpExtensions = 'xml';
    }

    /**
     * Create required parser
     */
    protected function createParser()
    {
        $this->objXml = xml_parser_create();
        xml_set_object($this->objXml, $this);
    }

    /**
     * Method parses a mirror.xml file.
     *
     * @param string $file GZIP stream resource
     * @throws \TYPO3\CMS\Extensionmanager\Exception\ExtensionManagerException in case of XML parser errors
     */
    public function parseXml($file)
    {
        $this->createParser();
        if (!is_resource($this->objXml)) {
            throw new \TYPO3\CMS\Extensionmanager\Exception\ExtensionManagerException('Unable to create XML parser.', 1342641009);
        }
        // Disables the functionality to allow external entities to be loaded when parsing the XML, must be kept
        $previousValueOfEntityLoader = libxml_disable_entity_loader(true);
        // keep original character case of XML document
        xml_parser_set_option($this->objXml, XML_OPTION_CASE_FOLDING, false);
        xml_parser_set_option($this->objXml, XML_OPTION_SKIP_WHITE, false);
        xml_parser_set_option($this->objXml, XML_OPTION_TARGET_ENCODING, 'utf-8');
        xml_set_element_handler($this->objXml, 'startElement', 'endElement');
        xml_set_character_data_handler($this->objXml, 'characterData');
        if (!($fp = fopen($file, 'r'))) {
            throw new \TYPO3\CMS\Extensionmanager\Exception\ExtensionManagerException(sprintf('Unable to open file resource %s.', $file), 1342641010);
        }
        while ($data = fread($fp, 4096)) {
            if (!xml_parse($this->objXml, $data, feof($fp))) {
                throw new \TYPO3\CMS\Extensionmanager\Exception\ExtensionManagerException(sprintf('XML error %s in line %u of file resource %s.', xml_error_string(xml_get_error_code($this->objXml)), xml_get_current_line_number($this->objXml), $file), 1342641011);
            }
        }
        libxml_disable_entity_loader($previousValueOfEntityLoader);
        xml_parser_free($this->objXml);
    }

    /**
     * Method is invoked when parser accesses start tag of an element.
     *
     * @param resource $parser parser resource
     * @param string $elementName element name at parser's current position
     * @param array $attrs array of an element's attributes if available
     */
    protected function startElement($parser, $elementName, $attrs)
    {
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
     */
    protected function endElement($parser, $elementName)
    {
        switch ($elementName) {
            case 'mirror':
                $this->notify();
                $this->resetProperties();
                break;
            default:
                $this->element = null;
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
     */
    protected function characterData($parser, $data)
    {
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
                    // Do nothing
            }
        }
    }
}
