<?php
/***************************************************************
 *  Copyright notice
 *
 *  (c) 2010 Steffen Kamper (info@sk-typo3.de)
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
 * Module: Extension manager, developer module
 *
 * @author	Steffen Kamper <info@sk-typo3.de>
 */


$GLOBALS['LANG']->includeLLFile(t3lib_extMgm::extPath('em', 'language/locallang.xml'));


class tx_em_Settings implements t3lib_Singleton {

	public $MOD_MENU = array(); // Module menu items


	/**
	 * Settings array
	 *
	 * @var array
	 */
	protected $settings;


	/**
	 * Constructor
	 *
	 */
	public function __construct() {
		$this->readSettings();
	}

	/**
	 * Get settings
	 */
	public function getSettings() {
		return $this->settings;
	}

	/**
	 * Get user Settings
	 */
	public function getUserSettings() {
		$userSettings = t3lib_beFunc::getModTSconfig(0, 'mod.tools_em');
		return $userSettings['properties'];
	}

	/**
	 * Save user settings
	 *
	 * @param array $settings
	 */
	public function saveSettings($settings) {
		$this->settings = t3lib_BEfunc::getModuleData($this->MOD_MENU, $settings, 'tools_em');
		return $this->settings;
	}

	/**
	 * Save single value in session settings
	 *
	 * @param string $name
	 * @param string $value
	 */
	public function saveSetting($name, $value) {
		t3lib_BEfunc::getModuleData($this->MOD_MENU, array($name => $value), 'tools_em');
	}

	/**
	 * Initial settings for extension manager module data
	 *
	 * @return void
	 */
	protected function readSettings() {
		$globalSettings = unserialize($GLOBALS['TYPO3_CONF_VARS']['EXT']['extConf']['em']);
		if (!is_array($globalSettings)) {
			$globalSettings = array(
				'displayMyExtensions' => 0,
				'selectedLanguages' => array(),
				'inlineToWindow' => 1,
			);
		}
		$this->MOD_MENU = array(
			'function' => array(
				'loaded_list' => $GLOBALS['LANG']->getLL('menu_loaded_extensions'),
				'installed_list' => $GLOBALS['LANG']->getLL('menu_install_extensions'),
				'import' => $GLOBALS['LANG']->getLL('menu_import_extensions'),
				'translations' => $GLOBALS['LANG']->getLL('menu_translation_handling'),
				'settings' => $GLOBALS['LANG']->getLL('menu_settings'),
				'extensionmanager' => $GLOBALS['LANG']->getLL('header'),
				'updates' => $GLOBALS['LANG']->getLL('menu_extension_updates'),
			),
			'listOrder' => array(
				'cat' => $GLOBALS['LANG']->getLL('list_order_category'),
				'author_company' => $GLOBALS['LANG']->getLL('list_order_author'),
				'state' => $GLOBALS['LANG']->getLL('list_order_state'),
				'type' => $GLOBALS['LANG']->getLL('list_order_type'),
			),
			'display_details' => array(
				1 => $GLOBALS['LANG']->getLL('show_details'),
				0 => $GLOBALS['LANG']->getLL('show_description'),
				2 => $GLOBALS['LANG']->getLL('show_more_details'),

				3 => $GLOBALS['LANG']->getLL('show_technical'),
				4 => $GLOBALS['LANG']->getLL('show_validating'),
				5 => $GLOBALS['LANG']->getLL('show_changed'),
			),
			'display_shy' => '',
			'display_own' => '',
			'display_obsolete' => '',
			'display_installed' => '',
			'display_files' => '',
			'hide_shy' => 0,
			'hide_obsolete' => 0,


			'singleDetails' => array(
				'info' => $GLOBALS['LANG']->getLL('details_info'),
				'edit' => $GLOBALS['LANG']->getLL('details_edit'),
				'backup' => $GLOBALS['LANG']->getLL('details_backup_delete'),
				'dump' => $GLOBALS['LANG']->getLL('details_dump_db'),
				'upload' => $GLOBALS['LANG']->getLL('details_upload'),
				'updateModule' => $GLOBALS['LANG']->getLL('details_update'),
			),
			'fe_u' => '',
			'fe_p' => '',

			'mirrorListURL' => '',
			'rep_url' => '',
			'extMirrors' => '',

			// returns uid of currently selected repository
			// default and hardcoded: 1 = TYPO3.org
			'selectedRepository' => '1',
			'selectedMirror' => '0',

			'selectedLanguages' => '',

			'mainTab' => '0',
		);
		$this->settings = t3lib_BEfunc::getModuleData($this->MOD_MENU, t3lib_div::_GP('SET'), 'tools_em');
		$this->settings = array_merge($this->settings, $globalSettings);
	}

