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
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Core\Utility\PathUtility;

/**
 * Parser for PHP locallang array.
 *
 * @deprecated since TYPO3 CMS 7, this file will be removed in TYPO3 CMS 8. Please use XLF files for
 * translation handling. Also note that the extension "extdeveval" has a converter from PHP and XML to XLF.
 */
class LocallangArrayParser implements LocalizationParserInterface
{
    /**
     * @var string
     */
    protected $cacheFileName;

    /**
     * @var \TYPO3\CMS\Core\Charset\CharsetConverter
     */
    protected $csConvObj;

    /**
     * @var string
     */
    protected $hashSource;

    /**
     * @var string
     */
    protected $sourceCharset;

    /**
     * @var string
     */
    protected $targetCharset;

    /**
     * Initializes the parser.
     *
     * @deprecated since TYPO3 CMS 7, will be removed in TYPO3 CMS 8. Use xlf format for parsing translations
     */
    public function __construct()
    {
        GeneralUtility::logDeprecatedFunction();
        $this->createCsConvObject();
    }

    /**
     * Returns parsed representation of PHP locallang file.
     *
     * @param string $sourcePath Source file path
     * @param string $languageKey Language key
     * @param string $charset Charset
     * @return array
     * @throws \RuntimeException
     */
    public function getParsedData($sourcePath, $languageKey, $charset = '')
    {
        $this->validateParameters($sourcePath, $languageKey);
        $this->setCharsets($languageKey, $charset);
        $this->generateCacheFileName($sourcePath, $languageKey);
        if (!file_exists($this->cacheFileName)) {
            $LOCAL_LANG = $this->generateCacheFile($sourcePath, $languageKey);
        } else {
            $LOCAL_LANG = $this->getContentFromCacheFile();
        }
        $xliff = $this->convertToXLIFF($LOCAL_LANG);
        return $xliff;
    }

    /**
     * Converts the LOCAL_LANG array to XLIFF structure.
     *
     * @param array $LOCAL_LANG
     * @return array
     */
    protected function convertToXLIFF(array $LOCAL_LANG)
    {
        foreach ($LOCAL_LANG as &$keysLabels) {
            foreach ($keysLabels as &$label) {
                $label = [
                    0 => [
                        'target' => $label
                    ]
                ];
            }
            unset($label);
        }
        return $LOCAL_LANG;
    }

    /**
     * Creates a character conversion object.
     *
     * @return void
     */
    protected function createCsConvObject()
    {
        if (is_object($GLOBALS['LANG'])) {
            $this->csConvObj = $GLOBALS['LANG']->csConvObj;
        } elseif (is_object($GLOBALS['TSFE'])) {
            $this->csConvObj = $GLOBALS['TSFE']->csConvObj;
        } else {
            $this->csConvObj = GeneralUtility::makeInstance(CharsetConverter::class);
        }
    }

    /**
     * Generates the cache file.
     *
     * @param string $sourcePath
     * @param string $languageKey
     * @return array
     * @throws \RuntimeException
     */
    protected function generateCacheFile($sourcePath, $languageKey)
    {
        $LOCAL_LANG = [];
        // Get PHP data
        include $sourcePath;
        if (!is_array($LOCAL_LANG)) {
            $fileName = PathUtility::stripPathSitePrefix($sourcePath);
            throw new \RuntimeException('TYPO3 Fatal Error: "' . $fileName . '" is no TYPO3 language file!', 1308898491);
        }
        // Converting the default language (English)
        // This needs to be done for a few accented loan words and extension names
        if (is_array($LOCAL_LANG['default']) && $this->targetCharset !== 'utf-8') {
            foreach ($LOCAL_LANG['default'] as &$labelValue) {
                $labelValue = $this->csConvObj->conv($labelValue, 'utf-8', $this->targetCharset);
            }
            unset($labelValue);
        }
        if ($languageKey !== 'default' && is_array($LOCAL_LANG[$languageKey]) && $this->sourceCharset !== $this->targetCharset) {
            foreach ($LOCAL_LANG[$languageKey] as &$labelValue) {
                $labelValue = $this->csConvObj->conv($labelValue, $this->sourceCharset, $this->targetCharset);
            }
            unset($labelValue);
        }
        // Cache the content now:
        if (isset($LOCAL_LANG[$languageKey])) {
            $serContent = ['origFile' => $this->hashSource, 'LOCAL_LANG' => ['default' => $LOCAL_LANG['default'], $languageKey => $LOCAL_LANG[$languageKey]]];
        } else {
            $serContent = ['origFile' => $this->hashSource, 'LOCAL_LANG' => ['default' => $LOCAL_LANG['default']]];
        }
        $res = GeneralUtility::writeFileToTypo3tempDir($this->cacheFileName, serialize($serContent));
        if ($res) {
            throw new \RuntimeException('TYPO3 Fatal Error: "' . $res, 1308898501);
        }
        return $LOCAL_LANG;
    }

    /**
     * Generates the name of the cached file.
     *
     * @param string $sourcePath
     * @param string $languageKey
     * @return void
     */
    protected function generateCacheFileName($sourcePath, $languageKey)
    {
        $this->hashSource = PathUtility::stripPathSitePrefix($sourcePath) . '|' . date('d-m-Y H:i:s', filemtime($sourcePath)) . '|version=2.3';
        $this->cacheFileName = PATH_site . 'typo3temp/llxml/' . substr(basename($sourcePath), 10, 15) . '_' . GeneralUtility::shortMD5($this->hashSource) . '.' . $languageKey . '.' . $this->targetCharset . '.cache';
    }

    /**
     * Obtains the content from the cache file.
     *
     * @return array
     */
    protected function getContentFromCacheFile()
    {
        $serContent = (array)unserialize(file_get_contents($this->cacheFileName));
        $LOCAL_LANG = $serContent['LOCAL_LANG'];
        return (array)$LOCAL_LANG;
    }

    /**
     * Checks if the file is within the web root.
     *
     * @param string $fileName
     * @return bool
     */
    protected function isWithinWebRoot($fileName)
    {
        return (bool)GeneralUtility::getFileAbsFileName($fileName);
    }

    /**
     * Sets character sets for the language key.
     *
     * @param string $languageKey
     * @param string $charset
     * @return void
     */
    protected function setCharsets($languageKey, $charset)
    {
        $this->sourceCharset = $this->csConvObj->parse_charset($this->csConvObj->charSetArray[$languageKey] ?: 'utf-8');
        if ($charset) {
            $this->targetCharset = $this->csConvObj->parse_charset($charset);
        } else {
            $this->targetCharset = 'utf-8';
        }
    }

    /**
     * Validates parameters for the function.
     *
     * @param string $sourcePath
     * @param string $languageKey
     * @return void
     * @throws \RuntimeException
     */
    protected function validateParameters($sourcePath, $languageKey)
    {
        if (!$this->isWithinWebRoot($sourcePath) || !@is_file($sourcePath) || !$languageKey) {
            throw new \RuntimeException(sprintf('Invalid source path (%s) or languageKey (%s)', $sourcePath, $languageKey), 1309245002);
        }
    }
}
