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
 * $Id: class.em_extensionmanager.php 2106 2010-03-24 00:56:22Z steffenk $
 *
 * @author	Steffen Kamper <info@sk-typo3.de>
 */


class tx_em_ExtensionManager {

	/**
	 * Parent module object
	 *
	 * @var SC_mod_tools_em_index
	 */
	protected $parentObject;

	/**
	 * Page Renderer
	 *
	 * @var t3lib_PageRenderer
	 */
	protected $pageRenderer;

	/**
	 * Path of res for JS/CSS/resources
	 *
	 * @var string
	 */
	protected $resPath;

	/**
	 * Debug flag for develop debug=1 will use one uncompressed concatenated file, debug=2 will use single files
	 *
	 * @var int
	 */
	protected $debug;

	/**
	 * Gzip support - use it if server supports gzipped js files
	 *
	 * @var boolean
	 */
	protected $gzSupport = FALSE;



	/**
	 * Constructor
	 *
	 * @param SC_mod_tools_em_index $parentObject
	 */
	public function __construct(SC_mod_tools_em_index $parentObject) {
		$this->parentObject = $parentObject;
		$this->pageRenderer = $this->parentObject->doc->getPageRenderer();
		$this->resPath = $this->parentObject->doc->backPath . t3lib_extMgm::extRelPath('em') . 'res/';

		$userSettings = $this->parentObject->settings->getUserSettings();

		$this->debug = isset($userSettings['debug']) ? intval($userSettings['debug']) : 0;
		$this->gzSupport = isset($userSettings['jsGzCompressed']) ? TRUE : FALSE;

		$this->checkRepository();
	}

