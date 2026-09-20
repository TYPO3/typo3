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

namespace TYPO3\CMS\Fluid\ViewHelpers\Uri;

use Psr\Http\Message\RequestInterface;
use Psr\Http\Message\ServerRequestInterface;
use TYPO3\CMS\Core\Imaging\ImageDimension;
use TYPO3\CMS\Core\Imaging\ImageManipulation\CropVariantCollection;
use TYPO3\CMS\Core\Resource\Exception\ResourceDoesNotExistException;
use TYPO3\CMS\Core\Resource\File;
use TYPO3\CMS\Core\Resource\FileInterface;
use TYPO3\CMS\Core\Resource\FileReference;
use TYPO3\CMS\Core\Resource\ImageSourceResolver;
use TYPO3\CMS\Core\Resource\ProcessableFileInterface;
use TYPO3\CMS\Core\Resource\ProcessedFile;
use TYPO3\CMS\Core\SystemResource\Exception\SystemResourceException;
use TYPO3\CMS\Core\SystemResource\Type\SystemResourceInterface;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Frontend\ContentObject\ContentObjectRenderer;
use TYPO3Fluid\Fluid\Core\Rendering\RenderingContextInterface;
use TYPO3Fluid\Fluid\Core\ViewHelper\AbstractViewHelper;
use TYPO3Fluid\Fluid\Core\ViewHelper\Exception;
use TYPO3Fluid\Fluid\Core\ViewHelper\InvalidArgumentValueException;

/**
 * ViewHelper to resize, crop or convert a given image (if required) and return
 * the URL to this processed file.
 *
 * This ViewHelper works for images within FAL storages, as well as system
 * resources shipped with an extension and referenced via an EXT:/PKG: identifier.
 * A system resource has none of FAL's stored metadata (crop, title, ...), so it
 * only ever uses this ViewHelper's own arguments for that.
 *
 * For an extension resource that should not go through image processing at all,
 * use `<f:uri.resource>` instead.
 *
 * External URLs are not processed and just returned as is.
 *
 * ```
 *   <f:uri.image src="{variableWithFileadminLocation}" width="100c" />
 *   <f:uri.image src="EXT:myext/Resources/Public/typo3_logo.png" width="100c" />
 *   <f:uri.image image="{imageObject}" maxWidth="400" maxHeight="400" fileExtension="webp" />
 * ```
 *
 * @see https://docs.typo3.org/permalink/t3viewhelper:typo3-fluid-uri-image
 * @see https://docs.typo3.org/permalink/t3viewhelper:typo3-fluid-uri-resource
 */
final class ImageViewHelper extends AbstractViewHelper
{
    public function __construct(
        private readonly ImageSourceResolver $imageSourceResolver
    ) {}

    public function initializeArguments(): void
    {
        $this->registerArgument('src', 'string', 'src', false, '');
        $this->registerArgument('treatIdAsReference', 'bool', 'given src argument is a sys_file_reference record', false, false);
        $this->registerArgument('image', 'object', 'a FAL object (\\TYPO3\\CMS\\Core\\Resource\\File or \\TYPO3\\CMS\\Core\\Resource\\FileReference) or a public system resource, as returned by <f:resource>');
        $this->registerArgument('crop', 'string|bool|array', 'overrule cropping of image (setting to FALSE disables the cropping set in FileReference)');
        $this->registerArgument('cropVariant', 'string', 'select a cropping variant, in case multiple croppings have been specified or stored in FileReference', false, 'default');
        $this->registerArgument('fileExtension', 'string', 'Custom file extension to use');

        $this->registerArgument('width', 'string', 'width of the image. This can be a numeric value representing the fixed width of the image in pixels. But you can also perform simple calculations by adding "m" or "c" to the value. See imgResource.width for possible options.');
        $this->registerArgument('height', 'string', 'height of the image. This can be a numeric value representing the fixed height of the image in pixels. But you can also perform simple calculations by adding "m" or "c" to the value. See imgResource.width for possible options.');
        $this->registerArgument('minWidth', 'int', 'minimum width of the image');
        $this->registerArgument('minHeight', 'int', 'minimum height of the image');
        $this->registerArgument('maxWidth', 'int', 'maximum width of the image');
        $this->registerArgument('maxHeight', 'int', 'maximum height of the image');
        $this->registerArgument('absolute', 'bool', 'Force absolute URL', false, false);
        $this->registerArgument('base64', 'bool', 'Return a base64 encoded version of the image', false, false);
    }

