<?php
namespace TYPO3\CMS\Backend\Backend\Avatar;

/*
 * This file is part of the TYPO3 CMS project.
 *
 * It is free software; you can redistribute it and/or modify it under
 * the terms of the GNU General Public License, either version 2
 * of the License, or any later version.
 *
 * For the full copyright and license information, please read the
 * LICENSE.txt file that was distributed with this source code.
 *
 * The TYPO3 project - inspiring people to share!
 */

/**
 * Class Image
 *
 * Holds url + dimensions of avatar image
 */
class Image {

	/**
	 * Url of avatar image. Needs to be relative to the /typo3/ folder or an absolute URL.
	 *
	 * @var string
	 */
	protected $url;

	/**
	 * @var int
	 */
	protected $width;

	/**
	 * @var int
	 */
	protected $height;

	/**
	 * Constructor
	 *
	 * @param string $url url of image. Needs to be relative to the /typo3/ folder or an absolute URL.
	 * @param int $width width of image
	 * @param int $height height of image
	 */
	public function __construct($url, $width, $height) {
		$this->url = $url;
		$this->width = (int)$width;
		$this->height = (int)$height;
	}

	/**
	 * Get url
	 *
	 * @return string
	 */
	public function getUrl() {
		return $this->url;
	}

	/**
	 * Get width
	 *
	 * @return int
	 */
	public function getWidth() {
		return $this->width;
	}

	/**
	 * Get height
	 *
	 * @return int
	 */
	public function getHeight() {
		return $this->height;
	}
}
