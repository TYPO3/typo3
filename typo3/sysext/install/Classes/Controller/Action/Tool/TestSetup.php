<?php
namespace TYPO3\CMS\Install\Controller\Action\Tool;

/***************************************************************
 *  Copyright notice
 *
 *  (c) 2013 Christian Kuhn <lolli@schwarzbu.ch>
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
 *
 *  This script is distributed in the hope that it will be useful,
 *  but WITHOUT ANY WARRANTY; without even the implied warranty of
 *  MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 *  GNU General Public License for more details.
 *
 *  This copyright notice MUST APPEAR in all copies of the script!
 ***************************************************************/

use TYPO3\CMS\Install\Controller\Action;
use TYPO3\CMS\Core\Utility\GeneralUtility;

/**
 * Test various system setup settings
 */
class TestSetup extends Action\AbstractAction {

	/**
	 * @var string Absolute path to image folder
	 */
	protected $imageBasePath = '';

	/**
	 * Executes the tool
	 *
	 * @return string Rendered content
	 */
	protected function executeAction() {
		$this->imageBasePath = \TYPO3\CMS\Core\Utility\ExtensionManagementUtility::extPath('install') . 'Resources/Public/Images/';

		$actionMessages = array();
		if (isset($this->postValues['set']['testMail'])) {
			$actionMessages[] = $this->sendTestMail();
		}

		if (isset($this->postValues['set']['testTrueTypeFontDpi'])) {
			$this->view->assign('trueTypeFontDpiTested', TRUE);
			$actionMessages[] = $this->createTrueTypeFontDpiTestImage();
		}

		if (isset($this->postValues['set']['testConvertImageFormatsToJpg'])) {
			$this->view->assign('convertImageFormatsToJpgTested', TRUE);
			if ($this->isImageMagickEnabledAndConfigured()) {
				$actionMessages[] = $this->convertImageFormatsToJpg();
			} else {
				$actionMessages[] = $this->imageMagickDisabledMessage();
			}
		}

		if (isset($this->postValues['set']['testWriteGifAndPng'])) {
			$this->view->assign('writeGifAndPngTested', TRUE);
			if ($this->isImageMagickEnabledAndConfigured()) {
				$actionMessages[] = $this->writeGifAndPng();
			} else {
				$actionMessages[] = $this->imageMagickDisabledMessage();
			}
		}

		if (isset($this->postValues['set']['testScalingImages'])) {
			$this->view->assign('scalingImagesTested', TRUE);
			if ($this->isImageMagickEnabledAndConfigured()) {
				$actionMessages[] = $this->scaleImages();
			} else {
				$actionMessages[] = $this->imageMagickDisabledMessage();
			}
		}

		if (isset($this->postValues['set']['testCombiningImages'])) {
			$this->view->assign('combiningImagesTested', TRUE);
			if ($this->isImageMagickEnabledAndConfigured()) {
				$actionMessages[] = $this->combineImages();
			} else {
				$actionMessages[] = $this->imageMagickDisabledMessage();
			}
		}

		if (isset($this->postValues['set']['testGdlib'])) {
			$this->view->assign('gdlibTested', TRUE);
			$actionMessages[] = $this->gdlib();
		}

		$this->view->assign('actionMessages', $actionMessages);
		$this->view->assign('senderEmailAddress', $this->getSenderEmailAddress());
		$this->view->assign('imageConfiguration', $this->getImageConfiguration());

		return $this->view->render();
	}

