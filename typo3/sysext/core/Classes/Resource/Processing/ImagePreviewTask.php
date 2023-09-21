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

namespace TYPO3\CMS\Core\Resource\Processing;

use TYPO3\CMS\Core\Utility\MathUtility;

/**
 * A task for generating an image preview.
 */
class ImagePreviewTask extends AbstractGraphicalTask
{
    /**
     * @var string
     */
    protected $type = 'Image';

    /**
     * @var string
     */
    protected $name = 'Preview';

    /**
     * Returns the target filename for this task.
     *
     * @return string
     */
    public function getTargetFileName()
    {
        return 'preview_' . parent::getTargetFilename();
    }

    /**
     * Checks if the given configuration is sensible for this task, i.e. if all required parameters
     * are given, within the boundaries and don't conflict with each other.
     */
    protected function isValidConfiguration(array $configuration): bool
    {
        /**
         * Checks to perform:
         * - width and/or height given, integer values?
         */
        return true;
    }

    /**
     * Returns TRUE if the file has to be processed at all, such as e.g. the original file does.
     *
     * Note: This does not indicate if the concrete ProcessedFile attached to this task has to be (re)processed.
     * This check is done in ProcessedFile::isOutdated().
     * @todo isOutdated()/needsReprocessing()?
     */
    public function fileNeedsProcessing(): bool
    {
        // @todo Implement fileNeedsProcessing() method.

        /**
         * Checks to perform:
         * - width/height smaller than image, keeping aspect ratio?
         */
        return false;
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
            static function ($value, $name) {
                return !empty($value) && in_array($name, ['width', 'height'], true);
            },
            ARRAY_FILTER_USE_BOTH
        );
        parent::sanitizeConfiguration();
    }
}
