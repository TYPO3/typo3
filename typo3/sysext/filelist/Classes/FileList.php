<?php
namespace TYPO3\CMS\Filelist;

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

use TYPO3\CMS\Core\Resource\FolderInterface;

/**
 * Class for rendering of File>Filelist
 *
 * @author Kasper Skårhøj <kasperYYYY@typo3.com>
 */
class FileList extends \TYPO3\CMS\Backend\RecordList\AbstractRecordList {

	/**
	 * Default Max items shown
	 *
	 * @todo Define visibility
	 */
	public $iLimit = 40;

	/**
	 * Boolean. Thumbnails on records containing files (pictures)
	 *
	 * @todo Define visibility
	 */
	public $thumbs = 0;

	/**
	 * @todo Define visibility
	 */
	public $widthGif = '<img src="clear.gif" width="1" height="1" hspace="165" alt="" />';

	/**
	 * Max length of strings
	 *
	 * @todo Define visibility
	 */
	public $fixedL = 30;

	/**
	 * @todo Define visibility
	 */
	public $script = '';

	/**
	 * If TRUE click menus are generated on files and folders
	 *
	 * @todo Define visibility
	 */
	public $clickMenus = 1;

	/**
	 * The field to sort by
	 *
	 * @todo Define visibility
	 */
	public $sort = '';

	/**
	 * Reverse sorting flag
	 *
	 * @todo Define visibility
	 */
	public $sortRev = 1;

	/**
	 * @todo Define visibility
	 */
	public $firstElementNumber = 0;

	/**
	 * @todo Define visibility
	 */
	public $clipBoard = 0;

	/**
	 * @todo Define visibility
	 */
	public $bigControlPanel = 0;

	/**
	 * @todo Define visibility
	 */
	public $JScode = '';

	/**
	 * @todo Define visibility
	 */
	public $HTMLcode = '';

	/**
	 * @todo Define visibility
	 */
	public $totalbytes = 0;

	/**
	 * @todo Define visibility
	 */
	public $dirs = array();

	/**
	 * @todo Define visibility
	 */
	public $files = array();

	/**
	 * @todo Define visibility
	 */
	public $path = '';

	/**
	 * @var \TYPO3\CMS\Core\Resource\Folder
	 */
	protected $folderObject;

	/**
	 * Counting the elements no matter what
	 *
	 * @todo Define visibility
	 */
	public $eCounter = 0;

	/**
	 * @todo Define visibility
	 */
	public $dirCounter = 0;

	/**
	 * @todo Define visibility
	 */
	public $totalItems = '';

	/**
	 * @todo Define visibility
	 */
	public $CBnames = array();

	/**
	 * Initialization of class
	 *
	 * @param \TYPO3\CMS\Core\Resource\Folder $folderObject The folder to work on
	 * @param integer $pointer Pointer
	 * @param boolean $sort Sorting column
	 * @param boolean $sortRev Sorting direction
	 * @param boolean $bigControlPanel Show clipboard flag
	 * @return void
	 * @todo Define visibility
	 */
	public function start(\TYPO3\CMS\Core\Resource\Folder $folderObject, $pointer, $sort, $sortRev, $clipBoard = FALSE, $bigControlPanel = FALSE) {
		$this->script = \TYPO3\CMS\Backend\Utility\BackendUtility::getModuleUrl('file_list');
		$this->folderObject = $folderObject;
		$this->counter = 0;
		$this->totalbytes = 0;
		$this->JScode = '';
		$this->HTMLcode = '';
		$this->path = $folderObject->getIdentifier();
		$this->sort = $sort;
		$this->sortRev = $sortRev;
		$this->firstElementNumber = $pointer;
		$this->clipBoard = $clipBoard;
		$this->bigControlPanel = $bigControlPanel;
		// Setting the maximum length of the filenames to the user's settings or minimum 30 (= $this->fixedL)
		$this->fixedL = max($this->fixedL, $GLOBALS['BE_USER']->uc['titleLen']);
		$GLOBALS['LANG']->includeLLFile('EXT:lang/locallang_common.xlf');
	}

	/**
	 * Reading files and directories, counting elements and generating the list in ->HTMLcode
	 *
	 * @return void
	 * @todo Define visibility
	 */
	public function generateList() {
		$this->HTMLcode .= $this->getTable('fileext,tstamp,size,rw,_REF_');
	}

