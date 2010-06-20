<?php
/***************************************************************
*  Copyright notice
*
*  (c) 2010  Steffen Ritter (info@rs-websystems.de)
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
 * Contains the update class checking against configured compressionlevel. Used by the update wizard in the install tool.
 *
 * @author	Steffen Ritter <info@rs-websystems.de>
 */
class tx_coreupdates_compressionlevel {
	public $versionNumber;	// version number coming from t3lib_div::int_from_ver()
	public $pObj;	// parent object (tx_install)
	public $userInput;	// user input


	/**
	 * Checks if there there is an compression level configured which may break the BE.
	 *
	 * @param	string		&$description: The description for the update
	 * @return	boolean		whether an update is needed (true) or not (false)
	 */
	public function checkForUpdate(&$description) {
		$description = '<strong>TYPO3_CONF_VARS[BE][compressionLevel] is enabled.</strong><br />
		In TYPO3 4.4, compressionLevel was expanded to include automatic gzip compression of JavaScript and CSS stylessheet files. 
		<strong>To prevent the TYPO3 backend from being unusable, you must include the relevant lines from misc/advanced.htaccess.</strong>';
		if (intval($GLOBALS['TYPO3_CONF_VARS']['BE']['compressionLevel']) > 0) {
			return TRUE;
		}
		return FALSE;
	}

	/**
	 * second step: get user info
	 *
	 * @param	string		input prefix, all names of form fields have to start with this. Append custom name in [ ... ]
	 * @return	string		HTML output
	 */
	public function getUserInput($inputPrefix) {
		$content = '<strong>This configuration cannot be fixed automatically and requires a manual update.</strong> Please include the following lines from misc/advanced.htaccess on top of your .htacess file.
					<br /><br />
					<pre>
&lt;FilesMatch "\.js\.gzip$"&gt;
 AddType "text/javascript" .gzip
&lt;/FilesMatch&gt;
&lt;FilesMatch "\.css\.gzip$"&gt;
  AddType "text/css" .gzip
&lt;/FilesMatch&gt;
AddEncoding gzip .gzip
					</pre>';

		return $content;
	}

	/**
	 * performs the action of the UpdateManager
	 *
	 * @param	array		&$dbQueries: queries done in this update
	 * @param	mixed		&$customMessages: custom messages
	 * @return	bool		whether everything went smoothly or not
	 */
	public function performUpdate(array &$dbQueries, &$customMessages) {
		$customMessages = 'Cannot automatically fix this problem! Please check manually.';
		return FALSE;
	}
}
?>