	/**
	 * Gets url for mirror
	 *
	 * @return string
	 */
	public function getMirrorURL() {
		if (strlen($this->settings['rep_url'])) {
			return $this->settings['rep_url'];
		}

		$mirrors = unserialize($this->settings['extMirrors']);

		if(!is_array($mirrors)) {
			if ($this->settings['selectedRepository'] < 1) {
				$this->settings['selectedRepository'] = 1;
			}
		}
			/** @var $repository tx_em_Repository */
			$repository = t3lib_div::makeInstance('tx_em_Repository', $this->settings['selectedRepository']);
			if ($repository->getMirrorListUrl()) {
			$objRepositoryUtility = t3lib_div::makeInstance('tx_em_Repository_Utility', $repository);
			$mirrors = $objRepositoryUtility->getMirrors(TRUE)->getMirrors();
			if(!is_array($mirrors)) {
				return FALSE;
			} else {
				$this->settings['extMirrors'] = serialize($mirrors);
				$this->saveSetting('extMirrors', $this->settings['extMirrors']);
			}
		}
		if (!$this->settings['selectedMirror']) {
			$rand = array_rand($mirrors);
			$url = 'http://' . $mirrors[$rand]['host'] . $mirrors[$rand]['path'];
		}
		else {
			$url = 'http://' . $mirrors[$this->settings['selectedMirror']]['host'] . $mirrors[$this->settings['selectedMirror']]['path'];
		}

		return $url;
	}

	/**
	 * Method returns registered extension repositories.
	 *
	 * Registered repositories are global (per installation) settings.
	 *
	 * @access  public
	 * @return  array of {@link em_repository em_repository} instances
	 * @see	 registerDefaultRepository(), setRegisteredRepositories()
	 */
	public function getRegisteredRepositories() {
		$registry = t3lib_div::makeInstance('t3lib_Registry');
		$regRepos = $registry->get('core', 'em.repositories.registered');

		// create default entry if there wasn't one
		if (empty($regRepos)) {
			$this->registerDefaultRepository();
			$regRepos = $registry->get('core', 'em.repositories.registered');
		}

		return $regRepos;
	}

	/**
	 * Method creates default registered repositories entry.
	 *
	 * @access  protected
	 * @return  void
	 * @see	 getRegisteredRepository(), setRegisteredRepositories()
	 */
	protected function registerDefaultRepository() {
		$registry = t3lib_div::makeInstance('t3lib_Registry');
		$defaultRepo = t3lib_div::makeInstance('tx_em_Repository');
		$registry->set('core', 'em.repositories.registered', array($defaultRepo));
	}

	/**
	 * Method sets (persists) registered repositories.
	 *
	 * Registered repositories are global (per installation) settings.
	 *
	 * @access  public
	 * @param   array  $repositories  array of {@link em_repository em_repository} instances
	 * @see	 registerDefaultRepository(), setRegisteredRepositories()
	 * @throws  InvalidArgumentException in case argument contains no instances of {@link em_repository em_repository}
	 */
	public function setRegisteredRepositories(array $repositories) {
		// removing mirror instances
		foreach ($repositories as $repository) {
			if ($repository instanceof em_repository) {
				$repository->removeMirrors();
			} else {
				throw new InvalidArgumentException(get_class($this) . ': ' . 'No valid instances of em_repository given.');
			}
		}
		if (count($repositories)) {
			$registry = t3lib_div::makeInstance('t3lib_Registry');
			$registry->set('core', 'em.repositories.registered', $repositories);
		}
	}

	/**
	 * Method returns currently selected repository
	 *
	 * Selected repository is local (per user) settings.
	 *
	 * @access  public
	 * @return  em_repository  repository instance that is currently selected by a BE user
	 * @see	 setSelectedRepository()
	 */
	public function getSelectedRepository() {
		return t3lib_div::makeInstance('tx_em_Repository', $this->settings['selectedRepository']);
	}

	/**
	 * Method sets currently selected repository.
	 *
	 * Selected repository is local (per user) settings.
	 *
	 * @todo	STUB, implementation missing
	 * @access  public
	 * @param   em_repository  $repository  repository instance that is currently selected by a BE user
	 * @see	 getSelectedRepository()
	 */
	public function setSelectedRepository(em_repository $repository) {
		// this method would set sth. like "REPOSITORY_TITLE:INT" in a setting field
		// REPOSITORY_TITLE = example: main
		// INT = 0 means randomly selected mirror, >0 selects specific mirror
	}

}

if (defined('TYPO3_MODE') && isset($GLOBALS['TYPO3_CONF_VARS'][TYPO3_MODE]['XCLASS']['typo3/sysext/em/classes/settings/class.tx_em_settings.php'])) {
	include_once($GLOBALS['TYPO3_CONF_VARS'][TYPO3_MODE]['XCLASS']['typo3/sysext/em/classes/repository/class.tx_em_settings.php']);
}

?>