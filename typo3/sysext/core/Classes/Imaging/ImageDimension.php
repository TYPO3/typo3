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

namespace TYPO3\CMS\Core\Imaging;

use TYPO3\CMS\Core\Imaging\Exception\ZeroImageDimensionException;
use TYPO3\CMS\Core\Resource\Processing\TaskInterface;

/**
 * Representing an image dimension (width and height)
 * and calculating the dimension from a source with a given processing instruction
 */
class ImageDimension
{
    /**
     * @param int<0, max> $width
     * @param int<0, max> $height
     */
    public function __construct(
        private readonly int $width,
        private readonly int $height
    ) {}

    /**
     * @return int<0, max>
     */
    public function getWidth(): int
    {
        return $this->width;
    }

    /**
     * @return int<0, max>
     */
    public function getHeight(): int
    {
        return $this->height;
    }

    /**
     * @throws ZeroImageDimensionException
     */
    public static function fromProcessingTask(TaskInterface $task): self
    {
        $result = ImageProcessingInstructions::fromProcessingTask($task);
        return new self($result->width, $result->height);
    }
}
