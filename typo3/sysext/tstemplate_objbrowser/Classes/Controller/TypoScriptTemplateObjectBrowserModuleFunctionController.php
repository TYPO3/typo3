<?php
namespace TYPO3\CMS\TstemplateObjbrowser\Controller;

/***************************************************************
 *  Copyright notice
 *
 *  (c) 1999-2013 Kasper Skårhøj (kasperYYYY@typo3.com)
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
 * This class displays the submodule "TypoScript Object Browser" inside the Web > Template module
 *
 * @author Kasper Skårhøj <kasperYYYY@typo3.com>
 */
class TypoScriptTemplateObjectBrowserModuleFunctionController extends \TYPO3\CMS\Backend\Module\AbstractFunctionModule {

	/**
	 * Init
	 *
	 * @param object $pObj
	 * @param array $conf
	 * @return void
	 * @todo Define visibility
	 */
	public function init(&$pObj, $conf) {
		parent::init($pObj, $conf);
		$this->pObj->modMenu_dontValidateList .= ',ts_browser_toplevel_setup,ts_browser_toplevel_const,ts_browser_TLKeys_setup,ts_browser_TLKeys_const';
		$this->pObj->modMenu_setDefaultList .= ',ts_browser_fixedLgd,ts_browser_showComments';
	}

	/**
	 * Mod menu
	 *
	 * @return array
	 * @todo Define visibility
	 */
	public function modMenu() {
		$GLOBALS['LANG']->includeLLFile('EXT:tstemplate_objbrowser/locallang.xlf');
		$modMenu = array(
			'ts_browser_type' => array(
				'const' => $GLOBALS['LANG']->getLL('constants'),
				'setup' => $GLOBALS['LANG']->getLL('setup')
			),
			'ts_browser_toplevel_setup' => array(
				'0' => $GLOBALS['LANG']->csConvObj->conv_case($GLOBALS['LANG']->charSet, $GLOBALS['LANG']->getLL('all'), 'toUpper')
			),
			'ts_browser_toplevel_const' => array(
				'0' => $GLOBALS['LANG']->csConvObj->conv_case($GLOBALS['LANG']->charSet, $GLOBALS['LANG']->getLL('all'), 'toUpper')
			),
			'ts_browser_const' => array(
				'0' => $GLOBALS['LANG']->getLL('plainSubstitution'),
				'subst' => $GLOBALS['LANG']->getLL('substitutedGreen'),
				'const' => $GLOBALS['LANG']->getLL('unsubstitutedGreen')
			),
			'ts_browser_regexsearch' => '1',
			'ts_browser_fixedLgd' => '1',
			'ts_browser_showComments' => '1',
			'ts_browser_alphaSort' => '1'
		);
		foreach (array('setup', 'const') as $bType) {
			$addKey = \TYPO3\CMS\Core\Utility\GeneralUtility::_GET('addKey');
			// If any plus-signs were clicked, it's registred.
			if (is_array($addKey)) {
				reset($addKey);
				if (current($addKey)) {
					$this->pObj->MOD_SETTINGS['ts_browser_TLKeys_' . $bType][key($addKey)] = key($addKey);
				} else {
					unset($this->pObj->MOD_SETTINGS['ts_browser_TLKeys_' . $bType][key($addKey)]);
				}
				$GLOBALS['BE_USER']->pushModuleData($this->pObj->MCONF['name'], $this->pObj->MOD_SETTINGS);
			}
			if (count($this->pObj->MOD_SETTINGS['ts_browser_TLKeys_' . $bType])) {
				$modMenu['ts_browser_toplevel_' . $bType]['-'] = '---';
				$modMenu['ts_browser_toplevel_' . $bType] = $modMenu[('ts_browser_toplevel_' . $bType)] + $this->pObj->MOD_SETTINGS[('ts_browser_TLKeys_' . $bType)];
			}
		}
		return $modMenu;
	}

