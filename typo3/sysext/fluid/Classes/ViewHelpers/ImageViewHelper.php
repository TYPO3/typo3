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

namespace TYPO3\CMS\Fluid\ViewHelpers;

use Psr\Http\Message\RequestInterface;
use Psr\Http\Message\ServerRequestInterface;
use TYPO3\CMS\Core\Imaging\ImageManipulation\CropVariantCollection;
use TYPO3\CMS\Core\Resource\Exception\ResourceDoesNotExistException;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Extbase\Service\ImageService;
use TYPO3\CMS\Frontend\ContentObject\ContentObjectRenderer;
use TYPO3Fluid\Fluid\Core\ViewHelper\AbstractTagBasedViewHelper;
use TYPO3Fluid\Fluid\Core\ViewHelper\Exception;

/**
 * Resizes a given image (if required) and renders the respective img tag.
 *
 * Note that image operations (cropping, scaling, converting) on
 * non-FAL files (i.e. extension resources) may be changed in future TYPO3
 * versions, since those operations are coupled with FAL metadata. Each
 * non-FAL image operation creates a "fake" FAL record, which may lead to problems.
 *
 * External URLs are not processed. Only a given width and height will be set on the tag.
 *
 * Examples
 * ========
 *
 * Default
 * -------
 *
 * ::
 *
 *    <f:image src="EXT:myext/Resources/Public/typo3_logo.png" alt="alt text" />
 *
 * Output in frontend::
 *
 *    <img alt="alt text" src="typo3conf/ext/myext/Resources/Public/typo3_logo.png" width="396" height="375" />
 *
 * or in backend::
 *
 *    <img alt="alt text" src="../typo3conf/ext/viewhelpertest/Resources/Public/typo3_logo.png" width="396" height="375" />
 *
 * Image Object
 * ------------
 *
 * ::
 *
 *    <f:image image="{imageObject}" />
 *
 * Output::
 *
 *    <img alt="alt set in image record" src="fileadmin/_processed_/323223424.png" width="396" height="375" />
 *
 * Inline notation
 * ---------------
 *
 * ::
 *
 *    {f:image(src: 'EXT:viewhelpertest/Resources/Public/typo3_logo.png', alt: 'alt text', minWidth: 30, maxWidth: 40)}
 *
 * Output::
 *
 *    <img alt="alt text" src="../typo3temp/assets/images/f13d79a526.png" width="40" height="38" />
 *
 * Depending on your TYPO3s encryption key.
 *
 * Other resource type (e.g. PDF)
 * ------------------------------
 *
 * ::
 *
 *    <f:image src="fileadmin/user_upload/example.pdf" alt="foo" />
 *
 * If your graphics processing library is set up correctly then it will output a thumbnail of the first page of your PDF document:
 * ``<img src="fileadmin/_processed_/1/2/csm_example_aabbcc112233.gif" width="200" height="284" alt="foo">``
 *
 * Non-existent image
 * ------------------
 *
 * ::
 *
 *    <f:image src="NonExistingImage.png" alt="foo" />
 *
 * ``Could not get image resource for "NonExistingImage.png".``
 *
 * Base64 attribute
 * ----------------
 *
 * When the :typo3:viewhelper-argument:`base64 <typo3-cms-fluid-viewhelpers-imageviewhelper-base64>`
 * argument is set to true, the resulting image tag contains the source of the image in a base64
 * encoded form.
 *
 * ..  code-block:: html
 *
 *     <f:image base64="true"
 *              src="EXT:backend/Resources/Public/Images/typo3_logo_orange.svg"
 *              class="pr-2"
 *     />
 *
 * Will result in the according HTML tag providing the image encoded in base64.
 *
 * .. code-block:: html
 *
 *     <img class="pr-2"
 *          src="data:image/svg+xml;base64,PHN2...cuODQ4LTYuNzU3Ii8+Cjwvc3ZnPgo="
 *          alt=""
 *     >
 *
 * This can be particularly useful inside `FluidEmail` or to prevent unneeded HTTP calls.
 */
