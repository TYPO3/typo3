<?php
/***************************************************************
*  Copyright notice
*
*  (c) 2006 Thomas Hempel (thomas@work.de)
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
 * $Id$
 *
 * @author	Thomas Hempel	<thomas@work.de>
 */
/**
 * [CLASS/FUNCTION INDEX of SCRIPT]
 *
 */
class tx_install_module_directories extends tx_install_module_base	{
	
	/**
	 * Checks all needed directories if they are available. Writes the status of the checks into
	 * viewObject->lastMessage.
	 *
	 * @return boolean
	 */
	public function checkDirs()	{
		$result = true;
		$uniqueName = md5(uniqid(microtime()));

			// The requirement level (the integer value, ie. the second value of the value array) has the following meanings:
			// -1 = not required, but if it exists may be writable or not
			//  0 = not required, but if it exists the dir should be writable
			//  1 = required, don't have to be writable
			//  2 = required, has to be writable

		$checkWrite = array(
			'typo3temp/' => array($this->get_LL('im_directories_typo3temp'), 2),
			'typo3temp/pics/' => array($this->get_LL('im_directories_typo3temp_sub'), 2),
			'typo3temp/temp/' => array($this->get_LL('im_directories_typo3temp_sub'), 2),
			'typo3temp/llxml/' => array($this->get_LL('im_directories_typo3temp_sub'), 2),
			'typo3temp/cs/' => array($this->get_LL('im_directories_typo3temp_sub'), 2),
			'typo3temp/GB/' => array($this->get_LL('im_directories_typo3temp_sub'), 2),
			'typo3conf/' => array($this->get_LL('im_directories_typo3conf'), 2),
			'typo3conf/ext/' => array($this->get_LL('im_directories_typo3conf_ext'), 0),
			'typo3conf/l10n/' => array($this->get_LL('im_directories_typo3conf_l10n'), 0),
			TYPO3_mainDir.'ext/' => array($this->get_LL('im_directories_typo3_ext'), -1),
			'uploads/' => array($this->get_LL('im_directories_uploads'), 2),
			'uploads/pics/' => array($this->get_LL('im_directories_uploads_pics'), 0),
			'uploads/media/' => array($this->get_LL('im_directories_uploads_media'), 0),
			'uploads/tf/' => array($this->get_LL('im_directories_uploads_tf'), 0),
			'fileadmin/' => array($this->get_LL('im_directories_fileadmin'), -1),
			'fileadmin/_temp_/' => array($this->get_LL('im_directories_fileadmin_temp'), 0),
		);
		
		$listItems = array();

			// Loop through the paths which need to be checked
		foreach ($checkWrite as $relpath => $descr)	{
			$rowLabel = $relpath;
			$rowText = '';
			$rowResult = true;
			$rowHelp = array('button' => '', 'container' => '');

			$this->pObj->getViewObject()->clearErrors();
			
				// If the directory is missing, try to create it
			if (!@is_dir(PATH_site.$relpath))	{
				t3lib_div::mkdir(PATH_site.$relpath);
			}

			if (!@is_dir(PATH_site.$relpath))	{
				if ($descr[1] > 0)	{	// required...
					$rowResult = $result = false;
					$errorMsg = sprintf($this->get_LL('im_directories_directoryNotExisting'), $relpath).$this->get_LL('im_directories_fullPath');
					$this->addError($errorMsg, FATAL);
				} else {
					if ($descr[1] == 0)	{
						$this->addError($this->get_LL('im_directories_dirNotNeededToExist1'));
					} else {
						$this->addError($this->get_LL('im_directories_dirNotNeededToExist2'));
					}
				}
			} else {
				$file = PATH_site.$relpath.$uniqueName;
				@touch($file);
				if (@is_file($file))	{
					unlink($file);
					$rowText .= sprintf($this->get_LL('im_directories_writeable'), $relpath);
				} else {
					$severity = ($descr[1]==2 || $descr[1]==0) ? FATAL : WARNING;
					if ($descr[1] == 0 || $descr[1] == 2) {
						$this->addError(sprintf($this->get_LL('im_directories_dirMustBeWriteable'), $relpath), $severity);
					} elseif ($descr[1] == -1 || $descr[1] == 1) {
						$this->addError(sprintf($this->get_LL('im_directories_dirCanBeWriteable'), $relpath), $severity);
					}
					$rowResult = $result = false;
				}
			}
			
			
			if (!$rowResult)	{
				$rowText .= $this->pObj->getViewObject()->renderErrors();
			}
			
			if ($descr[0])	{
				$rowHelp = $this->pObj->getViewObject()->renderHelp($descr[0], str_replace('/', '', $rowLabel));
			}
			
			$renderSettings = array (
				'type' => 'message',
				'status' => (($rowResult) ? 'ok' : 'warning'),
				'value' => array (
					'label' => array('h3', $rowLabel.$rowHelp['button']),
					'message' => $rowHelp['container'].$rowText
				)
			);
			
			$listItems[] = $renderSettings;
		}
		
		
		$this->pObj->getViewObject()->addMessage($this->pObj->getViewObject()->render(array('type' => 'list', 'value' => $listItems)));
		return $result;
		// return array('type' => 'checklist', 'value' => $result);
	}
}

if (defined('TYPO3_MODE') && $TYPO3_CONF_VARS[TYPO3_MODE]['XCLASS']['ext/install/modules/directories/class.tx_install_directories.php'])	{
	include_once($TYPO3_CONF_VARS[TYPO3_MODE]['XCLASS']['ext/install/modules/directories/class.tx_install_directories.php']);
}
?>