	/**
	 * Send a test mail to specified email address
	 *
	 * @return \TYPO3\CMS\Install\Status\StatusInterface
	 */
	protected function sendTestMail() {
		if (
			!isset($this->postValues['values']['testEmailRecipient'])
			|| !GeneralUtility::validEmail($this->postValues['values']['testEmailRecipient'])
		) {
			/** @var $message \TYPO3\CMS\Install\Status\StatusInterface */
			$message = $this->objectManager->get('TYPO3\\CMS\\Install\\Status\\ErrorStatus');
			$message->setTitle('Mail not sent');
			$message->setMessage('Given address is not a valid email address.');
		} else {
			$recipient = $this->postValues['values']['testEmailRecipient'];
			$mailMessage = $this->objectManager->get('TYPO3\\CMS\\Core\\Mail\\MailMessage');
			$mailMessage
				->addTo($recipient)
				->addFrom($this->getSenderEmailAddress(), 'TYPO3 CMS install tool')
				->setSubject('Test TYPO3 CMS mail delivery')
				->setBody('<html><body>html test content</body></html>')
				->addPart('TEST CONTENT')
				->send();
			$message = $this->objectManager->get('TYPO3\\CMS\\Install\\Status\\OkStatus');
			$message->setTitle('Test mail sent');
			$message->setMessage('Recipient: ' . $recipient);
		}
		return $message;
	}

	/**
	 * Get sender address from configuration
	 * ['TYPO3_CONF_VARS']['MAIL']['defaultMailFromAddress']
	 * If this setting is empty fall back to 'no-reply@example.com'
	 *
	 * @return string Returns an email address
	 */
	protected function getSenderEmailAddress() {
		return !empty($GLOBALS['TYPO3_CONF_VARS']['MAIL']['defaultMailFromAddress'])
			? $GLOBALS['TYPO3_CONF_VARS']['MAIL']['defaultMailFromAddress']
			: 'no-reply@example.com';
	}

	/**
	 * Create true type font test image
	 *
	 * @return \TYPO3\CMS\Install\Status\StatusInterface
	 */
	protected function createTrueTypeFontDpiTestImage() {
		$parseTimeStart = GeneralUtility::milliseconds();

		$image = @imagecreate(200, 50);
		imagecolorallocate($image, 255, 255, 55);
		$textColor = imagecolorallocate($image, 233, 14, 91);
		@imagettftext(
			$image,
			GeneralUtility::freetypeDpiComp(20),
			0,
			10,
			20,
			$textColor,
			\TYPO3\CMS\Core\Utility\ExtensionManagementUtility::extPath('install') . 'Resources/Private/Font/vera.ttf',
			'Testing true type'
		);
		$outputFile = PATH_site . 'typo3temp/installTool-' . uniqid('createTrueTypeFontDpiTestImage') . '.gif';
		imagegif($image, $outputFile);

		/** @var \TYPO3\CMS\Install\Status\StatusInterface $message */
		$message = $this->objectManager->get('TYPO3\\CMS\\Install\\Status\\InfoStatus');
		$message->setTitle('True type font DPI settings');
		$message->setMessage(
			'If the two images below do not look the same, set $TYPO3_CONF_VARS[GFX][TTFdpi] to a value of 72.'
		);

		$testResults = array();
		$testResults['ttf'] = array();
		$testResults['ttf']['message'] = $message;
		$testResults['ttf']['title'] = '';
		$testResults['ttf']['outputFile'] = $outputFile;
		$testResults['ttf']['referenceFile'] = $this->imageBasePath . 'TestReference/Font.gif';

		$this->view->assign('testResults', $testResults);
		return $this->imageTestDoneMessage(GeneralUtility::milliseconds() - $parseTimeStart);
	}

