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
 * Module: Extension manager - Extension.xml pull-parser
 */
/**
 * Parser for TYPO3's extension.xml file.
 *
 * Depends on PHP ext/xmlreader which should be available
 * with PHP >= 5.1.0.
 * @since 	   2010-02-09
 */
class ExtensionXmlPullParser extends AbstractExtensionXmlParser
{
    /**
     * Class constructor.
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
     * @param string $file GZIP stream resource
     * @return void
     * @throws \TYPO3\CMS\Extensionmanager\Exception\ExtensionManagerException in case of parser error
     */
    public function parseXml($file)
    {
        $this->createParser();
        if (!(is_object($this->objXml) && get_class($this->objXml) == 'XMLReader')) {
            throw new \TYPO3\CMS\Extensionmanager\Exception\ExtensionManagerException('Unable to create XML parser.', 1342640540);
        }
        if ($this->objXml->open($file, 'utf-8') === false) {
            throw new \TYPO3\CMS\Extensionmanager\Exception\ExtensionManagerException(sprintf('Unable to open file resource %s.', $file));
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
    protected function startElement($elementName)
    {
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
                if ($this->version == null) {
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
    protected function endElement($elementName)
    {
        switch ($elementName) {
            case 'extension':
                $this->resetProperties(true);
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
