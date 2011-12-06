<?php
/***************************************************************
*  Copyright notice
*
*  (c) 2011 Philipp Gampe (dev.typo3@philippgampe.info)
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
 * Observer to automatically save a http request chunk by chunk to a file.
 * If the file already exists, it will be overwritten.
 * This follows an example in HTTP_Request2 manual.
 *
 * @see http://pear.php.net/manual/en/package.http.http-request2.observers.php
 * @author Philipp Gammpe
 */
class t3lib_http_observer_Download implements SplObserver {

	/**
	 * @var resource A file pointer resource
	 */
	protected $filePointer;

	/**
	 * @var string The full filename including the leading directory
	 */
	protected $targetFilePath;

	/**
	 * @var string The name of the target directory
	 */
	protected $targetDirectory;

	/**
	 * @var string The name of the target file
	 */
	protected $targetFilename;

	/**
	 * Constructor
	 *
	 * @throws InvalidArgumentException if directory is not found
	 * @param string $directory The absolute path to the directory in which the file is saved.
	 * A trailing '/' is removed automatically.
	 * @param string $filename The filename - if not set, it is determined automatically.
	 */
	public function __construct($directory, $filename = NULL) {
		$this->setDirectory($directory);
		$this->setFilename($filename);
	}

	/**
	 * The update method is called for each chunk and saves it to disk
	 *
	 * @throws Exception if file can not be opened
	 * @param SplSubject|HTTP_Request2 $subject
	 * @return void
	 */
	public function update(SplSubject $subject) {
		$event = $subject->getLastEvent();

		switch ($event['name']) {
			case 'receivedHeaders':
				if (empty($this->targetFilename)) {
					$this->determineFilename($subject, $event['data']);
				}
				$this->openFile();
				break;
			case 'receivedBodyPart':
				// Fall through
			case 'receivedEncodedBodyPart':
				fwrite($this->filePointer, $event['data']);
				break;
			case 'receivedBody':
				$this->closeFile();
				break;
			default:
				// do nothing
		}
	}

	/**
	 * Sets the directory and checks whether the directory is available
	 *
	 * @throws InvalidArgumentException if directory is not found
	 * @param string $directory The absolute path to the directory in which the file is saved.
	 * @return void
	 */
	public function setDirectory($directory) {
		if (!is_dir($directory)) {
			throw new InvalidArgumentException($directory . ' is not a directory', 1312223779);
		}
		if (substr($directory, -1) === DIRECTORY_SEPARATOR) {
			$directory = substr($directory, 0, -1);
		}
		$this->targetDirectory = $directory;
	}

	/**
	 * Sets the filename
	 *
	 * @param string $filename The filename - if not set, it is determined automatically.
	 * @return void
	 */
	public function setFilename($filename = NULL) {
		$this->targetFilename = $filename;
	}

	/**
	 * Determine the filename from either the 'content-disposition' header
	 * or from the basename of the current request.
	 *
	 * @param HTTP_Request2 $subject
	 * @param HTTP_Request2_Response $response
	 * @return void
	 */
	protected function determineFilename(HTTP_Request2 $subject, HTTP_Request2_Response $response) {
		$disposition = $response->getHeader('content-disposition');
		if ($disposition !== NULL
				&& 0 === strpos($disposition, 'attachment')
				&& 1 === preg_match('/filename="([^"]+)"/', $disposition, $matches)) {
			$this->targetFilename = basename($matches[1]);
		} else {
			$this->targetFilename = basename($subject->getUrl()->getPath());
		}
	}

	/**
	 * Determine the absolute path to the file by combining the directory and filename.
	 * Afterwards try to open the file for writing.
	 *
	 * $this->filename must be set before calling this function.
	 *
	 * @throws UnexpectedValueException if $this->filename is not set
	 * @throws Exception if file can not be opened
	 * @return void
	 */
	protected function openFile() {
		if (empty($this->targetFilename)) {
			throw new UnexpectedValueException('The file name must not be empty', 1321113658);
		}
		$this->targetFilePath = $this->targetDirectory . DIRECTORY_SEPARATOR . $this->targetFilename;
		$this->filePointer = @fopen($this->targetFilePath, 'wb');

		if ($this->filePointer === FALSE) {
			throw new Exception('Cannot open target file ' . $this->targetFilePath, 1320833203);
		}
	}

	/**
	 * Close the file handler and fix permissions.
	 *
	 * @return void
	 */
	protected function closeFile() {
		fclose($this->filePointer);
		$this->filePointer = NULL;
		t3lib_div::fixPermissions($this->targetFilePath);
	}
}

if (defined('TYPO3_MODE') && isset($GLOBALS['TYPO3_CONF_VARS'][TYPO3_MODE]['XCLASS']['t3lib/http/observer/class.t3lib_http_observer_download.php'])) {
	include_once($GLOBALS['TYPO3_CONF_VARS'][TYPO3_MODE]['XCLASS']['t3lib/http/observer/class.t3lib_http_observer_download.php']);
}

?>