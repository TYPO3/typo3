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
 * Event which is fired after an object/entity was sent to persistence layer to be updated.
 */
final class EntityUpdatedInPersistenceEvent
{
    /**
     * @var DomainObjectInterface
     */
    private $persistedObject;

    public function __construct(DomainObjectInterface $persistedObject)
    {
        $this->persistedObject = $persistedObject;
    }

    public function getObject(): DomainObjectInterface
    {
        return $this->persistedObject;
    }
}
