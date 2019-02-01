<?php
namespace TYPO3\CMS\Backend\ViewHelpers;

/*                                                                        *
 * This script is part of the TYPO3 project - inspiring people to share!  *
 *                                                                        *
 * TYPO3 is free software; you can redistribute it and/or modify it under *
 * the terms of the GNU General Public License version 2 as published by  *
 * the Free Software Foundation.                                          *
 *                                                                        *
 * This script is distributed in the hope that it will be useful, but     *
 * WITHOUT ANY WARRANTY; without even the implied warranty of MERCHAN-    *
 * TABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the GNU General      *
 * Public License for more details.                                       *
 *                                                                        */

use TYPO3\CMS\Backend\Utility\BackendUtility;
use TYPO3\CMS\Core\Imaging\ImageManipulation\CropVariantCollection;
use TYPO3\CMS\Core\Resource\Exception\ResourceDoesNotExistException;
use TYPO3\CMS\Core\Resource\ProcessedFile;
use TYPO3\CMS\Fluid\ViewHelpers\ImageViewHelper;
use TYPO3Fluid\Fluid\Core\ViewHelper\Exception;

/**
 * Class ThumbnailViewHelper
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
            if (!empty($this->arguments['context'])) {
                $processingInstructions['_context'] = $this->arguments['context'];
            }
            if (!$cropArea->isEmpty()) {
                $processingInstructions['crop'] = $cropArea->makeAbsoluteBasedOnFile($image);
            }
            foreach (['width', 'height', 'minWidth', 'minHeight', 'maxWidth', 'maxHeight'] as $argument) {
                if (!empty($this->arguments[$argument])) {
                    $processingInstructions[$argument] = $this->arguments[$argument];
                }
            }
            $imageUri = BackendUtility::getThumbnailUrl($image->getUid(), $processingInstructions);

            if (!$this->tag->hasAttribute('data-focus-area')) {
                $focusArea = $cropVariantCollection->getFocusArea($cropVariant);
                if (!$focusArea->isEmpty()) {
                    $this->tag->addAttribute('data-focus-area', $focusArea->makeAbsoluteBasedOnFile($image));
                }
            }
            $this->tag->addAttribute('src', $imageUri);
            $this->tag->addAttribute('width', $this->arguments['maxWidth'] ?? $this->arguments['width']);

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
