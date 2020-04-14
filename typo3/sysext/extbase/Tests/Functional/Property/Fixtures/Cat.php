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

namespace TYPO3\CMS\Extbase\Tests\Functional\Property\Fixtures;

class Cat extends Animal
{
    /**
     * @var string|null
     */
    protected $color;

    /**
     * @var int|null
     */
    protected $height;

    /**
     * @var int|null
     */
    protected $numberOfEars;

    /**
     * @return string|null
     */
    public function getColor(): ?string
    {
        return $this->color;
    }

    /**
     * @param string|null $color
     */
    public function setColor(?string $color): void
    {
        $this->color = $color;
    }

    /**
     * @return int|null
     */
    public function getHeight(): ?int
    {
        return $this->height;
    }

    /**
     * @param int|null $height
     */
    public function setHeight(?int $height): void
    {
        $this->height = $height;
    }

    /**
     * @return int|null
     */
    public function getNumberOfEars(): ?int
    {
        return $this->numberOfEars;
    }

    /**
     * @param int|null $numberOfEars
     */
    public function setNumberOfEars(?int $numberOfEars): void
    {
        $this->numberOfEars = $numberOfEars;
    }
}
