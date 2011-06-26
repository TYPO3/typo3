<?php
/***************************************************************
 *  Copyright notice
 *
 *  (c) 2011 Dominique Feyer <dfeyer@reelpeek.net>
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
 * @package	Core
 * @subpackage	tx_lang
 * @author	Dominique Feyer <dominique.feyer@reelpeek.net>
 */
class tx_lang_Store implements t3lib_Singleton {

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
			$this->supportedExtensions = t3lib_div::trimExplode(',', $GLOBALS['TYPO3_CONF_VARS']['SYS']['lang']['format']['priority']);
		} else {
			$this->supportedExtensions = array('xlf', 'xml', 'php');
		}
	}

	/**
	 * Checks if the store contains parsed data.
	 *
	 * @param string $fileReference File reference
	 * @param string $languageKey Valid language key
	 * @return bool
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
	 * @param $data
	 * @return tx_lang_Store This instance to allow method chaining
	 */
	public function setData($fileReference, $languageKey, $data) {
		$this->data[$fileReference][$languageKey] = $data;
		return $this;
	}

	/**
	 * Checks file reference configuration (charset, extension, ...).
	 *
	 * @throws tx_lang_exception_InvalidParser|tx_lang_exception_FileNotFound
	 * @param string $fileReference File reference
	 * @param string $languageKey Valid language key
	 * @param string $charset Rendering charset
	 * @return tx_lang_Store This instance to allow method chaining
	 */
	public function setConfiguration($fileReference, $languageKey, $charset) {
		$this->configuration[$fileReference] = array(
			'fileReference' => $fileReference,
			'fileExtension' => FALSE,
			'parserClass' => NULL,
			'languageKey' => $languageKey,
			'charset' => $charset
		);

		$fileWithoutExtension = t3lib_div::getFileAbsFileName(preg_replace('/\.[a-z0-9]+$/i' , '' , $fileReference));

		foreach ($this->supportedExtensions as $extension) {
			if (@is_file($fileWithoutExtension . '.' . $extension)) {
				$this->configuration[$fileReference]['fileReference'] = $fileWithoutExtension . '.' . $extension;
				$this->configuration[$fileReference]['fileExtension'] = $extension;
				break;
			}
		}

		if ($this->configuration[$fileReference]['fileExtension'] === FALSE) {
			throw new tx_lang_exception_FileNotFound(
				sprintf('Source localization file (%s) not found', $fileReference),
				1306410755
			);
		}

		$extension = $this->configuration[$fileReference]['fileExtension'];

		if (isset($GLOBALS['TYPO3_CONF_VARS']['SYS']['lang']['parser'][$extension]) && trim($GLOBALS['TYPO3_CONF_VARS']['SYS']['lang']['parser'][$extension]) !== '') {
			$this->configuration[$fileReference]['parserClass'] = $GLOBALS['TYPO3_CONF_VARS']['SYS']['lang']['parser'][$extension];
		} else {
			throw new tx_lang_exception_InvalidParser(
				'TYPO3 Fatal Error: l10n parser for file extension "' . $extension . '" is not configured! Please check you configuration.',
				1301579637
			);
		}

		if (!class_exists($this->configuration[$fileReference]['parserClass']) || trim($this->configuration[$fileReference]['parserClass']) === '') {
			throw new tx_lang_exception_InvalidParser(
				'TYPO3 Fatal Error: l10n parser "' . $this->configuration[$fileReference]['parserClass'] . '" cannot be found or is an empty parser!',
				1270853900
			);
		}

		return $this;
	}

	/**
	 * Returns the correct parser for a specific file reference.
	 *
	 * @throws tx_lang_exception_InvalidParser
	 * @param string $fileReference	File reference
	 * @return tx_lang_ParserInterface
	 */
	public function getParserInstance($fileReference) {
		if (isset($this->configuration[$fileReference]['parserClass']) && trim($this->configuration[$fileReference]['parserClass']) !== '') {
			return t3lib_div::makeInstance((string) $this->configuration[$fileReference]['parserClass']);
		} else {
			throw new tx_lang_exception_InvalidParser(
				sprintf('Invalid parser configuration for the current file (%s)', $fileReference),
				1307293692
			);
		}
	}

	/**
	 * Gets the absolute file path.
	 *
	 * @throws InvalidArgumentException
	 * @param string $fileReference
	 * @return string
	 */
	public function getAbsoluteFileReference($fileReference) {
		if (isset($this->configuration[$fileReference]['fileReference']) && trim($this->configuration[$fileReference]['fileReference']) !== '') {
			return (string) $this->configuration[$fileReference]['fileReference'];
		} else {
			throw new InvalidArgumentException(
				sprintf('Invalid file reference configuration for the current file (%s)', $fileReference),
				1307293692
			);
		}
	}

	/**
	 * @return array
	 */
	public function getSupportedExtensions()
	{
		return $this->supportedExtensions;
	}
}

?>