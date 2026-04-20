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
use TYPO3\CMS\Core\Imaging\Exception\InvalidSvgException;
use TYPO3\CMS\Core\Imaging\Exception\UnsupportedFileException;
use TYPO3\CMS\Core\Imaging\GraphicalFunctions;
use TYPO3\CMS\Core\Imaging\Svg\SvgDocumentFactory;
use TYPO3\CMS\Core\Imaging\Svg\SvgDocumentService;
use TYPO3\CMS\Core\Utility\GeneralUtility;

/**
 * An SPL FileInfo class providing information related to an image.
 *
 * @todo: This is a broken construct. ImageInfo (via FileInfo) extends
 *        \SplFileInfo, a pure data object whose only state is the file path
 *        passed to its constructor. FileInfo and ImageInfo then bolt service
 *        aspects (mime detection, image size extraction, SVG/graphics
 *        processing pulled in via makeInstance) onto that data object, which
 *        is why those collaborators cannot be injected and have to be fetched
 *        via GeneralUtility::makeInstance(). This should be refactored by
 *        splitting the service aspect away from the data object: a slim
 *        path/metadata value object, plus a separate injectable service that
 *        resolves image information for a given file.
 */
class ImageInfo extends FileInfo implements LoggerAwareInterface
{
    use LoggerAwareTrait;

    /**
     * @var array{0: int<0, max>|string, 1: int<0, max>|string, 2?: string, 3?: string, 4?: string}|false|null
     */
    protected $imageSizes;

    /**
     * Returns the width of the image.
     *
     * @return int<0, max>
     */
    public function getWidth()
    {
        $imageSizes = $this->getImageSizes();
        return (int)$imageSizes[0];
    }

    /**
     * Returns the height of the image.
     *
     * @return int<0, max>
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
     * @return array{0: int<0, max>, 1: int<0, max>}|false an array where [0]/[1] is w/h
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
     * @return array{0: int<0, max>|string, 1: int<0, max>|string, 2?: string, 3?: string, 4?: string}
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

    /**
     * @return array{0: string, 1: string, 2: string, 3: string, 4: string}|null
     */
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
     * Tries to read SVG as XML file and find width and height.
     *
     * @return array{0: int<0, max>, 1: int<0, max>}|false
     */
    protected function extractSvgImageSizes()
    {
        try {
            $document = GeneralUtility::makeInstance(SvgDocumentFactory::class)->fromFile($this->getPathname());
            $dimensions = GeneralUtility::makeInstance(SvgDocumentService::class)->getDimensions($document);
        } catch (InvalidSvgException) {
            return false;
        }
        return [$dimensions->getWidth(), $dimensions->getHeight()];
    }
}
