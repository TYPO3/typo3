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

namespace TYPO3\CMS\Backend\ViewHelpers;

use TYPO3\CMS\Core\Imaging\ImageManipulation\CropVariantCollection;
use TYPO3\CMS\Core\Resource\Exception\ResourceDoesNotExistException;
use TYPO3\CMS\Core\Resource\ProcessedFile;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Extbase\Service\ImageService;
use TYPO3Fluid\Fluid\Core\ViewHelper\AbstractTagBasedViewHelper;
use TYPO3Fluid\Fluid\Core\ViewHelper\Exception;

/**
 * ViewHelper for the backend which generates an :html:`<img>` tag with the special URI to render thumbnails deferred.
 *
 * Examples
 * ========
 *
 * Default
 * -------
 *
 * ::
 *
 *    <be:thumbnail image="{file.resource}" width="{thumbnail.width}" height="{thumbnail.height}" />
 *
 * Output::
 *
 *    <img src="/typo3/thumbnails?token=&parameters={"fileId":1,"configuration":{"_context":"Image.Preview","maxWidth":64,"maxHeight":64}}&hmac="
 *         width="64"
 *         height="64"
 *         alt="alt set in image record"
 *         title="title set in image record"/>
 *
 * Inline notation
 * ---------------
 *
 * ::
 *
 *    {be:thumbnail(image: file.resource, maxWidth: thumbnail.width, maxHeight: thumbnail.height)}
 *
 * Output::
 *
 *    <img src="/typo3/thumbnails?token=&parameters={"fileId":1,"configuration":{"_context":"Image.Preview","maxWidth":64,"maxHeight":64}}&hmac="
 *         width="64"
 *         height="64"
 *         alt="alt set in image record"
 *         title="title set in image record"/>
 */
final class ThumbnailViewHelper extends AbstractTagBasedViewHelper
{
    /**
     * @var string
     */
    protected $tagName = 'img';

    protected ImageService $imageService;

    public function __construct()
    {
        parent::__construct();
        $this->imageService = GeneralUtility::makeInstance(ImageService::class);
    }

    public function initializeArguments(): void
    {
        parent::initializeArguments();
        $this->registerUniversalTagAttributes();
        $this->registerTagAttribute('alt', 'string', 'Specifies an alternate text for an image', false);
        $this->registerTagAttribute('ismap', 'string', 'Specifies an image as a server-side image-map. Rarely used. Look at usemap instead', false);
        $this->registerTagAttribute('usemap', 'string', 'Specifies an image as a client-side image-map', false);
        $this->registerTagAttribute('loading', 'string', 'Native lazy-loading for images property. Can be "lazy", "eager" or "auto"', false);
        $this->registerTagAttribute('decoding', 'string', 'Provides an image decoding hint to the browser. Can be "sync", "async" or "auto"', false);

        // @todo: Probably not all of these are needed for thumbnails. That's a left over from ImageViewHelper
        $this->registerArgument('src', 'string', 'a path to a file, a combined FAL identifier or an uid (int). If $treatIdAsReference is set, the integer is considered the uid of the sys_file_reference record. If you already got a FAL object, consider using the $image parameter instead', false, '');
        $this->registerArgument('treatIdAsReference', 'bool', 'given src argument is a sys_file_reference record', false, false);
        $this->registerArgument('image', 'object', 'a FAL object (\\TYPO3\\CMS\\Core\\Resource\\File or \\TYPO3\\CMS\\Core\\Resource\\FileReference)');
        $this->registerArgument('crop', 'string|bool', 'overrule cropping of image (setting to FALSE disables the cropping set in FileReference)');
        $this->registerArgument('cropVariant', 'string', 'select a cropping variant, in case multiple croppings have been specified or stored in FileReference', false, 'default');
        $this->registerArgument('fileExtension', 'string', 'Custom file extension to use');

        $this->registerArgument('width', 'string', 'width of the image. This can be a numeric value representing the fixed width of the image in pixels. But you can also perform simple calculations by adding "m" or "c" to the value. See imgResource.width for possible options.');
        $this->registerArgument('height', 'string', 'height of the image. This can be a numeric value representing the fixed height of the image in pixels. But you can also perform simple calculations by adding "m" or "c" to the value. See imgResource.width for possible options.');
        $this->registerArgument('minWidth', 'int', 'minimum width of the image');
        $this->registerArgument('minHeight', 'int', 'minimum height of the image');
        $this->registerArgument('maxWidth', 'int', 'maximum width of the image');
        $this->registerArgument('maxHeight', 'int', 'maximum height of the image');
        $this->registerArgument('absolute', 'bool', 'Force absolute URL', false, false);
        $this->registerArgument('context', 'string', 'context for image rendering', false, ProcessedFile::CONTEXT_IMAGEPREVIEW);
    }

