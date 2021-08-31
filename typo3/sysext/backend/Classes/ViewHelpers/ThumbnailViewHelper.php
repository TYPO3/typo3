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

namespace TYPO3\CMS\Backend\ViewHelpers;

use TYPO3\CMS\Core\Imaging\ImageManipulation\CropVariantCollection;
use TYPO3\CMS\Core\Resource\Exception\ResourceDoesNotExistException;
use TYPO3\CMS\Core\Resource\ProcessedFile;
use TYPO3\CMS\Fluid\ViewHelpers\ImageViewHelper;
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
class ThumbnailViewHelper extends ImageViewHelper
{

    /**
     * Initialize arguments.
     */
    public function initializeArguments()
    {
        parent::initializeArguments();
        $this->registerArgument('context', 'string', 'context for image rendering', false, ProcessedFile::CONTEXT_IMAGEPREVIEW);
    }

    /**
     * Resizes a given image (if required) and renders the respective img tag
     *
     * @see https://docs.typo3.org/typo3cms/TyposcriptReference/ContentObjects/Image/
     *
     * @throws Exception
     * @return string Rendered tag
     */
    public function render()
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
