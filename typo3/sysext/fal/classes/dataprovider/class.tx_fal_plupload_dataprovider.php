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
 * File Abtraction Layer dataprovider for pluploader
 *
 * @author		FAL development team <fal@wmdb.de>
 * @package		TYPO3
 * @subpackage	tx_fal
 * @version		$Id:  $
 */



require_once(PATH_typo3 . 'classes/class.typo3_tcefile.php');

class tx_fal_plupload_dataprovider extends TYPO3_tcefile {

	/**
	 * Performing the file admin action:
	 * Initializes the objects, setting permissions, sending data to object.
	 *
	 * @return	void
	 */
	public function main() {
			// Initializing:
		$this->fileProcessor = t3lib_div::makeInstance('tx_fal_extfilefunc');
		$this->fileProcessor->init($GLOBALS['FILEMOUNTS'], $GLOBALS['TYPO3_CONF_VARS']['BE']['fileExtensions']);
		$this->fileProcessor->init_actionPerms($GLOBALS['BE_USER']->getFileoperationPermissions());
		$this->fileProcessor->dontCheckForUnique = ($this->overwriteExistingFiles ? 1 : 0);

			// Checking referer / executing:
		$refInfo = parse_url(t3lib_div::getIndpEnv('HTTP_REFERER'));
		$httpHost = t3lib_div::getIndpEnv('TYPO3_HOST_ONLY');
		if ($httpHost != $refInfo['host']
			&& $this->vC != $GLOBALS['BE_USER']->veriCode()
			&& !$GLOBALS['TYPO3_CONF_VARS']['SYS']['doNotCheckReferer']
			&& $GLOBALS['CLIENT']['BROWSER'] != 'flash') {
			$this->fileProcessor->writeLog(0, 2, 1, 'Referer host "%s" and server host "%s" did not match!', array($refInfo['host'], $httpHost));
		} else {
			$this->fileProcessor->start($this->file);
			$this->fileData = $this->fileProcessor->processData();
		}
	}

	/**
	 * Handles the actual process from within the ajaxExec function
	 * therefore, it does exactly the same as the real typo3/tce_file.php
	 * but without calling the "finish" method, thus makes it simpler to deal with the
	 * actual return value
	 *
	 *
	 * @param string $params 	always empty.
	 * @param string $ajaxObj	The Ajax object used to return content and set content types
	 * @return void
	 */
	public function processAjaxRequest(array $params, TYPO3AJAX $ajaxObj) {
		$this->init();
		$this->processBinaryStream();
		$this->main();

		$response = array();
		$error = null;
		foreach ($this->fileData['upload'] as $uploaded) {
			// $uploaded would contain the absolute file path, if it worked
			if ($uploaded === null) {
				$error = $this->fileProcessor->lastError;
				break;
			}
		}
		if (strlen($error)) {
			$response['message'] = $error;
			$response['success'] = FALSE;
		} else {
			$response['result'] = $this->fileData;
			if ($this->redirect) {
				$response['redirect'] = $this->redirect;
			}
			$response['success'] = TRUE;
		}
		$ajaxObj->setContentFormat('plain');
		$ajaxObj->setContent(array(json_encode($response)));
	}

	/**
	 * File upload can be as multipart or as binary stream.
	 *
	 * This method first checks, if upload is not a multipart upload and then
	 * converts the received data into the expected structures.
	 *
	 * @return void
	 */
	protected function processBinaryStream() {

			// Look for the content type header
		if (isset($_SERVER['HTTP_CONTENT_TYPE'])) {
			$contentType = $_SERVER['HTTP_CONTENT_TYPE'];
		}

		if (isset($_SERVER['CONTENT_TYPE'])) {
			$contentType = $_SERVER['CONTENT_TYPE'];
		}

		if (strpos($contentType, "multipart") === FALSE) {
			$tempFile = tempnam('/tmp', 'upload');

				// ppen temp file
			$out = fopen($tempFile, 'wb');
			if ($out) {
					// read binary input stream and append it to temp file
				$in = fopen("php://input", "rb");

				if ($in) {
					while ($buff = fread($in, 4096))
						fwrite($out, $buff);
				} else
					die('{"jsonrpc" : "2.0", "error" : {"code": 101, "message": "Failed to open input stream."}, "id" : "id"}');
				fclose($out);
				$_FILES['upload_1'] = array(
					'name' => $_GET['file']['upload']['1']['name'],
					'tmp_name' => $tempFile,
					'size' => filesize($tempFile),
					'relativeTarget' => $_GET['file']['upload']['1']['relativeTarget'],
				);
			} else
				die('{"jsonrpc" : "2.0", "error" : {"code": 102, "message": "Failed to open output stream."}, "id" : "id"}');
		}
	}

}

if (defined('TYPO3_MODE') && isset($GLOBALS['TYPO3_CONF_VARS'][TYPO3_MODE]['XCLASS']['ext/fal/classes/dataprovider/class.tx_fal_plupload_dataprovider.php'])) {
	include_once($GLOBALS['TYPO3_CONF_VARS'][TYPO3_MODE]['XCLASS']['ext/fal/classes/dataprovider/class.tx_fal_plupload_dataprovider.php']);
}
?>