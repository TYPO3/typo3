<?php
namespace TYPO3\CMS\Core\Http\Observer;

/***************************************************************
 *  Copyright notice
 *
 *  (c) 2011-2013 Philipp Gampe <philipp.gampe@typo3.org>
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
 * @author Philipp Gampe
 */
class Download implements \SplObserver {

	/**
	 * @var resource A file pointer resource
	 */
	protected $filePointer = FALSE;

	/**
	 * @var string The full filename including the leading directory
	 */
	protected $targetFilePath = '';

	/**
	 * @var string The name of the target directory
	 */
	protected $targetDirectory = '';

	/**
	 * @var string The name of the target file
	 */
	protected $targetFilename = '';

	/**
	 * Constructor
	 *
	 * @throws \InvalidArgumentException if directory is not found or is not within the PATH_site
	 * @param string $directory The absolute path to the directory in which the file is saved.
	 * @param string $filename The filename - if not set, it is determined automatically.
	 */
	public function __construct($directory, $filename = '') {
		$this->setDirectory($directory);
		$this->setFilename($filename);
	}

	/**
	 * Saves current chunk to disk each time a body part is received.
	 * If the filename is empty, tries to determine it from received headers
	 *
	 * @throws \TYPO3\CMS\Core\Exception if file can not be opened
	 * @throws \UnexpectedValueException if the file name is empty and can not be determined from headers
	 * @param \SplSubject|\HTTP_Request2 $request
	 * @return void
	 */
	public function update(\SplSubject $request) {
		$event = $request->getLastEvent();
		switch ($event['name']) {
		case 'receivedHeaders':
			if ($this->targetFilename === '') {
				$this->determineFilename($request, $event['data']);
			}
			$this->openFile();
			break;
		case 'receivedBodyPart':

		case 'receivedEncodedBodyPart':
			fwrite($this->filePointer, $event['data']);
			break;
		case 'receivedBody':
			$this->closeFile();
			break;
		default:

		}
	}

	/**
	 * Sets the directory and checks whether the directory is available.
	 *
	 * @throws \InvalidArgumentException if directory is not found or is not within the PATH_site
	 * @param string $directory The absolute path to the directory in which the file is saved.
	 * @return void
	 */
	public function setDirectory($directory) {
		if (!is_dir($directory)) {
			throw new \InvalidArgumentException($directory . ' is not a directory', 1312223779);
		}
		if (!\TYPO3\CMS\Core\Utility\GeneralUtility::isAllowedAbsPath($directory)) {
			throw new \InvalidArgumentException($directory . ' is not within the PATH_site' . ' OR within the lockRootPath', 1328734617);
		}
		$this->targetDirectory = ($directory = rtrim($directory, DIRECTORY_SEPARATOR));
	}

	/**
	 * Sets the filename.
	 *
	 * If the file already exists, it will be overridden
	 *
	 * @param string $filename The filename
	 * @return void
	 */
	public function setFilename($filename = '') {
		$this->targetFilename = basename($filename);
	}

	/**
	 * Determines the filename from either the 'content-disposition' header
	 * or from the basename of the current request.
	 *
	 * @param \HTTP_Request2 $request
	 * @param \HTTP_Request2_Response $response
	 * @return void
	 */
	protected function determineFilename(\HTTP_Request2 $request, \HTTP_Request2_Response $response) {
		$matches = array();
		$disposition = $response->getHeader('content-disposition');
		if ($disposition !== NULL && 0 === strpos($disposition, 'attachment') && 1 === preg_match('/filename="([^"]+)"/', $disposition, $matches)) {
			$filename = basename($matches[1]);
		} else {
			$filename = basename($request->getUrl()->getPath());
		}
		$this->setFilename($filename);
	}

	/**
	 * Determines the absolute path to the file by combining the directory and filename.
	 * Afterwards tries to open the file for writing.
	 *
	 * $this->filename must be set before calling this function.
	 *
	 * @throws \UnexpectedValueException if $this->filename is not set
	 * @throws \TYPO3\CMS\Core\Exception if file can not be opened
	 * @return void
	 */
	protected function openFile() {
		if ($this->targetFilename === '') {
			throw new \UnexpectedValueException('The file name must not be empty', 1321113658);
		}
		$this->targetFilePath = $this->targetDirectory . DIRECTORY_SEPARATOR . $this->targetFilename;
		$this->filePointer = @fopen($this->targetFilePath, 'wb');
		if ($this->filePointer === FALSE) {
			throw new \TYPO3\CMS\Core\Exception('Cannot open target file ' . $this->targetFilePath, 1320833203);
		}
	}

	/**
	 * Closes the file handler and fixes permissions.
	 *
	 * @return void
	 */
	protected function closeFile() {
		fclose($this->filePointer);
		$this->filePointer = FALSE;
		\TYPO3\CMS\Core\Utility\GeneralUtility::fixPermissions($this->targetFilePath);
	}

}

?>