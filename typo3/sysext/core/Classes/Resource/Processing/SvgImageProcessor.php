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

use TYPO3\CMS\Core\Imaging\ImageDimension;

/**
 * Processes (scales) SVG Images files, when no cropping is defined
 */
class SvgImageProcessor implements ProcessorInterface
{
    public function canProcessTask(TaskInterface $task): bool
    {
        return $task->getType() === 'Image'
            && in_array($task->getName(), ['Preview', 'CropScaleMask'], true)
            && empty($task->getConfiguration()['crop'])
            && $task->getTargetFileExtension() === 'svg';
    }

    /**
     * Processes the given task.
     *
     * @param TaskInterface $task
     * @throws \InvalidArgumentException
     */
    public function processTask(TaskInterface $task): void
    {
        $task->setExecuted(true);
        $task->getTargetFile()->setUsesOriginalFile();
        try {
            $imageDimension = ImageDimension::fromProcessingTask($task);
        } catch (\Throwable $e) {
            // To not fail image processing, we just assume an SVG image dimension here
            $imageDimension = new ImageDimension(64, 64);
        }
        $task->getTargetFile()->updateProperties(
            [
                'width' => $imageDimension->getWidth(),
                'height' => $imageDimension->getHeight(),
                'size' => $task->getSourceFile()->getSize(),
                'checksum' => $task->getConfigurationChecksum(),
            ]
        );
    }
}
