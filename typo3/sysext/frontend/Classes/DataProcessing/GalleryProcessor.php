<?php
namespace TYPO3\CMS\Frontend\DataProcessing;

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

use TYPO3\CMS\Core\Imaging\ImageManipulation\CropVariantCollection;
use TYPO3\CMS\Core\Resource\FileInterface;
use TYPO3\CMS\Frontend\ContentObject\ContentObjectRenderer;
use TYPO3\CMS\Frontend\ContentObject\DataProcessorInterface;
use TYPO3\CMS\Frontend\ContentObject\Exception\ContentRenderingException;

/**
 * This data processor will calculate rows, columns and dimensions for a gallery
 * based on several settings and can be used for f.i. the CType "textmedia"
 *
 * The output will be an array which contains the rows and columns,
 * including the file references and the calculated width and height for each media element,
 * but also some more information of the gallery, like position, width and counters
 *
 * Example TypoScript configuration:
 *
 * 10 = TYPO3\CMS\Frontend\DataProcessing\GalleryProcessor
 * 10 {
 *   filesProcessedDataKey = files
 *   mediaOrientation.field = imageorient
 *   numberOfColumns.field = imagecols
 *   equalMediaHeight.field = imageheight
 *   equalMediaWidth.field = imagewidth
 *   columnSpacing = 0
 *   borderEnabled.field = imageborder
 *   borderPadding = 0
 *   borderWidth = 0
 *   maxGalleryWidth = {$styles.content.mediatext.maxW}
 *   maxGalleryWidthInText = {$styles.content.mediatext.maxWInText}
 *   as = gallery
 * }
 *
 * Output example:
 *
 * gallery {
 *   position {
 *     horizontal = center
 *     vertical = above
 *     noWrap = FALSE
 *   }
 *   width = 600
 *   count {
 *     files = 2
 *     columns = 1
 *     rows = 2
 *   }
 *   rows {
 *     1 {
 *       columns {
 *         1 {
 *           media = TYPO3\CMS\Core\Resource\FileReference
 *           dimensions {
 *             width = 600
 *             height = 400
 *           }
 *         }
 *       }
 *     }
 *     2 {
 *       columns {
 *         1 {
 *           media = TYPO3\CMS\Core\Resource\FileReference
 *           dimensions {
 *             width = 600
 *             height = 400
 *           }
 *         }
 *       }
 *     }
 *   }
 *   columnSpacing = 0
 *   border {
 *     enabled = FALSE
 *     width = 0
 *     padding = 0
 *   }
 * }
 */
class GalleryProcessor implements DataProcessorInterface
{
    /**
     * The content object renderer
     *
     * @var \TYPO3\CMS\Frontend\ContentObject\ContentObjectRenderer
     */
    protected $contentObjectRenderer;

    /**
     * The processor configuration
     *
     * @var array
     */
    protected $processorConfiguration;

    /**
     * Matching the tt_content field towards the imageOrient option
     *
     * @var array
     */
    protected $availableGalleryPositions = [
        'horizontal' => [
            'center' => [0, 8],
            'right' => [1, 9, 17, 25],
            'left' => [2, 10, 18, 26]
        ],
        'vertical' => [
            'above' => [0, 1, 2],
            'intext' => [17, 18, 25, 26],
            'below' => [8, 9, 10]
        ]
    ];

    /**
     * Storage for processed data
     *
     * @var array
     */
    protected $galleryData = [
        'position' => [
            'horizontal' => '',
            'vertical' => '',
            'noWrap' => false
        ],
        'width' => 0,
        'count' => [
            'files' => 0,
            'columns' => 0,
            'rows' => 0,
        ],
        'columnSpacing' => 0,
        'border' => [
            'enabled' => false,
            'width' => 0,
            'padding' => 0,
        ],
        'rows' => []
    ];

    /**
     * @var int
     */
    protected $numberOfColumns;

    /**
     * @var int
     */
    protected $mediaOrientation;

    /**
     * @var int
     */
    protected $maxGalleryWidth;

    /**
     * @var int
     */
    protected $maxGalleryWidthInText;

    /**
     * @var int
     */
    protected $equalMediaHeight;

    /**
     * @var int
     */
    protected $equalMediaWidth;

