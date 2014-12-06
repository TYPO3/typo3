<?php
namespace TYPO3\CMS\Backend\Form\Element;

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

use TYPO3\CMS\Backend\Utility\BackendUtility;
use TYPO3\CMS\Backend\Utility\IconUtility;
use TYPO3\CMS\Core\Utility\GeneralUtility;

/**
 * Generation of TCEform elements of the type "flexform"
 */
class FlexElement extends AbstractFormElement {

	/**
	 * Handler for Flex Forms
	 *
	 * @param string $table The table name of the record
	 * @param string $field The field name which this element is supposed to edit
	 * @param array $row The record data array where the value(s) for the field can be found
	 * @param array $additionalInformation An array with additional configuration options.
	 * @return string The HTML code for the TCEform field
	 */
	public function render($table, $field, $row, &$additionalInformation) {
		// Data Structure:
		$dataStructArray = BackendUtility::getFlexFormDS($additionalInformation['fieldConf']['config'], $row, $table, $field);
		$item = '';
		// Manipulate Flexform DS via TSConfig and group access lists
		if (is_array($dataStructArray)) {
			$flexFormHelper = GeneralUtility::makeInstance(\TYPO3\CMS\Backend\Form\FlexFormsHelper::class);
			$dataStructArray = $flexFormHelper->modifyFlexFormDS($dataStructArray, $table, $field, $row, $additionalInformation['fieldConf']);
			unset($flexFormHelper);
		}
		// Get data structure:
		if (is_array($dataStructArray)) {
			// Get data:
			$xmlData = $additionalInformation['itemFormElValue'];
			$xmlHeaderAttributes = GeneralUtility::xmlGetHeaderAttribs($xmlData);
			$storeInCharset = strtolower($xmlHeaderAttributes['encoding']);
			if ($storeInCharset) {
				$currentCharset = $this->getLanguageService()->charSet;
				$xmlData = $this->getLanguageService()->csConvObj->conv($xmlData, $storeInCharset, $currentCharset, 1);
			}
			$editData = GeneralUtility::xml2array($xmlData);
			// Must be XML parsing error...
			if (!is_array($editData)) {
				$editData = array();
			} elseif (!isset($editData['meta']) || !is_array($editData['meta'])) {
				$editData['meta'] = array();
			}
			// Find the data structure if sheets are found:
			$sheet = $editData['meta']['currentSheetId'] ? $editData['meta']['currentSheetId'] : 'sDEF';
			// Sheet to display
			// Create language menu:
			$langChildren = $dataStructArray['meta']['langChildren'] ? 1 : 0;
			$langDisabled = $dataStructArray['meta']['langDisable'] ? 1 : 0;
			$editData['meta']['currentLangId'] = array();
			// Look up page overlays:
			$checkPageLanguageOverlay = $this->getBackendUserAuthentication()->getTSConfigVal('options.checkPageLanguageOverlay') ? TRUE : FALSE;
			if ($checkPageLanguageOverlay) {
				$where_clause = 'pid=' . (int)$row['pid'] . BackendUtility::deleteClause('pages_language_overlay')
					. BackendUtility::versioningPlaceholderClause('pages_language_overlay');
				$pageOverlays = $this->getDatabaseConnection()->exec_SELECTgetRows('*', 'pages_language_overlay', $where_clause, '', '', '', 'sys_language_uid');
			}
			$languages = $this->formEngine->getAvailableLanguages();
			foreach ($languages as $lInfo) {
				if (
					$this->getBackendUserAuthentication()->checkLanguageAccess($lInfo['uid'])
					&& (!$checkPageLanguageOverlay || $lInfo['uid'] <= 0 || is_array($pageOverlays[$lInfo['uid']]))
				) {
					$editData['meta']['currentLangId'][] = $lInfo['ISOcode'];
				}
			}
			if (!is_array($editData['meta']['currentLangId']) || !count($editData['meta']['currentLangId'])) {
				$editData['meta']['currentLangId'] = array('DEF');
			}
			$editData['meta']['currentLangId'] = array_unique($editData['meta']['currentLangId']);
			$additionalInformation['_noEditDEF'] = FALSE;
			if ($langChildren || $langDisabled) {
				$rotateLang = array('DEF');
			} else {
				if (!in_array('DEF', $editData['meta']['currentLangId'])) {
					array_unshift($editData['meta']['currentLangId'], 'DEF');
					$additionalInformation['_noEditDEF'] = TRUE;
				}
				$rotateLang = $editData['meta']['currentLangId'];
			}
			// Tabs sheets
			if (is_array($dataStructArray['sheets'])) {
				$tabsToTraverse = array_keys($dataStructArray['sheets']);
			} else {
				$tabsToTraverse = array($sheet);
			}

			$this->getControllerDocumentTemplate()->getPageRenderer()->loadRequireJsModule('TYPO3/CMS/Backend/FormEngineFlexForm');

			/** @var $elementConditionMatcher \TYPO3\CMS\Backend\Form\ElementConditionMatcher */
			$elementConditionMatcher = GeneralUtility::makeInstance(\TYPO3\CMS\Backend\Form\ElementConditionMatcher::class);

			foreach ($rotateLang as $lKey) {
				if (!$langChildren && !$langDisabled) {
					$item .= '<strong>' . $this->formEngine->getLanguageIcon($table, $row, ('v' . $lKey)) . $lKey . ':</strong>';
				}
				// Default language, other options are "lUK" or whatever country code (independent of system!!!)
				$lang = 'l' . $lKey;
				$tabParts = array();
				$sheetContent = '';
				foreach ($tabsToTraverse as $sheet) {
					list($dataStruct, $sheet) = GeneralUtility::resolveSheetDefInDS($dataStructArray, $sheet);
					// If sheet has displayCond
					if ($dataStruct['ROOT']['TCEforms']['displayCond']) {
						$splitCondition = GeneralUtility::trimExplode(':', $dataStruct['ROOT']['TCEforms']['displayCond']);
						$skipCondition = FALSE;
						$fakeRow = array();
						switch ($splitCondition[0]) {
							case 'FIELD':
								list($sheetName, $fieldName) = GeneralUtility::trimExplode('.', $splitCondition[1]);
								$fieldValue = $editData['data'][$sheetName][$lang][$fieldName];
								$splitCondition[1] = $fieldName;
								$dataStruct['ROOT']['TCEforms']['displayCond'] = join(':', $splitCondition);
								$fakeRow = array($fieldName => $fieldValue);
								break;
							case 'HIDE_FOR_NON_ADMINS':

							case 'VERSION':

							case 'HIDE_L10N_SIBLINGS':

							case 'EXT':
								break;
							case 'REC':
								$fakeRow = array('uid' => $row['uid']);
								break;
							default:
								$skipCondition = TRUE;
						}
						$displayConditionResult = TRUE;
						if ($dataStruct['ROOT']['TCEforms']['displayCond']) {
							$displayConditionResult = $elementConditionMatcher->match($dataStruct['ROOT']['TCEforms']['displayCond'], $fakeRow, 'vDEF');
						}
						// If sheets displayCond leads to false
						if (!$skipCondition && !$displayConditionResult) {
							// Don't create this sheet
							continue;
						}
					}
					// Render sheet:
					if (is_array($dataStruct['ROOT']) && is_array($dataStruct['ROOT']['el'])) {
						// Default language, other options are "lUK" or whatever country code (independent of system!!!)
						$additionalInformation['_valLang'] = $langChildren && !$langDisabled ? $editData['meta']['currentLangId'] : 'DEF';
						$additionalInformation['_lang'] = $lang;
						// Assemble key for loading the correct CSH file
						$dsPointerFields = GeneralUtility::trimExplode(',', $GLOBALS['TCA'][$table]['columns'][$field]['config']['ds_pointerField'], TRUE);
						$additionalInformation['_cshKey'] = $table . '.' . $field;
						foreach ($dsPointerFields as $key) {
							$additionalInformation['_cshKey'] .= '.' . $row[$key];
						}
						// Push the sheet level tab to DynNestedStack
						$tabIdentString = '';
						if (is_array($dataStructArray['sheets'])) {
							$tabIdentString = $this->getDocumentTemplate()->getDynTabMenuId('TCEFORMS:flexform:' . $additionalInformation['itemFormElName'] . $additionalInformation['_lang']);
							$this->formEngine->pushToDynNestedStack('tab', $tabIdentString . '-' . (count($tabParts) + 1));
						}
						// Render flexform:
						$tRows = $this->getSingleField_typeFlex_draw($dataStruct['ROOT']['el'], $editData['data'][$sheet][$lang], $table, $field, $row, $additionalInformation, '[data][' . $sheet . '][' . $lang . ']');
						$sheetContent = '<div class="typo3-TCEforms-flexForm t3-form-flexform">' . $tRows . '</div>';
						// Pop the sheet level tab from DynNestedStack
						if (is_array($dataStructArray['sheets'])) {
							$this->formEngine->popFromDynNestedStack('tab', $tabIdentString . '-' . (count($tabParts) + 1));
						}
					} else {
						$sheetContent = 'Data Structure ERROR: No ROOT element found for sheet "' . $sheet . '".';
					}
					// Add to tab:
					$tabParts[] = array(
						'label' => $dataStruct['ROOT']['TCEforms']['sheetTitle'] ? $this->formEngine->sL($dataStruct['ROOT']['TCEforms']['sheetTitle']) : $sheet,
						'description' => $dataStruct['ROOT']['TCEforms']['sheetDescription'] ? $this->formEngine->sL($dataStruct['ROOT']['TCEforms']['sheetDescription']) : '',
						'linkTitle' => $dataStruct['ROOT']['TCEforms']['sheetShortDescr'] ? $this->formEngine->sL($dataStruct['ROOT']['TCEforms']['sheetShortDescr']) : '',
						'content' => $sheetContent
					);
				}
				if (is_array($dataStructArray['sheets'])) {
					$item .= $this->formEngine->getDynTabMenu($tabParts, 'TCEFORMS:flexform:' . $additionalInformation['itemFormElName'] . $additionalInformation['_lang']);
				} else {
					$item .= $sheetContent;
				}
			}
		} else {
			$item = 'Data Structure ERROR: ' . $dataStructArray;
		}
		return $item;
	}


