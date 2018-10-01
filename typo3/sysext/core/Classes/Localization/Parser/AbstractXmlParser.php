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
     * Returns parsed representation of XML file.
     *
     * @param string $sourcePath Source file path
     * @param string $languageKey Language key
     * @return array
     * @throws \TYPO3\CMS\Core\Localization\Exception\FileNotFoundException
     */
    public function getParsedData($sourcePath, $languageKey)
    {
        $this->sourcePath = $sourcePath;
        $this->languageKey = $languageKey;
        if ($this->languageKey !== 'default') {
            $this->sourcePath = $this->getLocalizedFileName($this->sourcePath, $this->languageKey);
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
        // Disables the functionality to allow external entities to be loaded when parsing the XML, must be kept
        $previousValueOfEntityLoader = libxml_disable_entity_loader(true);
        $rootXmlNode = simplexml_load_string($xmlContent, 'SimpleXMLElement', LIBXML_NOWARNING);
        libxml_disable_entity_loader($previousValueOfEntityLoader);
        if (!isset($rootXmlNode) || $rootXmlNode === false) {
            $xmlError = libxml_get_last_error();
            throw new InvalidXmlFileException(
                'The path provided does not point to existing and accessible well-formed XML file. Reason: ' . $xmlError->message . ' in ' . $this->sourcePath . ', line ' . $xmlError->line,
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
     * @return string|null Absolute path to the language file, or null if error occurred
     */
    protected function getLocalizedFileName($fileRef, $language, $sameLocation = false)
    {
        // If $fileRef is already prefixed with "[language key]" then we should return it as is
        $fileName = PathUtility::basename($fileRef);
        if (GeneralUtility::isFirstPartOfStr($fileName, $language . '.')) {
            return GeneralUtility::getFileAbsFileName($fileRef);
        }

        if ($sameLocation) {
            return GeneralUtility::getFileAbsFileName(str_replace($fileName, $language . '.' . $fileName, $fileRef));
        }

        // Analyze file reference
        if (GeneralUtility::isFirstPartOfStr($fileRef, Environment::getFrameworkBasePath() . '/')) {
            // Is system
            $validatedPrefix = Environment::getFrameworkBasePath() . '/';
        } elseif (GeneralUtility::isFirstPartOfStr($fileRef, Environment::getBackendPath() . '/ext/')) {
            // Is global
            $validatedPrefix = Environment::getBackendPath() . '/ext/';
        } elseif (GeneralUtility::isFirstPartOfStr($fileRef, Environment::getExtensionsPath() . '/')) {
            // Is local
            $validatedPrefix = Environment::getExtensionsPath() . '/';
        } else {
            $validatedPrefix = '';
        }
        if ($validatedPrefix) {
            // Divide file reference into extension key, directory (if any) and base name:
            list($extensionKey, $file_extPath) = explode('/', substr($fileRef, strlen($validatedPrefix)), 2);
            $temp = GeneralUtility::revExplode('/', $file_extPath, 2);
            if (count($temp) === 1) {
                array_unshift($temp, '');
            }
            // Add empty first-entry if not there.
            list($file_extPath, $file_fileName) = $temp;
            // The filename is prefixed with "[language key]." because it prevents the llxmltranslate tool from detecting it.
            return Environment::getLabelsPath() . '/' . $language . '/' . $extensionKey . '/' . ($file_extPath ? $file_extPath . '/' : '') . $language . '.' . $file_fileName;
        }
        return null;
    }

    /**
     * Returns array representation of XML data, starting from a root node.
     *
     * @param \SimpleXMLElement $root A root node
     * @return array An array representing the parsed XML file
     */
    abstract protected function doParsingFromRoot(\SimpleXMLElement $root);
}
