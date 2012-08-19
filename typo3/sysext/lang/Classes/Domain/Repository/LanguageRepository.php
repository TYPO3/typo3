<?php
/***************************************************************
 *  Copyright notice
 *
 *  (c) 2012 Sebastian Fischer <typo3@evoweb.de>
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
 * Language repository
 *
 * @author Sebastian Fischer <typo3@evoweb.de>
 * @package lang
 * @subpackage LanguageRepository
 */
class Tx_Lang_Domain_Repository_LanguageRepository {

	/**
	 * @var Tx_Extbase_Object_ObjectManagerInterface
	 */
	protected $objectManager;

	/**
	 * @var array
	 */
	protected $selectedLanguages = array();

	/**
	 * @var t3lib_l10n_Locales
	 */
	protected $locales;

	/**
	 * @var array
	 */
	protected $languages = array();

	/**
	 * @var string
	 */
	protected $configurationPath = 'EXTCONF/lang';

	/**
	 * Constructor
	 */
	public function __construct() {
		try {
			$globalSettings = t3lib_configuration::getLocalConfigurationValueByPath($this->configurationPath);
			$this->selectedLanguages = (array) $globalSettings['availableLanguages'];
		} catch (Exception $e) {
			t3lib_configuration::setLocalConfigurationValueByPath(
				$this->configurationPath,
				array('availableLanguages' => array())
			);
		}
	}

	/**
	 * Injects the object manager
	 *
	 * @param Tx_Extbase_Object_ObjectManager $objectManager
	 * @return void
	 */
	public function injectObjectManager(Tx_Extbase_Object_ObjectManager $objectManager) {
		$this->objectManager = $objectManager;
	}

	/**
	 * Injects the locales
	 *
	 * @param t3lib_l10n_Locales $locales
	 * @return void
	 */
	public function injectLocales(t3lib_l10n_Locales $locales) {
		$this->locales = $locales;
	}

	/**
	 * Returns all objects of this repository.
	 *
	 * @return array
	 */
	public function findAll() {
		if (!count($this->languages)) {
			$languages = $this->locales->getLanguages();
			array_shift($languages);

			foreach ($languages as $locale => $language) {
				$label = htmlspecialchars($GLOBALS['LANG']->sL('LLL:EXT:setup/mod/locallang.xml:lang_' . $locale));
				if ($label === '') {
					$label = htmlspecialchars($language);
				}

				$this->languages[$locale] = $this->objectManager->create(
					'Tx_Lang_Domain_Model_Language',
					$locale,
					$label,
					in_array($locale, $this->selectedLanguages)
				);
			}

			usort($this->languages, function($a, $b) {
				/** @var $a Tx_Lang_Domain_Model_Language */
				/** @var $b Tx_Lang_Domain_Model_Language */
				if ($a->getLanguage() == $b->getLanguage()) {
					return 0;
				}
				return $a->getLanguage() < $b->getLanguage() ?  -1: 1;
			});
		}

		return $this->languages;
	}

	/**
	 * Find selected languages
	 *
	 * @return array
	 */
	public function findSelected() {
		$languages = $this->findAll();

		$result = array();
		/** @var $language Tx_Lang_Domain_Model_Language */
		foreach ($languages as $language) {
			if ($language->getSelected()) {
				$result[] = $language;
			}
		}

		return $result;
	}

	/**
	 * Update selected languages
	 *
	 * @param array $languages
	 * @return array
	 */
	public function updateSelectedLanguages($languages) {
			// Add possible dependencies for selected languages
		$dependencies = array();
		foreach ($languages as $language) {
			$dependencies = array_merge($dependencies, $this->locales->getLocaleDependencies($language));
		}
		if (count($dependencies)) {
			$languages = array_unique(array_merge($languages, $dependencies));
		}

		$dir = count($languages) - count($this->selectedLanguages);
		$diff = $dir < 0 ? array_diff($this->selectedLanguages, $languages) : array_diff($languages, $this->selectedLanguages);

		t3lib_configuration::setLocalConfigurationValueByPath(
			$this->configurationPath,
			array('availableLanguages' => $languages)
		);

		return array(
			'success' => count($diff) > 0,
			'dir' => $dir,
			'diff' => array_values($diff),
			'languages' => $languages
		);
	}
}

?>