    /**
     * @var int
     */
    protected $columnSpacing;

    /**
     * @var bool
     */
    protected $borderEnabled;

    /**
     * @var int
     */
    protected $borderWidth;

    /**
     * @var int
     */
    protected $borderPadding;

    /**
     * @var string
     */
    protected $cropVariant = 'default';

    /**
     * The (filtered) media files to be used in the gallery
     *
     * @var FileInterface[]
     */
    protected $fileObjects = [];

    /**
     * The calculated dimensions for each media element
     *
     * @var array
     */
    protected $mediaDimensions = [];

    /**
     * Process data for a gallery, for instance the CType "textmedia"
     *
     * @param ContentObjectRenderer $cObj The content object renderer, which contains data of the content element
     * @param array $contentObjectConfiguration The configuration of Content Object
     * @param array $processorConfiguration The configuration of this processor
     * @param array $processedData Key/value store of processed data (e.g. to be passed to a Fluid View)
     * @return array the processed data as key/value store
     * @throws ContentRenderingException
     */
    public function process(
        ContentObjectRenderer $cObj,
        array $contentObjectConfiguration,
        array $processorConfiguration,
        array $processedData
    ) {
        if (isset($processorConfiguration['if.']) && !$cObj->checkIf($processorConfiguration['if.'])) {
            return $processedData;
        }

        $this->contentObjectRenderer = $cObj;
        $this->processorConfiguration = $processorConfiguration;

        $filesProcessedDataKey = (string)$cObj->stdWrapValue(
            'filesProcessedDataKey',
            $processorConfiguration,
            'files'
        );
        if (isset($processedData[$filesProcessedDataKey]) && is_array($processedData[$filesProcessedDataKey])) {
            $this->fileObjects = $processedData[$filesProcessedDataKey];
            $this->galleryData['count']['files'] = count($this->fileObjects);
        } else {
            throw new ContentRenderingException('No files found for key ' . $filesProcessedDataKey . ' in $processedData.', 1436809789);
        }

        $this->numberOfColumns = (int)$this->getConfigurationValue('numberOfColumns', 'imagecols');
        $this->mediaOrientation = (int)$this->getConfigurationValue('mediaOrientation', 'imageorient');
        $this->maxGalleryWidth = (int)$this->getConfigurationValue('maxGalleryWidth') ?: 600;
        $this->maxGalleryWidthInText = (int)$this->getConfigurationValue('maxGalleryWidthInText') ?: 300;
        $this->equalMediaHeight = (int)$this->getConfigurationValue('equalMediaHeight', 'imageheight');
        $this->equalMediaWidth = (int)$this->getConfigurationValue('equalMediaWidth', 'imagewidth');
        $this->columnSpacing = (int)$this->getConfigurationValue('columnSpacing');
        $this->borderEnabled = (bool)$this->getConfigurationValue('borderEnabled', 'imageborder');
        $this->borderWidth = (int)$this->getConfigurationValue('borderWidth');
        $this->borderPadding = (int)$this->getConfigurationValue('borderPadding');
        $this->cropVariant = $this->getConfigurationValue('cropVariant') ?: 'default';

        $this->determineGalleryPosition();
        $this->determineMaximumGalleryWidth();

        $this->calculateRowsAndColumns();
        $this->calculateMediaWidthsAndHeights();

        $this->prepareGalleryData();

        $targetFieldName = (string)$cObj->stdWrapValue(
            'as',
            $processorConfiguration,
            'gallery'
        );

        $processedData[$targetFieldName] = $this->galleryData;

        return $processedData;
    }

    /**
     * Get configuration value from processorConfiguration
     * with when $dataArrayKey fallback to value from cObj->data array
     *
     * @param string $key
     * @param string|null $dataArrayKey
     * @return string
     */
    protected function getConfigurationValue($key, $dataArrayKey = null)
    {
        $defaultValue = '';
        if ($dataArrayKey && isset($this->contentObjectRenderer->data[$dataArrayKey])) {
            $defaultValue = $this->contentObjectRenderer->data[$dataArrayKey];
        }
        return $this->contentObjectRenderer->stdWrapValue(
            $key,
            $this->processorConfiguration,
            $defaultValue
        );
    }

