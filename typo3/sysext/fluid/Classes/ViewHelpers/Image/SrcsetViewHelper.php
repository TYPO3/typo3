<?php

declare(strict_types=1);

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

namespace TYPO3\CMS\Fluid\ViewHelpers\Image;

use Psr\Http\Message\ServerRequestInterface;
use TYPO3\CMS\Core\Html\Srcset\SrcsetAttribute;
use TYPO3\CMS\Core\Html\Srcset\WidthSrcsetCandidate;
use TYPO3\CMS\Core\Imaging\ImageManipulation\Area;
use TYPO3\CMS\Core\Imaging\ImageManipulation\CropVariantCollection;
use TYPO3\CMS\Core\Resource\File;
use TYPO3\CMS\Core\Resource\FileInterface;
use TYPO3\CMS\Core\Resource\FileReference;
use TYPO3\CMS\Core\Resource\ProcessedFile;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Extbase\Domain\Model\File as ExtbaseFile;
use TYPO3\CMS\Extbase\Domain\Model\FileReference as ExtbaseFileReference;
use TYPO3Fluid\Fluid\Core\ViewHelper\AbstractViewHelper;
use TYPO3Fluid\Fluid\Core\ViewHelper\InvalidArgumentValueException;

/**
 * ViewHelper to generate a list of image URLs and their corresponding srcset
 * descriptors to be used in a srcset attribute of an <img> or <source> tag.
 *
 * Responsive images can either be defined based on absolute widths or relative pixel densities.
 *
 * Width descriptors use the "w" unit, which refers to the width of the image file (not to be
 * confused with "px", which refers to so-called CSS pixels in the HTML document). The srcset
 * attribute in combination with the sizes attribute provide hints to the browser which one
 * of the provided image files should be used. The browser is free to consider other factors,
 * such as network speed, user preferences, or client-side caching status.
 *
 * Density descriptors use the "x" unit and target client devices based on the pixel density
 * of their screens. "1x" will be loaded for low-density devices, while "2x", "3x"... target
 * higher densities, also referred to as "High DPI" or "Retina".
 *
 * According to the HTML standard, "w" and "x" units cannot be mixed. Instead, browsers will
 * usually consider the pixel density, even if "w" units are used. In practice, "x" is only
 * relevant for fixed-width images across device sizes (e. g. an icon that always has the same
 * visual size). In all other cases, "w" should be preferred.
 *
 * Examples
 * ========
 *
 * Width descriptors
 * -----------------
 *
 * ```
 *    <source srcset="{f:image.srcset(image: imageObject, srcset: '1000w, 1200w, 1400w', cropVariant: 'desktop')}" media="(min-width: 1000px)" sizes="100vw" />
 * ```
 *
 * Output::
 *
 * ```
 *    <source srcset="/path/to/csm_myimage_1000.jpg 1000w, /path/to/csm_myimage_1200.jpg 1200w, /path/to/csm_myimage_1400.jpg 1400w" media="(min-width: 1000px)" sizes="100vw" />
 * ```
 *
 * Density descriptors
 * -------------------
 *
 *    <source srcset="{f:image.srcset(image: imageObject, srcset: '1x, 2x', referenceWidth: 500, cropVariant: 'desktop')}" media="(min-width: 1000px)" />
 *
 * Output::
 *
 *    <source srcset="/path/to/csm_myimage_500.jpg 1x, /path/to/csm_myimage_1000.jpg 2x" media="(min-width: 1000px)" />
 *
 * @see https://docs.typo3.org/permalink/t3viewhelper:typo3-fluid-image-srcset
 */
final class SrcsetViewHelper extends AbstractViewHelper
{
    public function initializeArguments(): void
    {
        $this->registerArgument('image', FileInterface::class . '|' . ExtbaseFile::class . '|' . ExtbaseFileReference::class, 'A FAL object (\\TYPO3\\CMS\\Core\\Resource\\File or \\TYPO3\\CMS\\Core\\Resource\\FileReference); if not specified, ViewHelper children will be used as a fallback.');
        $this->registerArgument('srcset', 'string', 'Comma-separated list of width descriptors (e. g. 200w) or pixel density descriptors (e. g. 2x).', true);

        $this->registerArgument('referenceWidth', 'int', 'Image width that will be used as base (1x) when calculating srcset with pixel density descriptors (e. g. 2x). This is irrelevant for width descriptors.');

        $this->registerArgument('crop', 'string|bool|array', 'Overrule cropping of image (setting to FALSE disables the cropping set in FileReference)');
        $this->registerArgument('cropVariant', 'string', 'Select a cropping variant, in case multiple croppings have been specified or stored in FileReference', false, 'default');
        $this->registerArgument('fileExtension', 'string', 'Use the specified target file extension for generated images; files will be converted if necessary');

        $this->registerArgument('absolute', 'bool', 'Force absolute URL for generated images', false, false);
    }

