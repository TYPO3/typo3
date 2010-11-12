<?php
/* **************************************************************
*  Copyright notice
*
*  (c) webservices.nl
*  (c) 2006-2010 Karsten Dambekalns <karsten@typo3.org>
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
/* $Id: class.tx_em_translations.php 2018 2010-03-14 12:25:58Z steffenk $ */

/**
 * Class for handling translations
 *
 */
class tx_em_Translations {


	protected $parentObject;
	protected $terConnection;


	/**
	 * Constructor
	 *
	 * @param object $parentObject
	 * @return void
	 */
	public function __construct($parentObject) {
		$this->parentObject = $parentObject;
		$this->terConnection = t3lib_div::makeInstance('tx_em_Connection_Ter', $this);
		$this->terConnection->wsdlURL = $GLOBALS['TYPO3_CONF_VARS']['EXT']['em_wsdlURL'];
	}


	/**
	 * Install translations for all selected languages for an extension
	 *
	 * @param string $extKey		The extension key to install the translations for
	 * @param string $mirrorURL		Mirror URL to fetch data from
	 * @return mixed	true on success, error string on fauilure
	 */
	function installTranslationsForExtension($extKey, $mirrorURL) {
		$selectedLanguages = unserialize($this->parentObject->MOD_SETTINGS['selectedLanguages']);
		if (!is_array($selectedLanguages)) {
			$selectedLanguages = array();
		}
		foreach ($selectedLanguages as $lang) {
			$l10n = $this->parentObject->terConnection->fetchTranslation($extKey, $lang, $mirrorURL);
			if (is_array($l10n)) {
				$file = PATH_typo3conf . 'l10n/' . $extKey . '-l10n-' . $lang . '.zip';
				$path = 'l10n/' . $lang . '/' . $extKey;
				t3lib_div::writeFile($file, $l10n[0]);
				if (!is_dir(PATH_typo3conf . $path)) {
					t3lib_div::mkdir_deep(PATH_typo3conf, $path);
				}
				if (tx_em_Tools::unzip($file, PATH_typo3conf . $path)) {
					return true;
				} else {
					return $GLOBALS['LANG']->getLL('translation_unpacking_failed');
				}
			} else {
				return $l10n;
			}
		}
	}

	/**
	 * Install translations for all selected languages for an extension
	 *
	 * @param string $extKey		The extension key to install the translations for
	 * @param string $lang		Language code of translation to fetch
	 * @param string $mirrorURL		Mirror URL to fetch data from
	 * @return mixed	true on success, error string on fauilure
	 */
	function updateTranslation($extKey, $lang, $mirrorURL) {
		$l10n = $this->parentObject->terConnection->fetchTranslation($extKey, $lang, $mirrorURL);
		if (is_array($l10n)) {
			$file = PATH_site . 'typo3temp/' . $extKey . '-l10n-' . $lang . '.zip';
			$path = 'l10n/' . $lang . '/';
			if (!is_dir(PATH_typo3conf . $path)) {
				t3lib_div::mkdir_deep(PATH_typo3conf, $path);
			}
			t3lib_div::writeFile($file, $l10n[0]);
			if (tx_em_Tools::unzip($file, PATH_typo3conf . $path)) {
				return true;
			} else {
				return $GLOBALS['LANG']->getLL('translation_unpacking_failed');
			}
		} else {
			return $l10n;
		}
	}

