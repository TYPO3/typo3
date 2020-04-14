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

namespace ExtbaseTeam\BlogExample\Domain\Model;

use TYPO3\CMS\Extbase\DomainObject\AbstractEntity;

class DateTimeImmutableExample extends AbstractEntity
{

    /**
     * A datetimeImmutable stored in a text field
     *
     * @var \DateTimeImmutable
     */
    protected $datetimeImmutableText;

    /**
     * A datetime stored in an integer field
     *
     * @var \DateTimeImmutable
     */
    protected $datetimeImmutableInt;

    /**
     * A datetime stored in a datetime field
     *
     * @var \DateTimeImmutable
     */
    protected $datetimeImmutableDatetime;

    /**
     * @return \DateTimeImmutable
     */
    public function getDatetimeImmutableText(): \DateTimeImmutable
    {
        return $this->datetimeImmutableText;
    }

    /**
     * @param \DateTimeImmutable $datetimeImmutableText
     */
    public function setDatetimeImmutableText(\DateTimeImmutable $datetimeImmutableText)
    {
        $this->datetimeImmutableText = $datetimeImmutableText;
    }

    /**
     * @return \DateTimeImmutable
     */
    public function getDatetimeImmutableInt(): \DateTimeImmutable
    {
        return $this->datetimeImmutableInt;
    }

    /**
     * @param \DateTimeImmutable $datetimeImmutableInt
     */
    public function setDatetimeImmutableInt(\DateTimeImmutable $datetimeImmutableInt)
    {
        $this->datetimeImmutableInt = $datetimeImmutableInt;
    }

    /**
     * @return \DateTimeImmutable
     */
    public function getDatetimeImmutableDatetime(): \DateTimeImmutable
    {
        return $this->datetimeImmutableDatetime;
    }

    /**
     * @param \DateTimeImmutable $datetimeImmutableDatetime
     */
    public function setDatetimeImmutableDatetime(\DateTimeImmutable $datetimeImmutableDatetime)
    {
        $this->datetimeImmutableDatetime = $datetimeImmutableDatetime;
    }
}
