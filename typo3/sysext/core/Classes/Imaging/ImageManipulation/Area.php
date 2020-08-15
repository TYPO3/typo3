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

namespace TYPO3\CMS\Core\Imaging\ImageManipulation;

use TYPO3\CMS\Core\Resource\FileInterface;

class Area
{
    /**
     * @var float
     */
    protected $x;

    /**
     * @var float
     */
    protected $y;

    /**
     * @var float
     */
    protected $width;

    /**
     * @var float
     */
    protected $height;

    /**
     * @param float $x
     * @param float $y
     * @param float $width
     * @param float $height
     */
    public function __construct(float $x, float $y, float $width, float $height)
    {
        $this->x = $x;
        $this->y = $y;
        $this->width = $width;
        $this->height = $height;
    }

    /**
     * @param array $config
     * @return Area
     * @throws InvalidConfigurationException
     */
    public static function createFromConfiguration(array $config): Area
    {
        try {
            return new self(
                (float)$config['x'],
                (float)$config['y'],
                (float)$config['width'],
                (float)$config['height']
            );
        } catch (\Throwable $throwable) {
            throw new InvalidConfigurationException(sprintf('Invalid type for area property given: %s', $throwable->getMessage()), 1485279226, $throwable);
        }
    }

    /**
     * @param array $config
     * @return Area[]
     * @throws InvalidConfigurationException
     */
    public static function createMultipleFromConfiguration(array $config): array
    {
        $areas = [];
        foreach ($config as $areaConfig) {
            $areas[] = self::createFromConfiguration($areaConfig);
        }
        return $areas;
    }

    /**
     * @return Area
     */
    public static function createEmpty()
    {
        return new self(0.0, 0.0, 1.0, 1.0);
    }

    public function getWidth(): float
    {
        return $this->width;
    }

    public function getHeight(): float
    {
        return $this->height;
    }

    public function getOffsetLeft(): float
    {
        return $this->x;
    }

    public function getOffsetTop(): float
    {
        return $this->y;
    }

    /**
     * @return array
     * @internal
     */
    public function asArray(): array
    {
        return [
            'x' => $this->x,
            'y' => $this->y,
            'width' => $this->width,
            'height' => $this->height,
        ];
    }

    /**
     * @param FileInterface $file
     * @return Area
     */
    public function makeAbsoluteBasedOnFile(FileInterface $file)
    {
        return new self(
            $this->x * $file->getProperty('width'),
            $this->y * $file->getProperty('height'),
            $this->width * $file->getProperty('width'),
            $this->height * $file->getProperty('height')
        );
    }

    /**
     * @param FileInterface $file
     * @return Area
     */
    public function makeRelativeBasedOnFile(FileInterface $file)
    {
        $width = $file->getProperty('width');
        $height = $file->getProperty('height');

        if (empty($width) || empty($height)) {
            return self::createEmpty();
        }

        return new self(
            $this->x / $width,
            $this->y / $height,
            $this->width / $width,
            $this->height / $height
        );
    }

    /**
     * @param Ratio $ratio
     * @return Area
     */
    public function applyRatioRestriction(Ratio $ratio): Area
    {
        if ($ratio->isFree()) {
            return $this;
        }
        $expectedRatio = $ratio->getRatioValue();
        $newArea = clone $this;
        if ($newArea->height * $expectedRatio > $newArea->width) {
            $newArea->height = $newArea->width / $expectedRatio;
            $newArea->y += ($this->height - $newArea->height) / 2;
        } else {
            $newArea->width = $newArea->height * $expectedRatio;
            $newArea->x += ($this->width - $newArea->width) / 2;
        }
        return $newArea;
    }

    /**
     * @return bool
     */
    public function isEmpty()
    {
        return $this->x === 0.0 && $this->y === 0.0 && $this->width === 1.0 && $this->height === 1.0;
    }

    /**
     * @return string
     */
    public function __toString()
    {
        if ($this->isEmpty()) {
            return '';
        }
        return json_encode($this->asArray());
    }
}
