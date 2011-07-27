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
 * If the file already exists, it will be overridden.
 * This follow an example in HTTP_Request2 manual.
 * @see http://pear.php.net/manual/en/package.http.http-request2.observers.php
 */
class t3lib_http_observer_Download implements SplObserver {
	protected $fp, $target, $dir, $filename;

	/**
	 * Constructor
	 *
	 * @throws Exception if directory is not found
	 * @param string $dir The absolute path to the directory in which the file is saved.
	 * @param string $filename The filename - if not set, it is determined automatically.
	 */
	public function __construct($dir, $filename = NULL) {
		$this->setDir($dir);
		$this->setFilename($filename);
	}

	/**
	 * The update method is called for each chunk
	 *
	 * @throws Exception if file can not be opened
	 * @param SplSubject $subject
	 * @return void
	 */
	public function update(SplSubject $subject)
	{
		$event = $subject->getLastEvent();

		switch ($event['name']) {
			case 'receivedHeaders':
				if (empty($this->filename)) {
					$disposition = $event['data']->getHeader('content-disposition');
					if ($disposition && 0 == strpos($disposition, 'attachment')
									&& preg_match('/filename="([^"]+)"/', $disposition, $matches)
					) {
						$this->filename = basename($matches[1]);
					} else {
						$this->filename = basename($subject->getUrl()->getPath());
					}
				}

				$this->target = $this->dir . DIRECTORY_SEPARATOR . $this->filename;

				if (!($this->fp = @fopen($this->target, 'wb'))) {
					throw new Exception("Cannot open target file '{$this->target}'");
				}

				break;

			case 'receivedBodyPart':
				// Fall through
			case 'receivedEncodedBodyPart':
				fwrite($this->fp, $event['data']);
				break;

			case 'receivedBody':
				fclose($this->fp);
				$this->fp = NULL;
				t3lib_div::fixPermissions($this->target);
		}
	}

	/**
	 * Sets the directory - Checks whether the directory is available
	 *
	 * @throws Exception if directory is not found
	 * @param string $dir The absolute path to the directory in which the file is saved.
	 * @return void
	 */
	public function setDir($dir) {
		if (!is_dir($dir)) {
			throw new Exception("'{$dir}' is not a directory", 1312223779);
		}
		substr($dir, -1) === DIRECTORY_SEPARATOR ? $this->dir = substr($dir, 0, -1) : $this->dir = $dir;
		$this->dir = $dir;
	}

	/**
	 * Sets the filename
	 *
	 * @param string $filename The filename - if not set, it is determined automatically.
	 * @return void
	 */
	public function setFilename($filename = NULL) {
		$this->filename = $filename;
	}
}
?>