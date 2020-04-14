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
    protected $width;

    /**
     * @var int
     */
    protected $height;

    /**
     * Constructor which fetches the size and resolves it to a pixel size
     *
     * @param string $size the icon size
     *
     * @throws \InvalidArgumentException
     */
    public function __construct($size = Icon::SIZE_DEFAULT)
    {
        switch ($size) {
            case Icon::SIZE_LARGE:
                $sizeInPixel = 48;
                break;
            case Icon::SIZE_DEFAULT:
                $sizeInPixel = 32;
                break;
            case Icon::SIZE_SMALL:
            case Icon::SIZE_OVERLAY:
                $sizeInPixel = 16;
                break;
            default:
                throw new \InvalidArgumentException('The given size ' . $size . ' is not a valid size, see Icon class for options', 1438871603);
        }

        $this->width = (int)$sizeInPixel;
        $this->height = (int)$sizeInPixel;
    }

    /**
     * Returns the width
     * @return int
     */
    public function getWidth()
    {
        return $this->width;
    }

    /**
     * Returns the height
     * @return int
     */
    public function getHeight()
    {
        return $this->height;
    }
}
