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
 * A task that takes care of cropping, scaling and/or masking an image.
 */
class ImageCropScaleMaskTask extends AbstractGraphicalTask {

	/**
	 * @var string
	 */
	protected $type = 'Image';

	/**
	 * @var string
	 */
	protected $name = 'CropScaleMask';

	/**
	 * @return string
	 */
	public function getTargetFileName() {
		return 'csm_' . parent::getTargetFilename();
	}

	/**
	 * Checks if the given configuration is sensible for this task, i.e. if all required parameters
	 * are given, within the boundaries and don't conflict with each other.
	 *
	 * @param array $configuration
	 * @return boolean
	 */
	protected function isValidConfiguration(array $configuration) {
		// TODO: Implement isValidConfiguration() method.
	}

	public function fileNeedsProcessing() {
		// TODO: Implement fileNeedsProcessing() method.
	}
}

?>