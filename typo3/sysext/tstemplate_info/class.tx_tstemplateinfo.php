<?php
/***************************************************************
*  Copyright notice
*
*  (c) 1999-2011 Kasper Skårhøj (kasperYYYY@typo3.com)
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

$GLOBALS['LANG']->includeLLFile('EXT:tstemplate_info/locallang.xml');

/**
 * This class displays the Info/Modify screen of the Web > Template module
 *
 * @author	Kasper Skårhøj <kasperYYYY@typo3.com>
 */
class tx_tstemplateinfo extends t3lib_extobjbase {

	public $tce_processed = FALSE;  // indicator for t3editor, whether data is stored

	/**
	 * Creates a row for a HTML table
	 *
	 * @param	string		$label: The label to be shown (e.g. 'Title:', 'Sitetitle:')
	 * @param	string		$data: The data/information to be shown (e.g. 'Template for my site')
	 * @param	string		$field: The field/variable to be sent on clicking the edit icon (e.g. 'title', 'sitetitle')
	 * @return	string		A row for a HTML table
	 */
	function tableRow($label, $data, $field)	{
		$ret = '<tr><td>';
		$ret.= '<a href="index.php?id=' . $this->pObj->id . '&e[' . $field . ']=1">' .
			t3lib_iconWorks::getSpriteIcon('actions-document-open', array('title' => $GLOBALS['LANG']->sL('LLL:EXT:lang/locallang_common.xml:editField', TRUE))) . '<strong>' . $label . '&nbsp;&nbsp;</strong></a>';
		$ret .= '</td><td width="80%" class="bgColor4">' . $data . '&nbsp;</td></tr>';
		return $ret;
	}

	/**
	 * Create an instance of t3lib_tsparser_ext in $GLOBALS['tmpl'] and looks for the first (visible) template
	 * record. If $template_uid was given and greater than zero, this record will be checked.
	 *
	 * @param	integer		$id: The uid of the current page
	 * @param	integer		$template_uid: The uid of the template record to be rendered (only if more than one template on the current page)
	 * @return	boolean		Returns TRUE if a template record was found, otherwise FALSE
	 */
	function initialize_editor($pageId, $template_uid=0)	{
			// Initializes the module. Done in this function because we may need to re-initialize if data is submitted!
		global $tmpl,$tplRow,$theConstants;

		$tmpl = t3lib_div::makeInstance('t3lib_tsparser_ext');	// Defined global here!
		$tmpl->tt_track = 0;	// Do not log time-performance information
		$tmpl->init();

			// Get the row of the first VISIBLE template of the page. whereclause like the frontend.
		$tplRow = $tmpl->ext_getFirstTemplate($pageId, $template_uid);
		if (is_array($tplRow)) {
			$tplRow = $this->processTemplateRowAfterLoading($tplRow);
			return TRUE;
		}
		return FALSE;
	}

	/**
	 * Process template row after loading
	 *
	 * @param 	array 	$tplRow: template row
	 * @return 	array	preprocessed template row
	 * @author	Fabrizio Branca <typo3@fabrizio-branca.de>
	 */
	function processTemplateRowAfterLoading(array $tplRow) {
		if ($this->pObj->MOD_SETTINGS['includeTypoScriptFileContent']) {
				// Let the recursion detection counter start at 91, so that only 10 recursive calls will be resolved
				// Otherwise the editor will be bloated with way to many lines making it hard the break the cyclic recursion.
			$tplRow['config'] = t3lib_TSparser::checkIncludeLines($tplRow['config'], 91);
			$tplRow['constants'] = t3lib_TSparser::checkIncludeLines($tplRow['constants'], 91);
		}
		return $tplRow;
	}

	/**
	 * Process template row before saving
	 *
	 * @param 	array 	$tplRow: template row
	 * @return 	array	preprocessed template row
	 * @author	Fabrizio Branca <typo3@fabrizio-branca.de>
	 */
	function processTemplateRowBeforeSaving(array $tplRow) {
		if ($this->pObj->MOD_SETTINGS['includeTypoScriptFileContent']) {
			$tplRow = t3lib_TSparser::extractIncludes_array($tplRow);
		}
		return $tplRow;
	}

