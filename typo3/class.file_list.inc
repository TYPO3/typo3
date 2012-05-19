<?php
/***************************************************************
*  Copyright notice
*
*  (c) 1999-2009 Kasper Skårhøj (kasperYYYY@typo3.com)
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
 * Include file extending t3lib_recordList
 *
 * Revised for TYPO3 3.6 2/2003 by Kasper Skårhøj
 *
 * @author	Kasper Skårhøj <kasperYYYY@typo3.com>
 */






/**
 * Class for rendering of File>Filelist
 *
 * @author	Kasper Skårhøj <kasperYYYY@typo3.com>
 * @package TYPO3
 * @subpackage core
 */
class fileList extends t3lib_recordList {
	var $iLimit = 40;					// default Max items shown
	var $thumbs = 0;					// Boolean. Thumbnails on records containing files (pictures)
	var $widthGif = '<img src="clear.gif" width="1" height="1" hspace="165" alt="" />';
	var $fixedL = 30;					// Max length of strings
	var $script = 'file_list.php';
	var $clickMenus=1;					// If TRUE click menus are generated on files and folders

	var $sort = '';						// The field to sort by...
	var $sortRev = 1;					// Reverse sorting flag
	var $firstElementNumber=0;
	var $clipBoard = 0;
	var $bigControlPanel = 0;

		// internal
	var $JScode = '';
	var $HTMLcode = '';
	var $totalbytes=0;
	var $dirs = Array();
	var $files = Array();
	var $path = '';

	/**
	 * @var t3lib_file_Folder
	 */
	protected $folderObject;

	var $eCounter=0;					// Counting the elements no matter what...
	var $dirCounter = 0;
	var $totalItems='';

	var $CBnames=array();


	/**
	 * Initialization of class
	 *
	 * @param t3lib_file_Folder $folderObject The folder to work on
	 * @param integer $pointer Pointer
	 * @param boolean $sort Sorting column
	 * @param boolean $sortRev Sorting direction
	 * @param boolean $bigControlPanel Show clipboard flag
	 * @return void
	 */
	function start(t3lib_file_Folder $folderObject, $pointer, $sort, $sortRev, $clipBoard = FALSE, $bigControlPanel = FALSE) {

		$this->script = t3lib_extMgm::extRelPath('filelist') . 'mod1/file_list.php';
		$this->folderObject = $folderObject;
		$this->counter=0;
		$this->totalbytes=0;
		$this->JScode='';
		$this->HTMLcode='';
		$this->path = $folderObject->getIdentifier();
		$this->sort = $sort;
		$this->sortRev = $sortRev;
		$this->firstElementNumber=$pointer;
		$this->clipBoard = $clipBoard;
		$this->bigControlPanel = $bigControlPanel;
			// setting the maximum length of the filenames to the user's settings or minimum 30 (= $this->fixedL)
		$this->fixedL = max($this->fixedL, $GLOBALS['BE_USER']->uc['titleLen']);

		$GLOBALS['LANG']->includeLLFile('EXT:lang/locallang_common.xml');
	}

	/**
	 * Reading files and directories, counting elements and generating the list in ->HTMLcode
	 *
	 * @return	void
	 */
	function generateList() {
		$this->HTMLcode .= $this->getTable('fileext,tstamp,size,rw,_REF_');
	}

