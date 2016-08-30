<?php
namespace TYPO3\CMS\Lang\Service;

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

use TYPO3\CMS\Core\Utility\ExtensionManagementUtility;
use TYPO3\CMS\Core\Utility\GeneralUtility;

/**
 * Extends of extensionmanager ter connection to enrich with translation
 * related methods
 */
class TerService extends \TYPO3\CMS\Extensionmanager\Utility\Connection\TerUtility implements \TYPO3\CMS\Core\SingletonInterface
{
    /**
     * Fetches extensions translation status
     *
     * @param string $extensionKey Extension Key
     * @param string $mirrorUrl URL of mirror to use
     * @return mixed
     */
    public function fetchTranslationStatus($extensionKey, $mirrorUrl)
    {
        $result = false;
        $extPath = GeneralUtility::strtolower($extensionKey);
        $mirrorUrl .= $extPath[0] . '/' . $extPath[1] . '/' . $extPath . '-l10n/' . $extPath . '-l10n.xml';
        $remote = GeneralUtility::getURL($mirrorUrl, 0, [TYPO3_user_agent]);
        if ($remote !== false) {
            $parsed = $this->parseL10nXML($remote);
            $result = $parsed['languagePackIndex'];
        }
        return $result;
    }

    /**
     * Parses content of *-l10n.xml into a suitable array
     *
     * @param string $string: XML data to parse
     * @throws \TYPO3\CMS\Lang\Exception\XmlParser
     * @return array Array representation of XML data
     */
    protected function parseL10nXML($string)
    {
        // Create parser:
        $parser = xml_parser_create();
        // Disables the functionality to allow external entities to be loaded when parsing the XML, must be kept
        $previousValueOfEntityLoader = libxml_disable_entity_loader(true);
        $values = [];
        $index = [];
        xml_parser_set_option($parser, XML_OPTION_CASE_FOLDING, 0);
        xml_parser_set_option($parser, XML_OPTION_SKIP_WHITE, 0);
            // Parse content
        xml_parse_into_struct($parser, $string, $values, $index);
        libxml_disable_entity_loader($previousValueOfEntityLoader);
            // If error, return error message
        if (xml_get_error_code($parser)) {
            $line = xml_get_current_line_number($parser);
            $error = xml_error_string(xml_get_error_code($parser));
            xml_parser_free($parser);
            throw new \TYPO3\CMS\Lang\Exception\XmlParser('Error in XML parser while decoding l10n XML file. Line ' . $line . ': ' . $error, 1345736517);
        } else {
            // Init vars
            $stack = [[]];
            $stacktop = 0;
            $current = [];
            $tagName = '';
            $documentTag = '';
                // Traverse the parsed XML structure:
            foreach ($values as $val) {
                // First, process the tag-name (which is used in both cases, whether "complete" or "close")
                $tagName = ($val['tag'] == 'languagepack' && $val['type'] == 'open') ? $val['attributes']['language'] : $val['tag'];
                if (!$documentTag) {
                    $documentTag = $tagName;
                }
                    // Setting tag-values, manage stack:
                switch ($val['type']) {
                        // If open tag it means there is an array stored in sub-elements.
                        // Therefore increase the stackpointer and reset the accumulation array
                    case 'open':
                            // Setting blank place holder
                        $current[$tagName] = [];
                        $stack[$stacktop++] = $current;
                        $current = [];
                        break;
                        // If the tag is "close" then it is an array which is closing and we decrease the stack pointer.
                    case 'close':
                        $oldCurrent = $current;
                        $current = $stack[--$stacktop];
                            // Going to the end of array to get placeholder key, key($current), and fill in array next
                        end($current);
                        $current[key($current)] = $oldCurrent;
                        unset($oldCurrent);
                        break;
                        // If "complete", then it's a value. Omits the tag if the value is empty.
                    case 'complete':
                        $trimmedValue = trim((string)$val['value']);
                        if ($trimmedValue !== '') {
                            $current[$tagName] = $trimmedValue;
                        }
                        break;
                }
            }
            $result = $current[$tagName];
        }
        return $result;
    }