	/**
	 * Verify TS objects
	 *
	 * @param array $propertyArray
	 * @param string $parentType
	 * @param string $parentValue
	 * @return array
	 * @todo Define visibility
	 */
	public function verify_TSobjects($propertyArray, $parentType, $parentValue) {
		$TSobjTable = array(
			'PAGE' => array(
				'prop' => array(
					'typeNum' => 'int',
					'1,2,3' => 'COBJ',
					'bodyTag' => 'string'
				)
			),
			'TEXT' => array(
				'prop' => array(
					'value' => 'string'
				)
			),
			'HTML' => array(
				'prop' => array(
					'value' => 'stdWrap'
				)
			),
			'stdWrap' => array(
				'prop' => array(
					'field' => 'string',
					'current' => 'boolean'
				)
			)
		);
		$TSobjDataTypes = array(
			'COBJ' => 'TEXT,CONTENT',
			'PAGE' => 'PAGE',
			'stdWrap' => ''
		);
		if ($parentType) {
			if (isset($TSobjDataTypes[$parentType]) && (!$TSobjDataTypes[$parentType] || \TYPO3\CMS\Core\Utility\GeneralUtility::inlist($TSobjDataTypes[$parentType], $parentValue))) {
				$ObjectKind = $parentValue;
			} else {
				// Object kind is "" if it should be known.
				$ObjectKind = '';
			}
		} else {
			// If parentType is not given, then it can be anything. Free.
			$ObjectKind = $parentValue;
		}
		if ($ObjectKind && is_array($TSobjTable[$ObjectKind])) {
			$result = array();
			if (is_array($propertyArray)) {
				foreach ($propertyArray as $key => $val) {
					if (\TYPO3\CMS\Core\Utility\MathUtility::canBeInterpretedAsInteger($key)) {
						// If num-arrays
						$result[$key] = $TSobjTable[$ObjectKind]['prop']['1,2,3'];
					} else {
						// standard
						$result[$key] = $TSobjTable[$ObjectKind]['prop'][$key];
					}
				}
			}
			return $result;
		}
	}

	/**
	 * Initialize editor
	 *
	 * @param integer $pageId
	 * @param integer $template_uid
	 * @return integer
	 * @todo Define visibility
	 */
	public function initialize_editor($pageId, $template_uid = 0) {
		// Initializes the module. Done in this function because we may need to re-initialize if data is submitted!
		global $tmpl, $tplRow, $theConstants;
		// Defined global here!
		$tmpl = \TYPO3\CMS\Core\Utility\GeneralUtility::makeInstance('TYPO3\\CMS\\Core\\TypoScript\\ExtendedTemplateService');
		// Do not log time-performance information
		$tmpl->tt_track = 0;
		$tmpl->init();
		// Gets the rootLine
		$sys_page = \TYPO3\CMS\Core\Utility\GeneralUtility::makeInstance('TYPO3\\CMS\\Frontend\\Page\\PageRepository');
		$rootLine = $sys_page->getRootLine($pageId);
		// This generates the constants/config + hierarchy info for the template.
		$tmpl->runThroughTemplates($rootLine, $template_uid);
		// Get the row of the first VISIBLE template of the page. whereclause like the frontend.
		$tplRow = $tmpl->ext_getFirstTemplate($pageId, $template_uid);
		// IF there was a template...
		if (is_array($tplRow)) {
			return 1;
		}
	}