	/**
	 * Return the buttons used by the file list to include in the top header
	 *
	 * @param t3lib_file_Folder $folderObject
	 * @return array
	 */
	public function getButtonsAndOtherMarkers(t3lib_file_Folder $folderObject) {

		$otherMarkers = array(
			'PAGE_ICON' => '',
			'TITLE' => '',
		);

		$buttons = array(
			'level_up' => '',
			'refresh' => '',
			'title' => '',
			'page_icon' => '',
		);

			// Makes the code for the foldericon in the top
		if ($folderObject) {
			list($title,$icon,$path) =  $this->dirData($folderObject);
			$title = htmlspecialchars($folderObject->getIdentifier());

				// Start compiling the HTML
			// @todo: how to fix this? $title = $GLOBALS['SOBE']->basicFF->blindPath($title);

				// if this is some subpage under the mount root....
			if ($folderObject->getStorage()->isWithinFileMountBoundaries($folderObject)) {
					// The icon with link
				$otherMarkers['PAGE_ICON'] = t3lib_iconWorks::getSpriteIcon($icon, array('title' => $title));

				$buttons['level_up'] = $this->linkWrapDir(t3lib_iconWorks::getSpriteIcon('actions-view-go-up', array('title' => $GLOBALS['LANG']->sL('LLL:EXT:lang/locallang_core.xml:labels.upOneLevel', 1))), $folderObject);
				// No HTML specialchars here - HTML like <strong> </strong> is allowed
				$otherMarkers['TITLE'] .= t3lib_div::removeXSS(t3lib_div::fixed_lgd_cs($title, -($this->fixedL+20)));

			} else {
					// this is the root page
				$otherMarkers['PAGE_ICON'] = t3lib_iconWorks::getSpriteIcon('apps-filetree-root');
				$otherMarkers['TITLE'] .= htmlspecialchars(t3lib_div::fixed_lgd_cs($title, - ($this->fixedL + 20)));
			}

			if ($this->clickMenus) {
				$otherMarkers['PAGE_ICON'] = $GLOBALS['SOBE']->doc->wrapClickMenuOnIcon($otherMarkers['PAGE_ICON'], $folderObject->getCombinedIdentifier());
			}
		}

		$buttons['refresh'] = '<a href="'.htmlspecialchars($this->listURL()).'" title="' . $GLOBALS['LANG']->sL('LLL:EXT:lang/locallang_core.xml:labels.reload', 1) . '">'.
				t3lib_iconWorks::getSpriteIcon('actions-system-refresh')  .
			'</a>';

		return array($buttons, $otherMarkers);
	}

	/**
	 * Wrapping input string in a link with clipboard command.
	 *
	 * @param	string		String to be linked - must be htmlspecialchar'ed / prepared before.
	 * @param	string		table - NOT USED
	 * @param	string		"cmd" value
	 * @param	string		Warning for JS confirm message
	 * @return	string		Linked string
	 */
	function linkClipboardHeaderIcon($string,$table,$cmd,$warning='') {
		$onClickEvent = 'document.dblistForm.cmd.value=\''.$cmd.'\';document.dblistForm.submit();';
		if ($warning)	$onClickEvent = 'if (confirm('.$GLOBALS['LANG']->JScharCode($warning).')){'.$onClickEvent.'}';
		return '<a href="#" onclick="'.htmlspecialchars($onClickEvent).'return false;">'.$string.'</a>';
	}

