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

use \TYPO3\CMS\Core\Resource;

/**
 * A task for generating an image preview.
 */
class ImagePreviewTask extends AbstractGraphicalTask {

	/**
	 * @var string
	 */
	protected $type = 'Image';

	/**
	 * @var string
	 */
	protected $name = 'Preview';

	/**
	 * Returns the target filename for this task.
	 *
	 * @return string
	 */
	public function getTargetFileName() {
		return 'preview_' . parent::getTargetFilename();
	}

	/**
	 * Checks if the given configuration is sensible for this task, i.e. if all required parameters
	 * are given, within the boundaries and don't conflict with each other.
	 *
	 * @param array $configuration
	 * @return boolean
	 */
	protected function isValidConfiguration(array $configuration) {
		/**
		 * Checks to perform:
		 * - width and/or height given, integer values?
		 */
	}

	/**
	 * Returns TRUE if the file has to be processed at all, such as e.g. the original file does.
	 *
	 * Note: This does not indicate if the concrete ProcessedFile attached to this task has to be (re)processed.
	 * This check is done in ProcessedFile::isOutdated(). TODO isOutdated()/needsReprocessing()?
	 *
	 * @return boolean
	 */
	public function fileNeedsProcessing() {
		// TODO: Implement fileNeedsProcessing() method.

		/**
		 * Checks to perform:
		 * - width/height smaller than image, keeping aspect ratio?
		 */
	}
}

?>