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
 * Parser for TYPO3's extension.xml file.
 *
 * Depends on PHP ext/xml which should be available
 * with PHP 4+. This is the parser used in TYPO3
 * Core <= 4.3 (without the "collect all data in one
 * array" behaviour).
 * Notice: ext/xml has proven to be buggy with entities.
 * Use at least PHP 5.2.9+ and libxml2 2.7.3+!
 */
class ExtensionXmlPushParser extends AbstractExtensionXmlParser
{
    /**
     * Keeps current data of element to process.
     *
     * @var string
     */
    protected $elementData = '';

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
     * Method parses an extensions.xml file.
     *
     * @param string $file GZIP stream resource
     * @throws \TYPO3\CMS\Extensionmanager\Exception\ExtensionManagerException in case of parse errors
     */
    public function parseXml($file)
    {
        $this->createParser();
        if (!is_resource($this->objXml)) {
            throw new \TYPO3\CMS\Extensionmanager\Exception\ExtensionManagerException('Unable to create XML parser.', 1342640663);
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
            throw new \TYPO3\CMS\Extensionmanager\Exception\ExtensionManagerException(sprintf('Unable to open file resource %s.', $file), 1342640689);
        }
        while ($data = fread($fp, 4096)) {
            if (!xml_parse($this->objXml, $data, feof($fp))) {
                throw new \TYPO3\CMS\Extensionmanager\Exception\ExtensionManagerException(sprintf('XML error %s in line %u of file resource %s.', xml_error_string(xml_get_error_code($this->objXml)), xml_get_current_line_number($this->objXml), $file), 1342640703);
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
            case 'extension':
                $this->extensionKey = $attrs['extensionkey'];
                break;
            case 'version':
                $this->version = $attrs['version'];
                break;
            default:
                $this->elementData = '';
        }
    }

    /**
     * Method is invoked when parser accesses end tag of an element.
     *
     * @param resource $parser parser resource
     * @param string $elementName Element name at parser's current position
     */
    protected function endElement($parser, $elementName)
    {
        switch ($elementName) {
            case 'extension':
                $this->resetProperties(true);
                break;
            case 'version':
                $this->notify();
                $this->resetProperties();
                break;
            case 'downloadcounter':
                // downloadcounter could be a child node of
                // extension or version
                if ($this->version == null) {
                    $this->extensionDownloadCounter = $this->elementData;
                } else {
                    $this->versionDownloadCounter = $this->elementData;
                }
                break;
            case 'title':
                $this->title = $this->elementData;
                break;
            case 'description':
                $this->description = $this->elementData;
                break;
            case 'state':
                $this->state = $this->elementData;
                break;
            case 'reviewstate':
                $this->reviewstate = $this->elementData;
                break;
            case 'category':
                $this->category = $this->elementData;
                break;
            case 'lastuploaddate':
                $this->lastuploaddate = $this->elementData;
                break;
            case 'uploadcomment':
                $this->uploadcomment = $this->elementData;
                break;
            case 'dependencies':
                $this->dependencies = $this->convertDependencies($this->elementData);
                break;
            case 'authorname':
                $this->authorname = $this->elementData;
                break;
            case 'authoremail':
                $this->authoremail = $this->elementData;
                break;
            case 'authorcompany':
                $this->authorcompany = $this->elementData;
                break;
            case 'ownerusername':
                $this->ownerusername = $this->elementData;
                break;
            case 't3xfilemd5':
                $this->t3xfilemd5 = $this->elementData;
                break;
        }
    }

    /**
     * Method is invoked when parser accesses any character other than elements.
     *
     * @param resource $parser parser resource
     * @param string $data An element's value
     */
    protected function characterData($parser, $data)
    {
        $this->elementData .= $data;
    }
}