	/**
	 * Returns a table with directories and files listed.
	 *
	 * @param	array		Array of files from path
	 * @param	array		Array of directories from path
	 * @param	string		List of rows to display horizontallyh
	 * @return	string		HTML-table
	 */
	function getTable($rowlist) {

		// TODO use folder methods directly when they support filters
		$storage = $this->folderObject->getStorage();
		$storage->resetFileAndFolderNameFiltersToDefault();

		$folders = $storage->getFolderList($this->folderObject->getIdentifier());
		$files = $storage->getFileList($this->folderObject->getIdentifier());


			// only render the contents of a browsable storage
		if ($this->folderObject->getStorage()->isBrowsable()) {

			$this->sort = trim($this->sort);
			if ($this->sort !== '') {
				$filesToSort = array();
				foreach($files as $file) {
					$fileObject = $storage->getFile($file['identifier']);

					switch ($this->sort) {
						case 'size':
							$sortingKey = $fileObject->getSize();
							break;
						case 'rw':
							$sortingKey = $fileObject->checkActionPermission('read') ? 'R' : ''
								. $fileObject->checkActionPermission('write') ? 'W' : '';
							break;
						case 'fileext':
							$sortingKey = $fileObject->getExtension();
							break;
						case 'tstamp':
							$sortingKey = $fileObject->getModificationTime();
							break;
						default:
							if($fileObject->hasProperty($this->sort)) {
								$sortingKey = $fileObject->getProperty($this->sort);
							} else {
								$sortingKey = $fileObject->getName();
							}
					}
					$i = 0;
					while(isset($filesToSort[$sortingKey . $i])) { $i++; }
					$filesToSort[$sortingKey . $i] = $fileObject;
				}
				if (intval($this->sortRev) === 1) {
					krsort($filesToSort);
				} else {
					ksort($filesToSort);
				}

				$files = $filesToSort;
			}
			$this->totalItems = count($folders) + count($files);

				// Adds the code of files/dirs
			$out = '';
			$titleCol = 'file';
			// @todo: fix this: go up one level with FAL
			// $upLevelDir = dirname(substr($files['files'][0]['path'], 0, -1)) . '/';
			// $levelUp = $GLOBALS['SOBE']->basicFF->checkPathAgainstMounts($upLevelDir) ? $this->linkWrapDir(t3lib_iconWorks::getSpriteIcon('actions-view-go-up', array('title' => $GLOBALS['LANG']->sL('LLL:EXT:lang/locallang_core.php:labels.upOneLevel', TRUE))), $upLevelDir) : '';

				// Cleaning rowlist for duplicates and place the $titleCol as the first column always!
			$rowlist = t3lib_div::rmFromList($titleCol, $rowlist);
			$rowlist = t3lib_div::uniqueList($rowlist);
			$rowlist = $rowlist ? $titleCol.','.$rowlist : $titleCol;
			if ($this->bigControlPanel || $this->clipBoard) {
				$rowlist = str_replace('file,', 'file,_CLIPBOARD_,', $rowlist);
			}
			$this->fieldArray = explode(',', $rowlist);

			$folderObjects = array();
			foreach ($folders as $folder) {
				$folderObjects[] = $storage->getFolder($folder['identifier']);
			}

				// Directories are added
			$iOut = $this->formatDirList($folderObjects);

			if ($iOut) {
					// half line is drawn
				$theData = array(
					$titleCol => ''
				);
			}

				// Files are added
			$iOut .= $this->formatFileList($files, $titleCol);


			// Header line is drawn
		$theData = array();

		foreach ($this->fieldArray as $v) {
			if ($v == '_CLIPBOARD_' && $this->clipBoard) {
				$cells=array();
				$table='_FILE';
				$elFromTable = $this->clipObj->elFromTable($table);
				if (count($elFromTable)) {
					$cells[] = '<a href="' . htmlspecialchars($this->clipObj->pasteUrl('_FILE',$this->folderObject->getCombinedIdentifier())) . '" onclick="return ' . htmlspecialchars($this->clipObj->confirmMsg('_FILE', $this->path, 'into', $elFromTable)) . '" title="' . $GLOBALS['LANG']->getLL('clip_paste', 1) . '">' .
						t3lib_iconWorks::getSpriteIcon('actions-document-paste-after') .
					'</a>';
				}
				if ($this->clipObj->current!='normal' && $iOut) {
					$cells[]=$this->linkClipboardHeaderIcon(t3lib_iconWorks::getSpriteIcon('actions-edit-copy', array('title' => $GLOBALS['LANG']->getLL('clip_selectMarked', 1))), $table,'setCB');
					$cells[]=$this->linkClipboardHeaderIcon(t3lib_iconWorks::getSpriteIcon('actions-edit-delete', array('title' => $GLOBALS['LANG']->getLL('clip_deleteMarked'))), $table,'delete',$GLOBALS['LANG']->getLL('clip_deleteMarkedWarning'));
					$onClick = 'checkOffCB(\'' . implode(',', $this->CBnames) . '\', this); return false;';
					$cells[] = '<a class="cbcCheckAll" rel="" href="#" onclick="' . htmlspecialchars($onClick) . '" title="' . $GLOBALS['LANG']->getLL('clip_markRecords', TRUE) . '">' .
							t3lib_iconWorks::getSpriteIcon('actions-document-select').
						'</a>';
				}
				$theData[$v] = implode('',$cells);
			} else {	// Normal row:
				$theT = $this->linkWrapSort($GLOBALS['LANG']->getLL('c_' . $v, 1), $this->folderObject->getCombinedIdentifier(), $v);
				$theData[$v] = $theT;
			}
		}
		$out .= '<thead>' . $this->addelement(1, $levelUp, $theData, ' class="t3-row-header"', '') . '</thead>';
		$out .= '<tbody>' . $iOut . '</tbody>';

			// half line is drawn

			// finish
		$out = '


		<!--
			File list table:
		-->
			<table cellpadding="0" cellspacing="0" id="typo3-filelist">
				'.$out.'
			</table>';
		} else {
			/** @var $flashMessage t3lib_FlashMessage */
			$flashMessage = t3lib_div::makeInstance(
				't3lib_FlashMessage',
				$GLOBALS['LANG']->getLL('storageNotBrowsableMessage'),
				$GLOBALS['LANG']->getLL('storageNotBrowsableTitle'),
				t3lib_FlashMessage::INFO
			);
			$out = $flashMessage->render();
		}

		return $out;
	}

