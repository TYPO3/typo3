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

class DateExample extends AbstractEntity
{

    /**
     * A datetime stored in a text field
     *
     * @var \DateTime
     */
    protected $datetimeText;

    /**
     * A datetime stored in an integer field
     *
     * @var \DateTime
     */
    protected $datetimeInt;

    /**
     * A datetime stored in a datetime field
     *
     * @var \DateTime
     */
    protected $datetimeDatetime;

    /**
     * @return \DateTime
     */
    public function getDatetimeText()
    {
        return $this->datetimeText;
    }

    /**
     * @param \DateTime $datetimeText
     */
    public function setDatetimeText($datetimeText)
    {
        $this->datetimeText = $datetimeText;
    }

    /**
     * @return \DateTime
     */
    public function getDatetimeInt()
    {
        return $this->datetimeInt;
    }

    /**
     * @param \DateTime $datetimeInt
     */
    public function setDatetimeInt($datetimeInt)
    {
        $this->datetimeInt = $datetimeInt;
    }

    /**
     * @return \DateTime
     */
    public function getDatetimeDatetime()
    {
        return $this->datetimeDatetime;
    }

    /**
     * @param \DateTime $datetimeDatetime
     */
    public function setDatetimeDatetime($datetimeDatetime)
    {
        $this->datetimeDatetime = $datetimeDatetime;
    }
}
