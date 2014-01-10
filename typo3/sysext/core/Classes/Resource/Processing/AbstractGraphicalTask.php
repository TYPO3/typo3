<?php
namespace TYPO3\CMS\Core\Resource\Processing;

/***************************************************************
 *  Copyright notice
 *
 *  (c) 2012-2013 Andreas Wolf <andreas.wolf@typo3.org>
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
 *  A copy is found in the text file GPL.txt and important notices to the license
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

use \TYPO3\CMS\Core\Resource, \TYPO3\CMS\Core\Utility;

/**
 * Abstract base implementation of a task.
 *
 * If you extend this class, make sure that you redefine the member variables $type and $name
 * or set them in the constructor. Otherwise your task won't be recognized by the system and several
 * things will fail.
 *
 */
abstract class AbstractGraphicalTask extends AbstractTask {

	/**
	 * @var string
	 */
	protected $targetFileExtension;

	/**
	 * Sets parameters needed in the checksum. Can be overridden to add additional parameters to the checksum.
	 * This should include all parameters that could possibly vary between different task instances, e.g. the
	 * TYPO3 image configuration in TYPO3_CONF_VARS[GFX] for graphic processing tasks.
	 *
	 * @return array
	 */
	protected function getChecksumData() {
		return array_merge(
			parent::getChecksumData(),
			array(serialize($GLOBALS['TYPO3_CONF_VARS']['GFX']))
		);
	}

	/**
	 * Returns the name the processed file should have
	 * in the filesystem.
	 *
	 * @return string
	 */
	public function getTargetFilename() {
		return $this->getSourceFile()->getNameWithoutExtension()
			. '_' . $this->getConfigurationChecksum()
			. '.' . $this->getTargetFileExtension();
	}

	/**
	 * Determines the file extension the processed file
	 * should have in the filesystem.
	 *
	 * @return string
	 */
	public function getTargetFileExtension() {
		if (!isset($this->targetFileExtension)) {
			$this->targetFileExtension = $this->determineTargetFileExtension();
		}

		return $this->targetFileExtension;
	}

	/**
	 * Gets the file extension the processed file should
	 * have in the filesystem by either using the configuration
	 * setting, or the extension of the original file.
	 *
	 * @return string
	 */
	protected function determineTargetFileExtension() {
		if (!empty($this->configuration['fileExtension'])) {
			$targetFileExtension = $this->configuration['fileExtension'];
		} else {
			// explanation for "thumbnails_png"
			// Bit0: If set, thumbnails from non-jpegs will be 'png', otherwise 'gif' (0=gif/1=png).
			// Bit1: Even JPG's will be converted to png or gif (2=gif/3=png)

			$targetFileExtensionConfiguration = $GLOBALS['TYPO3_CONF_VARS']['GFX']['thumbnails_png'];
			if ($this->getSourceFile()->getExtension() === 'jpg' || $this->getSourceFile()->getExtension() === 'jpeg') {
				if ($targetFileExtensionConfiguration == 2) {
					$targetFileExtension = 'gif';
				} elseif ($targetFileExtensionConfiguration == 3) {
					$targetFileExtension = 'png';
				} else {
					$targetFileExtension = 'jpg';
				}
			} else {
				// check if a png or a gif should be created
				if ($targetFileExtensionConfiguration == 1 || $this->getSourceFile()->getExtension() === 'png') {
					$targetFileExtension = 'png';
				} else {
					// thumbnails_png is "0"
					$targetFileExtension = 'gif';
				}
			}
		}

		return $targetFileExtension;
	}

}