	/**
	 * The main processing method if this class
	 *
	 * @return	string		Information of the template status or the taken actions as HTML string
	 */
	function main()	{
		global $BACK_PATH;
		global $tmpl,$tplRow,$theConstants;

		$this->pObj->MOD_MENU['includeTypoScriptFileContent'] = TRUE;

		$edit = $this->pObj->edit;
		$e = $this->pObj->e;

		t3lib_div::loadTCA('sys_template');




		// **************************
		// Checking for more than one template an if, set a menu...
		// **************************
		$manyTemplatesMenu = $this->pObj->templateMenu();
		$template_uid = 0;
		if ($manyTemplatesMenu)	{
			$template_uid = $this->pObj->MOD_SETTINGS['templatesOnPage'];
		}


		// **************************
		// Initialize
		// **************************
		$existTemplate = $this->initialize_editor($this->pObj->id, $template_uid);		// initialize

		if ($existTemplate)	{
			$saveId = ($tplRow['_ORIG_uid'] ? $tplRow['_ORIG_uid'] : $tplRow['uid']);
		}
		// **************************
		// Create extension template
		// **************************
		$newId = $this->pObj->createTemplate($this->pObj->id, $saveId);
		if($newId) {
			// switch to new template
			t3lib_utility_Http::redirect('index.php?id=' . $this->pObj->id. '&SET[templatesOnPage]=' . $newId);
		}

		if ($existTemplate)	{
				// Update template ?
			$POST = t3lib_div::_POST();
			if ($POST['submit'] || (t3lib_utility_Math::canBeInterpretedAsInteger($POST['submit_x']) && t3lib_utility_Math::canBeInterpretedAsInteger($POST['submit_y']))
				|| $POST['saveclose'] || (t3lib_utility_Math::canBeInterpretedAsInteger($POST['saveclose_x']) && t3lib_utility_Math::canBeInterpretedAsInteger($POST['saveclose_y']))) {
					// Set the data to be saved
				$recData = array();
				$alternativeFileName = array();

				$tmp_upload_name = '';
				$tmp_newresource_name = '';	// Set this to blank

				if (is_array($POST['data']))	{
					foreach ($POST['data'] as $field => $val) {
						switch ($field)	{
							case 'constants':
							case 'config':
							case 'title':
							case 'sitetitle':
							case 'description':
								$recData['sys_template'][$saveId][$field] = $val;
								break;
						}
					}
				}
				if (count($recData))	{

					$recData['sys_template'][$saveId] = $this->processTemplateRowBeforeSaving($recData['sys_template'][$saveId]);

						// Create new  tce-object
					$tce = t3lib_div::makeInstance('t3lib_TCEmain');
					$tce->stripslashes_values=0;
					$tce->alternativeFileName = $alternativeFileName;
						// Initialize
					$tce->start($recData, array());
						// Saved the stuff
					$tce->process_datamap();
						// Clear the cache (note: currently only admin-users can clear the cache in tce_main.php)
					$tce->clear_cacheCmd('all');

						// tce were processed successfully
					$this->tce_processed = TRUE;

						// re-read the template ...
					$this->initialize_editor($this->pObj->id, $template_uid);
				}

					// If files has been edited:
				if (is_array($edit))		{
					if ($edit['filename'] && $tplRow['resources'] && t3lib_div::inList($tplRow['resources'], $edit['filename']))	{		// Check if there are resources, and that the file is in the resourcelist.
						$path = PATH_site . $GLOBALS['TCA']['sys_template']['columns']['resources']['config']['uploadfolder'] . '/' . $edit['filename'];
						$fI = t3lib_div::split_fileref($edit['filename']);
						if (@is_file($path) && t3lib_div::getFileAbsFileName($path) && t3lib_div::inList($this->pObj->textExtensions, $fI['fileext']))	{		// checks that have already been done.. Just to make sure
								// @TODO: Check if the hardcorded value already has a config member, otherwise create one
							if (filesize($path) < 30720)	{	// checks that have already been done.. Just to make sure
								t3lib_div::writeFile($path, $edit['file']);

								$theOutput.= $this->pObj->doc->spacer(10);
								$theOutput.= $this->pObj->doc->section(
									'<font color=red>' . $GLOBALS['LANG']->getLL('fileChanged') . '</font>',
									sprintf($GLOBALS['LANG']->getLL('resourceUpdated'), $edit['filename']),
									0, 0, 0, 1
								);

									// Clear cache - the file has probably affected the template setup
									// @TODO: Check if the edited file really had something to do with cached data and prevent this clearing if possible!
								/** @var $tce t3lib_TCEmain */
								$tce = t3lib_div::makeInstance('t3lib_TCEmain');
								$tce->stripslashes_values = 0;
								$tce->start(array(), array());
								$tce->clear_cacheCmd('all');
							}
						}
					}
				}
			}

				// hook	Post updating template/TCE processing
			if (isset($GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS']['ext/tstemplate_info/class.tx_tstemplateinfo.php']['postTCEProcessingHook']))	{
				$postTCEProcessingHook =& $GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS']['ext/tstemplate_info/class.tx_tstemplateinfo.php']['postTCEProcessingHook'];
				if (is_array($postTCEProcessingHook)) {
					$hookParameters = array(
						'POST' 	=> $POST,
						'tce'	=> $tce,
					);
					foreach ($postTCEProcessingHook as $hookFunction)	{
						t3lib_div::callUserFunction($hookFunction, $hookParameters, $this);
					}
				}
			}

			$content = t3lib_iconWorks::getSpriteIconForRecord('sys_template', $tplRow) . '<strong>' . htmlspecialchars($tplRow['title']) . '</strong>' . htmlspecialchars(trim($tplRow['sitetitle']) ? ' (' . $tplRow['sitetitle'] . ')' : '');
			$theOutput .= $this->pObj->doc->section($GLOBALS['LANG']->getLL('templateInformation'), $content, 0, 1);
			if ($manyTemplatesMenu)	{
				$theOutput.= $this->pObj->doc->section('', $manyTemplatesMenu);
			}

			$theOutput .= $this->pObj->doc->spacer(10);

			#$numberOfRows= t3lib_utility_Math::forceIntegerInRange($this->pObj->MOD_SETTINGS["ts_template_editor_TArows"],0,150);
			#if (!$numberOfRows)
			$numberOfRows = 35;

				// If abort pressed, nothing should be edited:
			if ($POST['abort'] || (t3lib_utility_Math::canBeInterpretedAsInteger($POST['abort_x']) && t3lib_utility_Math::canBeInterpretedAsInteger($POST['abort_y']))
				|| $POST['saveclose'] || (t3lib_utility_Math::canBeInterpretedAsInteger($POST['saveclose_x']) && t3lib_utility_Math::canBeInterpretedAsInteger($POST['saveclose_y']))) {
				unset($e);
			}

			if ($e['title'])	{
				$outCode = '<input type="Text" name="data[title]" value="'.htmlspecialchars($tplRow['title']).'"'.$this->pObj->doc->formWidth().'>';
				$outCode.= '<input type="Hidden" name="e[title]" value="1">';
				$theOutput.= $this->pObj->doc->spacer(15);
				$theOutput.= $this->pObj->doc->section($GLOBALS['LANG']->getLL('title'), $outCode, TRUE);
			}
			if ($e['sitetitle'])	{
				$outCode = '<input type="Text" name="data[sitetitle]" value="'.htmlspecialchars($tplRow['sitetitle']).'"'.$this->pObj->doc->formWidth().'>';
				$outCode.= '<input type="Hidden" name="e[sitetitle]" value="1">';
				$theOutput.= $this->pObj->doc->spacer(15);
				$theOutput.= $this->pObj->doc->section($GLOBALS['LANG']->getLL('sitetitle'), $outCode, TRUE);
			}
			if ($e['description'])	{
				$outCode = '<textarea name="data[description]" rows="5" class="fixed-font enable-tab"'.$this->pObj->doc->formWidthText(48, '', '').'>'.t3lib_div::formatForTextarea($tplRow['description']).'</textarea>';
				$outCode.= '<input type="Hidden" name="e[description]" value="1">';
				$theOutput.= $this->pObj->doc->spacer(15);
				$theOutput.= $this->pObj->doc->section($GLOBALS['LANG']->getLL('description'), $outCode, TRUE);
			}
			if ($e['constants'])	{
				$outCode = '<textarea name="data[constants]" rows="'.$numberOfRows.'" wrap="off" class="fixed-font enable-tab"'.$this->pObj->doc->formWidthText(48, 'width:98%;height:70%', 'off').' class="fixed-font">'.t3lib_div::formatForTextarea($tplRow['constants']).'</textarea>';
				$outCode.= '<input type="Hidden" name="e[constants]" value="1">';

					// Display "Include TypoScript file content?" checkbox
				$outCode .= t3lib_BEfunc::getFuncCheck(
					$this->pObj->id,
					'SET[includeTypoScriptFileContent]',
					$this->pObj->MOD_SETTINGS['includeTypoScriptFileContent'],
					'index.php',
					'&e[constants]=1',
					'id="checkIncludeTypoScriptFileContent"'
				);
				$outCode .= '<label for="checkIncludeTypoScriptFileContent">' . $GLOBALS['LANG']->getLL('includeTypoScriptFileContent') . '</label><br />';


				$theOutput.= $this->pObj->doc->spacer(15);
				$theOutput.= $this->pObj->doc->section($GLOBALS['LANG']->getLL('constants'), '', TRUE);
				$theOutput.= $this->pObj->doc->sectionEnd().$outCode;
			}
			if ($e['file'])	{
				$path = PATH_site . $GLOBALS['TCA']['sys_template']['columns']['resources']['config']['uploadfolder'] . '/' . $e[file];

				$fI = t3lib_div::split_fileref($e[file]);
				if (@is_file($path) && t3lib_div::inList($this->pObj->textExtensions, $fI['fileext']))	{
					if (filesize($path) < $GLOBALS['TCA']['sys_template']['columns']['resources']['config']['max_size'] * 1024) {
						$fileContent = t3lib_div::getUrl($path);
						$outCode = $GLOBALS['LANG']->getLL('file'). ' <strong>' . $e[file] . '</strong><BR>';
						$outCode.= '<textarea name="edit[file]" rows="'.$numberOfRows.'" wrap="off" class="fixed-font enable-tab"'.$this->pObj->doc->formWidthText(48, 'width:98%;height:70%', 'off').' class="fixed-font">'.t3lib_div::formatForTextarea($fileContent).'</textarea>';
						$outCode.= '<input type="Hidden" name="edit[filename]" value="'.$e[file].'">';
						$outCode.= '<input type="Hidden" name="e[file]" value="'.htmlspecialchars($e[file]).'">';
						$theOutput.= $this->pObj->doc->spacer(15);
						$theOutput.= $this->pObj->doc->section($GLOBALS['LANG']->getLL('editResource'), '');
						$theOutput.= $this->pObj->doc->sectionEnd().$outCode;
					} else {
						$theOutput.= $this->pObj->doc->spacer(15);
						$fileToBig = sprintf($GLOBALS['LANG']->getLL('filesizeExceeded'), $GLOBALS['TCA']['sys_template']['columns']['resources']['config']['max_size']);
						$filesizeNotAllowed = sprintf($GLOBALS['LANG']->getLL('notAllowed'), $GLOBALS['TCA']['sys_template']['columns']['resources']['config']['max_size']);
						$theOutput.= $this->pObj->doc->section(
							'<font color=red>' . $fileToBig . '</font>',
							$filesizeNotAllowed,
							0, 0, 0, 1
						);
					}
				}
			}
			if ($e['config'])	{
				$outCode='<textarea name="data[config]" rows="'.$numberOfRows.'" wrap="off" class="fixed-font enable-tab"'.$this->pObj->doc->formWidthText(48,"width:98%;height:70%","off").' class="fixed-font">'.t3lib_div::formatForTextarea($tplRow["config"]).'</textarea>';
				$outCode.= '<input type="Hidden" name="e[config]" value="1">';

					// Display "Include TypoScript file content?" checkbox
				$outCode .= t3lib_BEfunc::getFuncCheck(
					$this->pObj->id,
					'SET[includeTypoScriptFileContent]',
					$this->pObj->MOD_SETTINGS['includeTypoScriptFileContent'],
					'index.php',
					'&e[config]=1',
					'id="checkIncludeTypoScriptFileContent"'
				);
				$outCode .= '<label for="checkIncludeTypoScriptFileContent">' . $GLOBALS['LANG']->getLL('includeTypoScriptFileContent') . '</label><br />';

				if (t3lib_extMgm::isLoaded('tsconfig_help'))	{
					$url = $BACK_PATH.'wizard_tsconfig.php?mode=tsref';
					$params = array(
						'formName' => 'editForm',
						'itemName' => 'data[config]',
					);
					$outCode.= '<a href="#" onClick="vHWin=window.open(\''.$url.t3lib_div::implodeArrayForUrl('', array('P' => $params)).'\',\'popUp'.$md5ID.'\',\'height=500,width=780,status=0,menubar=0,scrollbars=1\');vHWin.focus();return false;">'.t3lib_iconWorks::getSpriteIcon('actions-system-typoscript-documentation-open', array('title'=> $GLOBALS['LANG']->sL('LLL:EXT:lang/locallang_common.xml:tsRef', TRUE))) . '</a>';
				}

				$theOutput.= $this->pObj->doc->spacer(15);
				$theOutput.= $this->pObj->doc->section($GLOBALS['LANG']->getLL('setup'), '', TRUE);
				$theOutput.= $this->pObj->doc->sectionEnd().$outCode;
			}

				// Processing:
			$outCode = '';
			$outCode.= $this->tableRow(
				$GLOBALS['LANG']->getLL('title'),
				htmlspecialchars($tplRow['title']),
				'title'
			);
			$outCode.= $this->tableRow(
				$GLOBALS['LANG']->getLL('sitetitle'),
				htmlspecialchars($tplRow['sitetitle']),
				'sitetitle'
			);
			$outCode.= $this->tableRow(
				$GLOBALS['LANG']->getLL('description'),
				nl2br(htmlspecialchars($tplRow['description'])),
				'description'
			);
			$outCode.= $this->tableRow(
				$GLOBALS['LANG']->getLL('constants'),
				sprintf($GLOBALS['LANG']->getLL('editToView'), (trim($tplRow[constants]) ? count(explode(LF, $tplRow[constants])) : 0)),
				'constants'
			);
			$outCode.= $this->tableRow(
				$GLOBALS['LANG']->getLL('setup'),
				sprintf($GLOBALS['LANG']->getLL('editToView'), (trim($tplRow[config]) ? count(explode(LF, $tplRow[config])) : 0)),
				'config'
			);
			$outCode = '<table class="t3-table-info">' . $outCode . '</table>';

				// Edit all icon:
			$outCode.= '<br /><a href="#" onClick="' . t3lib_BEfunc::editOnClick(rawurlencode('&createExtension=0') .
				'&amp;edit[sys_template][' . $tplRow['uid'] . ']=edit', $BACK_PATH, '') . '"><strong>' .
				t3lib_iconWorks::getSpriteIcon('actions-document-open', array('title'=>
				$GLOBALS['LANG']->getLL('editTemplateRecord') ))  . $GLOBALS['LANG']->getLL('editTemplateRecord') . '</strong></a>';
			$theOutput.= $this->pObj->doc->section('', $outCode);


				// hook	after compiling the output
			if (isset($GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS']['ext/tstemplate_info/class.tx_tstemplateinfo.php']['postOutputProcessingHook']))	{
				$postOutputProcessingHook =& $GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS']['ext/tstemplate_info/class.tx_tstemplateinfo.php']['postOutputProcessingHook'];
				if (is_array($postOutputProcessingHook)) {
					$hookParameters = array(
						'theOutput' => &$theOutput,
						'POST'		=> $POST,
						'e'			=> $e,
						'tplRow'		=> $tplRow,
						'numberOfRows'		=> $numberOfRows
					);
					foreach ($postOutputProcessingHook as $hookFunction)	{
						t3lib_div::callUserFunction($hookFunction, $hookParameters, $this);
					}
				}
			}

		} else {
			$theOutput.= $this->pObj->noTemplate(1);
		}


		return $theOutput;
	}
}

if (defined('TYPO3_MODE') && isset($GLOBALS['TYPO3_CONF_VARS'][TYPO3_MODE]['XCLASS']['ext/tstemplate_info/class.tx_tstemplateinfo.php'])) {
	include_once($GLOBALS['TYPO3_CONF_VARS'][TYPO3_MODE]['XCLASS']['ext/tstemplate_info/class.tx_tstemplateinfo.php']);
}

?>