	/**
	 * Create jpg from various image formats using IM / GM
	 *
	 * @return \TYPO3\CMS\Install\Status\StatusInterface
	 */
	protected function convertImageFormatsToJpg() {
		$this->setUpDatabaseConnectionMock();
		$imageProcessor = $this->initializeImageProcessor();
		$parseTimeStart = GeneralUtility::milliseconds();

		$inputFormatsToTest = array('jpg', 'gif', 'png', 'tif', 'bmp', 'pcx', 'tga', 'pdf', 'ai');

		$testResults = array();
		foreach ($inputFormatsToTest as $formatToTest) {
			$result = array();
			if (!GeneralUtility::inList($GLOBALS['TYPO3_CONF_VARS']['GFX']['imagefile_ext'], $formatToTest)) {
				/** @var \TYPO3\CMS\Install\Status\StatusInterface $message */
				$message = $this->objectManager->get('TYPO3\\CMS\\Install\\Status\\WarningStatus');
				$message->setTitle('Skipped test');
				$message->setMessage('Handling format ' . $formatToTest . ' must be enabled in TYPO3_CONF_VARS[\'GFX\'][\'imagefile_ext\']');
				$result['error'] = $message;
			} else {
				$imageProcessor->IM_commands = array();
				$inputFile = $this->imageBasePath . 'TestInput/Test.' . $formatToTest;
				$imageProcessor->imageMagickConvert_forceFileNameBody = uniqid('read') . '-' . $formatToTest;
				$imResult = $imageProcessor->imageMagickConvert($inputFile, 'jpg', '170', '', '', '', array(), TRUE);
				$result['title'] = 'Read ' . $formatToTest;
				if ($imResult !== NULL) {
					$result['outputFile'] = $imResult[3];
					$result['referenceFile'] = $this->imageBasePath . 'TestReference/Read-' . $formatToTest . '.jpg';
					$result['command'] = $imageProcessor->IM_commands;
				} else {
					$result['error'] = $this->imageGenerationFailedMessage();
				}

			}
			$testResults[] = $result;
		}

		$this->view->assign('testResults', $testResults);
		return $this->imageTestDoneMessage(GeneralUtility::milliseconds() - $parseTimeStart);
	}

	/**
	 * Write gif and png test
	 *
	 * @return \TYPO3\CMS\Install\Status\StatusInterface
	 */
	protected function writeGifAndPng() {
		$this->setUpDatabaseConnectionMock();
		$imageProcessor = $this->initializeImageProcessor();
		$parseTimeStart = GeneralUtility::milliseconds();

		$testResults = array(
			'gif' => array(),
			'png' => array(),
		);

		// Gif
		$inputFile = $this->imageBasePath . 'TestInput/Test.gif';
		$imageProcessor->imageMagickConvert_forceFileNameBody = uniqid('write-gif');
		$imResult = $imageProcessor->imageMagickConvert($inputFile, 'gif', '', '', '', '', array(), TRUE);
		if ($imResult !== NULL && is_file($imResult[3])) {
			if ($GLOBALS['TYPO3_CONF_VARS']['GFX']['gif_compress']) {
				clearstatcache();
				$previousSize = GeneralUtility::formatSize(filesize($imResult[3]));
				$methodUsed = GeneralUtility::gif_compress($imResult[3], '');
				clearstatcache();
				$compressedSize = GeneralUtility::formatSize(filesize($imResult[3]));
				/** @var \TYPO3\CMS\Install\Status\StatusInterface $message */
				$message = $this->objectManager->get('TYPO3\\CMS\\Install\\Status\\InfoStatus');
				$message->setTitle('Compressed gif');
				$message->setMessage(
					'Method used by compress: ' . $methodUsed . LF
					. ' Previous filesize: ' . $previousSize . '. Current filesize:' . $compressedSize
				);
			} else {
				/** @var \TYPO3\CMS\Install\Status\StatusInterface $message */
				$message = $this->objectManager->get('TYPO3\\CMS\\Install\\Status\\InfoStatus');
				$message->setTitle('Gif compression not enabled by [GFX][gif_compress]');
			}
			$testResults['gif']['message'] = $message;
			$testResults['gif']['title'] = 'Write gif';
			$testResults['gif']['outputFile'] = $imResult[3];
			$testResults['gif']['referenceFile'] = $this->imageBasePath . 'TestReference/Write-gif.gif';
			$testResults['gif']['command'] = $imageProcessor->IM_commands;
		} else {
			$testResults['gif']['error'] = $this->imageGenerationFailedMessage();
		}

		// Png
		$inputFile = $this->imageBasePath . 'TestInput/Test.png';
		$imageProcessor->IM_commands = array();
		$imageProcessor->imageMagickConvert_forceFileNameBody = uniqid('write-png');
		$imResult = $imageProcessor->imageMagickConvert($inputFile, 'png', '', '', '', '', array(), TRUE);
		if ($imResult !== NULL) {
			$testResults['png']['title'] = 'Write png';
			$testResults['png']['outputFile'] = $imResult[3];
			$testResults['png']['referenceFile'] = $this->imageBasePath . 'TestReference/Write-png.png';
			$testResults['png']['command'] = $imageProcessor->IM_commands;
		} else {
			$testResults['png']['error'] = $this->imageGenerationFailedMessage();
		}

		$this->view->assign('testResults', $testResults);
		return $this->imageTestDoneMessage(GeneralUtility::milliseconds() - $parseTimeStart);
	}

