<?php
namespace TYPO3\CMS\Core\Type\File;

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
use TYPO3\CMS\Core\Imaging\GraphicalFunctions;
use TYPO3\CMS\Core\Log\LogManager;
use TYPO3\CMS\Core\Utility\GeneralUtility;

/**
 * A SPL FileInfo class providing information related to an image.
 */
class ImageInfo extends FileInfo
{
    /**
     * @var array
     */
    protected $imageSizes;

    /**
     * Returns the width of the Image.
     *
     * @return int
     */
    public function getWidth()
    {
        $imageSizes = $this->getImageSizes();
        return $imageSizes[0];
    }

    /**
     * Returns the height of the Image.
     *
     * @return int
     */
    public function getHeight()
    {
        $imageSizes = $this->getImageSizes();
        return $imageSizes[1];
    }

    /**
     * @return array
     */
    protected function getImageSizes()
    {
        if ($this->imageSizes === null) {
            $this->imageSizes = false;
            if (function_exists('getimagesize')) {
                $this->imageSizes = @getimagesize($this->getPathname());
            }
            // Try SVG first as SVG size detection with IM/GM leads to an error output
            if ($this->imageSizes === false && $this->getMimeType() === 'image/svg+xml') {
                $this->imageSizes = $this->extractSvgImageSizes();
            }
            // Fallback to IM/GM identify
            if ($this->imageSizes === false) {
                $graphicalFunctions = GeneralUtility::makeInstance(GraphicalFunctions::class);
                $graphicalFunctions->init();
                $this->imageSizes = $graphicalFunctions->imageMagickIdentify($this->getPathname());
            }

            // In case the image size could not be retrieved, log the incident as a warning.
            if (empty($this->imageSizes)) {
                $this->getLogger()->warning('I could not retrieve the image size for file ' . $this->getPathname());
                $this->imageSizes = [0, 0];
            }
        }
        return $this->imageSizes;
    }

    /**
     * Try to read SVG as XML file and
     * find width and height
     *
     * @return false|array
     */
    protected function extractSvgImageSizes()
    {
        $imagesSizes = [];

        $fileContent = file_get_contents($this->getPathname());
        // Disables the functionality to allow external entities to be loaded when parsing the XML, must be kept
        $previousValueOfEntityLoader = libxml_disable_entity_loader(true);
        $xml = simplexml_load_string($fileContent, 'SimpleXMLElement', LIBXML_NOERROR);

        // If something went wrong with simpleXml don't try to read information
        if ($xml === false) {
            return false;
        }

        libxml_disable_entity_loader($previousValueOfEntityLoader);
        $xmlAttributes = $xml->attributes();

        // First check if width+height are set
        if (!empty($xmlAttributes['width']) && !empty($xmlAttributes['height'])) {
            $imagesSizes = [(int)$xmlAttributes['width'], (int)$xmlAttributes['height']];
        } elseif (!empty($xmlAttributes['viewBox'])) {
            // Fallback to viewBox
            $viewBox = explode(' ', $xmlAttributes['viewBox']);
            $imagesSizes = [(int)$viewBox[2], (int)$viewBox[3]];
        }

        return $imagesSizes !== [] ? $imagesSizes : false;
    }

    /**
     * @return \TYPO3\CMS\Core\Log\Logger
     */
    protected function getLogger()
    {
        /** @var $loggerManager LogManager */
        $loggerManager = GeneralUtility::makeInstance(LogManager::class);

        return $loggerManager->getLogger(get_class($this));
    }

    /**
     * @return GraphicalFunctions
     */
    protected function getGraphicalFunctions()
    {
        $graphicalFunctions = GeneralUtility::makeInstance(GraphicalFunctions::class);
        $graphicalFunctions->init();

        return $graphicalFunctions;
    }
}
