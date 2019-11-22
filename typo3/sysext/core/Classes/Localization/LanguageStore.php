<?php
namespace TYPO3\CMS\Core\Localization;

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

use TYPO3\CMS\Core\Localization\Exception\FileNotFoundException;
use TYPO3\CMS\Core\Localization\Exception\InvalidParserException;
use TYPO3\CMS\Core\SingletonInterface;
use TYPO3\CMS\Core\Utility\GeneralUtility;

/**
 * @internal This class is not part of the TYPO3 Core API.
 */
class LanguageStore implements SingletonInterface
{
    /**
     * File extension supported by the localization parser
     *
     * @var array
     */
    protected $supportedExtensions;

    /**
     * Information about parsed file
     *
     * If data come from the cache, this array does not contain
     * any information about this file
     *
     * @var array
     */
    protected $configuration;

    /**
     * Parsed localization file
     *
     * @var array
     */
    protected $data;

    /**
     * Constructor
     */
    public function __construct()
    {
        $this->initialize();
    }

    /**
     * Initializes the current class.
     */
    public function initialize()
    {
        if (isset($GLOBALS['TYPO3_CONF_VARS']['SYS']['lang']['format']['priority']) && trim($GLOBALS['TYPO3_CONF_VARS']['SYS']['lang']['format']['priority']) !== '') {
            $this->supportedExtensions = GeneralUtility::trimExplode(',', $GLOBALS['TYPO3_CONF_VARS']['SYS']['lang']['format']['priority']);
        } else {
            $this->supportedExtensions = ['xlf', 'xml'];
        }
    }

    /**
     * Checks if the store contains parsed data.
     *
     * @param string $fileReference File reference
     * @param string $languageKey Valid language key
     * @return bool
     */
    public function hasData($fileReference, $languageKey)
    {
        if (isset($this->data[$fileReference][$languageKey]) && is_array($this->data[$fileReference][$languageKey])) {
            return true;
        }
        return false;
    }

    /**
     * Retrieves data from the store.
     *
     * This method returns all parsed languages for the current file reference.
     *
     * @param string $fileReference File reference
     * @return array
     */
    public function getData($fileReference)
    {
        return $this->data[$fileReference];
    }

    /**
     * Retrieves data from the store for a language.
     *
     * @param string $fileReference File reference
     * @param string $languageKey Valid language key
     * @return array
     * @see self::getData()
     */
    public function getDataByLanguage($fileReference, $languageKey)
    {
        return $this->data[$fileReference][$languageKey] ?? [];
    }

    /**
     * Sets data for a specific file reference and a language.
     *
     * @param string $fileReference File reference
     * @param string $languageKey Valid language key
     * @param array $data
     * @return \TYPO3\CMS\Core\Localization\LanguageStore This instance to allow method chaining
     */
    public function setData($fileReference, $languageKey, $data)
    {
        $this->data[$fileReference][$languageKey] = $data;
        return $this;
    }

    /**
     * Flushes data.
     *
     * @param string $fileReference
     * @return \TYPO3\CMS\Core\Localization\LanguageStore This instance to allow method chaining
     */
    public function flushData($fileReference)
    {
        unset($this->data[$fileReference]);
        return $this;
    }

    /**
     * Checks file reference configuration (charset, extension, ...).
     *
     * @param string $fileReference File reference
     * @param string $languageKey Valid language key
     * @return \TYPO3\CMS\Core\Localization\LanguageStore This instance to allow method chaining
     * @throws \TYPO3\CMS\Core\Localization\Exception\InvalidParserException
     * @throws \TYPO3\CMS\Core\Localization\Exception\FileNotFoundException
     */
    public function setConfiguration($fileReference, $languageKey)
    {
        $this->configuration[$fileReference] = [
            'fileReference' => $fileReference,
            'fileExtension' => false,
            'parserClass' => null,
            'languageKey' => $languageKey
        ];
        $fileWithoutExtension = GeneralUtility::getFileAbsFileName($this->getFileReferenceWithoutExtension($fileReference));
        foreach ($this->supportedExtensions as $extension) {
            if (@is_file($fileWithoutExtension . '.' . $extension)) {
                $this->configuration[$fileReference]['fileReference'] = $fileWithoutExtension . '.' . $extension;
                $this->configuration[$fileReference]['fileExtension'] = $extension;
                break;
            }
        }
        if ($this->configuration[$fileReference]['fileExtension'] === false) {
            throw new FileNotFoundException(sprintf('Source localization file (%s) not found', $fileReference), 1306410755);
        }
        $extension = $this->configuration[$fileReference]['fileExtension'];
        if (isset($GLOBALS['TYPO3_CONF_VARS']['SYS']['lang']['parser'][$extension]) && trim($GLOBALS['TYPO3_CONF_VARS']['SYS']['lang']['parser'][$extension]) !== '') {
            $this->configuration[$fileReference]['parserClass'] = $GLOBALS['TYPO3_CONF_VARS']['SYS']['lang']['parser'][$extension];
        } else {
            throw new InvalidParserException('TYPO3 Fatal Error: l10n parser for file extension "' . $extension . '" is not configured! Please check you configuration.', 1301579637);
        }
        if (!class_exists($this->configuration[$fileReference]['parserClass']) || trim($this->configuration[$fileReference]['parserClass']) === '') {
            throw new InvalidParserException('TYPO3 Fatal Error: l10n parser "' . $this->configuration[$fileReference]['parserClass'] . '" cannot be found or is an empty parser!', 1270853900);
        }
        return $this;
    }

    /**
     * Get the fileReference without the extension
     *
     * @param string $fileReference File reference
     * @return string
     */
    public function getFileReferenceWithoutExtension($fileReference)
    {
        if (!isset($this->configuration[$fileReference]['fileReferenceWithoutExtension'])) {
            $this->configuration[$fileReference]['fileReferenceWithoutExtension'] = preg_replace('/\\.[a-z0-9]+$/i', '', $fileReference);
        }
        return $this->configuration[$fileReference]['fileReferenceWithoutExtension'];
    }

    /**
     * Returns the correct parser for a specific file reference.
     *
     * @param string $fileReference File reference
     * @return \TYPO3\CMS\Core\Localization\Parser\LocalizationParserInterface
     * @throws \TYPO3\CMS\Core\Localization\Exception\InvalidParserException
     */
    public function getParserInstance($fileReference)
    {
        if (isset($this->configuration[$fileReference]['parserClass']) && trim($this->configuration[$fileReference]['parserClass']) !== '') {
            return GeneralUtility::makeInstance((string)$this->configuration[$fileReference]['parserClass']);
        }
        throw new InvalidParserException(sprintf('Invalid parser configuration for the current file (%s)', $fileReference), 1307293692);
    }

    /**
     * Gets the absolute file path.
     *
     * @param string $fileReference
     * @return string
     * @throws \InvalidArgumentException
     */
    public function getAbsoluteFileReference($fileReference)
    {
        if (isset($this->configuration[$fileReference]['fileReference']) && trim($this->configuration[$fileReference]['fileReference']) !== '') {
            return (string)$this->configuration[$fileReference]['fileReference'];
        }
        throw new \InvalidArgumentException(sprintf('Invalid file reference configuration for the current file (%s)', $fileReference), 1307293693);
    }

    /**
     * Get supported extensions
     *
     * @return array
     */
    public function getSupportedExtensions()
    {
        return $this->supportedExtensions;
    }
}