	/**
	 * Write gif and png test
	 *
	 * @return \TYPO3\CMS\Install\Status\StatusInterface
	 */
	protected function scaleImages() {
		$this->setUpDatabaseConnectionMock();
		$imageProcessor = $this->initializeImageProcessor();
		$parseTimeStart = GeneralUtility::milliseconds();

		$testResults = array(
			'gif-to-gif' => array(),
			'png-to-png' => array(),
			'gif-to-jpg' => array(),
		);

		$imageProcessor->IM_commands = array();
		$inputFile = $this->imageBasePath . 'TestInput/Transparent.gif';
		$imageProcessor->imageMagickConvert_forceFileNameBody = uniqid('scale-gif');
		$imResult = $imageProcessor->imageMagickConvert($inputFile, 'gif', '150', '', '', '', array(), TRUE);
		if ($imResult !== NULL) {
			$testResults['gif-to-gif']['title'] = 'gif to gif';
			$testResults['gif-to-gif']['outputFile'] = $imResult[3];
			$testResults['gif-to-gif']['referenceFile'] = $this->imageBasePath . 'TestReference/Scale-gif.gif';
			$testResults['gif-to-gif']['command'] = $imageProcessor->IM_commands;
		} else {
			$testResults['gif-to-gif']['error'] = $this->imageGenerationFailedMessage();
		}

		$imageProcessor->IM_commands = array();
		$inputFile = $this->imageBasePath . 'TestInput/Transparent.png';
		$imageProcessor->imageMagickConvert_forceFileNameBody = uniqid('scale-png');
		$imResult = $imageProcessor->imageMagickConvert($inputFile, 'png', '150', '', '', '', array(), TRUE);
		if ($imResult !== NULL) {
			$testResults['png-to-png']['title'] = 'png to png';
			$testResults['png-to-png']['outputFile'] = $imResult[3];
			$testResults['png-to-png']['referenceFile'] = $this->imageBasePath . 'TestReference/Scale-png.png';
			$testResults['png-to-png']['command'] = $imageProcessor->IM_commands;
		} else {
			$testResults['png-to-png']['error'] = $this->imageGenerationFailedMessage();
		}

		$imageProcessor->IM_commands = array();
		$inputFile = $this->imageBasePath . 'TestInput/Transparent.gif';
		$imageProcessor->imageMagickConvert_forceFileNameBody = uniqid('scale-jpg');
		$imResult = $imageProcessor->imageMagickConvert($inputFile, 'jpg', '150', '', '', '', array(), TRUE);
		if ($imResult !== NULL) {
			$testResults['gif-to-jpg']['title'] = 'gif to jpg';
			$testResults['gif-to-jpg']['outputFile'] = $imResult[3];
			$testResults['gif-to-jpg']['referenceFile'] = $this->imageBasePath . 'TestReference/Scale-jpg.jpg';
			$testResults['gif-to-jpg']['command'] = $imageProcessor->IM_commands;
		} else {
			$testResults['gif-to-jpg']['error'] = $this->imageGenerationFailedMessage();
		}

		$this->view->assign('testResults', $testResults);
		return $this->imageTestDoneMessage(GeneralUtility::milliseconds() - $parseTimeStart);
	}