final class ImageViewHelper extends AbstractTagBasedViewHelper
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
        $this->registerArgument('src', 'string', 'a path to a file, a combined FAL identifier or an uid (int). If $treatIdAsReference is set, the integer is considered the uid of the sys_file_reference record. If you already got a FAL object, consider using the $image parameter instead', false, '');
        $this->registerArgument('treatIdAsReference', 'bool', 'given src argument is a sys_file_reference record', false, false);
        $this->registerArgument('image', 'object', 'a FAL object (\\TYPO3\\CMS\\Core\\Resource\\File or \\TYPO3\\CMS\\Core\\Resource\\FileReference)');
        $this->registerArgument('crop', 'string|bool|array', 'overrule cropping of image (setting to FALSE disables the cropping set in FileReference)');
        $this->registerArgument('cropVariant', 'string', 'select a cropping variant, in case multiple croppings have been specified or stored in FileReference', false, 'default');
        $this->registerArgument('fileExtension', 'string', 'Custom file extension to use');

        $this->registerArgument('width', 'string', 'width of the image. This can be a numeric value representing the fixed width of the image in pixels. But you can also perform simple calculations by adding "m" or "c" to the value. See imgResource.width in the TypoScript Reference on https://docs.typo3.org/permalink/t3tsref:confval-imgresource-width for possible options.');
        $this->registerArgument('height', 'string', 'height of the image. This can be a numeric value representing the fixed height of the image in pixels. But you can also perform simple calculations by adding "m" or "c" to the value. See imgResource.height in the TypoScript Reference https://docs.typo3.org/permalink/t3tsref:confval-imgresource-height for possible options.');
        $this->registerArgument('minWidth', 'int', 'minimum width of the image');
        $this->registerArgument('minHeight', 'int', 'minimum height of the image');
        $this->registerArgument('maxWidth', 'int', 'maximum width of the image');
        $this->registerArgument('maxHeight', 'int', 'maximum height of the image');
        $this->registerArgument('absolute', 'bool', 'Force absolute URL', false, false);
        $this->registerArgument('base64', 'bool', 'Adds the image data base64-encoded inline to the image‘s "src" attribute. Useful for FluidEmail templates.', false, false);
    }

    /**
     * Resizes a given image (if required) and renders the respective img tag.
     *
     * @see https://docs.typo3.org/typo3cms/TyposcriptReference/ContentObjects/Image/
     * @throws Exception
     */
    public function render(): string
    {
        $src = (string)$this->arguments['src'];
        if (($src === '' && $this->arguments['image'] === null) || ($src !== '' && $this->arguments['image'] !== null)) {
            throw new Exception($this->getExceptionMessage('You must either specify a string src or a File object.'), 1382284106);
        }

        if ((string)$this->arguments['fileExtension'] && !GeneralUtility::inList($GLOBALS['TYPO3_CONF_VARS']['GFX']['imagefile_ext'], (string)$this->arguments['fileExtension'])) {
            throw new Exception(
                $this->getExceptionMessage(
                    'The extension ' . $this->arguments['fileExtension'] . ' is not specified in $GLOBALS[\'TYPO3_CONF_VARS\'][\'GFX\'][\'imagefile_ext\']'
                    . ' as a valid image file extension and can not be processed.',
                ),
                1618989190
            );
        }

        try {
            $image = $this->imageService->getImage($src, $this->arguments['image'], (bool)$this->arguments['treatIdAsReference']);
            $cropString = $this->arguments['crop'];
            if ($cropString === null && $image->hasProperty('crop') && $image->getProperty('crop')) {
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
                'crop' => $cropArea->isEmpty() ? null : $cropArea->makeAbsoluteBasedOnFile($image),
            ];
            if (!empty($this->arguments['fileExtension'] ?? '')) {
                $processingInstructions['fileExtension'] = $this->arguments['fileExtension'];
            }
            $processedImage = $this->imageService->applyProcessingInstructions($image, $processingInstructions);

            if ($this->arguments['base64']) {
                $imageSrc = 'data:' . $processedImage->getMimeType() . ';base64,' . base64_encode($processedImage->getContents());
            } else {
                $imageSrc = $this->imageService->getImageUri($processedImage, $this->arguments['absolute']);
            }

            if (!$this->tag->hasAttribute('data-focus-area')) {
                $focusArea = $cropVariantCollection->getFocusArea($cropVariant);
                if (!$focusArea->isEmpty()) {
                    $this->tag->addAttribute('data-focus-area', (string)$focusArea->makeAbsoluteBasedOnFile($image));
                }
            }
            $this->tag->addAttribute('src', $imageSrc);
            $this->tag->addAttribute('width', $processedImage->getProperty('width'));
            $this->tag->addAttribute('height', $processedImage->getProperty('height'));

            if (isset($this->additionalArguments['alt']) && $this->additionalArguments['alt'] === '') {
                // In case the "alt" attribute is explicitly set to an empty string, respect
                // this to allow excluding it from screen readers, improving accessibility.
                $this->tag->addAttribute('alt', '');
            } elseif (!isset($this->additionalArguments['alt'])) {
                // The alt-attribute is mandatory to have valid html-code, therefore use "alternative" property or empty
                $this->tag->addAttribute('alt', $image->hasProperty('alternative') ? $image->getProperty('alternative') : '');
            }
            // Add title-attribute from property if not already set and the property is not an empty string
            $title = (string)($image->hasProperty('title') ? $image->getProperty('title') : '');
            if (empty($this->additionalArguments['title']) && $title !== '') {
                $this->tag->addAttribute('title', $title);
            }
        } catch (ResourceDoesNotExistException $e) {
            // thrown if file does not exist
            throw new Exception($this->getExceptionMessage($e->getMessage()), 1509741911, $e);
        } catch (\UnexpectedValueException $e) {
            // thrown if a file has been replaced with a folder
            throw new Exception($this->getExceptionMessage($e->getMessage()), 1509741912, $e);
        } catch (\InvalidArgumentException $e) {
            // thrown if file storage does not exist
            throw new Exception($this->getExceptionMessage($e->getMessage()), 1509741914, $e);
        }
        return $this->tag->render();
    }

    protected function getExceptionMessage(string $detailedMessage): string
    {
        if ($this->renderingContext->hasAttribute(ServerRequestInterface::class)
            && $this->renderingContext->getAttribute(ServerRequestInterface::class) instanceof RequestInterface
        ) {
            $request = $this->renderingContext->getAttribute(ServerRequestInterface::class);
            $currentContentObject = $request->getAttribute('currentContentObject');
            if ($currentContentObject instanceof ContentObjectRenderer) {
                return sprintf('Unable to render image tag in "%s": %s', $currentContentObject->currentRecord, $detailedMessage);
            }
        }
        return "Unable to render image tag: $detailedMessage";
    }
}
