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

namespace TYPO3\CMS\Core\Configuration\Event;

/**
 * Listeners to this event are able to modify or enhance a flex form data
 * structure that corresponds to a given identifier, after it was parsed and
 * before it is used by further components.
 *
 * Note: Since this event is not stoppable, all registered listeners are
 * called. Therefore, you might want to namespace your identifiers in a way,
 * that there is little chance they overlap (e.g. prefix with extension name).
 *
 * See the note on FlexFormTools regarding the schema of $dataStructure.
 */
final class AfterFlexFormDataStructureParsedEvent
{
    public function __construct(
        private array $dataStructure,
        private readonly array $identifier,
    ) {
    }

    public function getIdentifier(): array
    {
        return $this->identifier;
    }

    public function getDataStructure(): array
    {
        return $this->dataStructure;
    }

    public function setDataStructure(array $dataStructure): void
    {
        $this->dataStructure = $dataStructure;
    }
}