	/**
	 * Return the buttons used by the file list to include in the top header
	 *
	 * @param \TYPO3\CMS\Core\Resource\Folder $folderObject
	 * @return array
	 */
	public function getButtonsAndOtherMarkers(\TYPO3\CMS\Core\Resource\Folder $folderObject) {
		$otherMarkers = array(
			'PAGE_ICON' => '',
			'TITLE' => ''
		);
		$buttons = array(
			'level_up' => '',
			'refresh' => '',
			'title' => '',
			'page_icon' => '',
			'PASTE' => ''
		);
		// Makes the code for the foldericon in the top
		if ($folderObject) {
			list($_, $icon, $path) = $this->dirData($folderObject);
			$title = htmlspecialchars($folderObject->getIdentifier());
			// Start compiling the HTML
			// @todo: how to fix this? $title = $GLOBALS['SOBE']->basicFF->blindPath($title);
			// If this is some subpage under the mount root....
			if ($folderObject->getStorage()->isWithinFileMountBoundaries($folderObject)) {
				// The icon with link
				$otherMarkers['PAGE_ICON'] = \TYPO3\CMS\Backend\Utility\IconUtility::getSpriteIcon($icon, array('title' => $title));
				$buttons['level_up'] = $this->linkWrapDir(\TYPO3\CMS\Backend\Utility\IconUtility::getSpriteIcon('actions-view-go-up', array('title' => $GLOBALS['LANG']->sL('LLL:EXT:lang/locallang_core.xlf:labels.upOneLevel', 1))), $folderObject);
				// No HTML specialchars here - HTML like <strong> </strong> is allowed
				$otherMarkers['TITLE'] .= \TYPO3\CMS\Core\Utility\GeneralUtility::removeXSS(\TYPO3\CMS\Core\Utility\GeneralUtility::fixed_lgd_cs($title, -($this->fixedL + 20)));
			} else {
				// This is the root page
				$otherMarkers['PAGE_ICON'] = \TYPO3\CMS\Backend\Utility\IconUtility::getSpriteIcon('apps-filetree-root');
				$otherMarkers['TITLE'] .= htmlspecialchars(\TYPO3\CMS\Core\Utility\GeneralUtility::fixed_lgd_cs($title, -($this->fixedL + 20)));
			}
			if ($this->clickMenus) {
				$otherMarkers['PAGE_ICON'] = $GLOBALS['SOBE']->doc->wrapClickMenuOnIcon($otherMarkers['PAGE_ICON'], $folderObject->getCombinedIdentifier());
			}
			// Add paste button
			$elFromTable = $this->clipObj->elFromTable('_FILE');
			if (count($elFromTable)) {
				$buttons['PASTE'] = '<a href="' . htmlspecialchars($this->clipObj->pasteUrl('_FILE', $this->folderObject->getCombinedIdentifier())) . '" onclick="return ' . htmlspecialchars($this->clipObj->confirmMsg('_FILE', $this->path, 'into', $elFromTable)) . '" title="' . $GLOBALS['LANG']->getLL('clip_paste', TRUE) . '">' . \TYPO3\CMS\Backend\Utility\IconUtility::getSpriteIcon('actions-document-paste-after') . '</a>';
			}
		}
		$buttons['refresh'] = '<a href="' . htmlspecialchars($this->listURL()) . '" title="' . $GLOBALS['LANG']->sL('LLL:EXT:lang/locallang_core.xlf:labels.reload', 1) . '">' . \TYPO3\CMS\Backend\Utility\IconUtility::getSpriteIcon('actions-system-refresh') . '</a>';
		return array($buttons, $otherMarkers);
	}

	/**
	 * Wrapping input string in a link with clipboard command.
	 *
	 * @param string $string String to be linked - must be htmlspecialchar'ed / prepared before.
	 * @param string $table table - NOT USED
	 * @param string $cmd "cmd" value
	 * @param string $warning Warning for JS confirm message
	 * @return string Linked string
	 * @todo Define visibility
	 */
	public function linkClipboardHeaderIcon($string, $table, $cmd, $warning = '') {
		$onClickEvent = 'document.dblistForm.cmd.value=\'' . $cmd . '\';document.dblistForm.submit();';
		if ($warning) {
			$onClickEvent = 'if (confirm(' . $GLOBALS['LANG']->JScharCode($warning) . ')){' . $onClickEvent . '}';
		}
		return '<a href="#" onclick="' . htmlspecialchars($onClickEvent) . 'return false;">' . $string . '</a>';
	}

