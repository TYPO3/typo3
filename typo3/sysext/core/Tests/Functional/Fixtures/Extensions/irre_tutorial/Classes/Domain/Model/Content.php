<?php
namespace OliverHader\IrreTutorial\Domain\Model;

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

/**
 * Content
 */
class Content extends \TYPO3\CMS\Extbase\DomainObject\AbstractEntity
{
    /**
     * @var string
     */
    protected $header = '';

    /**
     * @var \TYPO3\CMS\Extbase\Persistence\ObjectStorage<\OliverHader\IrreTutorial\Domain\Model\Hotel>
     */
    protected $hotels = null;

    /**
     * Initializes this object.
     */
    public function __construct()
    {
        $this->hotels = new \TYPO3\CMS\Extbase\Persistence\ObjectStorage();
    }

    /**
     * @return string $header
     */
    public function getHeader()
    {
        return $this->header;
    }

    /**
     * @param string $header
     * @return void
     */
    public function setHeader($header)
    {
        $this->header = $header;
    }

    /**
     * @return \TYPO3\CMS\Extbase\Persistence\ObjectStorage<\OliverHader\IrreTutorial\Domain\Model\Hotel>
     */
    public function getHotels()
    {
        return $this->hotels;
    }

    /**
     * @param \TYPO3\CMS\Extbase\Persistence\ObjectStorage<\OliverHader\IrreTutorial\Domain\Model\Hotel> $hotels
     * @return void
     */
    public function setHotels(\TYPO3\CMS\Extbase\Persistence\ObjectStorage $hotels)
    {
        $this->hotels = $hotels;
    }
}
