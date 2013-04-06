<?php
namespace TYPO3\CMS\Core\Localization;

/***************************************************************
 *  Copyright notice
 *
 *  (c) 2011-2013 Dominique Feyer <dfeyer@reelpeek.net>
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
 *  A copy is found in the textfile GPL.txt and important notices to the license
 *  from the author is found in LICENSE.txt distributed with these scripts.
 *
 *
 *  This script is distributed in the hope that it will be useful,
 *  but WITHOUT ANY WARRANTY; without even the implied warranty of
 *  MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 *  GNU General Public License for more details.
 *
 *  This copyright notice MUST APPEAR in all copies of the script!
 ***************************************************************/
/**
 * Language store.
 *
 * @author Dominique Feyer <dominique.feyer@reelpeek.net>
 */
class LanguageStore implements \TYPO3\CMS\Core\SingletonInterface {

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
	public function __construct() {
		$this->initialize();
	}

	/**
	 * Initializes the current class.
	 *
	 * @return void
	 */
	public function initialize() {
		if (isset($GLOBALS['TYPO3_CONF_VARS']['SYS']['lang']['format']['priority']) && trim($GLOBALS['TYPO3_CONF_VARS']['SYS']['lang']['format']['priority']) !== '') {
			$this->supportedExtensions = \TYPO3\CMS\Core\Utility\GeneralUtility::trimExplode(',', $GLOBALS['TYPO3_CONF_VARS']['SYS']['lang']['format']['priority']);
		} else {
			$this->supportedExtensions = array('xlf', 'xml', 'php');
		}
	}

	/**
	 * Checks if the store contains parsed data.
	 *
	 * @param string $fileReference File reference
	 * @param string $languageKey Valid language key
	 * @return boolean
	 */
	public function hasData($fileReference, $languageKey) {
		if (isset($this->data[$fileReference][$languageKey]) && is_array($this->data[$fileReference][$languageKey])) {
			return TRUE;
		}
		return FALSE;
	}

	/**
	 * Retrieves data from the store.
	 *
	 * This method returns all parsed languages for the current file reference.
	 *
	 * @param string $fileReference File reference
	 * @return array
	 */
	public function getData($fileReference) {
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
	public function getDataByLanguage($fileReference, $languageKey) {
		return $this->data[$fileReference][$languageKey];
	}

	/**
	 * Sets data for a specific file reference and a language.
	 *
	 * @param string $fileReference File reference
	 * @param string $languageKey Valid language key
	 * @param array $data
	 * @return \TYPO3\CMS\Core\Localization\LanguageStore This instance to allow method chaining
	 */
	public function setData($fileReference, $languageKey, $data) {
		$this->data[$fileReference][$languageKey] = $data;
		return $this;
	}

	/**
	 * Flushes data.
	 *
	 * @param string $fileReference
	 * @return \TYPO3\CMS\Core\Localization\LanguageStore This instance to allow method chaining
	 */
	public function flushData($fileReference) {
		unset($this->data[$fileReference]);
		return $this;
	}

	/**
	 * Checks file reference configuration (charset, extension, ...).
	 *
	 * @param string $fileReference File reference
	 * @param string $languageKey Valid language key
	 * @param string $charset Rendering charset
	 * @return \TYPO3\CMS\Core\Localization\LanguageStore This instance to allow method chaining
	 * @throws \TYPO3\CMS\Core\Localization\Exception\InvalidParserException
	 * @throws \TYPO3\CMS\Core\Localization\Exception\FileNotFoundException
	 */
	public function setConfiguration($fileReference, $languageKey, $charset) {
		$this->configuration[$fileReference] = array(
			'fileReference' => $fileReference,
			'fileExtension' => FALSE,
			'parserClass' => NULL,
			'languageKey' => $languageKey,
			'charset' => $charset
		);
		$fileWithoutExtension = \TYPO3\CMS\Core\Utility\GeneralUtility::getFileAbsFileName($this->getFileReferenceWithoutExtension($fileReference));
		foreach ($this->supportedExtensions as $extension) {
			if (@is_file(($fileWithoutExtension . '.' . $extension))) {
				$this->configuration[$fileReference]['fileReference'] = $fileWithoutExtension . '.' . $extension;
				$this->configuration[$fileReference]['fileExtension'] = $extension;
				break;
			}
		}
		if ($this->configuration[$fileReference]['fileExtension'] === FALSE) {
			throw new \TYPO3\CMS\Core\Localization\Exception\FileNotFoundException(sprintf('Source localization file (%s) not found', $fileReference), 1306410755);
		}
		$extension = $this->configuration[$fileReference]['fileExtension'];
		if (isset($GLOBALS['TYPO3_CONF_VARS']['SYS']['lang']['parser'][$extension]) && trim($GLOBALS['TYPO3_CONF_VARS']['SYS']['lang']['parser'][$extension]) !== '') {
			$this->configuration[$fileReference]['parserClass'] = $GLOBALS['TYPO3_CONF_VARS']['SYS']['lang']['parser'][$extension];
		} else {
			throw new \TYPO3\CMS\Core\Localization\Exception\InvalidParserException('TYPO3 Fatal Error: l10n parser for file extension "' . $extension . '" is not configured! Please check you configuration.', 1301579637);
		}
		if (!class_exists($this->configuration[$fileReference]['parserClass']) || trim($this->configuration[$fileReference]['parserClass']) === '') {
			throw new \TYPO3\CMS\Core\Localization\Exception\InvalidParserException('TYPO3 Fatal Error: l10n parser "' . $this->configuration[$fileReference]['parserClass'] . '" cannot be found or is an empty parser!', 1270853900);
		}
		return $this;
	}

	/**
	 * Get the filereference without the extension
	 *
	 * @param string $fileReference File reference
	 * @return string
	 */
	public function getFileReferenceWithoutExtension($fileReference) {
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
	public function getParserInstance($fileReference) {
		if (isset($this->configuration[$fileReference]['parserClass']) && trim($this->configuration[$fileReference]['parserClass']) !== '') {
			return \TYPO3\CMS\Core\Utility\GeneralUtility::makeInstance((string) $this->configuration[$fileReference]['parserClass']);
		} else {
			throw new \TYPO3\CMS\Core\Localization\Exception\InvalidParserException(sprintf('Invalid parser configuration for the current file (%s)', $fileReference), 1307293692);
		}
	}

	/**
	 * Gets the absolute file path.
	 *
	 * @param string $fileReference
	 * @return string
	 * @throws \InvalidArgumentException
	 */
	public function getAbsoluteFileReference($fileReference) {
		if (isset($this->configuration[$fileReference]['fileReference']) && trim($this->configuration[$fileReference]['fileReference']) !== '') {
			return (string) $this->configuration[$fileReference]['fileReference'];
		} else {
			throw new \InvalidArgumentException(sprintf('Invalid file reference configuration for the current file (%s)', $fileReference), 1307293692);
		}
	}

	/**
	 * Get supported extensions
	 *
	 * @return array
	 */
	public function getSupportedExtensions() {
		return $this->supportedExtensions;
	}

}


?>