	/**
	 * Combine multiple images into one test
	 *
	 * @return \TYPO3\CMS\Install\Status\StatusInterface
	 */
	protected function combineImages() {
		$this->setUpDatabaseConnectionMock();
		$imageProcessor = $this->initializeImageProcessor();
		$parseTimeStart = GeneralUtility::milliseconds();

		$testResults = array(
			'combine1' => array(),
			'combine2' => array(),
		);

		$inputFile = $this->imageBasePath . 'TestInput/BackgroundGreen.gif';
		$overlayFile = $this->imageBasePath . 'TestInput/Test.jpg';
		$maskFile = $this->imageBasePath . 'TestInput/MaskBlackWhite.gif';
		$resultFile = $imageProcessor->tempPath . $imageProcessor->filenamePrefix
			. uniqid($imageProcessor->alternativeOutputKey . 'combine1') . '.jpg';
		$imageProcessor->combineExec($inputFile, $overlayFile, $maskFile, $resultFile, TRUE);
		$result = $imageProcessor->getImageDimensions($resultFile);
		if ($result) {
			$testResults['combine1']['title'] = 'Combine using a GIF mask with only black and white';
			$testResults['combine1']['outputFile'] = $result[3];
			$testResults['combine1']['referenceFile'] = $this->imageBasePath . 'TestReference/Combine-1.jpg';
			$testResults['combine1']['command'] = $imageProcessor->IM_commands;
		} else {
			$testResults['combine1']['error'] = $this->imageGenerationFailedMessage();
		}

		$imageProcessor->IM_commands = array();
		$inputFile = $this->imageBasePath . 'TestInput/BackgroundCombine.jpg';
		$overlayFile = $this->imageBasePath . 'TestInput/Test.jpg';
		$maskFile = $this->imageBasePath . 'TestInput/MaskCombine.jpg';
		$resultFile = $imageProcessor->tempPath . $imageProcessor->filenamePrefix
			. uniqid($imageProcessor->alternativeOutputKey . 'combine2') . '.jpg';
		$imageProcessor->combineExec($inputFile, $overlayFile, $maskFile, $resultFile, TRUE);
		$result = $imageProcessor->getImageDimensions($resultFile);
		if ($result) {
			$testResults['combine2']['title'] = 'Combine using a JPG mask with graylevels';
			$testResults['combine2']['outputFile'] = $result[3];
			$testResults['combine2']['referenceFile'] = $this->imageBasePath . 'TestReference/Combine-2.jpg';
			$testResults['combine2']['command'] = $imageProcessor->IM_commands;
		} else {
			$testResults['combine2']['error'] = $this->imageGenerationFailedMessage();
		}

		$this->view->assign('testResults', $testResults);
		return $this->imageTestDoneMessage(GeneralUtility::milliseconds() - $parseTimeStart);
	}