    /**
     * Resizes the image (if required) and returns its path. If the image was not resized, the path will be equal to $src
     */
    public function render(): string
    {
        $src = (string)$this->arguments['src'];
        $image = $this->arguments['image'];
        $treatIdAsReference = (bool)$this->arguments['treatIdAsReference'];
        $cropString = $this->arguments['crop'];
        $absolute = $this->arguments['absolute'];
        if (($src === '' && $image === null) || ($src !== '' && $image !== null)) {
            throw new InvalidArgumentValueException(self::getExceptionMessage('You must either specify a string src or a File object.', $this->renderingContext), 1460976233);
        }
        if ((string)$this->arguments['fileExtension'] && !GeneralUtility::inList($GLOBALS['TYPO3_CONF_VARS']['GFX']['imagefile_ext'], (string)$this->arguments['fileExtension'])) {
            throw new InvalidArgumentValueException(
                self::getExceptionMessage(
                    'The extension ' . $this->arguments['fileExtension'] . ' is not specified in $GLOBALS[\'TYPO3_CONF_VARS\'][\'GFX\'][\'imagefile_ext\']'
                    . ' as a valid image file extension and can not be processed.',
                    $this->renderingContext
                ),
                1618992262
            );
        }
        try {
            $image = $this->imageSourceResolver->resolve($image ?? $src, $treatIdAsReference);

            // A resource outside the File Abstraction Layer has none of FAL's stored
            // metadata (crop) - there is nothing to fall back to, the ViewHelper's own
            // arguments are the only source for it.
            if ($cropString === null && $image instanceof FileInterface && $image->hasProperty('crop') && $image->getProperty('crop')) {
                $cropString = $image->getProperty('crop');
            }

            // CropVariantCollection needs a string, but this VH could also receive an array
            if (is_array($cropString)) {
                $cropString = json_encode($cropString);
            }

            $cropVariantCollection = CropVariantCollection::create((string)$cropString);
            $cropVariant = $this->arguments['cropVariant'] ?: 'default';
            $cropArea = $cropVariantCollection->getCropArea($cropVariant);
            $processingInstructions = [
                'width' => $this->arguments['width'],
                'height' => $this->arguments['height'],
                'minWidth' => $this->arguments['minWidth'],
                'minHeight' => $this->arguments['minHeight'],
                'maxWidth' => $this->arguments['maxWidth'],
                'maxHeight' => $this->arguments['maxHeight'],
                'crop' => $cropArea->isEmpty() ? null : (
                    $image instanceof FileInterface
                        ? $cropArea->makeAbsoluteBasedOnFile($image)
                        : $cropArea->makeAbsoluteBasedOnDimension($this->getSystemResourceImageDimension($image))
                ),
            ];
            if (!empty($this->arguments['fileExtension'])) {
                $processingInstructions['fileExtension'] = $this->arguments['fileExtension'];
            }

            $processedImage = $image->process(ProcessedFile::CONTEXT_IMAGECROPSCALEMASK, $processingInstructions);

            if ($this->arguments['base64']) {
                return 'data:' . $processedImage->getMimeType() . ';base64,' . base64_encode($processedImage->getContents());
            }
            $imageUri = (string)$processedImage->getPublicUrl();
            $request = $this->renderingContext->hasAttribute(ServerRequestInterface::class)
                ? $this->renderingContext->getAttribute(ServerRequestInterface::class)
                : ($GLOBALS['TYPO3_REQUEST'] ?? null);
            if ($imageUri !== '' && $absolute && $request instanceof ServerRequestInterface) {
                $imageUri = GeneralUtility::locationHeaderUrl($imageUri, $request);
            }
            return $imageUri;
        } catch (ResourceDoesNotExistException $e) {
            // thrown if file does not exist
            throw new Exception(self::getExceptionMessage($e->getMessage(), $this->renderingContext), 1509741907, $e);
        } catch (\UnexpectedValueException $e) {
            // thrown if a file has been replaced with a folder
            throw new Exception(self::getExceptionMessage($e->getMessage(), $this->renderingContext), 1509741908, $e);
        } catch (\InvalidArgumentException $e) {
            // thrown if file storage does not exist
            throw new Exception(self::getExceptionMessage($e->getMessage(), $this->renderingContext), 1509741910, $e);
        } catch (SystemResourceException $e) {
            // thrown if an EXT:/PKG: system resource does not exist or is not public
            throw new Exception(self::getExceptionMessage($e->getMessage(), $this->renderingContext), 1789915802, $e);
        }
    }

    /**
     * $image is never a FileInterface at this point (that branch is handled
     * separately) - the only other concrete ProcessableFileInterface implementor is a
     * system resource, which is also a SystemResourceInterface and therefore has real
     * dimensions to report.
     */
    private function getSystemResourceImageDimension(ProcessableFileInterface $image): ImageDimension
    {
        if (!$image instanceof SystemResourceInterface) {
            throw new \LogicException(sprintf('%s can only process a FAL File/FileReference or a system resource, %s given.', self::class, $image instanceof File || $image instanceof FileReference ? 'FAL file "' . $image->getCombinedIdentifier() . '"' : get_debug_type($image)), 1789915804);
        }
        return $image->getImageDimension();
    }

    private static function getExceptionMessage(string $detailedMessage, RenderingContextInterface $renderingContext): string
    {
        $request = null;
        if ($renderingContext->hasAttribute(ServerRequestInterface::class)) {
            $request = $renderingContext->getAttribute(ServerRequestInterface::class);
        }
        if ($request instanceof RequestInterface) {
            $currentContentObject = $request->getAttribute('currentContentObject');
            if ($currentContentObject instanceof ContentObjectRenderer) {
                return sprintf('Unable to render image URI in "%s": %s', $currentContentObject->currentRecord, $detailedMessage);
            }
        }
        return sprintf('Unable to render image URI: %s', $detailedMessage);
    }
}
