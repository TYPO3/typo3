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

namespace TYPO3\CMS\Core\Resource\Processing;

use TYPO3\CMS\Core\Imaging\GraphicalFunctions;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Core\Utility\MathUtility;

/**
 * A task for generating an image preview.
 */
class ImagePreviewTask extends AbstractTask
{
    protected ?string $targetFileExtension;

    public function getType(): string
    {
        return 'Image';
    }

    public function getName(): string
    {
        return 'Preview';
    }

    /**
     * Returns the name the processed file should have
     * in the filesystem.
     */
    public function getTargetFilename(): string
    {
        return 'preview_'
            . $this->getSourceFile()->getNameWithoutExtension()
            . '_' . $this->getConfigurationChecksum()
            . '.' . $this->getTargetFileExtension();
    }

    /**
     * Determines the file extension the processed file
     * should have in the filesystem.
     */
    public function getTargetFileExtension(): string
    {
        if (!isset($this->targetFileExtension)) {
            $this->targetFileExtension = $this->determineTargetFileExtension();
        }
        return $this->targetFileExtension;
    }

    /**
     * Gets the file extension the processed file should
     * have in the filesystem by either using the configuration
     * setting, or the extension of the original file.
     */
    protected function determineTargetFileExtension(): string
    {
        if (!empty($this->configuration['fileExtension'])) {
            return $this->configuration['fileExtension'];
        }

        // @todo - See note of determineDefaultProcessingFileExtension() - find a better place for this
        $imageService = GeneralUtility::makeInstance(GraphicalFunctions::class);
        return $imageService->determineDefaultProcessingFileExtension($this->getSourceFile()->getExtension());
    }

    /**
     * Enforce default configuration for preview processing here,
     * to be sure we find already processed files below,
     * which we wouldn't if we would change the configuration later, as configuration is part of the lookup.
     */
    public function sanitizeConfiguration(): void
    {
        $configuration = array_replace(
            [
                'width' => 64,
                'height' => 64,
            ],
            $this->configuration
        );
        $configuration['width'] = MathUtility::forceIntegerInRange($configuration['width'], 1, 1000);
        $configuration['height'] = MathUtility::forceIntegerInRange($configuration['height'], 1, 1000);

        $this->configuration = array_filter(
            $configuration,
            static function (string|int|bool|array|null $value, string $name): bool {
                return !empty($value) && in_array($name, ['width', 'height'], true);
            },
            ARRAY_FILTER_USE_BOTH
        );
        parent::sanitizeConfiguration();
    }
}