	/**
	 * Returns a table with directories and files listed.
	 *
	 * @param array $rowlist Array of files from path
	 * @return string HTML-table
	 * @todo Define visibility
	 */
	public function getTable($rowlist) {
		// TODO use folder methods directly when they support filters
		$storage = $this->folderObject->getStorage();
		$storage->resetFileAndFolderNameFiltersToDefault();
		$folders = $storage->getFolderList($this->folderObject->getIdentifier());
		$files = $storage->getFileList($this->folderObject->getIdentifier());
		// Only render the contents of a browsable storage
		if ($this->folderObject->getStorage()->isBrowsable()) {
			$this->sort = trim($this->sort);
			if ($this->sort !== '') {
				$filesToSort = array();
				foreach ($files as $file) {
					$fileObject = $storage->getFile($file['identifier']);
					switch ($this->sort) {
						case 'size':
							$sortingKey = $fileObject->getSize();
							break;
						case 'rw':
							$sortingKey = ($fileObject->checkActionPermission('read') ? 'R' : '' . $fileObject->checkActionPermission('write')) ? 'W' : '';
							break;
						case 'fileext':
							$sortingKey = $fileObject->getExtension();
							break;
						case 'tstamp':
							$sortingKey = $fileObject->getModificationTime();
							break;
						default:
							if ($fileObject->hasProperty($this->sort)) {
								$sortingKey = $fileObject->getProperty($this->sort);
							} else {
								$sortingKey = $fileObject->getName();
							}
					}
					$i = 0;
					while (isset($filesToSort[$sortingKey . $i])) {
						$i++;
					}
					$filesToSort[$sortingKey . $i] = $fileObject;
				}
				uksort($filesToSort, 'strnatcasecmp');
				if (intval($this->sortRev) === 1) {
					$filesToSort = array_reverse($filesToSort);
				}
				$files = $filesToSort;
			}
			$this->totalItems = count($folders) + count($files);
			// Adds the code of files/dirs
			$out = '';
			$titleCol = 'file';
			// Cleaning rowlist for duplicates and place the $titleCol as the first column always!
			$rowlist = \TYPO3\CMS\Core\Utility\GeneralUtility::rmFromList($titleCol, $rowlist);
			$rowlist = \TYPO3\CMS\Core\Utility\GeneralUtility::uniqueList($rowlist);
			$rowlist = $rowlist ? $titleCol . ',' . $rowlist : $titleCol;
			if ($this->bigControlPanel || $this->clipBoard) {
				$rowlist = str_replace('file,', 'file,_CLIPBOARD_,', $rowlist);
			}
			$this->fieldArray = explode(',', $rowlist);
			$folderObjects = array();
			foreach ($folders as $folder) {
				$folderObjects[] = $storage->getFolder($folder['identifier']);
			}

			$folderObjects = \TYPO3\CMS\Core\Resource\Utility\ListUtility::resolveSpecialFolderNames($folderObjects);
			uksort($folderObjects, 'strnatcasecmp');

			// Directories are added
			$iOut = $this->formatDirList($folderObjects);
			if ($iOut) {
				// Half line is drawn
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
					$cells = array();
					$table = '_FILE';
					$elFromTable = $this->clipObj->elFromTable($table);
					if (count($elFromTable)) {
						$cells[] = '<a href="' . htmlspecialchars($this->clipObj->pasteUrl('_FILE', $this->folderObject->getCombinedIdentifier())) . '" onclick="return ' . htmlspecialchars($this->clipObj->confirmMsg('_FILE', $this->path, 'into', $elFromTable)) . '" title="' . $GLOBALS['LANG']->getLL('clip_paste', 1) . '">' . \TYPO3\CMS\Backend\Utility\IconUtility::getSpriteIcon('actions-document-paste-after') . '</a>';
					}
					if ($this->clipObj->current != 'normal' && $iOut) {
						$cells[] = $this->linkClipboardHeaderIcon(\TYPO3\CMS\Backend\Utility\IconUtility::getSpriteIcon('actions-edit-copy', array('title' => $GLOBALS['LANG']->getLL('clip_selectMarked', 1))), $table, 'setCB');
						$cells[] = $this->linkClipboardHeaderIcon(\TYPO3\CMS\Backend\Utility\IconUtility::getSpriteIcon('actions-edit-delete', array('title' => $GLOBALS['LANG']->getLL('clip_deleteMarked'))), $table, 'delete', $GLOBALS['LANG']->getLL('clip_deleteMarkedWarning'));
						$onClick = 'checkOffCB(\'' . implode(',', $this->CBnames) . '\', this); return false;';
						$cells[] = '<a class="cbcCheckAll" rel="" href="#" onclick="' . htmlspecialchars($onClick) . '" title="' . $GLOBALS['LANG']->getLL('clip_markRecords', TRUE) . '">' . \TYPO3\CMS\Backend\Utility\IconUtility::getSpriteIcon('actions-document-select') . '</a>';
					}
					$theData[$v] = implode('', $cells);
				} else {
					// Normal row:
					$theT = $this->linkWrapSort($GLOBALS['LANG']->getLL('c_' . $v, 1), $this->folderObject->getCombinedIdentifier(), $v);
					$theData[$v] = $theT;
				}
			}

			if (!empty($iOut)) {
				$out .= '<thead>' . $this->addelement(1, $levelUp, $theData, ' class="t3-row-header"', '') . '</thead>';
				$out .= '<tbody>' . $iOut . '</tbody>';
				// half line is drawn
				// finish
				$out = '
		<!--
			File list table:
		-->
			<table cellpadding="0" cellspacing="0" id="typo3-filelist">
				' . $out . '
			</table>';
			}
		} else {
			/** @var $flashMessage \TYPO3\CMS\Core\Messaging\FlashMessage */
			$flashMessage = \TYPO3\CMS\Core\Utility\GeneralUtility::makeInstance('TYPO3\\CMS\\Core\\Messaging\\FlashMessage', $GLOBALS['LANG']->getLL('storageNotBrowsableMessage'), $GLOBALS['LANG']->getLL('storageNotBrowsableTitle'), \TYPO3\CMS\Core\Messaging\FlashMessage::INFO);
			$out = $flashMessage->render();
		}
		return $out;
	}

	/**
	 * Gets the number of files and total size of a folder
	 *
	 * @return string
	 * @todo Define visibility
	 */
	public function getFolderInfo() {
		if ($this->counter == 1) {
			$fileLabel = $GLOBALS['LANG']->getLL('file', TRUE);
		} else {
			$fileLabel = $GLOBALS['LANG']->getLL('files', TRUE);
		}
		return $this->counter . ' ' . $fileLabel . ', ' . \TYPO3\CMS\Core\Utility\GeneralUtility::formatSize($this->totalbytes, $GLOBALS['LANG']->getLL('byteSizeUnits', TRUE));
	}

	/**
	 * This returns tablerows for the directories in the array $items['sorting'].
	 *
	 * @param \TYPO3\CMS\Core\Resource\Folder[] $folders Folders of \TYPO3\CMS\Core\Resource\Folder
	 * @return string HTML table rows.
	 * @todo Define visibility
	 */
	public function formatDirList(array $folders) {
		$out = '';
		foreach ($folders as $folderName => $folderObject) {
			$role = $folderObject->getRole();
			if ($role === FolderInterface::ROLE_PROCESSING) {
				// don't show processing-folder
				continue;
			}
			if ($role !== FolderInterface::ROLE_DEFAULT) {
				$displayName = '<strong>' . htmlspecialchars($folderName) . '</strong>';
			} else {
				$displayName = htmlspecialchars($folderName);
			}

			list($flag, $code) = $this->fwd_rwd_nav();
			$out .= $code;
			if ($flag) {
				// Initialization
				$this->counter++;
				list($_, $icon, $path) = $this->dirData($folderObject);
				// The icon with link
				$theIcon = \TYPO3\CMS\Backend\Utility\IconUtility::getSpriteIcon($icon, array('title' => $folderName));
				if ($this->clickMenus) {
					$theIcon = $GLOBALS['SOBE']->doc->wrapClickMenuOnIcon($theIcon, $folderObject->getCombinedIdentifier());
				}
				// Preparing and getting the data-array
				$theData = array();
				foreach ($this->fieldArray as $field) {
					switch ($field) {
						case 'size':
							$numFiles = $folderObject->getFileCount();
							$theData[$field] = $numFiles . ' ' . $GLOBALS['LANG']->getLL(($numFiles === 1 ? 'file' : 'files'), TRUE);
							break;
						case 'rw':
							$theData[$field] = (!$folderObject->checkActionPermission('read') ? ' ' : '<span class="typo3-red"><strong>' . $GLOBALS['LANG']->getLL('read', TRUE) . '</strong></span>') . (!$folderObject->checkActionPermission('write') ? '' : '<span class="typo3-red"><strong>' . $GLOBALS['LANG']->getLL('write', TRUE) . '</strong></span>');
							break;
						case 'fileext':
							$theData[$field] = $GLOBALS['LANG']->getLL('folder', TRUE);
							break;
						case 'tstamp':
							// @todo: FAL: how to get the mtime info -- $theData[$field] = \TYPO3\CMS\Backend\Utility\BackendUtility::date($theFile['tstamp']);
							$theData[$field] = '-';
							break;
						case 'file':
							$theData[$field] = $this->linkWrapDir($displayName, $folderObject);
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
							$theData[$field] = \TYPO3\CMS\Core\Utility\GeneralUtility::fixed_lgd_cs($theFile[$field], $this->fixedL);
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
	 * @param \TYPO3\CMS\Core\Resource\Folder $folderObject Folder to work on
	 * @return string HTML
	 * @todo Define visibility
	 */
	public function linkWrapDir($title, \TYPO3\CMS\Core\Resource\Folder $folderObject) {
		$href = $this->backPath . $this->script . '&id=' . rawurlencode($folderObject->getCombinedIdentifier());
		$onclick = ' onclick="' . htmlspecialchars(('top.content.nav_frame.hilight_row("file","folder' . \TYPO3\CMS\Core\Utility\GeneralUtility::md5int($folderObject->getCombinedIdentifier()) . '_"+top.fsMod.currentBank)')) . '"';
		// Sometimes $code contains plain HTML tags. In such a case the string should not be modified!
		if (!strcmp($title, strip_tags($title))) {
			return '<a href="' . htmlspecialchars($href) . '"' . $onclick . ' title="' . htmlspecialchars($title) . '">' . \TYPO3\CMS\Core\Utility\GeneralUtility::fixed_lgd_cs($title, $this->fixedL) . '</a>';
		} else {
			return '<a href="' . htmlspecialchars($href) . '"' . $onclick . '>' . $title . '</a>';
		}
	}

	/**
	 * Wraps filenames in links which opens them in a window IF they are in web-path.
	 *
	 * @param string $code String to be wrapped in links
	 * @param \TYPO3\CMS\Core\Resource\File $fileObject File to be linked
	 * @return string HTML
	 * @todo Define visibility
	 */
	public function linkWrapFile($code, \TYPO3\CMS\Core\Resource\File $fileObject) {
		$fileUrl = $fileObject->getPublicUrl(TRUE);
		if ($fileUrl) {
			$aOnClick = 'return top.openUrlInWindow(\'' . $fileUrl . '\', \'WebFile\');';
			$code = '<a href="#" title="' . htmlspecialchars($code) . '" onclick="' . htmlspecialchars($aOnClick) . '">' . \TYPO3\CMS\Core\Utility\GeneralUtility::fixed_lgd_cs($code, $this->fixedL) . '</a>';
		}
		return $code;
	}

	/**
	 * Returns list URL; This is the URL of the current script with id and imagemode parameters, thats all.
	 * The URL however is not relative (with the backpath), otherwise \TYPO3\CMS\Core\Utility\GeneralUtility::sanitizeLocalUrl() would say that
	 * the URL would be invalid
	 *
	 * @return string URL
	 * @todo Define visibility
	 */
	public function listURL() {
		return \TYPO3\CMS\Core\Utility\GeneralUtility::linkThisScript(array(
			'target' => rawurlencode($this->folderObject->getCombinedIdentifier()),
			'imagemode' => $this->thumbs
		));
	}

	/**
	 * Returns some data specific for the directories...
	 *
	 * @param \TYPO3\CMS\Core\Resource\Folder $folderObject File information array
	 * @return array (title, icon, path)
	 * @todo Define visibility
	 */
	public function dirData(\TYPO3\CMS\Core\Resource\Folder $folderObject) {
		$title = htmlspecialchars($folderObject->getName());
		$icon = 'apps-filetree-folder-default';
		$role = $folderObject->getRole();
		if ($role === FolderInterface::ROLE_TEMPORARY) {
			$title = '<strong>' . $GLOBALS['LANG']->getLL('temp', TRUE) . '</strong>';
			$icon = 'apps-filetree-folder-temp';
		} elseif ($role === FolderInterface::ROLE_RECYCLER) {
			$icon = 'apps-filetree-folder-recycler';
			$title = '<strong>' . $GLOBALS['LANG']->getLL('recycler', TRUE) . '</strong>';
		}
		// Mark the icon as read-only icon if the folder is not writable
		if ($folderObject->checkActionPermission('write') === FALSE) {
			$icon = 'apps-filetree-folder-locked';
		}
		return array($title, $icon, $folderObject->getIdentifier());
	}

	/**
	 * This returns tablerows for the files in the array $items['sorting'].
	 *
	 * @param \TYPO3\CMS\Core\Resource\File[] $files File items
	 * @return string HTML table rows.
	 * @todo Define visibility
	 */
	public function formatFileList(array $files) {
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
				$theIcon = \TYPO3\CMS\Backend\Utility\IconUtility::getSpriteIconForFile($ext, array('title' => $fileName));
				if ($this->clickMenus) {
					$theIcon = $GLOBALS['SOBE']->doc->wrapClickMenuOnIcon($theIcon, $fileObject->getCombinedIdentifier());
				}
				// Preparing and getting the data-array
				$theData = array();
				foreach ($this->fieldArray as $field) {
					switch ($field) {
						case 'size':
							$theData[$field] = \TYPO3\CMS\Core\Utility\GeneralUtility::formatSize($fileObject->getSize(), $GLOBALS['LANG']->getLL('byteSizeUnits', TRUE));
							break;
						case 'rw':
							$theData[$field] = '' . (!$fileObject->checkActionPermission('read') ? ' ' : '<span class="typo3-red"><strong>' . $GLOBALS['LANG']->getLL('read', TRUE) . '</strong></span>') . (!$fileObject->checkActionPermission('write') ? '' : '<span class="typo3-red"><strong>' . $GLOBALS['LANG']->getLL('write', TRUE) . '</strong></span>');
							break;
						case 'fileext':
							$theData[$field] = strtoupper($ext);
							break;
						case 'tstamp':
							$theData[$field] = \TYPO3\CMS\Backend\Utility\BackendUtility::date($fileInfo['mtime']);
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
								$processedFile = $fileObject->process(\TYPO3\CMS\Core\Resource\ProcessedFile::CONTEXT_IMAGEPREVIEW, array());
								if ($processedFile) {
									$thumbUrl = $processedFile->getPublicUrl(TRUE);
									$theData[$field] .= '<br /><img src="' . $thumbUrl . '" hspace="2" title="' . htmlspecialchars($fileName) . '" alt="" />';
								}
							}
							break;
						default:
							// @todo: fix the access on the array
							$theData[$field] = htmlspecialchars(\TYPO3\CMS\Core\Utility\GeneralUtility::fixed_lgd_cs($theFile[$field], $this->fixedL));
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
	 * @param string $ext File extension
	 * @return boolean
	 * @todo Define visibility
	 */
	public function isImage($ext) {
		return \TYPO3\CMS\Core\Utility\GeneralUtility::inList($GLOBALS['TYPO3_CONF_VARS']['GFX']['imagefile_ext'], strtolower($ext));
	}

	/**
	 * Wraps the directory-titles ($code) in a link to filelist/mod1/index.php (id=$path) and sorting commands...
	 *
	 * @param string $code String to be wrapped
	 * @param string $folderIdentifier ID (path)
	 * @param string $col Sorting column
	 * @return string HTML
	 * @todo Define visibility
	 */
	public function linkWrapSort($code, $folderIdentifier, $col) {
		if ($this->sort === $col) {
			// Check reverse sorting
			$params = '&SET[sort]=' . $col . '&SET[reverse]=' . ($this->sortRev ? '0' : '1');
			$sortArrow = \TYPO3\CMS\Backend\Utility\IconUtility::getSpriteIcon('status-status-sorting-light-' . ($this->sortRev ? 'desc' : 'asc'));
		} else {
			$params = '&SET[sort]=' . $col . '&SET[reverse]=0';
			$sortArrow = '';
		}
		$href = \TYPO3\CMS\Core\Utility\GeneralUtility::resolveBackPath(($GLOBALS['BACK_PATH'] . $this->script)) . '&id=' . rawurlencode($folderIdentifier) . $params;
		return '<a href="' . htmlspecialchars($href) . '">' . $code . $sortArrow . '</a>';
	}

	/**
	 * Creates the clipboard control pad
	 *
	 * @param \TYPO3\CMS\Core\Resource\File|\TYPO3\CMS\Core\Resource\Folder $fileOrFolderObject Array with information about the file/directory for which to make the clipboard panel for the listing.
	 * @return string HTML-table
	 * @todo Define visibility
	 */
	public function makeClip($fileOrFolderObject) {
		$cells = array();
		$fullIdentifier = $fileOrFolderObject->getCombinedIdentifier();
		$md5 = \TYPO3\CMS\Core\Utility\GeneralUtility::shortmd5($fullIdentifier);
		// For normal clipboard, add copy/cut buttons:
		if ($this->clipObj->current == 'normal') {
			$isSel = $this->clipObj->isSelected('_FILE', $md5);
			$cells[] = '<a href="' . htmlspecialchars($this->clipObj->selUrlFile($fullIdentifier, 1, ($isSel == 'copy'))) . '">' . \TYPO3\CMS\Backend\Utility\IconUtility::getSpriteIcon(('actions-edit-copy' . ($isSel == 'copy' ? '-release' : '')), array('title' => $GLOBALS['LANG']->sL('LLL:EXT:lang/locallang_core.xlf:cm.copy', 1))) . '</a>';
			$cells[] = '<a href="' . htmlspecialchars($this->clipObj->selUrlFile($fullIdentifier, 0, ($isSel == 'cut'))) . '">' . \TYPO3\CMS\Backend\Utility\IconUtility::getSpriteIcon(('actions-edit-cut' . ($isSel == 'cut' ? '-release' : '')), array('title' => $GLOBALS['LANG']->sL('LLL:EXT:lang/locallang_core.xlf:cm.cut', 1))) . '</a>';
		} else {
			// For numeric pads, add select checkboxes:
			$n = '_FILE|' . $md5;
			$this->CBnames[] = $n;
			$checked = $this->clipObj->isSelected('_FILE', $md5) ? ' checked="checked"' : '';
			$cells[] = '<input type="hidden" name="CBH[' . $n . ']" value="0" />' . '<input type="checkbox" name="CBC[' . $n . ']" value="' . htmlspecialchars($fullIdentifier) . '" class="smallCheckboxes"' . $checked . ' />';
		}
		// Display PASTE button, if directory:
		$elFromTable = $this->clipObj->elFromTable('_FILE');
		if (is_a($fileOrFolderObject, 'TYPO3\\CMS\\Core\\Resource\\Folder') && count($elFromTable)) {
			$cells[] = '<a href="' . htmlspecialchars($this->clipObj->pasteUrl('_FILE', $fullIdentifier)) . '" onclick="return ' . htmlspecialchars($this->clipObj->confirmMsg('_FILE', $fullIdentifier, 'into', $elFromTable)) . '" title="' . $GLOBALS['LANG']->getLL('clip_pasteInto', TRUE) . '">' . \TYPO3\CMS\Backend\Utility\IconUtility::getSpriteIcon('actions-document-paste-into') . '</a>';
		}
		// Compile items into a DIV-element:
		return '							<!-- CLIPBOARD PANEL: -->
											<div class="typo3-clipCtrl">
												' . implode('
												', $cells) . '
											</div>';
	}

	/**
	 * Creates the edit control section
	 *
	 * @param \TYPO3\CMS\Core\Resource\File|\TYPO3\CMS\Core\Resource\Folder $fileOrFolderObject Array with information about the file/directory for which to make the edit control section for the listing.
	 * @return string HTML-table
	 * @todo Define visibility
	 */
	public function makeEdit($fileOrFolderObject) {
		$cells = array();
		$fullIdentifier = $fileOrFolderObject->getCombinedIdentifier();
		// Edit metadata of file
		try {
			if (is_a($fileOrFolderObject, 'TYPO3\\CMS\\Core\\Resource\\File') && $fileOrFolderObject->isIndexed() && $fileOrFolderObject->checkActionPermission('edit')) {
				$data = array(
					'sys_file' => array($fileOrFolderObject->getUid() => 'edit')
				);
				$editOnClick = \TYPO3\CMS\Backend\Utility\BackendUtility::editOnClick(\TYPO3\CMS\Core\Utility\GeneralUtility::implodeArrayForUrl('edit', $data), $GLOBALS['BACK_PATH'], $this->listUrl());
				$cells['editmetadata'] = '<a href="#" onclick="' . $editOnClick . '" title="Edit Metadata of this file">' . \TYPO3\CMS\Backend\Utility\IconUtility::getSpriteIcon('actions-document-open') . '</a>';
			} else {
				$cells['editmetadata'] = \TYPO3\CMS\Backend\Utility\IconUtility::getSpriteIcon('empty-empty');
			}
		} catch (\Exception $e) {
			$cells['editmetadata'] = \TYPO3\CMS\Backend\Utility\IconUtility::getSpriteIcon('empty-empty');
		}
		// Edit file content (if editable)
		if (is_a($fileOrFolderObject, 'TYPO3\\CMS\\Core\\Resource\\File') && $fileOrFolderObject->checkActionPermission('edit') && \TYPO3\CMS\Core\Utility\GeneralUtility::inList($GLOBALS['TYPO3_CONF_VARS']['SYS']['textfile_ext'], $fileOrFolderObject->getExtension())) {
			$editOnClick = 'top.content.list_frame.location.href=top.TS.PATH_typo3+\'file_edit.php?target=' . rawurlencode($fullIdentifier) . '&returnUrl=\'+top.rawurlencode(top.content.list_frame.document.location.pathname+top.content.list_frame.document.location.search);return false;';
			$cells['edit'] = '<a href="#" onclick="' . $editOnClick . '" title="' . $GLOBALS['LANG']->sL('LLL:EXT:lang/locallang_core.xlf:cm.edit') . '">' . \TYPO3\CMS\Backend\Utility\IconUtility::getSpriteIcon('actions-page-open') . '</a>';
		} else {
			$cells['edit'] = \TYPO3\CMS\Backend\Utility\IconUtility::getSpriteIcon('empty-empty');
		}
		// rename the file
		if ($fileOrFolderObject->checkActionPermission('rename')) {
			$renameOnClick = 'top.content.list_frame.location.href = top.TS.PATH_typo3+\'file_rename.php?target=' . rawurlencode($fullIdentifier) . '&returnUrl=\'+top.rawurlencode(top.content.list_frame.document.location.pathname+top.content.list_frame.document.location.search);return false;';
			$cells['rename'] = '<a href="#" onclick="' . $renameOnClick . '"  title="' . $GLOBALS['LANG']->sL('LLL:EXT:lang/locallang_core.xlf:cm.rename') . '">' . \TYPO3\CMS\Backend\Utility\IconUtility::getSpriteIcon('actions-edit-rename') . '</a>';
		} else {
			$cells['rename'] = \TYPO3\CMS\Backend\Utility\IconUtility::getSpriteIcon('empty-empty');
		}
		if (is_a($fileOrFolderObject, 'TYPO3\\CMS\\Core\\Resource\\Folder')) {
			$infoOnClick = 'top.launchView( \'_FOLDER\', \'' . $fullIdentifier . '\');return false;';
		} elseif (is_a($fileOrFolderObject, 'TYPO3\\CMS\\Core\\Resource\\File')) {
			$infoOnClick = 'top.launchView( \'_FILE\', \'' . $fullIdentifier . '\');return false;';
		}
		$cells['info'] = '<a href="#" onclick="' . $infoOnClick . '" title="' . $GLOBALS['LANG']->sL('LLL:EXT:lang/locallang_core.xlf:cm.info') . '">' . \TYPO3\CMS\Backend\Utility\IconUtility::getSpriteIcon('status-dialog-information') . '</a>';

		// delete the file
		if ($fileOrFolderObject->checkActionPermission('remove')) {
			$identifier = $fileOrFolderObject->getIdentifier();
			if ($fileOrFolderObject instanceof TYPO3\CMS\Core\Resource\Folder) {
				$referenceCountText = \TYPO3\CMS\Backend\Utility\BackendUtility::referenceCount('_FILE', $identifier, ' (There are %s reference(s) to this folder!)');
			} else {
				$referenceCountText = \TYPO3\CMS\Backend\Utility\BackendUtility::referenceCount('sys_file', $identifier, ' (There are %s reference(s) to this file!)');
			}

			if ($GLOBALS['BE_USER']->jsConfirmation(4)) {
				$confirmationCheck = 'confirm(' . $GLOBALS['LANG']->JScharCode(sprintf($GLOBALS['LANG']->sL('LLL:EXT:lang/locallang_core.xlf:mess.delete'), basename($identifier)) . $referenceCountText) . ')';
			} else {
				$confirmationCheck = '1 == 1';
			}

			$removeOnClick = 'if (' . $confirmationCheck . ') { top.content.list_frame.location.href=top.TS.PATH_typo3+\'tce_file.php?file[delete][0][data]=' . rawurlencode($fileOrFolderObject->getCombinedIdentifier()) . '&vC=' . $GLOBALS['BE_USER']->veriCode() . '&redirect=\'+top.rawurlencode(top.content.list_frame.document.location.pathname+top.content.list_frame.document.location.search);};';

			$cells['delete'] = '<a href="#" onclick="' . htmlspecialchars($removeOnClick) . '"  title="' . $GLOBALS['LANG']->sL('LLL:EXT:lang/locallang_core.xlf:cm.delete') . '">' . \TYPO3\CMS\Backend\Utility\IconUtility::getSpriteIcon('actions-edit-delete') . '</a>';
		} else {
			$cells['delete'] = \TYPO3\CMS\Backend\Utility\IconUtility::getSpriteIcon('empty-empty');
		}

		// Hook for manipulating edit icons.
		if (is_array($GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS']['fileList']['editIconsHook'])) {
			foreach ($GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS']['fileList']['editIconsHook'] as $classData) {
				$hookObject = \TYPO3\CMS\Core\Utility\GeneralUtility::getUserObj($classData);
				if (!$hookObject instanceof \TYPO3\CMS\Filelist\FileListEditIconHookInterface) {
					throw new \UnexpectedValueException('$hookObject must implement interface fileList_editIconHook', 1235225797);
				}
				$hookObject->manipulateEditIcons($cells, $this);
			}
		}
		// Compile items into a DIV-element:
		return '							<!-- EDIT CONTROLS: -->
											<div class="typo3-editCtrl">
												' . implode('
												', $cells) . '
											</div>';
	}

	/**
	 * Make reference count
	 *
	 * @param \TYPO3\CMS\Core\Resource\File|\TYPO3\CMS\Core\Resource\Folder $fileOrFolderObject Array with information about the file/directory for which to make the clipboard panel for the listing.
	 * @return string HTML
	 * @todo Define visibility
	 */
	public function makeRef($fileOrFolderObject) {
		if ($fileOrFolderObject instanceof \TYPO3\CMS\Core\Resource\FolderInterface) {
			return '-';
		}
		// Look up the path:
		$rows = $GLOBALS['TYPO3_DB']->exec_SELECTgetRows('*', 'sys_refindex', 'ref_table=\'sys_file\' AND ref_uid = ' . (integer)$fileOrFolderObject->getUid() . ' AND deleted=0');
		return $this->generateReferenceToolTip($rows, '\'_FILE\', ' . \TYPO3\CMS\Core\Utility\GeneralUtility::quoteJSvalue($fileOrFolderObject->getCombinedIdentifier()));
	}

}

?>