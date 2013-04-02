<?php
namespace TYPO3\CMS\Frontend\Controller;

/***************************************************************
 *  Copyright notice
 *
 *  (c) 1999-2013 Kasper Skårhøj (kasperYYYY@typo3.com)
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
 * Script Class, generating the page output.
 * Instantiated in the bottom of this script.
 *
 * @author Kasper Skårhøj <kasperYYYY@typo3.com>
 */
class ShowImageController {

	// Page content accumulated here.
	/**
	 * @todo Define visibility
	 */
	public $content;

	// Parameters loaded into these internal variables:
	/**
	 * @todo Define visibility
	 */
	public $file;

	/**
	 * @todo Define visibility
	 */
	public $width;

	/**
	 * @todo Define visibility
	 */
	public $height;

	/**
	 * @todo Define visibility
	 */
	public $sample;

	/**
	 * @todo Define visibility
	 */
	public $alternativeTempPath;

	/**
	 * @todo Define visibility
	 */
	public $effects;

	/**
	 * @todo Define visibility
	 */
	public $frame;

	/**
	 * @todo Define visibility
	 */
	public $bodyTag;

	/**
	 * @todo Define visibility
	 */
	public $title;

	/**
	 * @todo Define visibility
	 */
	public $wrap;

	/**
	 * @todo Define visibility
	 */
	public $md5;

	/**
	 * @var string
	 */
	protected $parametersEncoded;

	/**
	 * Init function, setting the input vars in the global space.
	 *
	 * @return void
	 * @todo Define visibility
	 */
	public function init() {
		// Loading internal vars with the GET/POST parameters from outside:
		$this->file = \TYPO3\CMS\Core\Utility\GeneralUtility::_GP('file');
		$parametersArray = \TYPO3\CMS\Core\Utility\GeneralUtility::_GP('parameters');
		$this->frame = \TYPO3\CMS\Core\Utility\GeneralUtility::_GP('frame');
		$this->md5 = \TYPO3\CMS\Core\Utility\GeneralUtility::_GP('md5');
		// Check parameters
		// If no file-param or parameters are given, we must exit
		if (!$this->file || !isset($parametersArray) || !is_array($parametersArray)) {
			\TYPO3\CMS\Core\Utility\HttpUtility::setResponseCodeAndExit(\TYPO3\CMS\Core\Utility\HttpUtility::HTTP_STATUS_410);
		}
		$this->parametersEncoded = implode('', $parametersArray);
		// Chech md5-checksum: If this md5-value does not match the one submitted, then we fail... (this is a kind of security that somebody don't just hit the script with a lot of different parameters
		$md5_value = \TYPO3\CMS\Core\Utility\GeneralUtility::hmac(implode('|', array($this->file, $this->parametersEncoded)));
		if ($md5_value !== $this->md5) {
			\TYPO3\CMS\Core\Utility\HttpUtility::setResponseCodeAndExit(\TYPO3\CMS\Core\Utility\HttpUtility::HTTP_STATUS_410);
		}
		$parameters = unserialize(base64_decode($this->parametersEncoded));
		foreach ($parameters as $parameterName => $parameterValue) {
			$this->{$parameterName} = $parameterValue;
		}
		// Check the file. If must be in a directory beneath the dir of this script...
		// $this->file remains unchanged, because of the code in stdgraphic, but we do check if the file exists within the current path
		$test_file = PATH_site . $this->file;
		if (!\TYPO3\CMS\Core\Utility\GeneralUtility::validPathStr($test_file)) {
			\TYPO3\CMS\Core\Utility\HttpUtility::setResponseCodeAndExit(\TYPO3\CMS\Core\Utility\HttpUtility::HTTP_STATUS_410);
		}
		if (!@is_file($test_file)) {
			\TYPO3\CMS\Core\Utility\HttpUtility::setResponseCodeAndExit(\TYPO3\CMS\Core\Utility\HttpUtility::HTTP_STATUS_404);
		}
	}

	/**
	 * Main function which creates the image if needed and outputs the HTML code for the page displaying the image.
	 * Accumulates the content in $this->content
	 *
	 * @return void
	 * @todo Define visibility
	 */
	public function main() {
		// Creating stdGraphic object, initialize it and make image:
		$img = \TYPO3\CMS\Core\Utility\GeneralUtility::makeInstance('TYPO3\\CMS\\Core\\Imaging\\GraphicalFunctions');
		$img->mayScaleUp = 0;
		$img->init();
		if ($this->sample) {
			$img->scalecmd = '-sample';
		}
		if ($this->alternativeTempPath && \TYPO3\CMS\Core\Utility\GeneralUtility::inList($GLOBALS['TYPO3_CONF_VARS']['FE']['allowedTempPaths'], $this->alternativeTempPath)) {
			$img->tempPath = $this->alternativeTempPath;
		}
		if (strstr($this->width . $this->height, 'm')) {
			$max = 'm';
		} else {
			$max = '';
		}
		$this->height = \TYPO3\CMS\Core\Utility\MathUtility::forceIntegerInRange($this->height, 0);
		$this->width = \TYPO3\CMS\Core\Utility\MathUtility::forceIntegerInRange($this->width, 0);
		if ($this->frame) {
			$this->frame = intval($this->frame);
		}
		$imgInfo = $img->imageMagickConvert($this->file, 'web', $this->width . $max, $this->height, $img->IMparams($this->effects), $this->frame);
		// Create HTML output:
		$this->content = '';
		$this->content .= '
<!DOCTYPE HTML PUBLIC "-//W3C//DTD HTML 4.0 Transitional//EN">

<html>
<head>
	<title>' . htmlspecialchars(($this->title ? $this->title : 'Image')) . '</title>
	' . ($this->title ? '' : '<meta name="robots" content="noindex,follow" />') . '
</head>
		' . ($this->bodyTag ? $this->bodyTag : '<body>');
		if (is_array($imgInfo)) {
			$wrapParts = explode('|', $this->wrap);
			$this->content .= trim($wrapParts[0]) . $img->imgTag($imgInfo) . trim($wrapParts[1]);
		}
		$this->content .= '
		</body>
		</html>';
	}

	/**
	 * Outputs the content from $this->content
	 *
	 * @return void
	 * @todo Define visibility
	 */
	public function printContent() {
		echo $this->content;
	}

}


?>