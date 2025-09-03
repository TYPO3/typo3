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

namespace TYPO3\CMS\Form\Event;

use Psr\EventDispatcher\StoppableEventInterface;

/**
 * Listeners to this Event will be able to:
 * - Get the form persistence identifier before a form is deleted
 * - Stop the deletion process by setting preventDeletion to true
 * - Add custom logic, e.g. cleanup tasks, before deletion
 */
final class BeforeFormIsDeletedEvent implements StoppableEventInterface
{
    public function __construct(
        public readonly string $formPersistenceIdentifier,
        public bool $preventDeletion = false
    ) {}

    public function isPropagationStopped(): bool
    {
        return $this->preventDeletion;
    }
}