	/**
	 * Recursive rendering of flexforms
	 *
	 * @param array $dataStruct (part of) Data Structure for which to render. Keys on first level is flex-form fields
	 * @param array $editData (part of) Data array of flexform corresponding to the input DS. Keys on first level is flex-form field names
	 * @param string $table Table name, eg. tt_content
	 * @param string $field Field name, eg. tx_templavoila_flex
	 * @param array $row The particular record from $table in which the field $field is found
	 * @param array $PA Array of standard information for rendering of a form field in TCEforms, see other rendering functions too
	 * @param string $formPrefix Form field prefix, eg. "[data][sDEF][lDEF][...][...]
	 * @param int $level Indicates nesting level for the function call
	 * @param string $idPrefix Prefix for ID-values
	 * @param bool $toggleClosed Defines whether the next flexform level is open or closed. Comes from _TOGGLE pseudo field in FlexForm xml.
	 * @return string HTMl code for form.
	 */
	public function getSingleField_typeFlex_draw($dataStruct, $editData, $table, $field, $row, &$PA, $formPrefix = '', $level = 0, $idPrefix = 'ID', $toggleClosed = FALSE) {
		$output = '';
		$mayRestructureFlexforms = $this->getBackendUserAuthentication()->checkLanguageAccess(0);
		// Data Structure array must be ... and array of course...
		if (is_array($dataStruct)) {
			foreach ($dataStruct as $key => $value) {
				// Traversing fields in structure:
				if (is_array($value)) {
					// The value of each entry must be an array.
					// ********************
					// Making the row:
					// ********************
					// Title of field:
					// <title>LLL:EXT:cms/locallang_ttc.xml:media.sources</title>
					$theTitle = $value['title'];

					// If there is a title, check for LLL label
					if (strlen($theTitle) > 0) {
						$theTitle = htmlspecialchars(GeneralUtility::fixed_lgd_cs($this->formEngine->sL($theTitle),
							(int)$this->getBackendUserAuthentication()->uc['titleLen']));
					}
					// If it's a "section" or "container":
					if ($value['type'] == 'array') {
						// Creating IDs for form fields:
						// It's important that the IDs "cascade" - otherwise we can't dynamically expand the flex form
						// because this relies on simple string substitution of the first parts of the id values.
						// This is a suffix used for forms on this level
						$thisId = GeneralUtility::shortMd5(uniqid('id', TRUE));
						// $idPrefix is the prefix for elements on lower levels in the hierarchy and we combine this
						// with the thisId value to form a new ID on this level.
						$idTagPrefix = $idPrefix . '-' . $thisId;
						// If it's a "section" containing other elements:
						if ($value['section']) {
							// Load script.aculo.us if flexform sections can be moved by drag'n'drop:
							$this->getControllerDocumentTemplate()->getPageRenderer()->loadScriptaculous();
							// Render header of section:
							$output .= '<div class="t3-form-field-label-flexsection"><strong>' . $theTitle . '</strong></div>';
							// Render elements in data array for section:
							$tRows = array();
							if (is_array($editData[$key]['el'])) {
								foreach ($editData[$key]['el'] as $k3 => $v3) {
									$cc = $k3;
									if (is_array($v3)) {
										$theType = key($v3);
										$theDat = $v3[$theType];
										$newSectionEl = $value['el'][$theType];
										if (is_array($newSectionEl)) {
											$tRows[] = $this->getSingleField_typeFlex_draw(array($theType => $newSectionEl),
												array($theType => $theDat), $table, $field, $row, $PA,
												$formPrefix . '[' . $key . '][el][' . $cc . ']', $level + 1,
												$idTagPrefix, $v3['_TOGGLE']);
										}
									}
								}
							}
							// Now, we generate "templates" for new elements that could be added to this section
							// by traversing all possible types of content inside the section:
							// We have to handle the fact that requiredElements and such may be set during this
							// rendering process and therefore we save and reset the state of some internal variables
							// ... little crude, but works...
							// Preserving internal variables we don't want to change:
							$TEMP_requiredElements = $this->formEngine->requiredElements;
							// Traversing possible types of new content in the section:
							$newElementsLinks = array();
							foreach ($value['el'] as $nnKey => $nCfg) {
								$additionalJS_post_saved = $this->formEngine->additionalJS_post;
								$this->formEngine->additionalJS_post = array();
								$additionalJS_submit_saved = $this->formEngine->additionalJS_submit;
								$this->formEngine->additionalJS_submit = array();
								$newElementTemplate = $this->getSingleField_typeFlex_draw(array($nnKey => $nCfg),
									array(), $table, $field, $row, $PA,
									$formPrefix . '[' . $key . '][el][' . $idTagPrefix . '-form]', $level + 1,
									$idTagPrefix);
								// Makes a "Add new" link:
								$var = str_replace('.', '', uniqid('idvar', TRUE));
								$replace = 'replace(/' . $idTagPrefix . '-/g,"' . $idTagPrefix . '-"+' . $var . '+"-")';
								$replace .= '.replace(/(tceforms-(datetime|date)field-)/g,"$1" + (new Date()).getTime())';
								$onClickInsert = 'var ' . $var . ' = "' . 'idx"+(new Date()).getTime();'
									// Do not replace $isTagPrefix in setActionStatus() because it needs section id!
									. 'new Insertion.Bottom($("' . $idTagPrefix . '"), ' . json_encode($newElementTemplate)
									. '.' . $replace . '); TYPO3.jQuery("#' . $idTagPrefix . '").t3FormEngineFlexFormElement();'
									. 'eval(unescape("' . rawurlencode(implode(';', $this->formEngine->additionalJS_post)) . '").' . $replace . ');'
									. 'TBE_EDITOR.addActionChecks("submit", unescape("'
									. rawurlencode(implode(';', $this->formEngine->additionalJS_submit)) . '").' . $replace . ');'
									. 'TYPO3.TCEFORMS.update();'
									. 'return false;';
								// Kasper's comment (kept for history):
								// Maybe there is a better way to do this than store the HTML for the new element
								// in rawurlencoded format - maybe it even breaks with certain charsets?
								// But for now this works...
								$this->formEngine->additionalJS_post = $additionalJS_post_saved;
								$this->formEngine->additionalJS_submit = $additionalJS_submit_saved;
								$title = '';
								if (isset($nCfg['title'])) {
									$title = $this->formEngine->sL($nCfg['title']);
								}
								$newElementsLinks[] = '<a href="#" onclick="' . htmlspecialchars($onClickInsert) . '">'
									. IconUtility::getSpriteIcon('actions-document-new')
									. htmlspecialchars(GeneralUtility::fixed_lgd_cs($title, 30)) . '</a>';
							}
							// Reverting internal variables we don't want to change:
							$this->formEngine->requiredElements = $TEMP_requiredElements;
							// Adding the sections

							// add the "toggle all" button for the sections
							$toggleAll = $this->getLanguageService()->sL('LLL:EXT:lang/locallang_core.xlf:labels.toggleall', TRUE);
							$output .= '
							<div class="t3-form-field-toggle-flexsection t3-form-flexsection-toggle">
								<a href="#">'. IconUtility::getSpriteIcon('actions-move-right', array('title' => $toggleAll)) . $toggleAll . '</a>
							</div>
							<div id="' . $idTagPrefix . '" class="t3-form-field-container-flexsection t3-flex-container" data-t3-flex-allow-restructure="' . ($mayRestructureFlexforms ? 1 : 0) . '">' . implode('', $tRows) . '</div>';

							// add the "new" link
							if ($mayRestructureFlexforms) {
								$output .= '<div class="t3-form-field-add-flexsection"><strong>'
										. $this->getLanguageService()->sL('LLL:EXT:lang/locallang_core.xlf:labels.addnew', TRUE)
										. ':</strong> ' . implode(' | ', $newElementsLinks) . '</div>';
							}

							$output = '<div class="t3-form-field-container t3-form-flex">' . $output . '</div>';
						} else {
							// It is a container of a single section
							$toggleIconOpenState  =  ($toggleClosed ? 'display: none;' : '');
							$toggleIconCloseState = (!$toggleClosed ? 'display: none;' : '');

							$toggleIcons = IconUtility::getSpriteIcon('actions-move-down', array('class' => 't3-flex-control-toggle-icon-open', 'style' => $toggleIconOpenState));
							$toggleIcons .= IconUtility::getSpriteIcon('actions-move-right', array('class' => 't3-flex-control-toggle-icon-close', 'style' => $toggleIconCloseState));

							// Notice: Creating "new" elements after others seemed to be too difficult to do
							// and since moving new elements created in the bottom is now so easy
							// with drag'n'drop I didn't see the need.
							// Putting together header of a section. Sections can be removed, copied, opened/closed, moved up and down:
							// I didn't know how to make something right-aligned without a table, so I put it in a table.
							// can be made into <div>'s if someone like to.
							// Notice: The fact that I make a "Sortable.create" right onmousedown is that if we
							// initialize this when rendering the form in PHP new and copied elements will not
							// be possible to move as a sortable. But this way a new sortable is initialized every time
							// someone tries to move and it will always work.
							$ctrlHeader = '
								<div class="pull-left">
									<a href="#" class="t3-flex-control-toggle-button">' . $toggleIcons . '</a>
									<span class="t3-record-title">' . $theTitle . '</span>
								</div>';

							if ($mayRestructureFlexforms) {
								$ctrlHeader .= '<div class="pull-right">'
									. IconUtility::getSpriteIcon('actions-move-move', array('title' => 'Drag to Move', 'class' => 't3-js-sortable-handle'))
									. IconUtility::getSpriteIcon('actions-edit-delete', array('title' => 'Delete', 'class' => 't3-delete'))
									. '</div>';
							}

							$ctrlHeader = '<div class="t3-form-field-header-flexsection t3-flex-section-header">' . $ctrlHeader . '</div>';

							$s = GeneralUtility::revExplode('[]', $formPrefix, 2);
							$actionFieldName = '_ACTION_FLEX_FORM' . $PA['itemFormElName'] . $s[0] . '][_ACTION][' . $s[1];
							// Push the container to DynNestedStack as it may be toggled
							$this->formEngine->pushToDynNestedStack('flex', $idTagPrefix);
							// Putting together the container:
							$this->formEngine->additionalJS_delete = array();
							$singleField_typeFlex_draw = $this->getSingleField_typeFlex_draw($value['el'],
								$editData[$key]['el'], $table, $field, $row, $PA,
								($formPrefix . '[' . $key . '][el]'), ($level + 1), $idTagPrefix);
							$output .= '
								<div id="' . $idTagPrefix . '" class="t3-form-field-container-flexsections t3-flex-section">
									<input class="t3-flex-control t3-flex-control-action" type="hidden" name="' . htmlspecialchars($actionFieldName) . '" value=""/>

									' . $ctrlHeader . '
									<div class="t3-form-field-record-flexsection t3-flex-section-content"'
								. ($toggleClosed ? ' style="display:none;"' : '') . '>' . $singleField_typeFlex_draw . '
									</div>
									<input class="t3-flex-control t3-flex-control-toggle" id="' . $idTagPrefix . '-toggleClosed" type="hidden" name="'
								. htmlspecialchars('data[' . $table . '][' . $row['uid'] . '][' . $field . ']' . $formPrefix . '[_TOGGLE]')
								. '" value="' . ($toggleClosed ? 1 : 0) . '" />
								</div>';
							$output = str_replace('/*###REMOVE###*/', GeneralUtility::slashJS(htmlspecialchars(implode('', $this->formEngine->additionalJS_delete))), $output);
							// NOTICE: We are saving the toggle-state directly in the flexForm XML and "unauthorized"
							// according to the data structure. It means that flexform XML will report unclean and
							// a cleaning operation will remove the recorded togglestates. This is not a fatal problem.
							// Ideally we should save the toggle states in meta-data but it is much harder to do that.
							// And this implementation was easy to make and with no really harmful impact.
							// Pop the container from DynNestedStack
							$this->formEngine->popFromDynNestedStack('flex', $idTagPrefix);
						}
					} elseif (is_array($value['TCEforms']['config'])) {
						// Rendering a single form element:
						if (is_array($PA['_valLang'])) {
							$rotateLang = $PA['_valLang'];
						} else {
							$rotateLang = array($PA['_valLang']);
						}
						$conditionData = is_array($editData) ? $editData : array();
						// Add current $row to data processed by \TYPO3\CMS\Backend\Form\ElementConditionMatcher
						$conditionData['parentRec'] = $row;
						$tRows = array();

						/** @var $elementConditionMatcher \TYPO3\CMS\Backend\Form\ElementConditionMatcher */
						$elementConditionMatcher = GeneralUtility::makeInstance(\TYPO3\CMS\Backend\Form\ElementConditionMatcher::class);

						foreach ($rotateLang as $vDEFkey) {
							$vDEFkey = 'v' . $vDEFkey;
							$displayConditionResult = TRUE;
							if ($value['TCEforms']['displayCond']) {
								$displayConditionResult = $elementConditionMatcher->match($value['TCEforms']['displayCond'], $conditionData, $vDEFkey);
							}
							if ($displayConditionResult) {
								$fakePA = array();
								$fakePA['fieldConf'] = array(
									'label' => $this->formEngine->sL(trim($value['TCEforms']['label'])),
									'config' => $value['TCEforms']['config'],
									'defaultExtras' => $value['TCEforms']['defaultExtras'],
									'onChange' => $value['TCEforms']['onChange']
								);
								if ($PA['_noEditDEF'] && $PA['_lang'] === 'lDEF') {
									$fakePA['fieldConf']['config'] = array(
										'type' => 'none',
										'rows' => 2
									);
								}
								if (
									$fakePA['fieldConf']['onChange'] === 'reload'
									|| !empty($GLOBALS['TCA'][$table]['ctrl']['type'])
									&& (string)$key === $GLOBALS['TCA'][$table]['ctrl']['type']
									|| !empty($GLOBALS['TCA'][$table]['ctrl']['requestUpdate'])
									&& GeneralUtility::inList($GLOBALS['TCA'][$table]['ctrl']['requestUpdate'], $key)
								) {
									if ($this->getBackendUserAuthentication()->jsConfirmation(1)) {
										$alertMsgOnChange = 'if (confirm(TBE_EDITOR.labels.onChangeAlert) && TBE_EDITOR.checkSubmit(-1)){ TBE_EDITOR.submitForm() };';
									} else {
										$alertMsgOnChange = 'if(TBE_EDITOR.checkSubmit(-1)){ TBE_EDITOR.submitForm();}';
									}
								} else {
									$alertMsgOnChange = '';
								}
								$fakePA['fieldChangeFunc'] = $PA['fieldChangeFunc'];
								if (strlen($alertMsgOnChange)) {
									$fakePA['fieldChangeFunc']['alert'] = $alertMsgOnChange;
								}
								$fakePA['onFocus'] = $PA['onFocus'];
								$fakePA['label'] = $PA['label'];
								$fakePA['itemFormElName'] = $PA['itemFormElName'] . $formPrefix . '[' . $key . '][' . $vDEFkey . ']';
								$fakePA['itemFormElName_file'] = $PA['itemFormElName_file'] . $formPrefix . '[' . $key . '][' . $vDEFkey . ']';
								$fakePA['itemFormElID'] = $fakePA['itemFormElName'];
								if (isset($editData[$key][$vDEFkey])) {
									$fakePA['itemFormElValue'] = $editData[$key][$vDEFkey];
								} else {
									$fakePA['itemFormElValue'] = $fakePA['fieldConf']['config']['default'];
								}
								$theFormEl = $this->formEngine->getSingleField_SW($table, $field, $row, $fakePA);
								$theTitle = htmlspecialchars($fakePA['fieldConf']['label']);
								if (!in_array('DEF', $rotateLang)) {
									$defInfo = '<div class="t3-form-original-language">'
										. $this->formEngine->getLanguageIcon($table, $row, 0)
										. $this->formEngine->previewFieldValue($editData[$key]['vDEF'], $fakePA['fieldConf'], $field)
										. '&nbsp;</div>';
								} else {
									$defInfo = '';
								}
								if (!$PA['_noEditDEF']) {
									$prLang = $this->formEngine->getAdditionalPreviewLanguages();
									foreach ($prLang as $prL) {
										$defInfo .= '<div class="t3-form-original-language">'
											. $this->formEngine->getLanguageIcon($table, $row, ('v' . $prL['ISOcode']))
											. $this->formEngine->previewFieldValue($editData[$key][('v' . $prL['ISOcode'])], $fakePA['fieldConf'], $field)
											. '&nbsp;</div>';
									}
								}
								$languageIcon = '';
								if ($vDEFkey != 'vDEF') {
									$languageIcon = $this->formEngine->getLanguageIcon($table, $row, $vDEFkey);
								}
								// Put row together
								// possible linebreaks in the label through xml: \n => <br/>, usage of nl2br()
								// not possible, so it's done through str_replace
								$processedTitle = str_replace('\\n', '<br />', $theTitle);
								$tRows[] = '<div class="t3-form-field-container t3-form-field-container-flex">'
									. '<div class="t3-form-field-label t3-form-field-label-flex">' . $languageIcon
									. BackendUtility::wrapInHelp($PA['_cshKey'], $key, $processedTitle) . '</div>
									<div class="t3-form-field t3-form-field-flex">' . $theFormEl . $defInfo
									. $this->formEngine->renderVDEFDiff($editData[$key], $vDEFkey) . '</div>
								</div>';
							}
						}
						if (count($tRows)) {
							$output .= implode('', $tRows);
						}
					}
				}
			}
		}
		return $output;
	}
}
