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

namespace TYPO3Tests\TestDataMapper\Domain\Model;

use TYPO3\CMS\Extbase\DomainObject\AbstractEntity;

class SystemCapabilities extends AbstractEntity
{
    public function __construct(
        public ?string $description,
        public ?\DateTime $tstamp,
        public ?\DateTime $crdate,
        public bool $deleted,
        public int $sorting,
        public bool $hidden,
        public ?\DateTime $starttime,
        public ?\DateTime $endtime,
        public ?string $feGroup,
        public bool $editlock,
    ) {}
}
