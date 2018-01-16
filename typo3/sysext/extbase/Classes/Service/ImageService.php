<?php
namespace TYPO3\CMS\Extbase\Service;

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

use TYPO3\CMS\Core\LinkHandling\LinkService;
use TYPO3\CMS\Core\Resource\File;
use TYPO3\CMS\Core\Resource\FileInterface;
use TYPO3\CMS\Core\Resource\FileReference;
use TYPO3\CMS\Core\Resource\ProcessedFile;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Core\Utility\MathUtility;

/**
 * Service for processing images
 */
class ImageService implements \TYPO3\CMS\Core\SingletonInterface
{
    /**
     * @var \TYPO3\CMS\Core\Resource\ResourceFactory
     */
    protected $resourceFactory;

    /**
     * @var \TYPO3\CMS\Extbase\Service\EnvironmentService
     */
    protected $environmentService;

    /**
     * @param \TYPO3\CMS\Core\Resource\ResourceFactory $resourceFactory
     */
    public function injectResourceFactory(\TYPO3\CMS\Core\Resource\ResourceFactory $resourceFactory)
    {
        $this->resourceFactory = $resourceFactory;
    }

    /**
     * @param \TYPO3\CMS\Extbase\Service\EnvironmentService $environmentService
     */
    public function injectEnvironmentService(\TYPO3\CMS\Extbase\Service\EnvironmentService $environmentService)
    {
        $this->environmentService = $environmentService;
    }

    /**
     * Create a processed file
     *
     * @param FileInterface|FileReference $image
     * @param array $processingInstructions
     * @return ProcessedFile
     * @api
     */
    public function applyProcessingInstructions($image, $processingInstructions)
    {
        if (is_callable([$image, 'getOriginalFile'])) {
            // Get the original file from the file reference
            $image = $image->getOriginalFile();
        }

        $processedImage = $image->process(ProcessedFile::CONTEXT_IMAGECROPSCALEMASK, $processingInstructions);
        $this->setCompatibilityValues($processedImage);

        return $processedImage;
    }

    /**
     * Get public url of image depending on the environment
     *
     * @param FileInterface $image
     * @param bool|false $absolute Force absolute URL
     * @return string
     * @api
     */
    public function getImageUri(FileInterface $image, $absolute = false)
    {
        $imageUrl = $image->getPublicUrl();
        $parsedUrl = parse_url($imageUrl);
        // no prefix in case of an already fully qualified URL
        if (isset($parsedUrl['host'])) {
            $uriPrefix = '';
        } elseif ($this->environmentService->isEnvironmentInFrontendMode()) {
            $uriPrefix = $GLOBALS['TSFE']->absRefPrefix;
        } else {
            $uriPrefix = GeneralUtility::getIndpEnv('TYPO3_SITE_PATH');
        }

        if ($absolute) {
            // If full URL has no scheme we add the same scheme as used by the site
            // so we have an absolute URL also usable outside of browser scope (e.g. in an email message)
            if (isset($parsedUrl['host']) && !isset($parsedUrl['scheme'])) {
                $uriPrefix = (GeneralUtility::getIndpEnv('TYPO3_SSL') ? 'https:' : 'http:') . $uriPrefix;
            }
            return GeneralUtility::locationHeaderUrl($uriPrefix . $imageUrl);
        }
        return $uriPrefix . $imageUrl;
    }

    /**
     * Get File or FileReference object
     *
     * This method is a factory and compatibility method that does not belong to
     * this service, but is put here for pragmatic reasons for the time being.
     * It should be removed once we do not support string sources for images anymore.
     *
     * @param string $src
     * @param mixed $image
     * @param bool $treatIdAsReference
     * @return FileInterface|FileReference
     * @throws \UnexpectedValueException
     * @internal
     */
    public function getImage($src, $image, $treatIdAsReference)
    {
        if (is_null($image)) {
            $image = $this->getImageFromSourceString($src, $treatIdAsReference);
        } elseif (is_callable([$image, 'getOriginalResource'])) {
            // We have a domain model, so we need to fetch the FAL resource object from there
            $image = $image->getOriginalResource();
        }

        if (!($image instanceof File || $image instanceof FileReference)) {
            $class = is_object($image) ? get_class($image) : 'null';
            throw new \UnexpectedValueException('Supplied file object type ' . $class . ' for ' . $src . ' must be File or FileReference.', 1382687163);
        }

        return $image;
    }

    /**
     * Get File or FileReference object by src
     *
     * @param string $src
     * @param bool $treatIdAsReference
     * @return FileInterface|FileReference|\TYPO3\CMS\Core\Resource\Folder
     */
    protected function getImageFromSourceString($src, $treatIdAsReference)
    {
        if ($this->environmentService->isEnvironmentInBackendMode() && substr($src, 0, 3) === '../') {
            $src = substr($src, 3);
        }
        if (MathUtility::canBeInterpretedAsInteger($src)) {
            if ($treatIdAsReference) {
                $image = $this->resourceFactory->getFileReferenceObject($src);
            } else {
                $image = $this->resourceFactory->getFileObject($src);
            }
        } elseif (strpos($src, 't3://file') === 0) {
            // We have a t3://file link to a file in FAL
            $linkService = GeneralUtility::makeInstance(LinkService::class);
            $data = $linkService->resolveByStringRepresentation($src);
            $image = $data['file'];
        } else {
            // We have a combined identifier or legacy (storage 0) path
            $image = $this->resourceFactory->retrieveFileOrFolderObject($src);
        }
        return $image;
    }

    /**
     * Set compatibility values to frontend controller object
     * in case we are in frontend environment.
     *
     * @param ProcessedFile $processedImage
     */
    protected function setCompatibilityValues(ProcessedFile $processedImage)
    {
        if ($this->environmentService->isEnvironmentInFrontendMode()) {
            $imageInfo = $this->getCompatibilityImageResourceValues($processedImage);
            $GLOBALS['TSFE']->lastImageInfo = $imageInfo;
            $GLOBALS['TSFE']->imagesOnPage[] = $imageInfo[3];
        }
    }

    /**
     * Calculates the compatibility values
     * This is duplicate code taken from ContentObjectRenderer::getImgResource()
     * Ideally we should get rid of this code in both places.
     *
     * @param ProcessedFile $processedImage
     * @return array
     */
    protected function getCompatibilityImageResourceValues(ProcessedFile $processedImage)
    {
        $hash = $processedImage->calculateChecksum();
        if (isset($GLOBALS['TSFE']->tmpl->fileCache[$hash])) {
            $compatibilityImageResourceValues = $GLOBALS['TSFE']->tmpl->fileCache[$hash];
        } else {
            $compatibilityImageResourceValues = [
                0 => $processedImage->getProperty('width'),
                1 => $processedImage->getProperty('height'),
                2 => $processedImage->getExtension(),
                3 => $processedImage->getPublicUrl(),
                'origFile' => $processedImage->getOriginalFile()->getPublicUrl(),
                'origFile_mtime' => $processedImage->getOriginalFile()->getModificationTime(),
                // This is needed by \TYPO3\CMS\Frontend\Imaging\GifBuilder,
                // in order for the setup-array to create a unique filename hash.
                'originalFile' => $processedImage->getOriginalFile(),
                'processedFile' => $processedImage,
                'fileCacheHash' => $hash
            ];
        }
        return $compatibilityImageResourceValues;
    }
}