	/**
	 * Render module content
	 *
	 * @return string $content
	 */
	public function render() {

		/* Add CSS */
		if ($this->debug == 2 || 1) {
			$this->pageRenderer->addCssFile($this->resPath . 'js/ux/css/GridFilters.css');
			$this->pageRenderer->addCssFile($this->resPath . 'js/ux/css/RangeMenu.css');
			$this->pageRenderer->addCssFile($this->resPath . 'css/t3_em.css');
		} elseif($this->debug == 1) {

		} else {

		}

			// TODO: use sprite iconCls
		$iconsGfxPath = t3lib_extMgm::extRelPath('t3skin') . 'icons/gfx/';
		$this->pageRenderer->addCssInlineBlock('em-t3skin-icons', '
			.x-tree-node-leaf img.tree-edit { background-image:url(' . $iconsGfxPath . 'edit_file.gif);}
			.x-btn-edit { background-image:url(' . $iconsGfxPath . 'edit2.gif) !important;}
			.x-btn-new { background-image:url(' . $iconsGfxPath . 'new_el.gif) !important;}
			.x-btn-delete { background-image:url(' . $iconsGfxPath . 'garbage.gif) !important;}
			.x-tree-node-leaf img.tree-unknown { background-image:url(' . $iconsGfxPath . 'default.gif); }
			.x-btn-save { background-image:url(' . $iconsGfxPath . 'savedok.gif) !important;}
			.x-btn-upload { background-image:url(' . $iconsGfxPath . 'upload.gif) !important;}
			.x-btn-download { background-image:url(' . $iconsGfxPath . 'down.gif) !important;}
		');

		/* load ExtJS */
		$this->pageRenderer->loadExtJS();
		$this->pageRenderer->enableExtJsDebug();

			// Load  JavaScript:
		$this->pageRenderer->addJsFile($this->parentObject->doc->backPath .
			'ajax.php?ajaxID=ExtDirect::getAPI&namespace=TYPO3.EM',
			NULL,
			FALSE
		);
		$this->pageRenderer->addJsFile($this->parentObject->doc->backPath .
			'ajax.php?ajaxID=ExtDirect::getAPI&namespace=TYPO3.EMSOAP',
			NULL,
			FALSE
		);

		$this->pageRenderer->addExtDirectCode();


			// Localization
		$labels = tx_em_Tools::getArrayFromLocallang(t3lib_extMgm::extPath('em', 'language/locallang.xml'));
		$this->pageRenderer->addInlineLanguageLabelArray($labels);

		/* TODO: new users have no settings */
		$settings = $this->parentObject->MOD_SETTINGS;
		$settings['siteUrl'] = t3lib_div::getIndpEnv('TYPO3_SITE_URL');
		$settings['backPath'] = $this->parentObject->doc->backPath;
		$settings['hasCredentials'] = ($settings['fe_u'] !== '' && $settings['fe_p'] !== '');
		$settings['scriptLink'] = $this->parentObject->script;

		// TODO add mirrors to sys_ter record and delete from settings
		// restructure mirror data
		$mirrors = unserialize($settings['extMirrors']);
		$settings['extMirrors'] = array(array('Random (recommended)', '', '', '', '', '', ''));
		if (is_array($mirrors)) {
			foreach ($mirrors as $mirror) {
				$settings['extMirrors'][] = array(
					$mirror['title'], $mirror['country'], $mirror['host'], $mirror['path'],
					$mirror['sponsor']['name'], $mirror['sponsor']['link'], $mirror['sponsor']['logo']
				);
			}
		}
		$settings['selectedLanguages'] = unserialize($settings['selectedLanguages']);
		$settings['selectedLanguagesList'] = implode(',', (array) $settings['selectedLanguages']);

		$this->pageRenderer->addInlineSettingArray('EM', $settings);


		// Add JS
		$this->pageRenderer->addJsFile($this->parentObject->doc->backPath . '../t3lib/js/extjs/ux/flashmessages.js');
		$this->pageRenderer->addJsFile($this->parentObject->doc->backPath . 'js/extjs/iframepanel.js');

		//Plugins
		$this->pageRenderer->addJsFile($this->resPath . 'js/overrides/ext_overrides.js');
		$this->pageRenderer->addJsFile($this->resPath . 'js/ux/custom_plugins.js');
		$this->pageRenderer->addJsFile($this->parentObject->doc->backPath . '../t3lib/js/extjs/ux/Ext.ux.FitToParent.js');
		$this->pageRenderer->addJsFile($this->resPath . 'js/ux/rowpanelexpander.js');
		$this->pageRenderer->addJsFile($this->resPath . 'js/ux/searchfield.js');
		$this->pageRenderer->addJsFile($this->resPath . 'js/ux/fileuploadfield.js');
		$this->pageRenderer->addJsFile($this->resPath . 'js/ux/menu/RangeMenu.js');
		$this->pageRenderer->addJsFile($this->resPath . 'js/ux/menu/ListMenu.js');
		$this->pageRenderer->addJsFile($this->resPath . 'js/ux/GridFilters.js');
		$this->pageRenderer->addJsFile($this->resPath . 'js/ux/filter/Filter.js');
		$this->pageRenderer->addJsFile($this->resPath . 'js/ux/filter/BooleanFilter.js');
		$this->pageRenderer->addJsFile($this->resPath . 'js/ux/filter/DateFilter.js');
		$this->pageRenderer->addJsFile($this->resPath . 'js/ux/filter/ListFilter.js');
		$this->pageRenderer->addJsFile($this->resPath . 'js/ux/filter/NumericFilter.js');
		$this->pageRenderer->addJsFile($this->resPath . 'js/ux/filter/StringFilter.js');

		//Scripts
		$this->pageRenderer->addJsFile($this->resPath . 'js/em_layouts.js');
		$this->pageRenderer->addJsFile($this->resPath . 'js/em_templates.js');

		$this->pageRenderer->addJsFile($this->resPath . 'js/em_components.js');
		$this->pageRenderer->addJsFile($this->resPath . 'js/em_files.js');
		$this->pageRenderer->addJsFile($this->resPath . 'js/em_ter.js');
		$this->pageRenderer->addJsFile($this->resPath . 'js/em_tools.js');

		$this->pageRenderer->addJsFile($this->resPath . 'js/em_locallist.js');
		$this->pageRenderer->addJsFile($this->resPath . 'js/em_repositorylist.js');
		$this->pageRenderer->addJsFile($this->resPath . 'js/em_usertools.js');

		$this->pageRenderer->addJsFile($this->resPath . 'js/em_languages.js');
		$this->pageRenderer->addJsFile($this->resPath . 'js/em_settings.js');
		//Application
		$this->pageRenderer->addJsFile($this->resPath . 'js/em_app.js');


		//Update from repository - box
		//$content = $this->parentObject->showRepositoryUpdateForm(0);

		$content .= '<div id="em-message-area"></div><div id="em-app"></div>';
		return $content;
	}

	/**
	 * Check integrity of repository entry in sys_ter
	 *
	 * @return void
	 */
	protected function checkRepository() {
		/** @var $repository tx_em_Repository  */
		$repository = t3lib_div::makeInstance('tx_em_Repository');
		if ($repository->getLastUpdate() == 0) {
			$extCount = tx_em_Database::getExtensionCountFromRepository($repository);
			if ($extCount > 0) {
				$repository->setExtensionCount($extCount);
				$repository->setLastUpdate(time());
				tx_em_Database::updateRepository($repository);
			}
		}
	}
}
?>