	/**
	 * Gets the number of files and total size of a folder
	 *
	 * @return string
	 **/
	function getFolderInfo() {
		if($this->counter == 1) {
			$fileLabel = $GLOBALS['LANG']->getLL('file', TRUE);
		} else {
			$fileLabel = $GLOBALS['LANG']->getLL('files', TRUE);
		}
		return $this->counter . ' ' . $fileLabel . ', ' . t3lib_div::formatSize($this->totalbytes, $GLOBALS['LANG']->getLL('byteSizeUnits', TRUE));
	}

	/**
	 * This returns tablerows for the directories in the array $items['sorting'].
	 *
	 * @param t3lib_file_Folder[] $folders Folders of t3lib_file_Folder
	 * @return string HTML table rows.
	 */
	function formatDirList(array $folders) {
		$out = '';

		foreach ($folders as $folderObject) {
			list($flag, $code) = $this->fwd_rwd_nav();
			$out .= $code;
			if ($flag) {
					// Initialization
				$this->counter++;

				list($title, $icon, $path) =  $this->dirData($folderObject);

					// The icon with link
				$theIcon = t3lib_iconWorks::getSpriteIconForFile('folder', array('title' => $title));
				if ($this->clickMenus) {
					$theIcon = $GLOBALS['SOBE']->doc->wrapClickMenuOnIcon($theIcon, $folderObject->getCombinedIdentifier());
				}

					// 	Preparing and getting the data-array
				$theData = array();
				foreach ($this->fieldArray as $field) {
					switch($field) {
						case 'size':
							$numFiles = $folderObject->getFileCount();
							$theData[$field] = $numFiles  . ' ' . $GLOBALS['LANG']->getLL(($numFiles === 1 ? 'file' : 'files'), TRUE);
						break;
						case 'rw':
							$theData[$field] = (!$folderObject->checkActionPermission('read') ? ' ' : '<span class="typo3-red"><strong>' . $GLOBALS['LANG']->getLL('read', TRUE) . '</strong></span>')
							. (!$folderObject->checkActionPermission('write') ? '' : '<span class="typo3-red"><strong>' . $GLOBALS['LANG']->getLL('write', TRUE) . '</strong></span>');
						break;
						case 'fileext':
							$theData[$field] = $GLOBALS['LANG']->getLL('folder', TRUE);
						break;
						case 'tstamp':
							// @todo: FAL: how to get the mtime info -- $theData[$field] = t3lib_BEfunc::date($theFile['tstamp']);
							$theData[$field] = '-';
						break;
						case 'file':
							$theData[$field] = $this->linkWrapDir($title, $folderObject);
						break;
						case '_CLIPBOARD_':
							$temp = '';
							if ($this->bigControlPanel) {
								$temp .= $this->makeEdit($folderObject);
							}
							$temp .= $this->makeClip($folderObject);
							$theData[$field] = $temp;
						break;
						case '_REF_':
							$theData[$field] = $this->makeRef($folderObject);
						break;
						default:
							$theData[$field] = t3lib_div::fixed_lgd_cs($theFile[$field], $this->fixedL);
						break;
					}
				}

				$out .= $this->addelement(1, $theIcon, $theData, ' class="file_list_normal"');
			}
			$this->eCounter++;
			$this->dirCounter = $this->eCounter;
		}
		return $out;
	}

