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

namespace TYPO3Tests\BlogExample\Domain\Model;

use TYPO3\CMS\Extbase\DomainObject\AbstractEntity;

class DateExample extends AbstractEntity
{
    /**
     * A datetime stored in a text field
     */
    protected ?\DateTime $datetimeText = null;

    /**
     * A datetime stored in an integer field
     */
    protected ?\DateTime $datetimeInt = null;

    /**
     * A datetime stored in a datetime field
     */
    protected ?\DateTime $datetimeDatetime = null;

    public function getDatetimeText(): ?\DateTime
    {
        return $this->datetimeText;
    }

    public function setDatetimeText(\DateTime $datetimeText): void
    {
        $this->datetimeText = $datetimeText;
    }

    public function getDatetimeInt(): ?\DateTime
    {
        return $this->datetimeInt;
    }

    public function setDatetimeInt(?\DateTime $datetimeInt): void
    {
        $this->datetimeInt = $datetimeInt;
    }

    public function getDatetimeDatetime(): ?\DateTime
    {
        return $this->datetimeDatetime;
    }

    public function setDatetimeDatetime(?\DateTime $datetimeDatetime): void
    {
        $this->datetimeDatetime = $datetimeDatetime;
    }
}
