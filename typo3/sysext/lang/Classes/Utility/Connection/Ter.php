<?php
namespace TYPO3\CMS\Lang\Utility\Connection;
/***************************************************************
 *  Copyright notice
 *
 *  (c) 2012-2013 Sebastian Fischer <typo3@evoweb.de>
 *  All rights reserved
 *
 *  This script is part of the TYPO3 project. The TYPO3 project is
 *  free software; you can redistribute it and/or modify
 *  it under the terms of the GNU General Public License as published by
 *  the Free Software Foundation; either version 2 of the License, or
 *  (at your option) any later version.
 *
 *  The GNU General Public License can be found at
 *  http://www.gnu.org/copyleft/gpl.html.
 *
 *  This script is distributed in the hope that it will be useful,
 *  but WITHOUT ANY WARRANTY; without even the implied warranty of
 *  MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 *  GNU General Public License for more details.
 *
 *  This copyright notice MUST APPEAR in all copies of the script!
 ***************************************************************/

/**
 * Extends of extensionmanager ter connection to enrich with translation related methods
 *
 * @author Sebastian Fischer <typo3@evoweb.de>
 */
class Ter extends \TYPO3\CMS\Extensionmanager\Utility\Connection\TerUtility {

	/**
	 * Fetches extensions translation status
	 *
	 * @param string $extensionKey Extension Key
	 * @param string $mirrorUrl URL of mirror to use
	 * @return mixed
	 */
	public function fetchTranslationStatus($extensionKey, $mirrorUrl) {
		$result = FALSE;
		$extPath = \TYPO3\CMS\Core\Utility\GeneralUtility::strtolower($extensionKey);
		$mirrorUrl .= $extPath{0} . '/' . $extPath{1} . '/' . $extPath . '-l10n/' . $extPath . '-l10n.xml';
		$remote = \TYPO3\CMS\Core\Utility\GeneralUtility::getURL($mirrorUrl, 0, array(TYPO3_user_agent));

		if ($remote !== FALSE) {
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
	protected function parseL10nXML($string) {
			// Create parser:
		$parser = xml_parser_create();
		$values = array();
		$index = array();

		xml_parser_set_option($parser, XML_OPTION_CASE_FOLDING, 0);
		xml_parser_set_option($parser, XML_OPTION_SKIP_WHITE, 0);

			// Parse content
		xml_parse_into_struct($parser, $string, $values, $index);

			// If error, return error message
		if (xml_get_error_code($parser)) {
			$line = xml_get_current_line_number($parser);
			$error = xml_error_string(xml_get_error_code($parser));
			xml_parser_free($parser);
			throw new \TYPO3\CMS\Lang\Exception\XmlParser('Error in XML parser while decoding l10n XML file. Line ' . $line . ': ' . $error, 1345736517);
		} else {
				// Init vars
			$stack = array(array());
			$stacktop = 0;
			$current = array();
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
						$current[$tagName] = array();
						$stack[$stacktop++] = $current;
						$current = array();
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
						// If "complete", then it's a value. If the attribute "base64" is set, then decode the value, otherwise just set it.
					case 'complete':
							// Had to cast it as a string - otherwise it would be evaluate FALSE if tested with isset()!!
						$current[$tagName] = (string) $val['value'];
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
	 * @return boolean TRUE on success, error string on fauilure
	 */
	public function updateTranslation($extensionKey, $language, $mirrorUrl) {
		$result = FALSE;
		try {
			$l10n = $this->fetchTranslation($extensionKey, $language, $mirrorUrl);
			if (is_array($l10n)) {
				$absolutePathToZipFile = \TYPO3\CMS\Core\Utility\GeneralUtility::getFileAbsFileName('typo3temp/' . $extensionKey . '-l10n-' . $language . '.zip');
				$relativeLanguagePath = 'l10n' . '/' . $language . '/';
				$absoluteLanguagePath = \TYPO3\CMS\Core\Utility\GeneralUtility::getFileAbsFileName(PATH_typo3conf . $relativeLanguagePath);
				$absoluteExtensionLanguagePath = \TYPO3\CMS\Core\Utility\GeneralUtility::getFileAbsFileName(PATH_typo3conf . $relativeLanguagePath. $extensionKey . '/');
				if (empty($absolutePathToZipFile) || empty($absoluteLanguagePath) || empty($absoluteExtensionLanguagePath)) {
					throw new \TYPO3\CMS\Lang\Exception\Lang('Given path is invalid.', 1352565336);
				}
				if (!is_dir($absoluteLanguagePath)) {
					\TYPO3\CMS\Core\Utility\GeneralUtility::mkdir_deep(PATH_typo3conf, $relativeLanguagePath);
				}
				\TYPO3\CMS\Core\Utility\GeneralUtility::writeFile($absolutePathToZipFile, $l10n[0]);
				if (is_dir($absoluteExtensionLanguagePath)) {
					\TYPO3\CMS\Core\Utility\GeneralUtility::rmdir($absoluteExtensionLanguagePath, TRUE);
				}

				if ($this->unzipTranslationFile($absolutePathToZipFile, $absoluteLanguagePath)) {
					$result = TRUE;
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
	protected function fetchTranslation($extensionKey, $language, $mirrorUrl) {
		$extensionPath = \TYPO3\CMS\Core\Utility\GeneralUtility::strtolower($extensionKey);
		$mirrorUrl .= $extensionPath{0} . '/' . $extensionPath{1} . '/' . $extensionPath .
			'-l10n/' . $extensionPath . '-l10n-' . $language . '.zip';
		$l10nResponse = \TYPO3\CMS\Core\Utility\GeneralUtility::getURL($mirrorUrl, 0, array(TYPO3_user_agent));

		if ($l10nResponse === FALSE) {
			throw new \TYPO3\CMS\Lang\Exception\XmlParser('Error: Translation could not be fetched.', 1345736785);
		} else {
			return array($l10nResponse);
		}
	}

	/**
	 * Unzip an language.zip.
	 *
	 * @param string $file path to zip file
	 * @param string $path path to extract to
	 * @throws \TYPO3\CMS\Lang\Exception\Lang
	 * @return boolean
	 */
	protected function unzipTranslationFile($file, $path) {
		$zip = zip_open($file);
		if (is_resource($zip)) {
			$result = TRUE;

			if (!is_dir($path)) {
				\TYPO3\CMS\Core\Utility\GeneralUtility::mkdir_deep($path);
			}

			while (($zipEntry = zip_read($zip)) !== FALSE) {
				$zipEntryName = zip_entry_name($zipEntry);
				if (strpos($zipEntryName, '/') !== FALSE) {
					$zipEntryPathSegments =  explode('/', $zipEntryName);
					$fileName = array_pop($zipEntryPathSegments);
					// It is a folder, because the last segment is empty, let's create it
					if (empty($fileName)) {
						\TYPO3\CMS\Core\Utility\GeneralUtility::mkdir_deep($path, implode('/', $zipEntryPathSegments));
					} else {
						$absoluteTargetPath = \TYPO3\CMS\Core\Utility\GeneralUtility::getFileAbsFileName($path . implode('/', $zipEntryPathSegments) . '/' . $fileName);
						if (strlen(trim($absoluteTargetPath)) > 0) {
							$return = \TYPO3\CMS\Core\Utility\GeneralUtility::writeFile(
								$absoluteTargetPath, zip_entry_read($zipEntry, zip_entry_filesize($zipEntry))
							);
							if ($return === FALSE) {
								throw new \TYPO3\CMS\Lang\Exception\Lang('Could not write file ' . $zipEntryName, 1345304560);
							}
						} else {
							throw new \TYPO3\CMS\Lang\Exception\Lang('Could not write file ' . $zipEntryName, 1352566904);
						}
					}
				} else {
					throw new \TYPO3\CMS\Lang\Exception\Lang('Extension directory missing in zip file!', 1352566904);
				}
			}
		} else {
			throw new \TYPO3\CMS\Lang\Exception\Lang('Unable to open zip file ' . $file, 1345304561);
		}

		return $result;
	}
}

?>