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
 * A blog post tag
 */
class Tag extends \TYPO3\CMS\Extbase\DomainObject\AbstractValueObject
{
    /**
     * @var string
     */
    protected $name = '';

    /**
     * Constructs this tag
     *
     * @param $name
     */
    public function __construct($name)
    {
        $this->name = $name;
    }

    /**
     * Returns this tag's name
     *
     * @return string This tag's name
     */
    public function getName()
    {
        return $this->name;
    }

    /**
     * Returns this tag as a formatted string
     *
     * @return string
     */
    public function __toString()
    {
        return $this->getName();
    }
}
