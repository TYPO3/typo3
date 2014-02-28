<?php
namespace TYPO3\CMS\Core\Resource\Processing;

/***************************************************************
 *  Copyright notice
 *
 *  (c) 2014 Frans Saris <franssaris@gmail.com>
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

/**
 * ImageProcessingRequest configuration container
 */
class ImageProcessingRequest extends AbstractProcessingRequest {

	/**
	 * Set width
	 *
	 * @param string $width in pixels add m for max width or c for crop
	 * @return self
	 */
	public function setWidth($width) {
		$this->configuration['width'] = $width;
		return $this;
	}

	/**
	 * Get width
	 *
	 * @return string
	 */
	public function getWidth() {
		$width = NULL;
		if (isset($this->configuration['width'])) {
			$width = $this->configuration['width'];
		}
		return $width;
	}

	/**
	 * Set height
	 *
	 * @param string $height in pixels add m for max height or c for crop
	 * @return self
	 */
	public function setHeight($height) {
		$this->configuration['height'] = $height;
		return $this;
	}

	/**
	 * Get height
	 *
	 * @return string
	 */
	public function getHeight() {
		$height = NULL;
		if (isset($this->configuration['height'])) {
			$height = $this->configuration['height'];
		}
		return $height;
	}

	/**
	 * Set max width
	 *
	 * @param int $maxWidth
	 * @return self
	 */
	public function setMaxWidth($maxWidth) {
		$this->configuration['maxWidth'] = (int)$maxWidth;
		return $this;
	}

	/**
	 * Get max height
	 *
	 * @return null|int
	 */
	public function getMaxWidth() {
		$maxWidth = NULL;
		if (isset($this->configuration['maxWidth'])) {
			$maxWidth = $this->configuration['maxWidth'];
		}
		return $maxWidth;
	}

	/**
	 * Set max height
	 *
	 * @param int $maxHeight
	 * @return self
	 */
	public function setMaxHeight($maxHeight) {
		$this->configuration['maxHeight'] = (int)$maxHeight;
		return $this;
	}

	/**
	 * Get max height
	 *
	 * @return null|int
	 */
	public function getMaxHeight() {
		$maxHeight = NULL;
		if (isset($this->configuration['maxHeight'])) {
			$maxHeight = $this->configuration['maxHeight'];
		}
		return $maxHeight;
	}

	/**
	 * Set frame
	 * Frame refers to which frame-number to select in the image
	 *
	 * @param int $frame
	 * @return self
	 */
	public function setFrame($frame) {
		$this->configuration['frame'] = (int)$frame;
		return $this;
	}

	/**
	 * Get frame
	 * Frame refers to which frame-number to select in the image
	 *
	 * @return int default 0 when not set
	 */
	public function getFrame() {
		$frame = 0;
		if (isset($this->configuration['frame'])) {
			$frame = $this->configuration['frame'];
		}
		return $frame;
	}

	/**
	 * Set useSample
	 * Instructs GifBuilder to use -sample instead of -geometry
	 * when scaling the image
	 *
	 * @param bool $useSample
	 * @return self
	 */
	public function setUseSample($useSample) {
		$this->configuration['useSample'] = $useSample;
		return $this;
	}

	/**
	 * Get useSample
	 *
	 * @return bool
	 */
	public function getUseSample() {
		return !empty($this->configuration['useSample']);
	}

	/**
	 * Set noScale
	 *
	 * @param bool $noScale
	 * @return self
	 */
	public function setNoScale($noScale) {
		$this->configuration['noScale'] = $noScale;
		return $this;
	}

	/**
	 * Get noScale
	 *
	 * @return bool
	 */
	public function getNoScale() {
		return !empty($this->configuration['noScale']);
	}

	/**
	 * Set custom ImageMagick configuration
	 *
	 * @param string $customIMConfig Additional ImageMagick parameters
	 * @return self
	 */
	public function setCustomIMConfig($customIMConfig) {
		$this->configuration['customIMConfig'] = $customIMConfig;
		return $this;
	}

	/**
	 * Get custom ImageMagick configuration
	 *
	 * @return string
	 */
	public function getCustomIMConfig() {
		$customIMConfig = '';
		if (!empty($this->configuration['customIMConfig'])) {
			$customIMConfig = $this->configuration['customIMConfig'];
		}
		return $customIMConfig;
	}
}