	/**
	 * Main
	 *
	 * @return string
	 * @todo Define visibility
	 */
	public function main() {
		global $BACK_PATH;
		global $tmpl, $tplRow, $theConstants;
		$POST = \TYPO3\CMS\Core\Utility\GeneralUtility::_POST();
		// Checking for more than one template an if, set a menu...
		$manyTemplatesMenu = $this->pObj->templateMenu();
		$template_uid = 0;
		if ($manyTemplatesMenu) {
			$template_uid = $this->pObj->MOD_SETTINGS['templatesOnPage'];
		}
		// BUGBUG: Should we check if the user may at all read and write template-records???
		$bType = $this->pObj->MOD_SETTINGS['ts_browser_type'];
		$existTemplate = $this->initialize_editor($this->pObj->id, $template_uid);
		// initialize
		if ($existTemplate) {
			$theOutput .= $this->pObj->doc->section($GLOBALS['LANG']->getLL('currentTemplate'), ' <img ' . \TYPO3\CMS\Backend\Utility\IconUtility::skinImg($GLOBALS['BACK_PATH'], \TYPO3\CMS\Backend\Utility\IconUtility::getIcon('sys_template', $tplRow)) . ' align="top" /> <strong>' . $this->pObj->linkWrapTemplateTitle($tplRow['title'], ($bType == 'setup' ? 'config' : 'constants')) . '</strong>' . htmlspecialchars((trim($tplRow['sitetitle']) ? ' (' . $tplRow['sitetitle'] . ')' : '')));
			if ($manyTemplatesMenu) {
				$theOutput .= $this->pObj->doc->section('', $manyTemplatesMenu);
			}
			$theOutput .= $this->pObj->doc->spacer(10);
			if ($POST['add_property'] || $POST['update_value'] || $POST['clear_object']) {
				// add property
				$line = '';
				if (is_array($POST['data'])) {
					$name = key($POST['data']);
					if ($POST['data'][$name]['name'] !== '') {
						// Workaround for this special case: User adds a key and submits by pressing the return key. The form however will use "add_property" which is the name of the first submit button in this form.
						unset($POST['update_value']);
						$POST['add_property'] = 'Add';
					}
					if ($POST['add_property']) {
						$property = trim($POST['data'][$name]['name']);
						if (preg_replace('/[^a-zA-Z0-9_\\.]*/', '', $property) != $property) {
							$badPropertyMessage = \TYPO3\CMS\Core\Utility\GeneralUtility::makeInstance('TYPO3\\CMS\\Core\\Messaging\\FlashMessage', $GLOBALS['LANG']->getLL('noSpaces') . '<br />' . $GLOBALS['LANG']->getLL('nothingUpdated'), $GLOBALS['LANG']->getLL('badProperty'), \TYPO3\CMS\Core\Messaging\FlashMessage::ERROR);
							$this->addFlashMessage($badPropertyMessage);
						} else {
							$pline = $name . '.' . $property . ' = ' . trim($POST['data'][$name]['propertyValue']);
							$propertyAddedMessage = \TYPO3\CMS\Core\Utility\GeneralUtility::makeInstance('TYPO3\\CMS\\Core\\Messaging\\FlashMessage', htmlspecialchars($pline), $GLOBALS['LANG']->getLL('propertyAdded'));
							$this->addFlashMessage($propertyAddedMessage);
							$line .= LF . $pline;
						}
					} elseif ($POST['update_value']) {
						$pline = $name . ' = ' . trim($POST['data'][$name]['value']);
						$updatedMessage = \TYPO3\CMS\Core\Utility\GeneralUtility::makeInstance('TYPO3\\CMS\\Core\\Messaging\\FlashMessage', htmlspecialchars($pline), $GLOBALS['LANG']->getLL('valueUpdated'));
						$this->addFlashMessage($updatedMessage);
						$line .= LF . $pline;
					} elseif ($POST['clear_object']) {
						if ($POST['data'][$name]['clearValue']) {
							$pline = $name . ' >';
							$objectClearedMessage = \TYPO3\CMS\Core\Utility\GeneralUtility::makeInstance('TYPO3\\CMS\\Core\\Messaging\\FlashMessage', htmlspecialchars($pline), $GLOBALS['LANG']->getLL('objectCleared'));
							$this->addFlashMessage($objectClearedMessage);
							$line .= LF . $pline;
						}
					}
				}
				if ($line) {
					$saveId = $tplRow['_ORIG_uid'] ? $tplRow['_ORIG_uid'] : $tplRow['uid'];
					// Set the data to be saved
					$recData = array();
					$field = $bType == 'setup' ? 'config' : 'constants';
					$recData['sys_template'][$saveId][$field] = $tplRow[$field] . $line;
					// Create new  tce-object
					$tce = \TYPO3\CMS\Core\Utility\GeneralUtility::makeInstance('TYPO3\\CMS\\Core\\DataHandling\\DataHandler');
					$tce->stripslashes_values = 0;
					// Initialize
					$tce->start($recData, array());
					// Saved the stuff
					$tce->process_datamap();
					// Clear the cache (note: currently only admin-users can clear the cache in tce_main.php)
					$tce->clear_cacheCmd('all');
					// re-read the template ...
					$this->initialize_editor($this->pObj->id, $template_uid);
				}
			}
		}
		$tsbr = \TYPO3\CMS\Core\Utility\GeneralUtility::_GET('tsbr');
		$update = 0;
		if (is_array($tsbr)) {
			// If any plus-signs were clicked, it's registred.
			$this->pObj->MOD_SETTINGS['tsbrowser_depthKeys_' . $bType] = $tmpl->ext_depthKeys($tsbr, $this->pObj->MOD_SETTINGS['tsbrowser_depthKeys_' . $bType]);
			$update = 1;
		}
		if ($POST['Submit']) {
			// If any POST-vars are send, update the condition array
			$this->pObj->MOD_SETTINGS['tsbrowser_conditions'] = $POST['conditions'];
			$update = 1;
		}
		if ($update) {
			$GLOBALS['BE_USER']->pushModuleData($this->pObj->MCONF['name'], $this->pObj->MOD_SETTINGS);
		}
		$tmpl->matchAlternative = $this->pObj->MOD_SETTINGS['tsbrowser_conditions'];
		$tmpl->matchAlternative[] = 'dummydummydummydummydummydummydummydummydummydummydummy';
		// This is just here to make sure that at least one element is in the array so that the tsparser actually uses this array to match.
		$tmpl->constantMode = $this->pObj->MOD_SETTINGS['ts_browser_const'];
		if ($this->pObj->sObj && $tmpl->constantMode) {
			$tmpl->constantMode = 'untouched';
		}
		$tmpl->regexMode = $this->pObj->MOD_SETTINGS['ts_browser_regexsearch'];
		$tmpl->fixedLgd = $this->pObj->MOD_SETTINGS['ts_browser_fixedLgd'];
		$tmpl->linkObjects = TRUE;
		$tmpl->ext_regLinenumbers = TRUE;
		$tmpl->ext_regComments = $this->pObj->MOD_SETTINGS['ts_browser_showComments'];
		$tmpl->bType = $bType;
		$tmpl->resourceCheck = 1;
		$tmpl->removeFromGetFilePath = PATH_site;
		if ($this->pObj->MOD_SETTINGS['ts_browser_type'] == 'const') {
			$tmpl->ext_constants_BRP = intval(\TYPO3\CMS\Core\Utility\GeneralUtility::_GP('breakPointLN'));
		} else {
			$tmpl->ext_config_BRP = intval(\TYPO3\CMS\Core\Utility\GeneralUtility::_GP('breakPointLN'));
		}
		$tmpl->generateConfig();
		if ($bType == 'setup') {
			$theSetup = $tmpl->setup;
		} else {
			$theSetup = $tmpl->setup_constants;
		}
		// EDIT A VALUE:
		if ($this->pObj->sObj) {
			list($theSetup, $theSetupValue) = $tmpl->ext_getSetup($theSetup, $this->pObj->sObj ? $this->pObj->sObj : '');
			if ($existTemplate) {
				// Value
				$out = '';
				$out .= htmlspecialchars($this->pObj->sObj) . ' =<br />';
				$out .= '<input type="Text" name="data[' . htmlspecialchars($this->pObj->sObj) . '][value]" value="' . htmlspecialchars($theSetupValue) . '"' . $GLOBALS['TBE_TEMPLATE']->formWidth(40) . ' />';
				$out .= '<input type="Submit" name="update_value" value="' . $GLOBALS['LANG']->getLL('updateButton') . '" />';
				$theOutput .= $this->pObj->doc->section($GLOBALS['LANG']->getLL('editProperty'), $out, 0, 0);
				// Property
				if (\TYPO3\CMS\Core\Utility\ExtensionManagementUtility::isLoaded('tsconfig_help')) {
					$url = $BACK_PATH . 'wizard_tsconfig.php?mode=tsref&onlyProperty=1';
					$params = array();
					$params['formName'] = 'editForm';
					$params['itemName'] = 'data[' . htmlspecialchars($this->pObj->sObj) . '][name]';
					$params['itemValue'] = 'data[' . htmlspecialchars($this->pObj->sObj) . '][propertyValue]';
					$TSicon = '<a href="#" onClick="vHWin=window.open(\'' . $url . \TYPO3\CMS\Core\Utility\GeneralUtility::implodeArrayForUrl('', array('P' => $params)) . '\',\'popUp' . $md5ID . '\',\'height=500,width=780,status=0,menubar=0,scrollbars=1\');vHWin.focus();return false;"><img src="' . $BACK_PATH . 'gfx/wizard_tsconfig_s.gif" width="22" height="16" border="0" class="absmiddle" hspace=2 title="' . $GLOBALS['LANG']->sL('LLL:EXT:lang/locallang_common.xlf:tsRef') . '"></a>';
				} else {
					$TSicon = '';
				}
				$out = '';
				$out = '<nobr>' . htmlspecialchars($this->pObj->sObj) . '.';
				$out .= '<input type="Text" name="data[' . htmlspecialchars($this->pObj->sObj) . '][name]"' . $GLOBALS['TBE_TEMPLATE']->formWidth(20) . ' />' . $TSicon . ' = </nobr><br />';
				$out .= '<input type="Text" name="data[' . htmlspecialchars($this->pObj->sObj) . '][propertyValue]"' . $GLOBALS['TBE_TEMPLATE']->formWidth(40) . ' />';
				$out .= '<input type="Submit" name="add_property" value="' . $GLOBALS['LANG']->getLL('addButton') . '" />';
				$theOutput .= $this->pObj->doc->spacer(20);
				$theOutput .= $this->pObj->doc->section($GLOBALS['LANG']->getLL('addProperty'), $out, 0, 0);
				// clear
				$out = '';
				$out = htmlspecialchars($this->pObj->sObj) . ' <strong>' . $GLOBALS['LANG']->csConvObj->conv_case($GLOBALS['LANG']->charSet, $GLOBALS['LANG']->getLL('clear'), 'toUpper') . '</strong> &nbsp;&nbsp;';
				$out .= '<input type="Checkbox" name="data[' . htmlspecialchars($this->pObj->sObj) . '][clearValue]" value="1" />';
				$out .= '<input type="Submit" name="clear_object" value="' . $GLOBALS['LANG']->getLL('clearButton') . '" />';
				$theOutput .= $this->pObj->doc->spacer(20);
				$theOutput .= $this->pObj->doc->section($GLOBALS['LANG']->getLL('clearObject'), $out, 0, 0);
				$theOutput .= $this->pObj->doc->spacer(10);
			} else {
				$noTemplateMessage = \TYPO3\CMS\Core\Utility\GeneralUtility::makeInstance('TYPO3\\CMS\\Core\\Messaging\\FlashMessage', $GLOBALS['LANG']->getLL('noCurrentTemplate'), $GLOBALS['LANG']->getLL('edit'), \TYPO3\CMS\Core\Messaging\FlashMessage::ERROR);
				$this->addFlashMessage($noTemplateMessage);
			}
			// Links:
			$out = '';
			$urlParameters = array(
				'id' => $this->pObj->id
			);
			$aHref = \TYPO3\CMS\Backend\Utility\BackendUtility::getModuleUrl('web_ts', $urlParameters);
			if (!$this->pObj->MOD_SETTINGS[('ts_browser_TLKeys_' . $bType)][$this->pObj->sObj]) {
				if (count($theSetup)) {
					$out = '<a href="' . htmlspecialchars(($aHref . '&addKey[' . rawurlencode($this->pObj->sObj) . ']=1&SET[ts_browser_toplevel_' . $bType . ']=' . rawurlencode($this->pObj->sObj))) . '">';
					$out .= sprintf($GLOBALS['LANG']->getLL('addKey'), htmlspecialchars($this->pObj->sObj));
				}
			} else {
				$out = '<a href="' . htmlspecialchars(($aHref . '&addKey[' . rawurlencode($this->pObj->sObj) . ']=0&SET[ts_browser_toplevel_' . $bType . ']=0')) . '">';
				$out .= sprintf($GLOBALS['LANG']->getLL('removeKey'), htmlspecialchars($this->pObj->sObj));
			}
			if ($out) {
				$theOutput .= $this->pObj->doc->divider(5);
				$theOutput .= $this->pObj->doc->section('', $out);
			}
			// back
			$out = $GLOBALS['LANG']->getLL('back');
			$out = '<a href="' . htmlspecialchars($aHref) . '"><strong>' . $out . '</strong></a>';
			$theOutput .= $this->pObj->doc->divider(5);
			$theOutput .= $this->pObj->doc->section('', $out);
		} else {
			$tmpl->tsbrowser_depthKeys = $this->pObj->MOD_SETTINGS['tsbrowser_depthKeys_' . $bType];
			if (\TYPO3\CMS\Core\Utility\GeneralUtility::_POST('search') && \TYPO3\CMS\Core\Utility\GeneralUtility::_POST('search_field')) {
				// If any POST-vars are send, update the condition array
				$tmpl->tsbrowser_depthKeys = $tmpl->ext_getSearchKeys($theSetup, '', \TYPO3\CMS\Core\Utility\GeneralUtility::_POST('search_field'), array());
			}
			$menu = '<div class="tsob-menu"><label>' . $GLOBALS['LANG']->getLL('browse') . '</label>';
			$menu .= \TYPO3\CMS\Backend\Utility\BackendUtility::getFuncMenu($this->pObj->id, 'SET[ts_browser_type]', $bType, $this->pObj->MOD_MENU['ts_browser_type']);
			$menu .= '<label for="ts_browser_toplevel_' . $bType . '">' . $GLOBALS['LANG']->getLL('objectList') . '</label>';
			$menu .= \TYPO3\CMS\Backend\Utility\BackendUtility::getFuncMenu($this->pObj->id, 'SET[ts_browser_toplevel_' . $bType . ']', $this->pObj->MOD_SETTINGS['ts_browser_toplevel_' . $bType], $this->pObj->MOD_MENU['ts_browser_toplevel_' . $bType]);
			//search
			$menu .= '<label for="search_field">' . $GLOBALS['LANG']->getLL('search') . '</label>';
			$menu .= '<input type="Text" name="search_field" id="search_field" value="' . htmlspecialchars($POST['search_field']) . '"' . $GLOBALS['TBE_TEMPLATE']->formWidth(20) . '/>';
			$menu .= '<input type="Submit" name="search" class="tsob-search-submit" value="' . $GLOBALS['LANG']->sL('LLL:EXT:lang/locallang_common.xlf:search') . '" />';
			$menu .= \TYPO3\CMS\Backend\Utility\BackendUtility::getFuncCheck($this->pObj->id, 'SET[ts_browser_regexsearch]', $this->pObj->MOD_SETTINGS['ts_browser_regexsearch'], '', '', 'id="checkTs_browser_regexsearch"');
			$menu .= '<label for="checkTs_browser_regexsearch">' . $GLOBALS['LANG']->getLL('regExp') . '</label>';
			$menu .= '</div>';
			$theOutput .= $this->pObj->doc->section('', '<nobr>' . $menu . '</nobr>');
			$theKey = $this->pObj->MOD_SETTINGS['ts_browser_toplevel_' . $bType];
			if (!$theKey || !str_replace('-', '', $theKey)) {
				$theKey = '';
			}
			list($theSetup, $theSetupValue) = $tmpl->ext_getSetup($theSetup, $this->pObj->MOD_SETTINGS['ts_browser_toplevel_' . $bType] ? $this->pObj->MOD_SETTINGS['ts_browser_toplevel_' . $bType] : '');
			$tree = $tmpl->ext_getObjTree($theSetup, $theKey, '', '', $theSetupValue, $this->pObj->MOD_SETTINGS['ts_browser_alphaSort']);
			$tree = $tmpl->substituteCMarkers($tree);
			$urlParameters = array(
				'id' => $this->pObj->id
			);
			$aHref = \TYPO3\CMS\Backend\Utility\BackendUtility::getModuleUrl('web_ts', $urlParameters);
			// Parser Errors:
			$pEkey = $bType == 'setup' ? 'config' : 'constants';
			if (count($tmpl->parserErrors[$pEkey])) {
				$errMsg = array();
				$templateAnalyzerInstalled = \TYPO3\CMS\Core\Utility\ExtensionManagementUtility::isLoaded('tstemplate_analyzer');
				foreach ($tmpl->parserErrors[$pEkey] as $inf) {
					$errorLink = '';
					if ($templateAnalyzerInstalled) {
						$errorLink = ' <a href="' . htmlspecialchars(($aHref . '&SET[function]=tx_tstemplateanalyzer&template=all&SET[ts_analyzer_checkLinenum]=1#line-' . $inf[2])) . '">' . $GLOBALS['LANG']->getLL('errorShowDetails') . '</a>';
					}
					$errMsg[] = $inf[1] . ': &nbsp; &nbsp;' . $inf[0] . $errorLink;
				}
				$theOutput .= $this->pObj->doc->spacer(10);
				$flashMessage = \TYPO3\CMS\Core\Utility\GeneralUtility::makeInstance('TYPO3\\CMS\\Core\\Messaging\\FlashMessage', implode($errMsg, '<br />'), $GLOBALS['LANG']->getLL('errorsWarnings'), \TYPO3\CMS\Core\Messaging\FlashMessage::ERROR);
				$theOutput .= $flashMessage->render();
			}
			if (isset($this->pObj->MOD_SETTINGS['ts_browser_TLKeys_' . $bType][$theKey])) {
				$remove = '<td width="1%" nowrap><a href="' . htmlspecialchars(($aHref . '&addKey[' . $theKey . ']=0&SET[ts_browser_toplevel_' . $bType . ']=0')) . '"><strong>' . $GLOBALS['LANG']->getLL('removeKey') . '</strong></a></td>';
			} else {
				$remove = '';
			}
			$label = $theKey ? $theKey : ($bType == 'setup' ? $GLOBALS['LANG']->csConvObj->conv_case($GLOBALS['LANG']->charSet, $GLOBALS['LANG']->getLL('setupRoot'), 'toUpper') : $GLOBALS['LANG']->csConvObj->conv_case($GLOBALS['LANG']->charSet, $GLOBALS['LANG']->getLL('constantRoot'), 'toUpper'));
			$theOutput .= $this->pObj->doc->spacer(15);
			$theOutput .= $this->pObj->doc->sectionEnd();
			$theOutput .= '<table border="0" id="typo3-objectBrowser">
					<tr class="t3-row-header">
						<td nowrap="nowrap" width="99%"><strong>' . $label . '</strong></td>' . $remove . '
					</tr>
					<tr>
						<td class="bgColor4" nowrap="nowrap">' . $tree . '</td>' . ($remove ? '<td></td>' : '') . '
					</tr>
				</table>
			';
			// second row options
			$menu = '<div class="tsob-menu-row2">';
			$menu .= \TYPO3\CMS\Backend\Utility\BackendUtility::getFuncCheck($this->pObj->id, 'SET[ts_browser_showComments]', $this->pObj->MOD_SETTINGS['ts_browser_showComments'], '', '', 'id="checkTs_browser_showComments"');
			$menu .= '<label for="checkTs_browser_showComments">' . $GLOBALS['LANG']->getLL('displayComments') . '</label>';
			$menu .= \TYPO3\CMS\Backend\Utility\BackendUtility::getFuncCheck($this->pObj->id, 'SET[ts_browser_alphaSort]', $this->pObj->MOD_SETTINGS['ts_browser_alphaSort'], '', '', 'id="checkTs_browser_alphaSort"');
			$menu .= '<label for="checkTs_browser_alphaSort">' . $GLOBALS['LANG']->getLL('sortAlphabetically') . '</label>';
			$menu .= \TYPO3\CMS\Backend\Utility\BackendUtility::getFuncCheck($this->pObj->id, 'SET[ts_browser_fixedLgd]', $this->pObj->MOD_SETTINGS['ts_browser_fixedLgd'], '', '', 'id="checkTs_browser_fixedLgd"');
			$menu .= '<label for="checkTs_browser_fixedLgd">' . $GLOBALS['LANG']->getLL('cropLines') . '</label>';
			if ($bType == 'setup' && !$this->pObj->MOD_SETTINGS['ts_browser_fixedLgd']) {
				$menu .= '<br /><br /><label>' . $GLOBALS['LANG']->getLL('displayConstants') . '</label>';
				$menu .= \TYPO3\CMS\Backend\Utility\BackendUtility::getFuncMenu($this->pObj->id, 'SET[ts_browser_const]', $this->pObj->MOD_SETTINGS['ts_browser_const'], $this->pObj->MOD_MENU['ts_browser_const']);
			}
			$menu .= '</div>';
			$theOutput .= $this->pObj->doc->section($GLOBALS['LANG']->getLL('displayOptions'), '<nobr>' . $menu . '</nobr>', 0, 1);
			// Conditions:
			if (is_array($tmpl->sections)) {
				$theOutput .= $this->pObj->doc->section($GLOBALS['LANG']->getLL('conditions'), '', 0, 1);
				$out = '';
				foreach ($tmpl->sections as $key => $val) {
					$out .= '<tr><td nowrap class="tsob-conditions"><input type="checkbox" name="conditions[' . $key . ']" id="check' . $key . '" value="' . htmlspecialchars($val) . '"' . ($this->pObj->MOD_SETTINGS['tsbrowser_conditions'][$key] ? ' checked' : '') . ' />';
					$out .= '<label for="check' . $key . '">' . $tmpl->substituteCMarkers(htmlspecialchars($val)) . '</label></td></tr>';
				}
				$theOutput .= '
								<table border="0" cellpadding="0" cellspacing="0" class="bgColor4">' . $out . '
						<td><br /><input type="Submit" name="Submit" value="' . $GLOBALS['LANG']->getLL('setConditions') . '" /></td>
								</table>

				';
			}
			// Ending section:
			$theOutput .= $this->pObj->doc->sectionEnd();
		}
		return $theOutput;
	}

	/**
	 * Add flash message to queue
	 *
	 * @param \TYPO3\CMS\Core\Messaging\FlashMessage $flasgMessage
	 * @return void
	 */
	protected function addFlashMessage(\TYPO3\CMS\Core\Messaging\FlashMessage $flashMessage) {
		/** @var $flashMessageService \TYPO3\CMS\Core\Messaging\FlashMessageService */
		$flashMessageService = \TYPO3\CMS\Core\Utility\GeneralUtility::makeInstance('TYPO3\\CMS\\Core\\Messaging\\FlashMessageService');
		/** @var $defaultFlashMessageQueue \TYPO3\CMS\Core\Messaging\FlashMessageQueue */
		$defaultFlashMessageQueue = $flashMessageService->getMessageQueueByIdentifier();
		$defaultFlashMessageQueue->enqueue($flashMessage);
	}

}

?>