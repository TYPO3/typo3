<?php

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

namespace TYPO3\CMS\Core\Localization\Parser;

use TYPO3\CMS\Core\Core\Environment;
use TYPO3\CMS\Core\Localization\Exception\FileNotFoundException;
use TYPO3\CMS\Core\Localization\Exception\InvalidXmlFileException;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Core\Utility\PathUtility;

/**
 * Abstract class for XML based parser.
 * @internal This class is a concrete implementation and is not part of the TYPO3 Core API.
 */
abstract class AbstractXmlParser implements LocalizationParserInterface
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
     * New method for parsing xml files, not part of an interface as the plan is to replace
     * the entire API for labels with something different in TYPO3 12
     *
     * @param string $sourcePath
     * @param string $languageKey
     * @param string $fileNamePattern
     * @return array
     */
    public function parseExtensionResource(string $sourcePath, string $languageKey, string $fileNamePattern): array
    {
        $fileName = Environment::getLabelsPath() . sprintf($fileNamePattern, $languageKey);

        return $this->_getParsedData($sourcePath, $languageKey, $fileName);
    }

    /**
     * Returns parsed representation of XML file.
     *
     * @param string $sourcePath Source file path
     * @param string $languageKey Language key
     * @return array
     * @throws \TYPO3\CMS\Core\Localization\Exception\FileNotFoundException
     */
    public function getParsedData($sourcePath, $languageKey)
    {
        return $this->_getParsedData($sourcePath, $languageKey, null);
    }

    /**
     * Actually doing all the work of parsing an XML file
     *
     * @param string $sourcePath Source file path
     * @param string $languageKey Language key
     * @return array
     * @throws \TYPO3\CMS\Core\Localization\Exception\FileNotFoundException
     */
    protected function _getParsedData($sourcePath, $languageKey, ?string $labelsPath)
    {
        $this->sourcePath = $sourcePath;
        $this->languageKey = $languageKey;
        if ($this->languageKey !== 'default') {
            $this->sourcePath = $labelsPath ?? $this->getLocalizedFileName($this->sourcePath, $this->languageKey);
            if (!@is_file($this->sourcePath)) {
                // Global localization is not available, try split localization file
                $this->sourcePath = $this->getLocalizedFileName($sourcePath, $languageKey, true);
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
     * Loads the current XML file before processing.
     *
     * @return array An array representing parsed XML file (structure depends on concrete parser)
     * @throws \TYPO3\CMS\Core\Localization\Exception\InvalidXmlFileException
     */
    protected function parseXmlFile()
    {
        $xmlContent = file_get_contents($this->sourcePath);
        if ($xmlContent === false) {
            throw new InvalidXmlFileException(
                'The path provided does not point to an existing and accessible file.',
                1278155987
            );
        }
        // Disables the functionality to allow external entities to be loaded when parsing the XML, must be kept
        $previousValueOfEntityLoader = null;
        if (PHP_MAJOR_VERSION < 8) {
            $previousValueOfEntityLoader = libxml_disable_entity_loader(true);
        }
        $rootXmlNode = simplexml_load_string($xmlContent, \SimpleXMLElement::class, LIBXML_NOWARNING);
        if (PHP_MAJOR_VERSION < 8) {
            libxml_disable_entity_loader($previousValueOfEntityLoader);
        }
        if ($rootXmlNode === false) {
            $xmlError = libxml_get_last_error();
            throw new InvalidXmlFileException(
                'The path provided does not point to an existing and accessible well-formed XML file. Reason: ' . $xmlError->message . ' in ' . $this->sourcePath . ', line ' . $xmlError->line,
                1278155988
            );
        }
        return $this->doParsingFromRoot($rootXmlNode);
    }

    /**
     * Checks if a localized file is found in labels pack (e.g. a language pack was downloaded in the backend)
     * or if $sameLocation is set, then checks for a file located in "{language}.locallang.xlf" at the same directory
     *
     * @param string $fileRef Absolute file reference to locallang file
     * @param string $language Language key
     * @param bool $sameLocation If TRUE, then locallang localization file name will be returned with same directory as $fileRef
     * @return string Absolute path to the language file
     */
    protected function getLocalizedFileName(string $fileRef, string $language, bool $sameLocation = false)
    {
        // If $fileRef is already prefixed with "[language key]" then we should return it as is
        $fileName = PathUtility::basename($fileRef);
        if (str_starts_with($fileName, $language . '.')) {
            return GeneralUtility::getFileAbsFileName($fileRef);
        }

        if ($sameLocation) {
            return GeneralUtility::getFileAbsFileName(str_replace($fileName, $language . '.' . $fileName, $fileRef));
        }

        // Analyze file reference
        if (str_starts_with($fileRef, Environment::getFrameworkBasePath() . '/')) {
            // Is system
            $validatedPrefix = Environment::getFrameworkBasePath() . '/';
        } elseif (str_starts_with($fileRef, Environment::getBackendPath() . '/ext/')) {
            // Is global
            $validatedPrefix = Environment::getBackendPath() . '/ext/';
        } elseif (str_starts_with($fileRef, Environment::getExtensionsPath() . '/')) {
            // Is local
            $validatedPrefix = Environment::getExtensionsPath() . '/';
        } else {
            $validatedPrefix = '';
        }
        if ($validatedPrefix) {
            // Divide file reference into extension key, directory (if any) and base name:
            [$extensionKey, $file_extPath] = explode('/', substr($fileRef, strlen($validatedPrefix)), 2);
            $temp = GeneralUtility::revExplode('/', $file_extPath, 2);
            if (count($temp) === 1) {
                array_unshift($temp, '');
            }
            // Add empty first-entry if not there.
            [$file_extPath, $file_fileName] = $temp;
            // The filename is prefixed with "[language key]." because it prevents the llxmltranslate tool from detecting it.
            return Environment::getLabelsPath() . '/' . $language . '/' . $extensionKey . '/' . ($file_extPath ? $file_extPath . '/' : '') . $language . '.' . $file_fileName;
        }
        return '';
    }

    /**
     * Returns array representation of XML data, starting from a root node.
     *
     * @param \SimpleXMLElement $root A root node
     * @return array An array representing the parsed XML file
     */
    abstract protected function doParsingFromRoot(\SimpleXMLElement $root);
}