	/**
	 * Wraps the directory-titles
	 *
	 * @param string $title String to be wrapped in links
	 * @param t3lib_file_Folder $folderObject Folder to work on
	 * @return string HTML
	 */
	function linkWrapDir($title, t3lib_file_Folder $folderObject) {
		$href = $this->backPath . $this->script . '?id=' . rawurlencode($folderObject->getCombinedIdentifier());
		$onclick = ' onclick="'.htmlspecialchars('top.content.nav_frame.hilight_row("file","folder' . t3lib_div::md5int($folderObject->getCombinedIdentifier()) . '_"+top.fsMod.currentBank)') . '"';

			// Sometimes $code contains plain HTML tags. In such a case the string should not be modified!
		if (!strcmp($title, strip_tags($title))) {
			return '<a href="'.htmlspecialchars($href).'"'.$onclick.' title="'.htmlspecialchars($title).'">'.t3lib_div::fixed_lgd_cs($title, $this->fixedL).'</a>';
		} else	{
			return '<a href="'.htmlspecialchars($href).'"'.$onclick.'>' . $title . '</a>';
		}
	}

	/**
	 * Wraps filenames in links which opens them in a window IF they are in web-path.
	 *
	 * @param string $code String to be wrapped in links
	 * @param t3lib_file_File $fileObject File to be linked
	 * @return string HTML
	 */
	function linkWrapFile($code, t3lib_file_File $fileObject) {
		$fileUrl = $fileObject->getPublicUrl(TRUE);
		if ($fileUrl) {
			$aOnClick = "return top.openUrlInWindow('" . $fileUrl . "', 'WebFile');";
			$code = '<a href="#" title="'.htmlspecialchars($code).'" onclick="'.htmlspecialchars($aOnClick).'">'.t3lib_div::fixed_lgd_cs($code,$this->fixedL).'</a>';
		}
		return $code;
	}

	/**
	 * Returns list URL; This is the URL of the current script with id and imagemode parameters, thats all.
	 * The URL however is not relative (with the backpath), otherwise t3lib_div::sanitizeLocalUrl() would say that
	 * the URL would be invalid
	 *
	 * @return	string		URL
	 */
	function listURL() {
		return t3lib_div::linkThisScript(array(
			'target'    => rawurlencode($this->folderObject->getCombinedIdentifier()),
			'imagemode' => $this->thumbs
		));
	}

	/**
	 * Returns some data specific for the directories...
	 *
	 * @param t3lib_file_Folder $folderObject File information array
	 * @return array (title, icon, path)
	 */
	function dirData(t3lib_file_Folder $folderObject) {
		$title = htmlspecialchars($folderObject->getName());
		$icon = 'apps-filetree-folder-default';

		if ($title == '_temp_') {
			$icon = 'apps-filetree-folder-temp';
			$title = '<strong>' . $GLOBALS['LANG']->getLL('temp', TRUE) . '</strong>';
		}

		if ($title == '_recycler_') {
			$icon = 'apps-filetree-folder-recycler';
			$title = '<strong>' . $GLOBALS['LANG']->getLL('recycler', TRUE) . '</strong>';
		}

			// mark the icon as read-only icon if the folder is not writable
		if ($folderObject->checkActionPermission('write') === FALSE) {
			$icon = 'apps-filetree-folder-locked';
		}
		return array($title, $icon, $folderObject->getIdentifier());
	}