    /**
     * Define the gallery position
     *
     * Gallery has a horizontal and a vertical position towards the text
     * and a possible wrapping of the text around the gallery.
     */
    protected function determineGalleryPosition()
    {
        foreach ($this->availableGalleryPositions as $positionDirectionKey => $positionDirectionValue) {
            foreach ($positionDirectionValue as $positionKey => $positionArray) {
                if (in_array($this->mediaOrientation, $positionArray, true)) {
                    $this->galleryData['position'][$positionDirectionKey] = $positionKey;
                }
            }
        }

        if ($this->mediaOrientation === 25 || $this->mediaOrientation === 26) {
            $this->galleryData['position']['noWrap'] = true;
        }
    }

    /**
     * Get the gallery width based on vertical position
     */
    protected function determineMaximumGalleryWidth()
    {
        if ($this->galleryData['position']['vertical'] === 'intext') {
            $this->galleryData['width'] = $this->maxGalleryWidthInText;
        } else {
            $this->galleryData['width'] = $this->maxGalleryWidth;
        }
    }

    /**
     * Calculate the amount of rows and columns
     */
    protected function calculateRowsAndColumns()
    {

        // If no columns defined, set it to 1
        $columns = max((int)$this->numberOfColumns, 1);

        // When more columns than media elements, set the columns to the amount of media elements
        if ($columns > $this->galleryData['count']['files']) {
            $columns = $this->galleryData['count']['files'];
        }

        if ($columns === 0) {
            $columns = 1;
        }

        // Calculate the rows from the amount of files and the columns
        $rows = ceil($this->galleryData['count']['files'] / $columns);

        $this->galleryData['count']['columns'] = $columns;
        $this->galleryData['count']['rows'] = (int)$rows;
    }

    /**
     * Calculate the width/height of the media elements
     *
     * Based on the width of the gallery, defined equal width or height by a user, the spacing between columns and
     * the use of a border, defined by user, where the border width and padding are taken into account
     *
     * File objects MUST already be filtered. They need a height and width to be shown in the gallery
     */
    protected function calculateMediaWidthsAndHeights()
    {
        $columnSpacingTotal = ($this->galleryData['count']['columns'] - 1) * $this->columnSpacing;

        $galleryWidthMinusBorderAndSpacing = max($this->galleryData['width'] - $columnSpacingTotal, 1);

        if ($this->borderEnabled) {
            $borderPaddingTotal = ($this->galleryData['count']['columns'] * 2) * $this->borderPadding;
            $borderWidthTotal = ($this->galleryData['count']['columns'] * 2) * $this->borderWidth;
            $galleryWidthMinusBorderAndSpacing = $galleryWidthMinusBorderAndSpacing - $borderPaddingTotal - $borderWidthTotal;
        }

        // User entered a predefined height
        if ($this->equalMediaHeight) {
            $mediaScalingCorrection = 1;
            $maximumRowWidth = 0;

            // Calculate the scaling correction when the total of media elements is wider than the gallery width
            for ($row = 1; $row <= $this->galleryData['count']['rows']; $row++) {
                $totalRowWidth = 0;
                for ($column = 1; $column <= $this->galleryData['count']['columns']; $column++) {
                    $fileKey = (($row - 1) * $this->galleryData['count']['columns']) + $column - 1;
                    if ($fileKey > $this->galleryData['count']['files'] - 1) {
                        break 2;
                    }
                    $currentMediaScaling = $this->equalMediaHeight / max($this->getCroppedDimensionalProperty($this->fileObjects[$fileKey], 'height'), 1);
                    $totalRowWidth += $this->getCroppedDimensionalProperty($this->fileObjects[$fileKey], 'width') * $currentMediaScaling;
                }
                $maximumRowWidth = max($totalRowWidth, $maximumRowWidth);
                $mediaInRowScaling = $totalRowWidth / $galleryWidthMinusBorderAndSpacing;
                $mediaScalingCorrection = max($mediaInRowScaling, $mediaScalingCorrection);
            }

            // Set the corrected dimensions for each media element
            foreach ($this->fileObjects as $key => $fileObject) {
                $mediaHeight = floor($this->equalMediaHeight / $mediaScalingCorrection);
                $mediaWidth = floor(
                    $this->getCroppedDimensionalProperty($fileObject, 'width') * ($mediaHeight / max($this->getCroppedDimensionalProperty($fileObject, 'height'), 1))
                );
                $this->mediaDimensions[$key] = [
                    'width' => $mediaWidth,
                    'height' => $mediaHeight
                ];
            }

            // Recalculate gallery width
            $this->galleryData['width'] = floor($maximumRowWidth / $mediaScalingCorrection);

        // User entered a predefined width
        } elseif ($this->equalMediaWidth) {
            $mediaScalingCorrection = 1;

            // Calculate the scaling correction when the total of media elements is wider than the gallery width
            $totalRowWidth = $this->galleryData['count']['columns'] * $this->equalMediaWidth;
            $mediaInRowScaling = $totalRowWidth / $galleryWidthMinusBorderAndSpacing;
            $mediaScalingCorrection = max($mediaInRowScaling, $mediaScalingCorrection);

            // Set the corrected dimensions for each media element
            foreach ($this->fileObjects as $key => $fileObject) {
                $mediaWidth = floor($this->equalMediaWidth / $mediaScalingCorrection);
                $mediaHeight = floor(
                    $this->getCroppedDimensionalProperty($fileObject, 'height') * ($mediaWidth / max($this->getCroppedDimensionalProperty($fileObject, 'width'), 1))
                );
                $this->mediaDimensions[$key] = [
                    'width' => $mediaWidth,
                    'height' => $mediaHeight
                ];
            }

            // Recalculate gallery width
            $this->galleryData['width'] = floor($totalRowWidth / $mediaScalingCorrection);

        // Automatic setting of width and height
        } else {
            $maxMediaWidth = (int)($galleryWidthMinusBorderAndSpacing / $this->galleryData['count']['columns']);
            foreach ($this->fileObjects as $key => $fileObject) {
                $croppedWidth = $this->getCroppedDimensionalProperty($fileObject, 'width');
                $mediaWidth = $croppedWidth > 0 ? min($maxMediaWidth, $croppedWidth) : $maxMediaWidth;
                $mediaHeight = floor(
                    $this->getCroppedDimensionalProperty($fileObject, 'height') * ($mediaWidth / max($this->getCroppedDimensionalProperty($fileObject, 'width'), 1))
                );
                $this->mediaDimensions[$key] = [
                    'width' => $mediaWidth,
                    'height' => $mediaHeight
                ];
            }
        }
    }

