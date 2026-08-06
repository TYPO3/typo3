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

use Symfony\Component\DependencyInjection\Attribute\Autoconfigure;
use TYPO3\CMS\Core\Resource\FileInterface;
use TYPO3\CMS\Core\Resource\ProcessableFileInterface;
use TYPO3\CMS\Core\Resource\ProcessedFile;
use TYPO3\CMS\Core\Resource\ResourceFactory;
use TYPO3\CMS\Core\Utility\GeneralUtility;

/**
 * Service for processing images
 */
#[Autoconfigure(public: true)]
readonly class ImageService
{
    public function __construct(
        protected ResourceFactory $resourceFactory,
    ) {}

    /**
     * Create a processed file
     */
    public function applyProcessingInstructions(ProcessableFileInterface $image, array $processingInstructions): ProcessedFile
    {
        return $image->process(ProcessedFile::CONTEXT_IMAGECROPSCALEMASK, $processingInstructions);
    }

    /**
     * Get public url of image depending on the environment
     *
     * @param bool|false $absolute Force absolute URL
     */
    public function getImageUri(FileInterface $image, bool $absolute = false): string
    {
        $imageUrl = $image->getPublicUrl();
        if (!$absolute || $imageUrl === null) {
            return (string)$imageUrl;
        }
        // @todo: Change method signature in >=v15 to receive $request, probably as first or second argument.
        $request = $GLOBALS['TYPO3_REQUEST'] ?? null;
        return GeneralUtility::locationHeaderUrl($imageUrl, $request);
    }

    /**
     * Get File or FileReference object
     *
     * @param string $src
     * @param FileInterface|\TYPO3\CMS\Extbase\Domain\Model\FileReference|null $image
     * @param bool $treatIdAsReference
     * @throws \UnexpectedValueException
     * @internal
     */
    public function getImage(string $src, $image, bool $treatIdAsReference): FileInterface&ProcessableFileInterface
    {
        return $this->resourceFactory->resolveFileObject($image ?? $src, $treatIdAsReference);
    }
}