	/**
	 * This returns tablerows for the files in the array $items['sorting'].
	 *
	 * @param t3lib_file_File[] $files File items
	 * @return string HTML table rows.
	 */
	function formatFileList(array $files) {
		$out = '';

		foreach ($files as $fileObject) {
			list($flag, $code) = $this->fwd_rwd_nav();
			$out .= $code;
			if ($flag) {
					// Initialization
				$this->counter++;
				$fileInfo = $fileObject->getStorage()->getFileInfo($fileObject);

				$this->totalbytes += $fileObject->getSize();
				$ext = $fileObject->getExtension();
				$fileName = trim($fileObject->getName());

					// The icon with link
				$theIcon = t3lib_iconWorks::getSpriteIconForFile($ext, array('title' => $fileName));

				if ($this->clickMenus) {
					$theIcon = $GLOBALS['SOBE']->doc->wrapClickMenuOnIcon($theIcon, $fileObject->getCombinedIdentifier());
				}

					// 	Preparing and getting the data-array
				$theData = array();
				foreach ($this->fieldArray as $field) {
					switch ($field) {
						case 'size':
							$theData[$field] = t3lib_div::formatSize($fileObject->getSize(), $GLOBALS['LANG']->getLL('byteSizeUnits', TRUE));
						break;
						case 'rw':
							$theData[$field] = '' . 
								(!$fileObject->checkActionPermission('read') ? ' ' : '<span class="typo3-red"><strong>' . $GLOBALS['LANG']->getLL('read', TRUE) . '</strong></span>') .
								(!$fileObject->checkActionPermission('write') ? ''  : '<span class="typo3-red"><strong>' . $GLOBALS['LANG']->getLL('write', TRUE) . '</strong></span>');
						break;
						case 'fileext':
							$theData[$field] = strtoupper($ext);
						break;
						case 'tstamp':
							$theData[$field] = t3lib_BEfunc::date($fileInfo['mtime']);
						break;
						case '_CLIPBOARD_':
							$temp = '';
							if ($this->bigControlPanel) {
								$temp .= $this->makeEdit($fileObject);
							}
							$temp .= $this->makeClip($fileObject);
							$theData[$field] = $temp;
						break;
						case '_REF_':
							$theData[$field] = $this->makeRef($fileObject);
						break;
						case 'file':
							$theData[$field] = $this->linkWrapFile(htmlspecialchars($fileName), $fileObject);

								// Thumbnails?
							if ($this->thumbs && $this->isImage($ext)) {
								$processedFile = $fileObject->process(t3lib_file_ProcessedFile::CONTEXT_IMAGEPREVIEW, array());
								if ($processedFile) {
									$thumbUrl = $processedFile->getPublicUrl(TRUE);
									$theData[$field] .= '<br /><img src="' . $thumbUrl . '" hspace="2" title="' . htmlspecialchars($fileName) . '" alt="" />';
								}
							}
						break;
						default:
								// @todo: fix the access on the array
							$theData[$field]= htmlspecialchars(t3lib_div::fixed_lgd_cs($theFile[$field], $this->fixedL));
						break;
					}
				}

				$out .= $this->addelement(1, $theIcon, $theData, ' class="file_list_normal"');
			}
			$this->eCounter++;
		}
		return $out;
	}

	/**
	 * Returns TRUE if $ext is an image-extension according to $GLOBALS['TYPO3_CONF_VARS']['GFX']['imagefile_ext']
	 *
	 * @param	string		file extension
	 * @return	boolean
	 */
	function isImage($ext) {
		return t3lib_div::inList($GLOBALS['TYPO3_CONF_VARS']['GFX']['imagefile_ext'],strtolower($ext));
	}

	/**
	 * Wraps the directory-titles ($code) in a link to file_list.php (id=$path) and sorting commands...
	 *
	 * @param	string		String to be wrapped
	 * @param	string		ID (path)
	 * @param	string		Sorting column
	 * @return	string		HTML
	 */
	function linkWrapSort($code, $folderIdentifier, $col) {
		if ($this->sort==$col && !$this->sortRev)	{		// reverse sorting
			$params='&SET[sort]=' . $col . '&SET[reverse]=1';
		} else {
			$params='&SET[sort]=' . $col . '&SET[reverse]=0';
		}
		$href = t3lib_div::resolveBackPath($GLOBALS['BACK_PATH'] . $this->script) . '?id=' . rawurlencode($folderIdentifier) . $params;
		return '<a href="'.htmlspecialchars($href).'">'.$code.'</a>';
	}

