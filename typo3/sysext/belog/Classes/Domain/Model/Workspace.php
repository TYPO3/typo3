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

namespace TYPO3\CMS\Belog\Domain\Model;

use TYPO3\CMS\Extbase\DomainObject\AbstractEntity;

/**
 * Stub model for workspaces - only properties required for belog module are added currently
 *
 * @todo : This should be extended and put at some more central place
 * @internal This class is a TYPO3 Backend implementation and is not considered part of the Public TYPO3 API.
 */
class Workspace extends AbstractEntity
{
    /**
     * @var int
     */
    const UID_LIVE_WORKSPACE = 0;

    /**
     * @var int
     */
    const UID_ANY_WORKSPACE = -99;

    /**
     * title of the workspace
     *
     * @var string
     */
    protected $title = '';

    /**
     * Set workspace title
     *
     * @param string $title
     */
    public function setTitle($title)
    {
        $this->title = $title;
    }

    /**
     * Get workspace title
     *
     * @return string
     */
    public function getTitle()
    {
        return $this->title;
    }
}
