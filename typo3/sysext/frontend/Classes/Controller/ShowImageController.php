<?php
namespace TYPO3\CMS\Frontend\Controller;

/**
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

use \TYPO3\CMS\Core\Utility\HttpUtility;
use \TYPO3\CMS\Core\Utility\GeneralUtility;
use \TYPO3\CMS\Core\Utility\MathUtility;

/**
 * Script Class, generating the page output.
 * Instantiated in the bottom of this script.
 *
 * @author Kasper Skårhøj <kasperYYYY@typo3.com>
 */
class ShowImageController {

	// Parameters loaded into these internal variables:
	/**
	 * @var \TYPO3\CMS\Core\Resource\File
	 */
	protected $file;

	/**
	 * @var int
	 */
	protected $width;

	/**
	 * @var int
	 */
	protected $height;

	/**
	 * @var string
	 */
	protected $sample;

	/**
	 * @var string
	 */
	protected $effects;

	/**
	 * @var int
	 */
	protected $frame;

	/**
	 * @var string
	 */
	protected $hmac;

	/**
	 * @var string
	 */
	protected $bodyTag = '<body>';

	/**
	 * @var string
	 */
	protected $wrap = '|';

	/**
	 * @var string
	 */
	protected $title = 'Image';

	/**
	 * @var string
	 */
	protected $content = <<<EOF
<!DOCTYPE html>
<html>
<head>
	<title>###TITLE###</title>
	<meta name="robots" content="noindex,follow" />
</head>
###BODY###
	###IMAGE###
</body>
</html>
EOF;

	protected $imageTag = '<img src="###publicUrl###" alt="###alt###" title="###title###" />';

	/**
	 * Init function, setting the input vars in the global space.
	 *
	 * @return void
	 */
	public function init() {
		// Loading internal vars with the GET/POST parameters from outside:
		$fileUid = GeneralUtility::_GP('file');
		$this->frame = GeneralUtility::_GP('frame');
		/* For backwards compatibility the HMAC is transported within the md5 param */
		$this->hmac = GeneralUtility::_GP('md5');

		$parametersArray = GeneralUtility::_GP('parameters');

		// If no file-param or parameters are given, we must exit
		if (!$fileUid || !isset($parametersArray) || !is_array($parametersArray)) {
			HttpUtility::setResponseCodeAndExit(HttpUtility::HTTP_STATUS_410);
		}

		// rebuild the parameter array and check if the HMAC is correct
		$parametersEncoded = implode('', $parametersArray);
		$hmac = GeneralUtility::hmac(implode('|', array($fileUid, $parametersEncoded)));
		if ($hmac !== $this->hmac) {
			HttpUtility::setResponseCodeAndExit(HttpUtility::HTTP_STATUS_410);

		}

		// decode the parameters Array
		$parameters = unserialize(base64_decode($parametersEncoded));
		foreach ($parameters as $parameterName => $parameterValue) {
			$this->{$parameterName} = $parameterValue;
		}

		try {
			if (\TYPO3\CMS\Core\Utility\MathUtility::canBeInterpretedAsInteger($fileUid)) {
				$this->file = \TYPO3\CMS\Core\Resource\ResourceFactory::getInstance()->getFileObject((int)$fileUid);
			} else {
				$this->file = \TYPO3\CMS\Core\Resource\ResourceFactory::getInstance()->retrieveFileOrFolderObject($fileUid);
			}
		} catch (\TYPO3\CMS\Core\Exception $e) {
			HttpUtility::setResponseCodeAndExit(HttpUtility::HTTP_STATUS_404);
		}
	}

	/**
	 * Main function which creates the image if needed and outputs the HTML code for the page displaying the image.
	 * Accumulates the content in $this->content
	 *
	 * @return void
	 */
	public function main() {
		$processedImage = $this->processImage();
		$imageTagMarkers = array(
			'###publicUrl###' => htmlspecialchars($processedImage->getPublicUrl()),
			'###alt###' => htmlspecialchars($this->file->getProperty('alternative') ?: $this->title),
			'###title###' => htmlspecialchars($this->file->getProperty('title') ?: $this->title)
		);
		$this->imageTag = str_replace(array_keys($imageTagMarkers), array_values($imageTagMarkers), $this->imageTag);
		if ($this->wrap !== '|') {
			$wrapParts = explode('|', $this->wrap, 2);
			$this->imageTag = $wrapParts[0] . $this->imageTag . $wrapParts[1];
		}
		$markerArray = array(
			'###TITLE###' => ($this->file->getProperty('title') ?: $this->title),
			'###IMAGE###' => $this->imageTag,
			'###BODY###' => $this->bodyTag
		);

		$this->content = str_replace(array_keys($markerArray), array_values($markerArray), $this->content);

	}

	/**
	 * Does the actual image processing
	 * @return \TYPO3\CMS\Core\Resource\ProcessedFile
	 */
	protected function processImage() {
		if (strstr($this->width . $this->height, 'm')) {
			$max = 'm';
		} else {
			$max = '';
		}
		$this->height = MathUtility::forceIntegerInRange($this->height, 0);
		$this->width = MathUtility::forceIntegerInRange($this->width, 0) . $max;

		$processingConfiguration = array(
			'width' => $this->width,
			'height' => $this->height,
			'frame' => $this->frame,

		);
		return $this->file->process('Image.CropScaleMask', $processingConfiguration);
	}
	/**
	 * Outputs the content from $this->content
	 *
	 * @return void
	 */
	public function printContent() {
		echo $this->content;
		HttpUtility::setResponseCodeAndExit(HttpUtility::HTTP_STATUS_200);
	}

	/**
	 *
	 * @return void
	 */
	public function execute() {
		$this->init();
		$this->main();
		$this->printContent();
	}
}