	/**
	 * Test gdlib functions
	 *
	 * @return \TYPO3\CMS\Install\Status\StatusInterface
	 */
	protected function gdlib() {
		$this->setUpDatabaseConnectionMock();
		$imageProcessor = $this->initializeImageProcessor();
		$parseTimeStart = GeneralUtility::milliseconds();
		$gifOrPng = $imageProcessor->gifExtension;
		$testResults = array();

		// GD with simple box
		$imageProcessor->IM_commands = array();
		$image = imagecreatetruecolor(170, 136);
		$backgroundColor = imagecolorallocate($image, 0, 0, 0);
		imagefilledrectangle($image, 0, 0, 170, 136, $backgroundColor);
		$workArea = array(0, 0, 170, 136);
		$conf = array(
			'dimensions' => '10,50,150,36',
			'color' => 'olive',
		);
		$imageProcessor->makeBox($image, $conf, $workArea);
		$outputFile = $imageProcessor->tempPath . $imageProcessor->filenamePrefix . uniqid('gdSimple') . '.' . $gifOrPng;
		$imageProcessor->ImageWrite($image, $outputFile);
		$result = $imageProcessor->getImageDimensions($outputFile);
		$testResults['simple'] = array();
		$testResults['simple']['title'] = 'Create simple image';
		$testResults['simple']['outputFile'] = $result[3];
		$testResults['simple']['referenceFile'] = $this->imageBasePath . 'TestReference/Gdlib-simple.' . $gifOrPng;

		// GD from image with box
		$imageProcessor->IM_commands = array();
		$inputFile = $this->imageBasePath . 'TestInput/Test.' . $gifOrPng;
		$image = $imageProcessor->imageCreateFromFile($inputFile);
		$workArea = array(0, 0, 170, 136);
		$conf = array(
			'dimensions' => '10,50,150,36',
			'color' => 'olive',
		);
		$imageProcessor->makeBox($image, $conf, $workArea);
		$outputFile = $imageProcessor->tempPath . $imageProcessor->filenamePrefix . uniqid('gdBox') . '.' . $gifOrPng;
		$imageProcessor->ImageWrite($image, $outputFile);
		$result = $imageProcessor->getImageDimensions($outputFile);
		$testResults['box'] = array();
		$testResults['box']['title'] = 'Create image from file';
		$testResults['box']['outputFile'] = $result[3];
		$testResults['box']['referenceFile'] = $this->imageBasePath . 'TestReference/Gdlib-box.' . $gifOrPng;

		// GD with text
		$imageProcessor->IM_commands = array();
		$image = imagecreatetruecolor(170, 136);
		$backgroundColor = imagecolorallocate($image, 128, 128, 150);
		imagefilledrectangle($image, 0, 0, 170, 136, $backgroundColor);
		$workArea = array(0, 0, 170, 136);
		$conf = array(
			'iterations' => 1,
			'angle' => 0,
			'antiAlias' => 1,
			'text' => 'HELLO WORLD',
			'fontColor' => '#003366',
			'fontSize' => 18,
			'fontFile' => \TYPO3\CMS\Core\Utility\ExtensionManagementUtility::extPath('install') . 'Resources/Private/Font/vera.ttf',
			'offset' => '17,40',
		);
		$conf['BBOX'] = $imageProcessor->calcBBox($conf);
		$imageProcessor->makeText($image, $conf, $workArea);
		$outputFile = $imageProcessor->tempPath . $imageProcessor->filenamePrefix . uniqid('gdText') . '.' . $gifOrPng;
		$imageProcessor->ImageWrite($image, $outputFile);
		$result = $imageProcessor->getImageDimensions($outputFile);
		$testResults['text'] = array();
		$testResults['text']['title'] = 'Render text with TrueType font';
		$testResults['text']['outputFile'] = $result[3];
		$testResults['text']['referenceFile'] = $this->imageBasePath . 'TestReference/Gdlib-text.' . $gifOrPng;

		// GD with text, niceText
		$testResults['niceText'] = array();
		if ($this->isImageMagickEnabledAndConfigured()) {
			// Warning: Re-uses $conf from above!
			$conf['offset'] = '17,65';
			$conf['niceText'] = 1;
			$imageProcessor->makeText($image, $conf, $workArea);
			$outputFile = $imageProcessor->tempPath . $imageProcessor->filenamePrefix . uniqid('gdNiceText') . '.' . $gifOrPng;
			$imageProcessor->ImageWrite($image, $outputFile);
			$result = $imageProcessor->getImageDimensions($outputFile);
			$testResults['niceText']['title'] = 'Render text with TrueType font using \'niceText\' option';
			$testResults['niceText']['outputFile'] = $result[3];
			$testResults['niceText']['referenceFile'] = $this->imageBasePath . 'TestReference/Gdlib-niceText.' . $gifOrPng;
			$testResults['niceText']['commands'] = $imageProcessor->IM_commands;
			/** @var \TYPO3\CMS\Install\Status\StatusInterface $message */
			$message = $this->objectManager->get('TYPO3\\CMS\\Install\\Status\\InfoStatus');
			$message->setTitle('Note on \'niceText\'');
			$message->setMessage(
				'\'niceText\' is a concept that tries to improve the antialiasing of the rendered type by'
				. ' actually rendering the textstring in double size on a black/white mask, downscaling the mask'
				. ' and masking the text onto the image through this mask. This involves'
				. ' ImageMagick \'combine\'/\'composite\' and \'convert\'.'
			);
			$testResults['niceText']['message'] = $message;
		} else {
			$result['niceText']['error'] = $this->imageGenerationFailedMessage();
		}

		// GD with text, niceText, shadow
		$testResults['shadow'] = array();
		if ($this->isImageMagickEnabledAndConfigured()) {
			// Warning: Re-uses $conf from above!
			$conf['offset'] = '17,90';
			$conf['niceText'] = 1;
			$conf['shadow.'] = array(
				'offset' => '2,2',
				'blur' => $imageProcessor->V5_EFFECTS ? '20' : '90',
				'opacity' => '50',
				'color' => 'black'
			);
			// Warning: Re-uses $image from above!
			$imageProcessor->makeShadow($image, $conf['shadow.'], $workArea, $conf);
			$imageProcessor->makeText($image, $conf, $workArea);
			$outputFile = $imageProcessor->tempPath . $imageProcessor->filenamePrefix . uniqid('GDwithText-niceText-shadow') . '.' . $gifOrPng;
			$imageProcessor->ImageWrite($image, $outputFile);
			$result = $imageProcessor->getImageDimensions($outputFile);
			$testResults['shadow']['title'] = 'Render \'niceText\' with a shadow under';
			$testResults['shadow']['outputFile'] = $result[3];
			$testResults['shadow']['referenceFile'] = $this->imageBasePath . 'TestReference/Gdlib-shadow.' . $gifOrPng;
			$testResults['shadow']['commands'] = $imageProcessor->IM_commands;
			/** @var \TYPO3\CMS\Install\Status\StatusInterface $message */
			$message = $this->objectManager->get('TYPO3\\CMS\\Install\\Status\\InfoStatus');
			$message->setTitle('Note on \'shadow\'');
			$message->setMessage(
				'This test makes sense only if the above test had a correct output. But if so, you may not see'
				. ' a soft dropshadow from the third text string as you should. In that case you are most likely'
				. ' using ImageMagick 5 and should set the flag TYPO3_CONF_VARS[GFX][im_v5effects].'
			);
			$testResults['shadow']['message'] = $message;
		} else {
			$result['shadow']['error'] = $this->imageGenerationFailedMessage();
		}

		$this->view->assign('testResults', $testResults);
		return $this->imageTestDoneMessage(GeneralUtility::milliseconds() - $parseTimeStart);
	}

