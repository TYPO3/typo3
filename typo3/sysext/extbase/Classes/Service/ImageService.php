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

use Psr\Http\Message\ServerRequestInterface;
use TYPO3\CMS\Core\Http\ApplicationType;
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
     * ImageService constructor.
     *
     * @param ResourceFactory $resourceFactory
     */
    public function __construct(ResourceFactory $resourceFactory)
    {
        $this->resourceFactory = $resourceFactory;
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
        if (!$absolute || $imageUrl === null) {
            return (string)$imageUrl;
        }

        return GeneralUtility::locationHeaderUrl($imageUrl);
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
     * @return FileInterface|File|FileReference
     * @throws \UnexpectedValueException
     * @internal
     */
    public function getImage(string $src, $image, bool $treatIdAsReference): FileInterface
    {
        if ($image instanceof File || $image instanceof FileReference) {
            // We already received a valid file and therefore just return it
            return $image;
        }

        if (is_callable([$image, 'getOriginalResource'])) {
            // We have a domain model, so we need to fetch the FAL resource object from there
            $originalResource = $image->getOriginalResource();
            if (!($originalResource instanceof File || $originalResource instanceof FileReference)) {
                throw new \UnexpectedValueException('No original resource could be resolved for supplied file ' . get_class($image), 1625838481);
            }
            return $originalResource;
        }

        if ($image !== null) {
            // Some value is given for $image, but it's not a valid type
            throw new \UnexpectedValueException(
                'Supplied file must be File or FileReference, ' . (($type = gettype($image)) === 'object' ? get_class($image) : $type) . ' given.',
                1625585157
            );
        }

        // Since image is not given, try to resolve an image from the source string
        $resolvedImage = $this->getImageFromSourceString($src, $treatIdAsReference);

        if ($resolvedImage instanceof File || $resolvedImage instanceof FileReference) {
            return $resolvedImage;
        }

        if ($resolvedImage === null) {
            // No image could be resolved using the given source string
            throw new \UnexpectedValueException('Supplied ' . $src . ' could not be resolved to a File or FileReference.', 1625585158);
        }

        // A FileInterface was found, however only File and FileReference are valid
        throw new \UnexpectedValueException(
            'Resolved file object type ' . get_class($resolvedImage) . ' for ' . $src . ' must be File or FileReference.',
            1382687163
        );
    }

    /**
     * Get File or FileReference object by src
     *
     * @param string $src
     * @param bool $treatIdAsReference
     * @return FileInterface|null
     */
    protected function getImageFromSourceString(string $src, bool $treatIdAsReference): ?FileInterface
    {
        if (($GLOBALS['TYPO3_REQUEST'] ?? null) instanceof ServerRequestInterface
            && ApplicationType::fromRequest($GLOBALS['TYPO3_REQUEST'])->isBackend()
            && strpos($src, '../') === 0
        ) {
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

        // Check the resolved image as this could also be a FolderInterface
        return $image instanceof FileInterface ? $image : null;
    }

    /**
     * Set compatibility values to frontend controller object
     * in case we are in frontend environment.
     *
     * @param ProcessedFile $processedImage
     */
    protected function setCompatibilityValues(ProcessedFile $processedImage): void
    {
        $publicUrl = $processedImage->getPublicUrl();
        if ($publicUrl !== null) {
            // only add the processed image to AssetCollector if the public url is not NULL
            $imageInfoValues = $this->getCompatibilityImageResourceValues($processedImage);
            GeneralUtility::makeInstance(AssetCollector::class)->addMedia(
                $publicUrl,
                $imageInfoValues
            );
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