    public function render(): SrcsetAttribute
    {
        $image = $this->arguments['image'] ?? $this->renderChildren();
        if ($image instanceof ExtbaseFile || $image instanceof ExtbaseFileReference) {
            $image = $image->getOriginalResource();
        }
        if (!$image instanceof File && !$image instanceof FileReference) {
            throw new InvalidArgumentValueException('A valid file object must be specified.', 1697797783);
        }

        $fileExtension = $this->validateFileExtension($this->arguments['fileExtension']);
        $cropArea = $this->getCropAreaFromArguments($image, $this->arguments['crop'], $this->arguments['cropVariant']);
        $cropArea = $cropArea->isEmpty() ? null : $cropArea->makeAbsoluteBasedOnFile($image);

        $srcsetAsArray = GeneralUtility::trimExplode(',', $this->arguments['srcset'], true);
        try {
            $srcset = SrcsetAttribute::createFromDescriptors($srcsetAsArray, $this->arguments['referenceWidth']);
        } catch (\Exception $e) {
            throw new InvalidArgumentValueException('Invalid srcset configuration provided: ' . $e->getMessage(), 1774530722, $e);
        }
        $request = $this->renderingContext->hasAttribute(ServerRequestInterface::class)
            ? $this->renderingContext->getAttribute(ServerRequestInterface::class)
            : ($GLOBALS['TYPO3_REQUEST'] ?? null);
        foreach ($srcset->getCandidates() as $candidate) {
            $processedImage = $image->process(ProcessedFile::CONTEXT_IMAGECROPSCALEMASK, [
                'width' => $candidate->getCalculatedWidth(),
                'crop' => $cropArea,
                ...($fileExtension ? ['fileExtension' => $fileExtension] : []),
            ]);

            // If processor_allowUpscaling is set to false and a bigger image than the original was requested,
            // the srcset string should still offer the maximum image size available as a fallback, even if this
            // diverts from the specific configuration. In this case, width descriptors need to be updated to
            // match the actual width of the generated image file. This might lead to duplicate files in the first
            // place, but descriptors are used as array keys, so they won't appear as duplicates in the markup
            if ($candidate instanceof WidthSrcsetCandidate && $processedImage->getProperty('width') !== $candidate->getCalculatedWidth()) {
                $candidate->setWidth($processedImage->getProperty('width'));
            }

            $imageUri = (string)$processedImage->getPublicUrl();
            if ($imageUri !== '' && $this->arguments['absolute'] && $request instanceof ServerRequestInterface) {
                $imageUri = GeneralUtility::locationHeaderUrl($imageUri, $request);
            }
            $candidate->setUri($imageUri);
        }

        return $srcset;
    }

    protected function getCropAreaFromArguments(FileInterface $image, $crop, string $cropVariant): Area
    {
        if ($crop === null && $image->hasProperty('crop') && $image->getProperty('crop')) {
            $crop = $image->getProperty('crop');
        }
        $cropVariantsCollection = CropVariantCollection::create(is_array($crop) ? json_encode($crop) : (string)$crop);
        return $cropVariantsCollection->getCropArea($cropVariant);
    }

    protected function validateFileExtension(?string $fileExtension): ?string
    {
        if ($fileExtension === null) {
            return null;
        }
        if (!GeneralUtility::inList($GLOBALS['TYPO3_CONF_VARS']['GFX']['imagefile_ext'], $fileExtension)) {
            throw new InvalidArgumentValueException(
                'The extension ' . $fileExtension . ' is not specified in $GLOBALS[\'TYPO3_CONF_VARS\'][\'GFX\'][\'imagefile_ext\']'
                . ' as a valid image file extension and can not be processed.',
                1697797923
            );
        }
        return $fileExtension;
    }
}
