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

namespace TYPO3Tests\TypeConverterTest\Domain\Model;

use TYPO3\CMS\Extbase\Persistence\ObjectStorage;

class Home
{
    /**
     * @var ObjectStorage<Cat>
     */
    protected ObjectStorage $cats;

    /**
     * @var ObjectStorage<Horse>
     */
    protected ObjectStorage $horses;

    public function __construct()
    {
        $this->cats = new ObjectStorage();
        $this->horses = new ObjectStorage();
    }

    public function initializeObject(): void
    {
        $this->cats = new ObjectStorage();
        $this->horses = new ObjectStorage();
    }

    public function getCats(): ObjectStorage
    {
        return $this->cats;
    }

    public function setCats(ObjectStorage $cats): void
    {
        $this->cats = $cats;
    }

    public function addCat(Cat $cat): void
    {
        $this->cats->attach($cat);
    }

    public function removeCat(Cat $cat): void
    {
        $this->cats->detach($cat);
    }

    public function getHorses(): ObjectStorage
    {
        return $this->horses;
    }

    public function setHorses(ObjectStorage $horses): void
    {
        $this->horses = $horses;
    }

    public function addHorse(Horse $horse): void
    {
        $this->horses->attach($horse);
    }

    public function removeHorse(Horse $horse): void
    {
        $this->horses->detach($horse);
    }
}
