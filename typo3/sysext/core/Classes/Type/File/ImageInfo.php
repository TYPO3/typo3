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
use Psr\Log\LoggerAwareInterface;
use Psr\Log\LoggerAwareTrait;
use TYPO3\CMS\Core\Imaging\GraphicalFunctions;
use TYPO3\CMS\Core\Utility\GeneralUtility;

/**
 * A SPL FileInfo class providing information related to an image.
 */
class ImageInfo extends FileInfo implements LoggerAwareInterface
{
    use LoggerAwareTrait;

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
                $graphicalFunctions = GeneralUtility::makeInstance(GraphicalFunctions::class);
                $this->imageSizes = $graphicalFunctions->imageMagickIdentify($this->getPathname());
            }

            // In case the image size could not be retrieved, log the incident as a warning.
            if (empty($this->imageSizes)) {
                $this->logger->warning('I could not retrieve the image size for file ' . $this->getPathname());
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
        $xml = simplexml_load_string($fileContent, 'SimpleXMLElement', LIBXML_NOERROR | LIBXML_NOWARNING);

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
}