	/**
	 * Creates the clipboard control pad
	 *
	 * @param t3lib_file_File|t3lib_file_Folder $fileOrFolderObject Array with information about the file/directory for which to make the clipboard panel for the listing.
	 * @return string HTML-table
	 */
	function makeClip($fileOrFolderObject) {
		$cells = array();
		$fullIdentifier = $fileOrFolderObject->getCombinedIdentifier();
		$md5 = t3lib_div::shortmd5($fullIdentifier);

			// For normal clipboard, add copy/cut buttons:
		if ($this->clipObj->current == 'normal') {
			$isSel = $this->clipObj->isSelected('_FILE', $md5);
			$cells[]='<a href="' . htmlspecialchars($this->clipObj->selUrlFile($fullIdentifier, 1, ($isSel == 'copy'))) . '">' .
						t3lib_iconWorks::getSpriteIcon('actions-edit-copy'.($isSel=='copy'?'-release':''),array('title'=>$GLOBALS['LANG']->sL('LLL:EXT:lang/locallang_core.xml:cm.copy',1))).
						'</a>';
			$cells[]='<a href="' . htmlspecialchars($this->clipObj->selUrlFile($fullIdentifier, 0, ($isSel == 'cut'))) . '">' .
						t3lib_iconWorks::getSpriteIcon('actions-edit-cut'.($isSel=='cut'?'-release':''),array('title'=>$GLOBALS['LANG']->sL('LLL:EXT:lang/locallang_core.xml:cm.cut',1))).
						'</a>';
		} else {	// For numeric pads, add select checkboxes:
			$n='_FILE|'.$md5;
			$this->CBnames[]=$n;

			$checked = ($this->clipObj->isSelected('_FILE',$md5)?' checked="checked"':'');
			$cells[]='<input type="hidden" name="CBH['.$n.']" value="0" />'.
					'<input type="checkbox" name="CBC[' . $n . ']" value="' . htmlspecialchars($fullIdentifier) . '" class="smallCheckboxes"' . $checked . ' />';
		}

			// Display PASTE button, if directory:
		$elFromTable = $this->clipObj->elFromTable('_FILE');
		if (is_a($fileOrFolderObject, 't3lib_file_Folder') && count($elFromTable)) {
			$cells[] = '<a href="' . htmlspecialchars($this->clipObj->pasteUrl('_FILE', $fullIdentifier)) . '" onclick="return ' . htmlspecialchars($this->clipObj->confirmMsg('_FILE', $fullIdentifier, 'into', $elFromTable)) . '" title="' . $GLOBALS['LANG']->getLL('clip_pasteInto', TRUE) . '">' .
			t3lib_iconWorks::getSpriteIcon('actions-document-paste-into') .
			'</a>';
		}

			// Compile items into a DIV-element:
		return '							<!-- CLIPBOARD PANEL: -->
											<div class="typo3-clipCtrl">
												'.implode('
												',$cells).'
											</div>';
	}

