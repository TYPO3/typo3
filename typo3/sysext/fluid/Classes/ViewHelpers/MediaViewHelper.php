<?php
namespace TYPO3\CMS\Fluid\ViewHelpers;

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
use TYPO3\CMS\Core\Resource\FileReference;
use TYPO3\CMS\Core\Resource\Rendering\RendererRegistry;
use TYPO3\CMS\Extbase\Domain\Model\AbstractFileFolder;
use TYPO3\CMS\Extbase\Service\ImageService;
use TYPO3\CMS\Fluid\Core\ViewHelper\AbstractTagBasedViewHelper;

/**
 * Render a given media file with the correct html tag.
 *
 * It asks the RendererRegister for the correct Renderer class and if not found it falls
 * back to the ImageViewHelper as that is the "Renderer" class for images in Fluid context.
 *
 * = Examples =
 *
 * <code title="Image Object">
 *     <f:media file="{file}" width="400" height="375" />
 * </code>
 * <output>
 *     <img alt="alt set in image record" src="fileadmin/_processed_/323223424.png" width="396" height="375" />
 * </output>
 *
 * <code title="MP4 Video Object">
 *     <f:media file="{file}" width="400" height="375" />
 * </code>
 * <output>
 *     <video width="400" height="375" controls><source src="fileadmin/user_upload/my-video.mp4" type="video/mp4"></video>
 * </output>
 *
 * <code title="MP4 Video Object with loop and autoplay option set">
 *     <f:media file="{file}" width="400" height="375" additionalConfig="{loop: '1', autoplay: '1'}" />
 * </code>
 * <output>
 *     <video width="400" height="375" controls loop><source src="fileadmin/user_upload/my-video.mp4" type="video/mp4"></video>
 * </output>
 */
class MediaViewHelper extends AbstractTagBasedViewHelper
{
    /**
     * @var string
     */
    protected $tagName = 'img';

    /**
     * Initialize arguments.
     */
    public function initializeArguments()
    {
        parent::initializeArguments();
        $this->registerUniversalTagAttributes();
        $this->registerTagAttribute('alt', 'string', 'Specifies an alternate text for an image', false);
        $this->registerArgument('file', 'object', 'File', true);
        $this->registerArgument('additionalConfig', 'array', 'This array can hold additional configuration that is passed though to the Renderer object', false, []);
        $this->registerArgument('width', 'string', 'This can be a numeric value representing the fixed width of in pixels. But you can also perform simple calculations by adding "m" or "c" to the value. See imgResource.width for possible options.');
        $this->registerArgument('height', 'string', 'This can be a numeric value representing the fixed height in pixels. But you can also perform simple calculations by adding "m" or "c" to the value. See imgResource.width for possible options.');
        $this->registerArgument('cropVariant', 'string', 'select a cropping variant, in case multiple croppings have been specified or stored in FileReference', false, 'default');
    }

    /**
     * Render a given media file
     *
     * @return string Rendered tag
     * @throws \UnexpectedValueException
     */
    public function render()
    {
        $file = $this->arguments['file'];
        $additionalConfig = $this->arguments['additionalConfig'];
        $width = $this->arguments['width'];
        $height = $this->arguments['height'];

        // get Resource Object (non ExtBase version)
        if (is_callable([$file, 'getOriginalResource'])) {
            // We have a domain model, so we need to fetch the FAL resource object from there
            $file = $file->getOriginalResource();
        }

        if (!($file instanceof FileInterface || $file instanceof AbstractFileFolder)) {
            throw new \UnexpectedValueException('Supplied file object type ' . get_class($file) . ' must be FileInterface or AbstractFileFolder.', 1454252193);
        }

        $fileRenderer = RendererRegistry::getInstance()->getRenderer($file);

        // Fallback to image when no renderer is found
        if ($fileRenderer === null) {
            return $this->renderImage($file, $width, $height);
        }
        $additionalConfig = array_merge_recursive($this->arguments, $additionalConfig);
        return $fileRenderer->render($file, $width, $height, $additionalConfig);
    }

    /**
     * Render img tag
     *
     * @param FileInterface $image
     * @param string $width
     * @param string $height
     * @return string Rendered img tag
     */
    protected function renderImage(FileInterface $image, $width, $height)
    {
        $cropVariant = $this->arguments['cropVariant'] ?: 'default';
        $cropString = $image instanceof FileReference ? $image->getProperty('crop') : '';
        $cropVariantCollection = CropVariantCollection::create((string)$cropString);
        $cropArea = $cropVariantCollection->getCropArea($cropVariant);
        $processingInstructions = [
            'width' => $width,
            'height' => $height,
            'crop' => $cropArea->isEmpty() ? null : $cropArea->makeAbsoluteBasedOnFile($image),
        ];
        $imageService = $this->getImageService();
        $processedImage = $imageService->applyProcessingInstructions($image, $processingInstructions);
        $imageUri = $imageService->getImageUri($processedImage);

        if (!$this->tag->hasAttribute('data-focus-area')) {
            $focusArea = $cropVariantCollection->getFocusArea($cropVariant);
            if (!$focusArea->isEmpty()) {
                $this->tag->addAttribute('data-focus-area', $focusArea->makeAbsoluteBasedOnFile($image));
            }
        }
        $this->tag->addAttribute('src', $imageUri);
        $this->tag->addAttribute('width', $processedImage->getProperty('width'));
        $this->tag->addAttribute('height', $processedImage->getProperty('height'));

        $alt = $image->getProperty('alternative');
        $title = $image->getProperty('title');

        // The alt-attribute is mandatory to have valid html-code, therefore add it even if it is empty
        if (empty($this->arguments['alt'])) {
            $this->tag->addAttribute('alt', $alt);
        }
        if (empty($this->arguments['title']) && $title) {
            $this->tag->addAttribute('title', $title);
        }

        return $this->tag->render();
    }

    /**
     * Return an instance of ImageService
     *
     * @return ImageService
     */
    protected function getImageService()
    {
        return $this->objectManager->get(ImageService::class);
    }
}
