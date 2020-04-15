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

namespace TYPO3\CMS\Extbase\Service;

use TYPO3\CMS\Core\LinkHandling\LinkService;
use TYPO3\CMS\Core\Page\AssetCollector;
use TYPO3\CMS\Core\Resource\File;
use TYPO3\CMS\Core\Resource\FileInterface;
use TYPO3\CMS\Core\Resource\FileReference;
use TYPO3\CMS\Core\Resource\ProcessedFile;
use TYPO3\CMS\Core\Resource\ResourceFactory;
use TYPO3\CMS\Core\SingletonInterface;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Core\Utility\MathUtility;

/**
 * Service for processing images
 */
class ImageService implements SingletonInterface
{
    /**
     * @var ResourceFactory
     */
    protected $resourceFactory;

    /**
     * @var EnvironmentService
     */
    protected $environmentService;

    /**
     * ImageService constructor.
     *
     * @param EnvironmentService|null $environmentService
     * @param ResourceFactory|null $resourceFactory
     */
    public function __construct(EnvironmentService $environmentService = null, ResourceFactory $resourceFactory = null)
    {
        $this->environmentService = $environmentService ?? GeneralUtility::makeInstance(EnvironmentService::class);
        $this->resourceFactory = $resourceFactory ?? GeneralUtility::makeInstance(ResourceFactory::class);
    }

    /**
     * Create a processed file
     *
     * @param FileInterface|FileReference $image
     * @param array $processingInstructions
     * @return ProcessedFile
     */
    public function applyProcessingInstructions($image, array $processingInstructions): ProcessedFile
    {
        /*
         * todo: this method should be split to be able to have a proper method signature.
         * todo: actually, this method only really works with objects of type \TYPO3\CMS\Core\Resource\File, as this
         * todo: is the only implementation that supports the support method.
         */
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
     */
    public function getImageUri(FileInterface $image, bool $absolute = false): string
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
     * @param FileInterface|\TYPO3\CMS\Extbase\Domain\Model\FileReference|null $image
     * @param bool $treatIdAsReference
     * @return FileInterface
     * @throws \UnexpectedValueException
     * @internal
     */
    public function getImage(string $src, $image, bool $treatIdAsReference): FileInterface
    {
        if ($image === null) {
            $image = $this->getImageFromSourceString($src, $treatIdAsReference);
        } elseif (is_callable([$image, 'getOriginalResource'])) {
            // We have a domain model, so we need to fetch the FAL resource object from there
            $image = $image->getOriginalResource();
        }

        if (!($image instanceof File || $image instanceof FileReference)) {
            throw new \UnexpectedValueException('Supplied file object type ' . get_class($image) . ' for ' . $src . ' must be File or FileReference.', 1382687163);
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
    protected function getImageFromSourceString(string $src, bool $treatIdAsReference): object
    {
        if ($this->environmentService->isEnvironmentInBackendMode() && strpos($src, '../') === 0) {
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
    protected function setCompatibilityValues(ProcessedFile $processedImage): void
    {
        $imageInfoValues = $this->getCompatibilityImageResourceValues($processedImage);
        if (
            $this->environmentService->isEnvironmentInFrontendMode()
            && is_object($GLOBALS['TSFE'])
        ) {
            // This is needed by \TYPO3\CMS\Frontend\Imaging\GifBuilder,
            // but was never needed to be set in lastImageInfo.
            // We set it for BC here anyway, as this TSFE property is deprecated anyway.
            $imageInfoValues['originalFile'] = $processedImage->getOriginalFile();
            $imageInfoValues['processedFile'] = $processedImage;
            $GLOBALS['TSFE']->lastImageInfo = $imageInfoValues;
            $GLOBALS['TSFE']->imagesOnPage[] = $processedImage->getPublicUrl();
        }
        GeneralUtility::makeInstance(AssetCollector::class)->addMedia(
            $processedImage->getPublicUrl(),
            $imageInfoValues
        );
    }

    /**
     * Calculates the compatibility values
     * This is duplicate code taken from ContentObjectRenderer::getImgResource()
     * Ideally we should get rid of this code in both places.
     *
     * @param ProcessedFile $processedImage
     * @return array
     */
    protected function getCompatibilityImageResourceValues(ProcessedFile $processedImage): array
    {
        $originalFile = $processedImage->getOriginalFile();
        return [
            0 => $processedImage->getProperty('width'),
            1 => $processedImage->getProperty('height'),
            2 => $processedImage->getExtension(),
            3 => $processedImage->getPublicUrl(),
            'origFile' => $originalFile->getPublicUrl(),
            'origFile_mtime' => $originalFile->getModificationTime(),
        ];
    }
}
