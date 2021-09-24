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

namespace OliverHader\IrreTutorial\Domain\Model;

use TYPO3\CMS\Extbase\DomainObject\AbstractEntity;
use TYPO3\CMS\Extbase\Persistence\ObjectStorage;

/**
 * Content
 */
class Content extends AbstractEntity
{
    /**
     * @var string
     */
    protected $header = '';

    /**
     * @var \TYPO3\CMS\Extbase\Persistence\ObjectStorage<\OliverHader\IrreTutorial\Domain\Model\Hotel>
     */
    protected $hotels;

    /**
     * Initializes this object.
     */
    public function __construct()
    {
        $this->hotels = new ObjectStorage();
    }

    /**
     * @return string $header
     */
    public function getHeader(): string
    {
        return $this->header;
    }

    /**
     * @param string $header
     */
    public function setHeader($header): void
    {
        $this->header = $header;
    }

    /**
     * @return \TYPO3\CMS\Extbase\Persistence\ObjectStorage
     */
    public function getHotels(): ObjectStorage
    {
        return $this->hotels;
    }

    /**
     * @param \TYPO3\CMS\Extbase\Persistence\ObjectStorage $hotels
     */
    public function setHotels(ObjectStorage $hotels): void
    {
        $this->hotels = $hotels;
    }
}
