<?php
namespace TYPO3\CMS\Rtehtmlarea\Controller;

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

use TYPO3\CMS\Core\Resource;
use TYPO3\CMS\Core\Utility\GeneralUtility;

/**
 * Render the image attributes and reconstruct magic images, if necessary (and possible)
 */
class ImageRenderingController extends \TYPO3\CMS\Frontend\Plugin\AbstractPlugin
{
    /**
     * Same as class name
     *
     * @var string
     */
    public $prefixId = 'ImageRenderingController';

    /**
     * Path to this script relative to the extension dir
     *
     * @var string
     */
    public $scriptRelPath = 'Classes/Controller/ImageRenderingController.php';

    /**
     * The extension key
     *
     * @var string
     */
    public $extKey = 'rtehtmlarea';

    /**
     * Configuration
     *
     * @var array
     */
    public $conf = [];

    /**
     * cObj object
     *
     * @var \TYPO3\CMS\Frontend\ContentObject\ContentObjectRenderer
     */
    public $cObj;

    /**
     * Returns a processed image to be displayed on the Frontend.
     *
     * @param string $content Content input (not used).
     * @param array $conf TypoScript configuration
     * @return string HTML output
     */
    public function renderImageAttributes($content = '', $conf)
    {
        $imageAttributes = $this->getImageAttributes();

        // It is pretty rare to be in presence of an external image as the default behaviour
        // of the RTE is to download the external image and create a local image.
        // However, it may happen if the RTE has the flag "disable"
        if (!$this->isExternalImage()) {
            $fileUid = (int)$imageAttributes['data-htmlarea-file-uid'];
            if ($fileUid) {
                try {
                    $file = Resource\ResourceFactory::getInstance()->getFileObject($fileUid);
                    if ($imageAttributes['src'] !== $file->getPublicUrl()) {
                        // Source file is a processed image
                        $imageConfiguration = [
                            'width' => (int)$imageAttributes['width'],
                            'height' => (int)$imageAttributes['height']
                        ];
                        $processedFile = $this->getMagicImageService()->createMagicImage($file, $imageConfiguration);
                        $additionalAttributes = [
                            'src' => $processedFile->getPublicUrl(),
                            'title' => $imageAttributes['title'] ?: $file->getProperty('title'),
                            'alt' => $imageAttributes['alt'] ?: $file->getProperty('alternative'),
                            'width' => $processedFile->getProperty('width'),
                            'height' => $processedFile->getProperty('height'),
                        ];
                        $imageAttributes = array_merge($imageAttributes, $additionalAttributes);
                    }
                } catch (Resource\Exception\FileDoesNotExistException $fileDoesNotExistException) {
                    // Log the fact the file could not be retrieved.
                    $message = sprintf('I could not find file with uid "%s"', $fileUid);
                    $this->getLogger()->error($message);
                }
            }
        }
        return '<img ' . GeneralUtility::implodeAttributes($imageAttributes, true, true) . ' />';
    }

    /**
     * Returns a sanitizes array of attributes out of $this->cObj
     *
     * @return array
     */
    protected function getImageAttributes()
    {
        return $this->cObj->parameters;
    }

    /**
     * Instantiates and prepares the Magic Image service.
     *
     * @return \TYPO3\CMS\Core\Resource\Service\MagicImageService
     */
    protected function getMagicImageService()
    {

        /** @var $magicImageService Resource\Service\MagicImageService */
        $magicImageService = GeneralUtility::makeInstance(\TYPO3\CMS\Core\Resource\Service\MagicImageService::class);

        // Get RTE configuration
        $pageTSConfig = $this->frontendController->getPagesTSconfig();
        if (is_array($pageTSConfig) && is_array($pageTSConfig['RTE.']['default.'])) {
            $magicImageService->setMagicImageMaximumDimensions($pageTSConfig['RTE.']['default.']);
        }

        return $magicImageService;
    }

    /**
     * Tells whether the image URL is found to be "external".
     *
     * @return bool
     */
    protected function isExternalImage()
    {
        $srcAbsoluteUrl = $this->cObj->parameters['src'];
        return strtolower(substr($srcAbsoluteUrl, 0, 4)) === 'http' || substr($srcAbsoluteUrl, 0, 2) === '//';
    }

    /**
     * @return \TYPO3\CMS\Core\Log\Logger
     */
    protected function getLogger()
    {

        /** @var $logManager \TYPO3\CMS\Core\Log\LogManager */
        $logManager = GeneralUtility::makeInstance(\TYPO3\CMS\Core\Log\LogManager::class);

        return $logManager->getLogger(get_class($this));
    }
}
