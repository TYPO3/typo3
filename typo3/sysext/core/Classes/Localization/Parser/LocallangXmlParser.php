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

use TYPO3\CMS\Core\Localization\Exception\InvalidXmlFileException;
use TYPO3\CMS\Core\Utility\ArrayUtility;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Core\Utility\PathUtility;

/**
 * Parser for XML locallang file.
 */
class LocallangXmlParser extends AbstractXmlParser
{
    /**
     * Associative array of "filename => parsed data" pairs.
     *
     * @var array
     */
    protected $parsedTargetFiles;

    /**
     * Returns parsed representation of XML file.
     *
     * @param string $sourcePath Source file path
     * @param string $languageKey Language key
     * @param string $charset Charset
     * @return array
     */
    public function getParsedData($sourcePath, $languageKey, $charset = '')
    {
        $this->sourcePath = $sourcePath;
        $this->languageKey = $languageKey;
        $this->charset = $this->getCharset($languageKey, $charset);
        // Parse source
        $parsedSource = $this->parseXmlFile();
        // Parse target
        $localizedTargetPath = GeneralUtility::getFileAbsFileName(GeneralUtility::llXmlAutoFileName($this->sourcePath, $this->languageKey));
        $targetPath = $this->languageKey !== 'default' && @is_file($localizedTargetPath) ? $localizedTargetPath : $this->sourcePath;
        try {
            $parsedTarget = $this->getParsedTargetData($targetPath);
        } catch (InvalidXmlFileException $e) {
            $parsedTarget = $this->getParsedTargetData($this->sourcePath);
        }
        $LOCAL_LANG = [];
        $LOCAL_LANG[$languageKey] = $parsedSource;
        ArrayUtility::mergeRecursiveWithOverrule($LOCAL_LANG[$languageKey], $parsedTarget);
        return $LOCAL_LANG;
    }

    /**
     * Returns array representation of XLIFF data, starting from a root node.
     *
     * @param \SimpleXMLElement $root XML root element
     * @param string $element Target or Source
     * @return array
     * @throws InvalidXmlFileException
     */
    protected function doParsingFromRootForElement(\SimpleXMLElement $root, $element)
    {
        $bodyOfFileTag = $root->data->languageKey;
        if ($bodyOfFileTag === null) {
            throw new InvalidXmlFileException('Invalid locallang.xml language file "' . PathUtility::stripPathSitePrefix($this->sourcePath) . '"', 1487944884);
        }

        // Check if the source llxml file contains localized records
        $localizedBodyOfFileTag = $root->data->xpath('languageKey[@index=\'' . $this->languageKey . '\']');
        if ($element === 'source' || $this->languageKey === 'default') {
            $parsedData = $this->getParsedDataForElement($bodyOfFileTag, $element);
        } else {
            $parsedData = [];
        }
        if ($element === 'target' && isset($localizedBodyOfFileTag[0]) && $localizedBodyOfFileTag[0] instanceof \SimpleXMLElement) {
            $parsedDataTarget = $this->getParsedDataForElement($localizedBodyOfFileTag[0], $element);
            $mergedData = $parsedDataTarget + $parsedData;
            if ($this->languageKey === 'default') {
                $parsedData = array_intersect_key($mergedData, $parsedData, $parsedDataTarget);
            } else {
                $parsedData = array_intersect_key($mergedData, $parsedDataTarget);
            }
        }
        return $parsedData;
    }

    /**
     * Parse the given language key tag
     *
     * @param \SimpleXMLElement $bodyOfFileTag
     * @param string $element
     * @return array
     */
    protected function getParsedDataForElement(\SimpleXMLElement $bodyOfFileTag, $element)
    {
        $parsedData = [];
        $children = $bodyOfFileTag->children();
        if ($children->count() === 0) {
            // Check for externally-referenced resource:
            // <languageKey index="fr">EXT:yourext/path/to/localized/locallang.xml</languageKey>
            $reference = sprintf('%s', $bodyOfFileTag);
            if (substr($reference, -4) === '.xml') {
                return $this->getParsedTargetData(GeneralUtility::getFileAbsFileName($reference));
            }
        }
        /** @var \SimpleXMLElement $translationElement */
        foreach ($children as $translationElement) {
            if ($translationElement->getName() === 'label') {
                $parsedData[(string)$translationElement['index']][0] = [
                    $element => (string)$translationElement
                ];
            }
        }
        return $parsedData;
    }

    /**
     * Returns array representation of XLIFF data, starting from a root node.
     *
     * @param \SimpleXMLElement $root A root node
     * @return array An array representing parsed XLIFF
     */
    protected function doParsingFromRoot(\SimpleXMLElement $root)
    {
        return $this->doParsingFromRootForElement($root, 'source');
    }

    /**
     * Returns array representation of XLIFF data, starting from a root node.
     *
     * @param \SimpleXMLElement $root A root node
     * @return array An array representing parsed XLIFF
     */
    protected function doParsingTargetFromRoot(\SimpleXMLElement $root)
    {
        return $this->doParsingFromRootForElement($root, 'target');
    }

    /**
     * Returns parsed representation of XML file.
     *
     * Parses XML if it wasn't done before. Caches parsed data.
     *
     * @param string $path An absolute path to XML file
     * @return array Parsed XML file
     */
    public function getParsedTargetData($path)
    {
        if (!isset($this->parsedTargetFiles[$path])) {
            $this->parsedTargetFiles[$path] = $this->parseXmlTargetFile($path);
        }
        return $this->parsedTargetFiles[$path];
    }

    /**
     * Reads and parses XML file and returns internal representation of data.
     *
     * @param string $targetPath Path of the target file
     * @return array
     * @throws \TYPO3\CMS\Core\Localization\Exception\InvalidXmlFileException
     */
    protected function parseXmlTargetFile($targetPath)
    {
        $rootXmlNode = false;
        if (file_exists($targetPath)) {
            $xmlContent = file_get_contents($targetPath);
            // Disables the functionality to allow external entities to be loaded when parsing the XML, must be kept
            $previousValueOfEntityLoader = libxml_disable_entity_loader(true);
            $rootXmlNode = simplexml_load_string($xmlContent, 'SimpleXMLElement', LIBXML_NOWARNING);
            libxml_disable_entity_loader($previousValueOfEntityLoader);
        }
        if (!isset($rootXmlNode) || $rootXmlNode === false) {
            throw new InvalidXmlFileException('The path provided does not point to existing and accessible well-formed XML file (' . $targetPath . ').', 1278155987);
        }
        return $this->doParsingTargetFromRoot($rootXmlNode);
    }
}