    /**
     * Install translations for all selected languages for an extension
     *
     * @param string $extensionKey The extension key to install the translations for
     * @param string $language Language code of translation to fetch
     * @param string $mirrorUrl Mirror URL to fetch data from
     * @return bool TRUE on success, error string on failure
     */
    public function updateTranslation($extensionKey, $language, $mirrorUrl)
    {
        $result = false;
        try {
            $l10n = $this->fetchTranslation($extensionKey, $language, $mirrorUrl);
            if (is_array($l10n)) {
                $absolutePathToZipFile = GeneralUtility::getFileAbsFileName('typo3temp/Language/' . $extensionKey . '-l10n-' . $language . '.zip');
                $relativeLanguagePath = 'l10n' . '/' . $language . '/';
                $absoluteLanguagePath = GeneralUtility::getFileAbsFileName(PATH_typo3conf . $relativeLanguagePath);
                $absoluteExtensionLanguagePath = GeneralUtility::getFileAbsFileName(PATH_typo3conf . $relativeLanguagePath . $extensionKey . '/');
                if (empty($absolutePathToZipFile) || empty($absoluteLanguagePath) || empty($absoluteExtensionLanguagePath)) {
                    throw new \TYPO3\CMS\Lang\Exception\Language('Given path is invalid.', 1352565336);
                }
                if (!is_dir($absoluteLanguagePath)) {
                    GeneralUtility::mkdir_deep(PATH_typo3conf, $relativeLanguagePath);
                }
                GeneralUtility::writeFileToTypo3tempDir($absolutePathToZipFile, $l10n[0]);
                if (is_dir($absoluteExtensionLanguagePath)) {
                    GeneralUtility::rmdir($absoluteExtensionLanguagePath, true);
                }
                if ($this->unzipTranslationFile($absolutePathToZipFile, $absoluteLanguagePath)) {
                    $result = true;
                }
            }
        } catch (\TYPO3\CMS\Core\Exception $exception) {
            // @todo logging
        }
        return $result;
    }

    /**
     * Fetches an extensions l10n file from the given mirror
     *
     * @param string $extensionKey Extension Key
     * @param string $language The language code of the translation to fetch
     * @param string $mirrorUrl URL of mirror to use
     * @throws \TYPO3\CMS\Lang\Exception\XmlParser
     * @return array Array containing l10n data
     */
    protected function fetchTranslation($extensionKey, $language, $mirrorUrl)
    {
        $extensionPath = GeneralUtility::strtolower($extensionKey);
        // Typical non sysext path, Hungarian:
        // http://my.mirror/ter/a/n/anextension-l10n/anextension-l10n-hu.zip
        $packageUrl = $extensionPath[0] . '/' . $extensionPath[1] . '/' . $extensionPath .
            '-l10n/' . $extensionPath . '-l10n-' . $language . '.zip';

        try {
            $path = ExtensionManagementUtility::extPath($extensionPath);
            if (strpos($path, '/sysext/') !== false) {
                // This is a system extension and the package URL should be adapted
                list($majorVersion, ) = explode('.', TYPO3_branch);
                // Typical non sysext path, mind the additional version part, French
                // http://my.mirror/ter/b/a/backend-l10n/backend-l10n-fr.v7.zip
                $packageUrl = $extensionPath[0] . '/' . $extensionPath[1] . '/' . $extensionPath .
                    '-l10n/' . $extensionPath . '-l10n-' . $language . '.v' . $majorVersion . '.zip';
            }
        } catch (\BadFunctionCallException $e) {
            // Nothing to do
        }

        $l10nResponse = GeneralUtility::getURL($mirrorUrl . $packageUrl, 0, [TYPO3_user_agent]);
        if ($l10nResponse === false) {
            throw new \TYPO3\CMS\Lang\Exception\XmlParser('Error: Translation could not be fetched.', 1345736785);
        } else {
            return [$l10nResponse];
        }
    }

    /**
     * Unzip an language zip file
     *
     * @param string $file path to zip file
     * @param string $path path to extract to
     * @throws \TYPO3\CMS\Lang\Exception\Language
     * @return bool
     */
    protected function unzipTranslationFile($file, $path)
    {
        $zip = zip_open($file);
        if (is_resource($zip)) {
            $result = true;
            if (!is_dir($path)) {
                GeneralUtility::mkdir_deep($path);
            }
            while (($zipEntry = zip_read($zip)) !== false) {
                $zipEntryName = zip_entry_name($zipEntry);
                if (strpos($zipEntryName, '/') !== false) {
                    $zipEntryPathSegments =  explode('/', $zipEntryName);
                    $fileName = array_pop($zipEntryPathSegments);
                    // It is a folder, because the last segment is empty, let's create it
                    if (empty($fileName)) {
                        GeneralUtility::mkdir_deep($path, implode('/', $zipEntryPathSegments));
                    } else {
                        $absoluteTargetPath = GeneralUtility::getFileAbsFileName($path . implode('/', $zipEntryPathSegments) . '/' . $fileName);
                        if (trim($absoluteTargetPath) !== '') {
                            $return = GeneralUtility::writeFile(
                                $absoluteTargetPath, zip_entry_read($zipEntry, zip_entry_filesize($zipEntry))
                            );
                            if ($return === false) {
                                throw new \TYPO3\CMS\Lang\Exception\Language('Could not write file ' . $zipEntryName, 1345304560);
                            }
                        } else {
                            throw new \TYPO3\CMS\Lang\Exception\Language('Could not write file ' . $zipEntryName, 1352566904);
                        }
                    }
                } else {
                    throw new \TYPO3\CMS\Lang\Exception\Language('Extension directory missing in zip file!', 1352566905);
                }
            }
        } else {
            throw new \TYPO3\CMS\Lang\Exception\Language('Unable to open zip file ' . $file, 1345304561);
        }
        return $result;
    }
}