    /**
     * When retrieving the height or width for a media file
     * a possible cropping needs to be taken into account.
     *
     * @param FileInterface $fileObject
     * @param string $dimensionalProperty 'width' or 'height'
     *
     * @return int
     */
    protected function getCroppedDimensionalProperty(FileInterface $fileObject, $dimensionalProperty)
    {
        if (!$fileObject->hasProperty('crop') || empty($fileObject->getProperty('crop'))) {
            return $fileObject->getProperty($dimensionalProperty);
        }

        $croppingConfiguration = $fileObject->getProperty('crop');
        $cropVariantCollection = CropVariantCollection::create((string)$croppingConfiguration);
        return (int)$cropVariantCollection->getCropArea($this->cropVariant)->makeAbsoluteBasedOnFile($fileObject)->asArray()[$dimensionalProperty];
    }

    /**
     * Prepare the gallery data
     *
     * Make an array for rows, columns and configuration
     */
    protected function prepareGalleryData()
    {
        for ($row = 1; $row <= $this->galleryData['count']['rows']; $row++) {
            for ($column = 1; $column <= $this->galleryData['count']['columns']; $column++) {
                $fileKey = (($row - 1) * $this->galleryData['count']['columns']) + $column - 1;

                $this->galleryData['rows'][$row]['columns'][$column] = [
                    'media' => $this->fileObjects[$fileKey] ?? null,
                    'dimensions' => [
                        'width' => $this->mediaDimensions[$fileKey]['width'] ?? null,
                        'height' => $this->mediaDimensions[$fileKey]['height'] ?? null
                    ]
                ];
            }
        }

        $this->galleryData['columnSpacing'] = $this->columnSpacing;
        $this->galleryData['border']['enabled'] = $this->borderEnabled;
        $this->galleryData['border']['width'] = $this->borderWidth;
        $this->galleryData['border']['padding'] = $this->borderPadding;
    }
}