	/**
	 * Create a 'image test was done' message
	 *
	 * @param integer $parseTime Parse time
	 * @return \TYPO3\CMS\Install\Status\StatusInterface
	 */
	protected function imageTestDoneMessage($parseTime = 0) {
		/** @var \TYPO3\CMS\Install\Status\StatusInterface $message */
		$message = $this->objectManager->get('TYPO3\\CMS\\Install\\Status\\OkStatus');
		$message->setTitle('Executed image tests');
		$message->setMessage('Parse time: ' . $parseTime . ' ms');
		return $message;
	}

	/**
	 * Create a 'imageMagick disabled' message
	 *
	 * @return \TYPO3\CMS\Install\Status\StatusInterface
	 */
	protected function imageMagickDisabledMessage() {
		/** @var \TYPO3\CMS\Install\Status\StatusInterface $message */
		$message = $this->objectManager->get('TYPO3\\CMS\\Install\\Status\\ErrorStatus');
		$message->setTitle('Tests not executed');
		$message->setMessage('ImageMagick / GraphicsMagick handling is disabled or not configured correctly.');
		return $message;
	}

	/**
	 * Create a 'image generation failed' message
	 *
	 * @return \TYPO3\CMS\Install\Status\StatusInterface
	 */
	protected function imageGenerationFailedMessage() {
		/** @var \TYPO3\CMS\Install\Status\StatusInterface $message */
		$message = $this->objectManager->get('TYPO3\\CMS\\Install\\Status\\ErrorStatus');
		$message->setTitle('Image generation failed');
		$message->setMessage(
			'ImageMagick / GraphicsMagick handling is enabled, but the execute'
			. ' command returned an error. Please check your settings, especially'
			. ' [\'GFX\'][\'im_path\'] and [\'GFX\'][\'im_path_lzw\'].'
		);
		return $message;
	}

