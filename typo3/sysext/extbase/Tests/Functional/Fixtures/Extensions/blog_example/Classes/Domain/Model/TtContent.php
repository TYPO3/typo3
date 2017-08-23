<?php
namespace ExtbaseTeam\BlogExample\Domain\Model;

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
 * A tt_content model
 */
class TtContent extends \TYPO3\CMS\Extbase\DomainObject\AbstractEntity
{
    /**
     * uid
     *
     * @var string
     */
    protected $uid = '';

    /**
     * pid
     *
     * @var string
     */
    protected $pid = '';

    /**
     * @var string
     */
    protected $header;

    /**
     * Images
     *
     * @var \TYPO3\CMS\Extbase\Persistence\ObjectStorage<\TYPO3\CMS\Extbase\Domain\Model\FileReference>
     * @lazy
     */
    protected $image;

    /**
     * Constructs this post
     */
    public function __construct()
    {
        $this->image = new \TYPO3\CMS\Extbase\Persistence\ObjectStorage();
    }

    /**
     * Gets the uid
     *
     * @return string $uid
     */
    public function getUid()
    {
        return $this->uid;
    }

    /**
     * Gets the pid
     *
     * @return string $pid
     */
    public function getPid()
    {
        return $this->pid;
    }

    /**
     * Returns the header
     *
     * @return string $header
     */
    public function getHeader()
    {
        return $this->header;
    }

    /**
     * Sets the header
     *
     * @param string $header
     */
    public function setHeader($header)
    {
        $this->header = $header;
    }

    /**
     * @return \TYPO3\CMS\Extbase\Persistence\ObjectStorage
     */
    public function getImage()
    {
        return $this->image;
    }

    /**
     * @param \TYPO3\CMS\Extbase\Persistence\ObjectStorage $image
     */
    public function setImage(\TYPO3\CMS\Extbase\Persistence\ObjectStorage $image)
    {
        $this->image = $image;
    }

    /**
     * Returns this as a formatted string
     *
     * @return string
     */
    public function __toString()
    {
        return $this->getHeader();
    }
}
