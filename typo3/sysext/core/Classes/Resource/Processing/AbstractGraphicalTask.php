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
		} elseif ($this->getSourceFile()->getExtension() === 'jpg') {
			$targetFileExtension = 'jpg';
		} else {
			$targetFileExtension = 'png';
		}

		return $targetFileExtension;
	}

}

?>