	/**
	 * Gather image configuration overview
	 *
	 * @return array Result array
	 */
	protected function getImageConfiguration() {
		$result = array();
		$result['imageMagickOrGraphicsMagick'] = $GLOBALS['TYPO3_CONF_VARS']['GFX']['im_version_5'] === 'gm' ? 'gm' : 'im';
		$result['imageMagickEnabled'] =  $GLOBALS['TYPO3_CONF_VARS']['GFX']['im'];
		$result['imageMagickPath'] = $GLOBALS['TYPO3_CONF_VARS']['GFX']['im_path'];
		$result['imageMagickVersion'] = $this->determineImageMagickVersion();
		$result['imageMagick5Effects'] = $GLOBALS['TYPO3_CONF_VARS']['GFX']['im_v5effects'];
		$result['gdlibEnabled'] = $GLOBALS['TYPO3_CONF_VARS']['GFX']['gdlib'];
		$result['gdlibPng'] = $GLOBALS['TYPO3_CONF_VARS']['GFX']['gdlib_png'];
		$result['freeTypeDpi'] = $GLOBALS['TYPO3_CONF_VARS']['GFX']['TTFdpi'];
		$result['fileFormats'] = $GLOBALS['TYPO3_CONF_VARS']['GFX']['imagefile_ext'];
		return $result;
	}

	/**
	 * Initialize image processor
	 *
	 * @return \TYPO3\CMS\Core\Imaging\GraphicalFunctions Initialized image processor
	 */
	protected function initializeImageProcessor() {
		/** @var \TYPO3\CMS\Core\Imaging\GraphicalFunctions $imageProcessor */
		$imageProcessor = $this->objectManager->get('TYPO3\\CMS\\Core\\Imaging\\GraphicalFunctions');
		$imageProcessor->init();
		$imageProcessor->tempPath = PATH_site . 'typo3temp/';
		$imageProcessor->dontCheckForExistingTempFile = 1;
		$imageProcessor->enable_typo3temp_db_tracking = 0;
		$imageProcessor->filenamePrefix = 'installTool-';
		$imageProcessor->dontCompress = 1;
		$imageProcessor->alternativeOutputKey = 'typo3InstallTest';
		$imageProcessor->noFramePrepended = $GLOBALS['TYPO3_CONF_VARS']['GFX']['im_noFramePrepended'];
		return $imageProcessor;
	}

	/**
	 * Find out if ImageMagick or GraphicsMagick is enabled and set up
	 *
	 * @return boolean TRUE if enabled and path is set
	 */
	protected function isImageMagickEnabledAndConfigured() {
		$enabled = $GLOBALS['TYPO3_CONF_VARS']['GFX']['im'];
		$path = $GLOBALS['TYPO3_CONF_VARS']['GFX']['im_path'];
		return $enabled && $path;
	}

	/**
	 * Determine ImageMagick / GraphicsMagick version
	 *
	 * @return string Version
	 */
	protected function determineImageMagickVersion() {
		$command = \TYPO3\CMS\Core\Utility\CommandUtility::imageMagickCommand('identify', '-version');
		\TYPO3\CMS\Core\Utility\CommandUtility::exec($command, $result);
		$string = $result[0];
		list(, $version) = explode('Magick', $string);
		list($version) = explode(' ', trim($version));
		return trim($version);
	}

	/**
	 * Instantiate a dummy instance for $GLOBALS['TYPO3_DB'] to
	 * prevent real database calls
	 *
	 * @return void
	 */
	protected function setUpDatabaseConnectionMock() {
		$database = $this->objectManager->get('TYPO3\\CMS\\Install\\Database\\DatabaseConnectionMock');
		$GLOBALS['TYPO3_DB'] = $database;
	}
}