	/**
	 * Renders translation module
	 *
	 * @return string or direct output
	 */
	public function translationHandling() {
		global $LANG, $TYPO3_LOADED_EXT;
		$LANG->includeLLFile('EXT:setup/mod/locallang.xml');

		//prepare docheader
		$docHeaderButtons = $this->parentObject->getButtons();
		$markers = array(
			'CSH' => $docHeaderButtons['csh'],
			'FUNC_MENU' => $this->parentObject->getFuncMenu(),
		);


		$incoming = t3lib_div::_POST('SET');
		if (isset($incoming['selectedLanguages']) && is_array($incoming['selectedLanguages'])) {
			t3lib_BEfunc::getModuleData($this->parentObject->MOD_MENU, array('selectedLanguages' => serialize($incoming['selectedLanguages'])), $this->parentObject->MCONF['name'], '', 'selectedLanguages');
			$this->parentObject->MOD_SETTINGS['selectedLanguages'] = serialize($incoming['selectedLanguages']);
		}

		$selectedLanguages = unserialize($this->parentObject->MOD_SETTINGS['selectedLanguages']);
		if (count($selectedLanguages) == 1 && empty($selectedLanguages[0])) {
			$selectedLanguages = array();
		}
		$theLanguages = t3lib_div::trimExplode('|', TYPO3_languages);
		foreach ($theLanguages as $val) {
			if ($val != 'default') {
				$localLabel = '  -  [' . htmlspecialchars($GLOBALS['LOCAL_LANG']['default']['lang_' . $val]) . ']';
				$selected = (is_array($selectedLanguages) && in_array($val, $selectedLanguages)) ? ' selected="selected"' : '';
				$opt[$GLOBALS['LANG']->getLL('lang_' . $val, 1) . '--' . $val] = '
			 <option value="' . $val . '"' . $selected . '>' . $LANG->getLL('lang_' . $val, 1) . $localLabel . '</option>';
			}
		}
		ksort($opt);

		$headline = $GLOBALS['LANG']->getLL('translation_settings');
		$headline = t3lib_BEfunc::wrapInHelp('_MOD_tools_em', 'translation', $headline);

		// Prepare the HTML output:
		$content .= '
			<form action="' . $this->parentObject->script . '" method="post" name="translationform">
			<fieldset><legend>' . $GLOBALS['LANG']->getLL('translation_settings') . '</legend>
			<table border="0" cellpadding="2" cellspacing="2">
				<tr class="bgColor4">
					<td>' . $GLOBALS['LANG']->getLL('languages_to_fetch') . '</td>
					<td>
					  <select name="SET[selectedLanguages][]" multiple="multiple" size="10">
					  <option>&nbsp;</option>' .
				implode('', $opt) . '
			</select>
		  </td>
				</tr>
			</table>
			<br />
			<p>' . $GLOBALS['LANG']->getLL('translation_info') . '<br />
			<br />' . $GLOBALS['LANG']->getLL('translation_loaded_exts') . '</p>
			</fieldset>
			<br />
			<input type="submit" value="' . $GLOBALS['LANG']->getLL('translation_save_selection') . '" />
			<br />
			</form>';

		$this->parentObject->content .= $this->parentObject->doc->section($headline, $content, FALSE, TRUE, FALSE, TRUE);

		if (count($selectedLanguages) > 0) {
			$mirrorURL = $this->parentObject->getMirrorURL();
			$content = '<input type="button" value="' . $GLOBALS['LANG']->getLL('translation_check_status_button') .
					'" onclick="document.location.href=\'' . htmlspecialchars(t3lib_div::linkThisScript(array('l10n' => 'check'))) .
					'\'" />&nbsp;<input type="button" value="' . $GLOBALS['LANG']->getLL('translation_update_button') .
					'" onclick="document.location.href=\'' . htmlspecialchars(t3lib_div::linkThisScript(array('l10n' => 'update'))) .
					'\'" />';

			// as this page loads dynamically, quit output buffering caused by ob_gzhandler
			t3lib_div::cleanOutputBuffers();

			if (t3lib_div::_GET('l10n') == 'check') {
				$loadedExtensions = array_keys($TYPO3_LOADED_EXT);
				$loadedExtensions = array_diff($loadedExtensions, array('_CACHEFILE'));

				// Override content output - we now do that ourselves:
				$this->parentObject->content .= $this->parentObject->doc->section($GLOBALS['LANG']->getLL('translation_status'), $content, 0, 1);
				// Setting up the buttons and markers for docheader
				$content = $this->parentObject->doc->startPage('Extension Manager');
				$content .= $this->parentObject->doc->moduleBody($this->parentObject->pageinfo, $docHeaderButtons, $markers);
				$contentParts = explode('###CONTENT###', $content);

				echo $contentParts[0] . $this->parentObject->content;

				$this->parentObject->doPrintContent = FALSE;
				flush();

				echo '
				<br />
				<br />
				<p id="progress-message">
					' . $GLOBALS['LANG']->getLL('translation_check_status') . '
				</p>
				<br />
				<div style="width:100%; height:20px; border: 1px solid black;">
					<div id="progress-bar" style="float: left; width: 0%; height: 20px; background-color:green;">&nbsp;</div>
					<div id="transparent-bar" style="float: left; width: 100%; height: 20px; background-color:' . $this->parentObject->doc->bgColor2 . ';">&nbsp;</div>
				</div>
				<br />
				<br /><p>' . $GLOBALS['LANG']->getLL('translation_table_check') . '</p><br />
				<table border="0" cellpadding="2" cellspacing="2">
					<tr class="t3-row-header"><td>' . $GLOBALS['LANG']->getLL('translation_extension_key') . '</td>
				';

				foreach ($selectedLanguages as $lang) {
					echo ('<td>' . $LANG->getLL('lang_' . $lang, 1) . '</td>');
				}
				echo ('</tr>');

				$counter = 1;
				foreach ($loadedExtensions as $extKey) {

					$percentDone = intval(($counter / count($loadedExtensions)) * 100);
					echo ('
					<script type="text/javascript">
						document.getElementById("progress-bar").style.width = "' . $percentDone . '%";
						document.getElementById("transparent-bar").style.width = "' . (100 - $percentDone) . '%";
						document.getElementById("progress-message").firstChild.data="' .
							sprintf($GLOBALS['LANG']->getLL('translation_checking_extension'), $extKey) . '";
					</script>
					');

					flush();
					$translationStatusArr = $this->parentObject->terConnection->fetchTranslationStatus($extKey, $mirrorURL);

					echo ('<tr class="bgColor4"><td>' . $extKey . '</td>');
					foreach ($selectedLanguages as $lang) {
						// remote unknown -> no l10n available
						if (!isset($translationStatusArr[$lang])) {
							echo ('<td title="' . $GLOBALS['LANG']->getLL('translation_no_translation') . '">' .
									$GLOBALS['LANG']->getLL('translation_n_a') . '</td>');
							continue;
						}
						// determine local md5 from zip
						if (is_file(PATH_site . 'typo3temp/' . $extKey . '-l10n-' . $lang . '.zip')) {
							$localmd5 = md5_file(PATH_site . 'typo3temp/' . $extKey . '-l10n-' . $lang . '.zip');
						} else {
							echo ('<td title="' . $GLOBALS['LANG']->getLL('translation_not_installed') .
									'" style="background-color:#ff0">' . $GLOBALS['LANG']->getLL('translation_status_unknown') .
									'</td>');
							continue;
						}
						// local!=remote -> needs update
						if ($localmd5 != $translationStatusArr[$lang]['md5']) {
							echo ('<td title="' . $GLOBALS['LANG']->getLL('translation_needs_update') .
									'" style="background-color:#ff0">' . $GLOBALS['LANG']->getLL('translation_status_update') .
									'</td>');
							continue;
						}
						echo ('<td title="' . $GLOBALS['LANG']->getLL('translation_is_ok') .
								'" style="background-color:#69a550">' . $GLOBALS['LANG']->getLL('translation_status_ok') .
								'</td>');
					}
					echo ('</tr>');

					$counter++;
				}
				echo '</table>
					<script type="text/javascript">
						document.getElementById("progress-message").firstChild.data="' .
						$GLOBALS['LANG']->getLL('translation_check_done') . '";
					</script>
				';
				echo $contentParts[1] . $this->parentObject->doc->endPage();
				exit;

			} elseif (t3lib_div::_GET('l10n') == 'update') {
				$loadedExtensions = array_keys($TYPO3_LOADED_EXT);
				$loadedExtensions = array_diff($loadedExtensions, array('_CACHEFILE'));

				// Override content output - we now do that ourselves:
				$this->parentObject->content .= $this->parentObject->doc->section($GLOBALS['LANG']->getLL('translation_status'), $content, 0, 1);
				// Setting up the buttons and markers for docheader
				$content = $this->parentObject->doc->startPage('Extension Manager');
				$content .= $this->parentObject->doc->moduleBody($this->parentObject->pageinfo, $docHeaderButtons, $markers);
				$contentParts = explode('###CONTENT###', $content);

				echo $contentParts[0] . $this->parentObject->content;

				$this->parentObject->doPrintContent = FALSE;
				flush();

				echo ('
				<br />
				<br />
				<p id="progress-message">
					' . $GLOBALS['LANG']->getLL('translation_update_status') . '
				</p>
				<br />
				<div style="width:100%; height:20px; border: 1px solid black;">
					<div id="progress-bar" style="float: left; width: 0%; height: 20px; background-color:green;">&nbsp;</div>
					<div id="transparent-bar" style="float: left; width: 100%; height: 20px; background-color:' . $this->parentObject->doc->bgColor2 . ';">&nbsp;</div>
				</div>
				<br />
				<br /><p>' . $GLOBALS['LANG']->getLL('translation_table_update') . '<br />
				<em>' . $GLOBALS['LANG']->getLL('translation_full_check_update') . '</em></p><br />
				<table border="0" cellpadding="2" cellspacing="2">
					<tr class="t3-row-header"><td>' . $GLOBALS['LANG']->getLL('translation_extension_key') . '</td>
				');

				foreach ($selectedLanguages as $lang) {
					echo '<td>' . $LANG->getLL('lang_' . $lang, 1) . '</td>';
				}
				echo '</tr>';

				$counter = 1;
				foreach ($loadedExtensions as $extKey) {
					$percentDone = intval(($counter / count($loadedExtensions)) * 100);
					echo ('
					<script type="text/javascript">
						document.getElementById("progress-bar").style.width = "' . $percentDone . '%";
						document.getElementById("transparent-bar").style.width = "' . (100 - $percentDone) . '%";
						document.getElementById("progress-message").firstChild.data="' .
							sprintf($GLOBALS['LANG']->getLL('translation_updating_extension'), $extKey) . '";
					</script>
					');

					flush();
					$translationStatusArr = $this->parentObject->terConnection->fetchTranslationStatus($extKey, $mirrorURL);

					echo ('<tr class="bgColor4"><td>' . $extKey . '</td>');
					if (is_array($translationStatusArr)) {
						foreach ($selectedLanguages as $lang) {
							// remote unknown -> no l10n available
							if (!isset($translationStatusArr[$lang])) {
								echo ('<td title="' . $GLOBALS['LANG']->getLL('translation_no_translation') .
										'">' . $GLOBALS['LANG']->getLL('translation_n_a') . '</td>');
								continue;
							}
							// determine local md5 from zip
							if (is_file(PATH_site . 'typo3temp/' . $extKey . '-l10n-' . $lang . '.zip')) {
								$localmd5 = md5_file(PATH_site . 'typo3temp/' . $extKey . '-l10n-' . $lang . '.zip');
							} else {
								$localmd5 = 'zzz';
							}
							// local!=remote or not installed -> needs update
							if ($localmd5 != $translationStatusArr[$lang]['md5']) {
								$ret = $this->updateTranslation($extKey, $lang, $mirrorURL);
								if ($ret === true) {
									echo ('<td title="' . $GLOBALS['LANG']->getLL('translation_has_been_updated') .
											'" style="background-color:#69a550">' . $GLOBALS['LANG']->getLL('translation_status_update') .
											'</td>');
								} else {
									echo ('<td title="' . htmlspecialchars($ret) .
											'" style="background-color:#cb3352">' . $GLOBALS['LANG']->getLL('translation_status_error') .
											'</td>');
								}
								continue;
							}
							echo ('<td title="' . $GLOBALS['LANG']->getLL('translation_is_ok') .
									'" style="background-color:#69a550">' . $GLOBALS['LANG']->getLL('translation_status_ok') . '</td>');
						}
					} else {
						echo ('<td colspan="' . count($selectedLanguages) .
								'" title="' . $GLOBALS['LANG']->getLL('translation_problems') .
								'">' . $GLOBALS['LANG']->getLL('translation_status_could_not_fetch') . '</td>');
					}
					echo ('</tr>');
					$counter++;
				}
				echo '</table>
					<script type="text/javascript">
						document.getElementById("progress-message").firstChild.data="' .
						$GLOBALS['LANG']->getLL('translation_update_done') . '";
					</script>
				';

				// Fix permissions on unzipped language xml files in the entire l10n folder and all subfolders
				t3lib_div::fixPermissions(PATH_typo3conf . 'l10n', TRUE);

				echo $contentParts[1] . $this->parentObject->doc->endPage();
				exit;
			}

			$this->parentObject->content .= $this->parentObject->doc->section($GLOBALS['LANG']->getLL('translation_status'), $content, 0, 1);
		}
	}


}

?>