	/**
	* Creates the edit control section
	*
	* @param t3lib_file_File|t3lib_file_Folder $fileOrFolderObject Array with information about the file/directory for which to make the edit control section for the listing.
	* @return string HTML-table
	*/
	function makeEdit($fileOrFolderObject) {
		$cells = array();
		$fullIdentifier = $fileOrFolderObject->getCombinedIdentifier();

			// edit metadata of file
		try {
			if (is_a($fileOrFolderObject, 't3lib_file_File') && $fileOrFolderObject->isIndexed() && $fileOrFolderObject->checkActionPermission('edit')) {
				$data = array(
					'sys_file' => array($fileOrFolderObject->getUid() => 'edit')
				);
				$editOnClick = t3lib_BEfunc::editOnClick(t3lib_div::implodeArrayForUrl('edit', $data), $GLOBALS['BACK_PATH'], $this->listUrl());
				$cells['editmetadata'] = '<a href="#" onclick="' . $editOnClick . '" title="Edit Metadata of this file">' . t3lib_iconWorks::getSpriteIcon('actions-document-open') . '</a>';
			} else{
				$cells['editmetadata'] = t3lib_iconWorks::getSpriteIcon('empty-empty');
			}
		} catch (Exception $e) {
			$cells['editmetadata'] = t3lib_iconWorks::getSpriteIcon('empty-empty');
		}

			// edit file content (if editable)
		if (is_a($fileOrFolderObject, 't3lib_file_File') && $fileOrFolderObject->checkActionPermission('edit') && t3lib_div::inList($GLOBALS['TYPO3_CONF_VARS']['SYS']['textfile_ext'], $fileOrFolderObject->getExtension())) {
			$editOnClick = 'top.content.list_frame.location.href=top.TS.PATH_typo3+\'file_edit.php?target=' .
				rawurlencode($fullIdentifier) .
				'&returnUrl=\'+top.rawurlencode(top.content.list_frame.document.location.pathname+top.content.list_frame.document.location.search);return false;';
			$cells['edit'] = '<a href="#" onclick="' . $editOnClick . '" title="' . $GLOBALS['LANG']->sL('LLL:EXT:lang/locallang_core.php:cm.edit') . '">' . t3lib_iconWorks::getSpriteIcon('actions-page-open') . '</a>';
		} else {
			$cells['edit'] = t3lib_iconWorks::getSpriteIcon('empty-empty');
		}

			// rename the file
		if ($fileOrFolderObject->checkActionPermission('rename')) {
			$renameOnClick = 'top.content.list_frame.location.href = top.TS.PATH_typo3+\'file_rename.php?target=' .
				rawurlencode($fullIdentifier) .
				'&returnUrl=\'+top.rawurlencode(top.content.list_frame.document.location.pathname+top.content.list_frame.document.location.search);return false;';

			$cells['rename'] = '<a href="#" onclick="' . $renameOnClick . '"  title="' . $GLOBALS['LANG']->sL('LLL:EXT:lang/locallang_core.php:cm.rename') . '">' . t3lib_iconWorks::getSpriteIcon('actions-edit-rename') . '</a>';
		} else {
			$cells['rename'] = t3lib_iconWorks::getSpriteIcon('empty-empty');
		}

		if (is_a($fileOrFolderObject, 't3lib_file_Folder')) {
			$infoOnClick = 'top.launchView( \'_FOLDER\', \'' . $fullIdentifier . '\');return false;';
		} elseif (is_a($fileOrFolderObject, 't3lib_file_File')) {
			$infoOnClick = 'top.launchView( \'_FILE\', \'' . $fullIdentifier . '\');return false;';
		}

		$cells['info'] = '<a href="#" onclick="' . $infoOnClick . '" title="' . $GLOBALS['LANG']->sL('LLL:EXT:lang/locallang_core.php:cm.info') . '">' . t3lib_iconWorks::getSpriteIcon('status-dialog-information') . '</a>';
			// Hook for manipulating edit icons.
		if(is_array($GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS']['fileList']['editIconsHook'])) {
			foreach($GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS']['fileList']['editIconsHook'] as $classData) {
				$hookObject = t3lib_div::getUserObj($classData);

				if(!($hookObject instanceof fileList_editIconHook)) {
					throw new UnexpectedValueException('$hookObject must implement interface fileList_editIconHook', 1235225797);
				}

				$hookObject->manipulateEditIcons($cells, $this);
			}
		}

			// Compile items into a DIV-element:
		return '							<!-- EDIT CONTROLS: -->
											<div class="typo3-editCtrl">
												' . implode('
												', $cells).'
											</div>';
	}

	/**
	 * Make reference count
	 *
	 * @param t3lib_file_File|t3lib_file_Folder $fileOrFolderObject Array with information about the file/directory for which to make the clipboard panel for the listing.
	 * @return string HTML
	 */
	function makeRef($fileOrFolderObject) {
		if ($fileOrFolderObject instanceof t3lib_file_FolderInterface) {
			return '-';
		}

			// Look up the path:
		$rows = $GLOBALS['TYPO3_DB']->exec_SELECTgetRows(
			'*',
			'sys_refindex',
			'ref_table = ' . $GLOBALS['TYPO3_DB']->fullQuoteStr('sys_file', 'sys_refindex') .
				' AND ref_uid = ' . $fileOrFolderObject->getUid() . ' AND deleted=0'
		);

			// Compile information for title tag:
		$infoData=array();
		if (is_array($rows)) {
			foreach($rows as $row) {
				$infoData[]= $row['tablename'] . ':' . $row['recuid'] . ':' . $row['field'];
			}
		}

		return count($infoData) ? '<a href="#" onclick="'.htmlspecialchars('top.launchView( \'_FILE\', \'' . $fileOrFolderObject->getCombinedIdentifier() . '\'); return false;').'" title="'.htmlspecialchars(t3lib_div::fixed_lgd_cs(implode(' / ',$infoData),100)).'">' . count($infoData) . '</a>' : '-';
	}
}

if (defined('TYPO3_MODE') && isset($GLOBALS['TYPO3_CONF_VARS'][TYPO3_MODE]['XCLASS']['typo3/class.file_list.inc'])) {
	include_once($GLOBALS['TYPO3_CONF_VARS'][TYPO3_MODE]['XCLASS']['typo3/class.file_list.inc']);
}

?>