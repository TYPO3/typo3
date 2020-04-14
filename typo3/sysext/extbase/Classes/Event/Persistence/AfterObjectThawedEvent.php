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

namespace TYPO3\CMS\Extbase\Event\Persistence;

use TYPO3\CMS\Extbase\DomainObject\DomainObjectInterface;

/**
 * Allows to modify values when creating domain objects.
 */
final class AfterObjectThawedEvent
{
    /**
     * @var DomainObjectInterface
     */
    private $mappedObject;

    /**
     * @var array
     */
    private $record;

    public function __construct(DomainObjectInterface $mappedObject, array $record)
    {
        $this->mappedObject = $mappedObject;
        $this->record = $record;
    }

    public function getObject(): DomainObjectInterface
    {
        return $this->mappedObject;
    }

    public function getRecord(): array
    {
        return $this->record;
    }
}
