<?php
/***************************************************************
*  Copyright notice
*
*  (c) 2010 FAL development team <fal@wmdb.de>
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
 * File Abtraction Layer extension for class.ext_filefunc
 *
 * @author		FAL development team <fal@wmdb.de>
 * @package		TYPO3
 * @subpackage	tx_fal
 * @version		$Id: class.tx_fal_extfilefunc.php 9520 2010-11-23 22:58:16Z rupi $
 */
class tx_fal_extfilefunc extends t3lib_extFileFunctions {
	/**
	 * Upload of files (action=1)
	 *
	 * @param	array		$cmds['data'] is the ID-number (points to the global var that holds the filename-ref  ($_FILES['upload_'.$id]['name']). $cmds['target'] is the target directory, $cmds['charset'] is the the character set of the file name (utf-8 is needed for JS-interaction)
	 * @return	string		Returns the new filename upon success
	 */
	function func_upload($cmds)	{
		if (!$this->isInit) return FALSE;
		$id = $cmds['data'];
		if ($_FILES['upload_'.$id]['name'])	{
			$theFile = $_FILES['upload_'.$id]['tmp_name'];				// filename of the uploaded file
			$theFileSize = $_FILES['upload_'.$id]['size'];				// filesize of the uploaded file
			$theName = $this->cleanFileName(stripslashes($_FILES['upload_'.$id]['name']), (isset($cmds['charset']) ? $cmds['charset'] : ''));	// The original filename
			if (is_file($theFile) && $theName)	{	// Check the file
				if ($this->actionPerms['uploadFile'])	{
					if ($theFileSize<($this->maxUploadFileSize*1024))	{
						$fI = t3lib_div::split_fileref($theName);
						$theTarget = $this->is_directory($cmds['target']);	// Check the target dir
						if ($theTarget && $this->checkPathAgainstMounts($theTarget.'/'))	{
							if ($this->checkIfAllowed($fI['fileext'], $theTarget, $fI['file'])) {
								$theNewFile = $this->getUniqueName($theName, $theTarget, $this->dontCheckForUnique);
								if ($theNewFile)	{
									t3lib_div::upload_copy_move($theFile,$theNewFile);
									clearstatcache();
									if (@is_file($theNewFile))	{
										$this->internalUploadMap[$id] = $theNewFile;
										$this->writelog(1,0,1,'Uploading file "%s" to "%s"',Array($theName,$theNewFile, $id));
										return $theNewFile;
									} else $this->writelog(1,1,100,'Uploaded file could not be moved! Write-permission problem in "%s"?',Array($theTarget.'/'));
								} else $this->writelog(1,1,101,'No unique filename available in "%s"!',Array($theTarget.'/'));
							} else $this->writelog(1,1,102,'Extension of file name "%s" is not allowed in "%s"!',Array($fI['file'], $theTarget.'/'));
						} else $this->writelog(1,1,103,'Destination path "%s" was not within your mountpoints!',Array($theTarget.'/'));
					} else $this->writelog(1,1,104,'The uploaded file exceeds the size-limit of %s bytes',Array($this->maxUploadFileSize*1024));
				} else $this->writelog(1,1,105,'You are not allowed to upload files!','');
			} else $this->writelog(1,2,106,'The upload has failed, no uploaded file found!','');
		} else $this->writelog(1,2,108,'No file was uploaded!','');
	}
}

if (defined('TYPO3_MODE') && isset($GLOBALS['TYPO3_CONF_VARS'][TYPO3_MODE]['XCLASS']['ext/fal/classes/dataprovider/class.tx_fal_extfilefunc.php'])) {
	include_once($GLOBALS['TYPO3_CONF_VARS'][TYPO3_MODE]['XCLASS']['ext/fal/classes/dataprovider/class.tx_fal_extfilefunc.php']);
}
?>