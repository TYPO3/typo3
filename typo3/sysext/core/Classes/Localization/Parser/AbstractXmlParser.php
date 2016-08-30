<?php
namespace TYPO3\CMS\Core\Localization\Parser;

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

use TYPO3\CMS\Core\Charset\CharsetConverter;
use TYPO3\CMS\Core\Localization\Exception\FileNotFoundException;
use TYPO3\CMS\Core\Localization\Exception\InvalidXmlFileException;
use TYPO3\CMS\Core\Utility\GeneralUtility;

/**
 * Abstract class for XML based parser.
 */
abstract class AbstractXmlParser implements \TYPO3\CMS\Core\Localization\Parser\LocalizationParserInterface
{
    /**
     * @var string
     */
    protected $sourcePath;

    /**
     * @var string
     */
    protected $languageKey;

    /**
     * @var string
     */
    protected $charset;

    /**
     * Returns parsed representation of XML file.
     *
     * @param string $sourcePath Source file path
     * @param string $languageKey Language key
     * @param string $charset File charset
     * @return array
     * @throws \TYPO3\CMS\Core\Localization\Exception\FileNotFoundException
     */
    public function getParsedData($sourcePath, $languageKey, $charset = '')
    {
        $this->sourcePath = $sourcePath;
        $this->languageKey = $languageKey;
        $this->charset = $this->getCharset($languageKey, $charset);
        if ($this->languageKey !== 'default') {
            $this->sourcePath = GeneralUtility::getFileAbsFileName(GeneralUtility::llXmlAutoFileName($this->sourcePath, $this->languageKey));
            if (!@is_file($this->sourcePath)) {
                // Global localization is not available, try split localization file
                $this->sourcePath = GeneralUtility::getFileAbsFileName(GeneralUtility::llXmlAutoFileName($sourcePath, $languageKey, true));
            }
            if (!@is_file($this->sourcePath)) {
                throw new FileNotFoundException('Localization file does not exist', 1306332397);
            }
        }
        $LOCAL_LANG = [];
        $LOCAL_LANG[$languageKey] = $this->parseXmlFile();
        return $LOCAL_LANG;
    }

    /**
     * Gets the character set to use.
     *
     * @param string $languageKey
     * @param string $charset
     * @return string
     */
    protected function getCharset($languageKey, $charset = '')
    {
        /** @var $charsetConverter CharsetConverter */
        if (is_object($GLOBALS['LANG'])) {
            $charsetConverter = $GLOBALS['LANG']->csConvObj;
        } elseif (is_object($GLOBALS['TSFE'])) {
            $charsetConverter = $GLOBALS['TSFE']->csConvObj;
        } else {
            $charsetConverter = GeneralUtility::makeInstance(CharsetConverter::class);
        }
        if ($charset !== '') {
            $targetCharset = $charsetConverter->parse_charset($charset);
        } else {
            $targetCharset = 'utf-8';
        }
        return $targetCharset;
    }

    /**
     * Loads the current XML file before processing.
     *
     * @return array An array representing parsed XML file (structure depends on concrete parser)
     * @throws \TYPO3\CMS\Core\Localization\Exception\InvalidXmlFileException
     */
    protected function parseXmlFile()
    {
        $xmlContent = file_get_contents($this->sourcePath);
        // Disables the functionality to allow external entities to be loaded when parsing the XML, must be kept
        $previousValueOfEntityLoader = libxml_disable_entity_loader(true);
        $rootXmlNode = simplexml_load_string($xmlContent, 'SimpleXMLElement', LIBXML_NOWARNING);
        libxml_disable_entity_loader($previousValueOfEntityLoader);
        if (!isset($rootXmlNode) || $rootXmlNode === false) {
            throw new InvalidXmlFileException('The path provided does not point to existing and accessible well-formed XML file.', 1278155988);
        }
        return $this->doParsingFromRoot($rootXmlNode);
    }

    /**
     * Returns array representation of XML data, starting from a root node.
     *
     * @param \SimpleXMLElement $root A root node
     * @return array An array representing the parsed XML file
     */
    abstract protected function doParsingFromRoot(\SimpleXMLElement $root);
}
