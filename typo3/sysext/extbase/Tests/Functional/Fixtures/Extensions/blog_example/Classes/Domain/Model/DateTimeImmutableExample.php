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
     * Static value which is not part of an "entity".
     * (this property has to be ignored by Extbase when persisting this entity)
     */
    public static string $publicStaticValue;

    /**
     * Transient value, having a name starting with `_`.
     * (this property has to be ignored by Extbase when persisting this entity)
     */
    public string $_publicTransientValue;

    /**
     * Transient value without any getter or setter.
     * (this property has to be ignored by Extbase when persisting this entity)
     */
    private string $privateTransientValue; // @phpstan-ignore-line since it is unused on purpose

    /**
     * A datetimeImmutable stored in a text field
     */
    protected ?\DateTimeImmutable $datetimeImmutableText = null;

    /**
     * A datetime stored in an integer field
     */
    protected ?\DateTimeImmutable $datetimeImmutableInt = null;

    /**
     * A datetime stored in a datetime field
     */
    protected ?\DateTimeImmutable $datetimeImmutableDatetime = null;

    public function getDatetimeImmutableText(): ?\DateTimeImmutable
    {
        return $this->datetimeImmutableText;
    }

    public function setDatetimeImmutableText(\DateTimeImmutable $datetimeImmutableText): void
    {
        $this->datetimeImmutableText = $datetimeImmutableText;
    }

    public function getDatetimeImmutableInt(): ?\DateTimeImmutable
    {
        return $this->datetimeImmutableInt;
    }

    public function setDatetimeImmutableInt(\DateTimeImmutable $datetimeImmutableInt): void
    {
        $this->datetimeImmutableInt = $datetimeImmutableInt;
    }

    public function getDatetimeImmutableDatetime(): ?\DateTimeImmutable
    {
        return $this->datetimeImmutableDatetime;
    }

    public function setDatetimeImmutableDatetime(\DateTimeImmutable $datetimeImmutableDatetime): void
    {
        $this->datetimeImmutableDatetime = $datetimeImmutableDatetime;
    }
}