    /**
     * @throws Exception
     */
    public function render(): string
    {
        if (($this->arguments['src'] === '' && $this->arguments['image'] === null) || ($this->arguments['src'] !== '' && $this->arguments['image'] !== null)) {
            throw new Exception('You must either specify a string src or a File object.', 1533290762);
        }

        try {
            $image = $this->imageService->getImage((string)$this->arguments['src'], $this->arguments['image'], (bool)$this->arguments['treatIdAsReference']);

            $cropString = $this->arguments['crop'];
            if ($cropString === null && $image->hasProperty('crop') && $image->getProperty('crop')) {
                $cropString = $image->getProperty('crop');
            }
            $cropVariantCollection = CropVariantCollection::create((string)$cropString);
            $cropVariant = $this->arguments['cropVariant'] ?: 'default';
            $cropArea = $cropVariantCollection->getCropArea($cropVariant);
            $processingInstructions = [];
            if (!$cropArea->isEmpty()) {
                $processingInstructions['crop'] = $cropArea->makeAbsoluteBasedOnFile($image);
            }
            foreach (['width', 'height', 'minWidth', 'minHeight', 'maxWidth', 'maxHeight'] as $argument) {
                if (!empty($this->arguments[$argument])) {
                    $processingInstructions[$argument] = $this->arguments[$argument];
                }
            }

            if (is_callable([$image, 'getOriginalFile'])) {
                // Get the original file from the file reference
                $image = $image->getOriginalFile();
            }

            $processedFile = $image->process($this->arguments['context'], $processingInstructions);
            $imageUri = $processedFile->getPublicUrl();

            if (!$this->tag->hasAttribute('data-focus-area')) {
                $focusArea = $cropVariantCollection->getFocusArea($cropVariant);
                if (!$focusArea->isEmpty()) {
                    $this->tag->addAttribute('data-focus-area', $focusArea->makeAbsoluteBasedOnFile($image));
                }
            }
            $this->tag->addAttribute('src', $imageUri);
            $this->tag->addAttribute('width', $processedFile->getProperty('width'));
            $this->tag->addAttribute('height', $processedFile->getProperty('height'));

            $alt = $image->getProperty('alternative');
            $title = $image->getProperty('title');

            // The alt-attribute is mandatory to have valid html-code, therefore add it even if it is empty
            if (empty($this->arguments['alt'])) {
                $this->tag->addAttribute('alt', $alt);
            }
            if (empty($this->arguments['title']) && $title) {
                $this->tag->addAttribute('title', $title);
            }
        } catch (ResourceDoesNotExistException $e) {
            // thrown if file does not exist
            throw new Exception($e->getMessage(), 1533294109, $e);
        } catch (\UnexpectedValueException $e) {
            // thrown if a file has been replaced with a folder
            throw new Exception($e->getMessage(), 1533294113, $e);
        } catch (\RuntimeException $e) {
            // RuntimeException thrown if a file is outside of a storage
            throw new Exception($e->getMessage(), 1533294116, $e);
        } catch (\InvalidArgumentException $e) {
            // thrown if file storage does not exist
            throw new Exception($e->getMessage(), 1533294120, $e);
        }

        return $this->tag->render();
    }
}
