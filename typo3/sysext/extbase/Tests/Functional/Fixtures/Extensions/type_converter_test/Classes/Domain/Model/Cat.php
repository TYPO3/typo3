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

namespace ExtbaseTeam\TypeConverterTest\Domain\Model;

class Cat extends Animal
{
    protected ?string $color = null;

    protected ?int $height = null;

    protected ?int $numberOfEars = null;

    public function getColor(): ?string
    {
        return $this->color;
    }

    public function setColor(?string $color): void
    {
        $this->color = $color;
    }

    public function getHeight(): ?int
    {
        return $this->height;
    }

    public function setHeight(?int $height): void
    {
        $this->height = $height;
    }

    public function getNumberOfEars(): ?int
    {
        return $this->numberOfEars;
    }

    public function setNumberOfEars(?int $numberOfEars): void
    {
        $this->numberOfEars = $numberOfEars;
    }
}
