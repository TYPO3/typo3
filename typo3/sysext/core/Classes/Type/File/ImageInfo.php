<?php

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

namespace TYPO3\CMS\Core\Type\File;

use Psr\Log\LoggerAwareInterface;
use Psr\Log\LoggerAwareTrait;
use TYPO3\CMS\Core\Imaging\Exception\UnsupportedFileException;
use TYPO3\CMS\Core\Imaging\GraphicalFunctions;
use TYPO3\CMS\Core\Utility\GeneralUtility;

/**
 * A SPL FileInfo class providing information related to an image.
 */
class ImageInfo extends FileInfo implements LoggerAwareInterface
{
    use LoggerAwareTrait;

    /**
     * @var array|false|null
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
        return (int)$imageSizes[0];
    }

    /**
     * Returns the height of the Image.
     *
     * @return int
     */
    public function getHeight()
    {
        $imageSizes = $this->getImageSizes();
        return (int)$imageSizes[1];
    }

    /**
     * Gets the image size, considering the exif-rotation present in the file
     *
     * @param string $imageFile The image filepath
     * @return array|false Returns an array where [0]/[1] is w/h.
     */
    protected function getExifAwareImageSize(string $imageFile)
    {
        $size = false;
        if (function_exists('getimagesize')) {
            $size = @getimagesize($imageFile);
        }
        if ($size === false) {
            return false;
        }
        [$width, $height] = $size;

        if (function_exists('exif_read_data')) {
            $exif = @exif_read_data($imageFile);
            // see: http://sylvana.net/jpegcrop/exif_orientation.html
            if (isset($exif['Orientation']) && $exif['Orientation'] >= 5 && $exif['Orientation'] <= 8) {
                return [$height, $width];
            }
        }

        return [$width, $height];
    }

    /**
     * @return array
     */
    protected function getImageSizes()
    {
        if ($this->imageSizes === null) {
            $this->imageSizes = $this->getExifAwareImageSize($this->getPathname());

            // Try SVG first as SVG size detection with IM/GM leads to an error output
            if ($this->imageSizes === false && $this->getMimeType() === 'image/svg+xml') {
                $this->imageSizes = $this->extractSvgImageSizes();
            }
            // Fallback to IM/GM identify
            if ($this->imageSizes === false) {
                $this->imageSizes = $this->getImageSizesFromImageMagick();
            }

            // In case the image size could not be retrieved, log the incident as a warning.
            if (empty($this->imageSizes)) {
                $this->logger->warning('I could not retrieve the image size for file {file}', ['file' => $this->getPathname()]);
                $this->imageSizes = [0, 0];
            }
        }
        return $this->imageSizes;
    }

    protected function getImageSizesFromImageMagick(): ?array
    {
        try {
            $graphicalFunctions = GeneralUtility::makeInstance(GraphicalFunctions::class);
            return $graphicalFunctions->imageMagickIdentify($this->getPathname());
        } catch (UnsupportedFileException $e) {
            $this->logger->error(
                'Error resolving image sizes with ImageMagick: ' . $this->getPathname(),
                ['exception' => $e]
            );
            return null;
        }
    }

    /**
     * Try to read SVG as XML file and
     * find width and height
     *
     * @return false|array
     */
    protected function extractSvgImageSizes()
    {
        $fileContent = file_get_contents($this->getPathname());
        if ($fileContent === false) {
            return false;
        }
        // Disables the functionality to allow external entities to be loaded when parsing the XML, must be kept
        $previousValueOfEntityLoader = null;
        if (PHP_MAJOR_VERSION < 8) {
            $previousValueOfEntityLoader = libxml_disable_entity_loader(true);
        }
        $xml = simplexml_load_string($fileContent, \SimpleXMLElement::class, LIBXML_NOERROR | LIBXML_NOWARNING);
        if (PHP_MAJOR_VERSION < 8) {
            libxml_disable_entity_loader($previousValueOfEntityLoader);
        }
        // If something went wrong with simpleXml don't try to read information
        if ($xml === false) {
            return false;
        }

        $xmlAttributes = $xml->attributes();

        // First check if width+height are set
        if (!empty($xmlAttributes['width']) && !empty($xmlAttributes['height'])) {
            $imagesSizes = [(int)$xmlAttributes['width'], (int)$xmlAttributes['height']];
        } elseif (!empty($xmlAttributes['viewBox'])) {
            // Fallback to viewBox
            $viewBox = explode(' ', $xmlAttributes['viewBox']);
            $imagesSizes = [(int)$viewBox[2], (int)$viewBox[3]];
        } else {
            // To not fail image processing, we just assume an SVG image dimension here
            $imagesSizes = [64, 64];
        }

        return $imagesSizes !== [] ? $imagesSizes : false;
    }
}
