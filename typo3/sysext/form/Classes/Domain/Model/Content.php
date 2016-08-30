<?php
namespace TYPO3\CMS\Form\Domain\Model;

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
 * Content domain model
 */
class Content
{
    /**
     * The uid
     *
     * @var int
     */
    protected $uid = 0;

    /**
     * The page id
     *
     * @var int
     */
    protected $pageId = 0;

    /**
     * The configuration Typoscript
     *
     * @var array
     */
    protected $typoscript = [];

    /**
     * Sets the uid
     *
     * @param int $uid The uid
     * @return void
     */
    public function setUid($uid)
    {
        $this->uid = (int)$uid;
    }

    /**
     * Returns the uid
     *
     * @return int The uid
     */
    public function getUid()
    {
        return $this->uid;
    }

    /**
     * Sets the page id
     *
     * @param int $pageId The page id
     * @return void
     */
    public function setPageId($pageId)
    {
        $this->pageId = (int)$pageId;
    }

    /**
     * Returns the page id
     *
     * @return int The page id
     */
    public function getPageId()
    {
        return $this->pageId;
    }

    /**
     * Sets the Typoscript configuration
     *
     * @param array $typoscript The Typoscript configuration
     * @return void
     */
    public function setTyposcript(array $typoscript)
    {
        $this->typoscript = (array)$typoscript;
    }

    /**
     * Returns the Typoscript configuration
     *
     * @return array The Typoscript configuration
     */
    public function getTyposcript()
    {
        return $this->typoscript;
    }
}
