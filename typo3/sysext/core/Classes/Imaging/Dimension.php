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

namespace TYPO3\CMS\Core\Imaging;

/**
 * Dimension class holds width and height for an icon
 */
class Dimension
{
    /**
     * @var int
     */
    protected int $width;

    /**
     * @var int
     */
    protected int $height;

    /**
     * @throws \InvalidArgumentException
     */
    public function __construct(IconSize $size = IconSize::MEDIUM)
    {
        $dimensions = $size->getDimensions();

        $this->width = $dimensions[0];
        $this->height = $dimensions[1];
    }

    public function getWidth(): int
    {
        return $this->width;
    }

    public function getHeight(): int
    {
        return $this->height;
    }
}
