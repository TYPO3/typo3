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

namespace ExtbaseTeam\BlogExample\Domain\Model;

use TYPO3\CMS\Extbase\DomainObject\AbstractEntity;

/**
 * A post additional info (1:1 inline relation to post)
 */
class Info extends AbstractEntity
{

    /**
     * @var string
     */
    protected $name = '';

    /**
     * Sets the name
     *
     * @param string $name
     */
    public function setName($name)
    {
        $this->content = $name;
    }

    /**
     * Getter for name
     *
     * @return string
     */
    public function getName()
    {
        return $this->name;
    }

    /**
     * Returns this info as a formatted string
     *
     * @return string
     */
    public function __toString()
    {
        return $this->name;
    }
}
